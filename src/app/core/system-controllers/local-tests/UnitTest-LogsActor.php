<?php

//El registro de acciones dice QUIÉN actuaba: con suplantación, `createdBy` es el suplantado, y sin
//nadie conectado el 1 no significa el principal. Crea filas zz- y las retira. 276.2.

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/logs-actor', function ($args) {

    echoTerminal("\e[33m[TEST:LogsActor] El registro de acciones dice quién actuaba de verdad\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c0 hay conexión a la base')) {
        return $balance();
    }
    $tabla = LogsMapper::TABLE;
    $marca = 'zz-actor-' . bin2hex(random_bytes(3));
    $antes = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    echoTerminal("Filas de registro antes: {$antes}");

    //La sesión y la suplantación se fingen con la configuración, que es de donde las lee `save()`.
    $usuarioPrevio = get_config('current_user');
    $almacenadoPrevio = get_config('pcsphp_current_user_stored');
    $rootPrevio = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);

    $comoUsuario = function (?int $id) {
        set_config('current_user', $id === null ? null : (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    //Por `addLog()`, que es como registra la aplicación: es quien pone `ip` y `geolocationByIp`.
    $registrar = function (string $texto) use ($marca): LogsMapper {
        return LogsMapper::addLog(LogsMapper::MSG_GENERIC, ['message' => "{$marca} {$texto}"]);
    };
    $metaDe = function (int $id) use ($database, $tabla) {
        $st = $database->prepare("SELECT meta FROM `{$tabla}` WHERE id = ?");
        $st->execute([$id]);
        $valor = $st->fetchColumn();
        return is_string($valor) ? json_decode($valor) : null;
    };
    $campoDe = function (int $id, string $campo) use ($database, $tabla) {
        $st = $database->prepare("SELECT `{$campo}` FROM `{$tabla}` WHERE id = ?");
        $st->execute([$id]);
        return $st->fetchColumn();
    };

    //Dos usuarios reales de la base: el principal y otro cualquiera.
    $root = (int) $database->query("SELECT id FROM `pcsphp_users` WHERE type = 0 ORDER BY id LIMIT 1")->fetchColumn();
    $otro = (int) $database->query("SELECT id FROM `pcsphp_users` WHERE type <> 0 ORDER BY id LIMIT 1")->fetchColumn();
    if (!$check($root > 0 && $otro > 0 && $root !== $otro, 'c1 hay un principal y otro usuario para el banco', "root={$root} otro={$otro}")) {
        return $balance();
    }

    $creadas = [];

    try {

        //─── a · El canario: una acción normal se registra igual que hoy ─────────────────────────
        echoTerminal('[a] El canario: sin suplantación, nada cambia');
        $comoUsuario($otro);
        set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, null);
        $normal = $registrar('normal');
        $creadas[] = (int) $normal->id;
        $check((int) $campoDe((int) $normal->id, 'createdBy') === $otro, 'a1 createdBy es el usuario conectado, como siempre', (string) $campoDe((int) $normal->id, 'createdBy'));
        $meta = $metaDe((int) $normal->id);
        $check($meta !== null && !isset($meta->{LogsMapper::META_ACTOR}), 'a2 y meta NO lleva ruido nuevo: sin actor');
        $check($meta !== null && isset($meta->ip), 'a3 CANARIO: meta sigue trayendo lo de siempre (ip), así que se guardó de verdad');

        //─── b · Con suplantación: quién actuaba de verdad ───────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Con el principal suplantando, la fila dice quién actuaba');
        $comoUsuario($otro);
        set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, $root);
        $suplantada = $registrar('suplantada');
        $creadas[] = (int) $suplantada->id;
        $check((int) $campoDe((int) $suplantada->id, 'createdBy') === $otro, 'b1 createdBy sigue siendo el SUPLANTADO, sin reinterpretar nada');
        $meta = $metaDe((int) $suplantada->id);
        $actor = $meta !== null ? ($meta->{LogsMapper::META_ACTOR} ?? null) : null;
        $check($actor !== null, 'b2 meta trae el actor', (string) json_encode($meta));
        $check($actor !== null && ($actor->kind ?? null) === LogsMapper::ACTOR_IMPERSONATION, 'b3 y dice que es una suplantación');
        $check($actor !== null && (int) ($actor->id ?? 0) === $root, 'b4 con el id de quien actuaba de verdad: ' . (string) ($actor->id ?? '-'));
        $check($actor !== null && (int) ($actor->onBehalfOf ?? 0) === $otro, 'b5 y en nombre de quién: ' . (string) ($actor->onBehalfOf ?? '-'));
        $check($meta !== null && isset($meta->ip), 'b6 y lo que meta ya traía NO se pisa');

        //─── c · Sin usuario conectado: no afirma que fue el principal ───────────────────────────
        echoTerminal('');
        echoTerminal('[c] Sin nadie conectado, la fila no afirma que fue el principal');
        $comoUsuario(null);
        set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, null);
        $delSistema = $registrar('del-sistema');
        $creadas[] = (int) $delSistema->id;
        $check((int) $campoDe((int) $delSistema->id, 'createdBy') === 1, 'c1 createdBy sigue en 1, porque la columna no acepta nulo (medido)');
        $meta = $metaDe((int) $delSistema->id);
        $actor = $meta !== null ? ($meta->{LogsMapper::META_ACTOR} ?? null) : null;
        $check($actor !== null && ($actor->kind ?? null) === LogsMapper::ACTOR_SYSTEM, 'c2 pero meta declara que lo hizo el SISTEMA', (string) json_encode($actor));
        $check($actor !== null && property_exists($actor, 'id') && $actor->id === null, 'c3 y sin id de actor: nadie actuaba', (string) json_encode($actor));

        //─── d · Cómo se nombra en pantalla ──────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] Lo que la pantalla enseña en cada caso');
        $check(LogsMapper::actorLabel(null, null, 'zz-pepe') === 'zz-pepe', 'd1 sin actor, el nombre de siempre');
        $check(LogsMapper::actorLabel(LogsMapper::ACTOR_SYSTEM, null, 'zz-pepe') === __(LogsMapper::LANG_GROUP, 'Sistema'), 'd2 del sistema, dice «Sistema» y NO el nombre del 1', LogsMapper::actorLabel(LogsMapper::ACTOR_SYSTEM, null, 'zz-pepe'));
        $etiqueta = LogsMapper::actorLabel(LogsMapper::ACTOR_IMPERSONATION, 'zz-root', 'zz-pepe');
        $check(str_contains($etiqueta, 'zz-root') && str_contains($etiqueta, 'zz-pepe'), 'd3 con suplantación, los DOS nombres: ' . $etiqueta);

        //─── e · Los mensajes de inicio y fin existen ────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] El inicio y el fin de la suplantación tienen su mensaje');
        $mensajes = LogsMapper::MESSAGES;
        //Se mira el CONTENIDO, no `isset`: las claves de una constante siempre existen y PHPStan lo
        //sabe, así que un `isset` ahí no prueba nada.
        $delInicio = (string) $mensajes[LogsMapper::MSG_IMPERSONATION_START];
        $delFin = (string) $mensajes[LogsMapper::MSG_IMPERSONATION_END];
        $check(str_contains($delInicio, '%username%'), 'e1 el mensaje del inicio nombra a quien suplanta', $delInicio);
        $check(str_contains($delInicio, '%target%'), 'e2 y a quién se suplanta', $delInicio);
        $check(str_contains($delFin, '%username%') && $delFin !== $delInicio, 'e3 y el del fin es otro, con su nombre', $delFin);

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //─── z · Limpieza ───────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[z] Limpieza: las filas de la prueba salen del registro');
    set_config('current_user', $usuarioPrevio);
    set_config('pcsphp_current_user_stored', $almacenadoPrevio);
    set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, $rootPrevio);
    //Por ID y por SQL: `update()` devuelve false a propósito, y `addLog()` guarda en `textMessage`
    //la PLANTILLA («%message%»), no el texto, así que buscar por el texto no encuentra nada.
    $borradas = 0;
    foreach ($creadas as $idCreada) {
        $st = $database->prepare("DELETE FROM `{$tabla}` WHERE id = ?");
        $st->execute([$idCreada]);
        $borradas += $st->rowCount();
    }
    $quedan = 0;
    foreach ($creadas as $idCreada) {
        $st = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE id = ?");
        $st->execute([$idCreada]);
        $quedan += (int) $st->fetchColumn();
    }
    $check($quedan === 0, "z1 no queda ninguna fila de la prueba ({$borradas} borradas de " . count($creadas) . ")");
    $despues = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    $check($despues === $antes, "z2 el registro vuelve a tener {$antes} filas", "ahora {$despues}");

    return $balance();

})->setDescription('El registro de acciones dice quién actuaba: con suplantación guarda el actor real en meta sin tocar createdBy, sin sesión declara que fue el sistema, y la pantalla no presenta el 1 como el principal.')->setEffects([CliActions::EFFECT_DATABASE])->register();
