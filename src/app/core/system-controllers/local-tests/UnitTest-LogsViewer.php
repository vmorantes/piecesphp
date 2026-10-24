<?php

//La vista «Registros»: pestañas por permiso, 403, la referencia de un error y el escapado.
//Crea dos usuarios zz-agente-logs-* y un rotado olds/error.log.zz-logs-viewer-*.json, y los borra.

use PiecesPHP\UserSystem\Authentication\UserSessions;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\ErrorLogReader;
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\Controllers\UserTokensController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/logs-viewer', function ($args) {

    echoTerminal("\e[33m[TEST:LogsViewer] Registros: pestañas por permiso, 403, la referencia de un error y el escapado\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla del registro de correos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    if ($base === '') {
        return $balance();
    }

    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $pagina = $ruta('logs-admin-index');
    $datos = $ruta('logs-admin-errors');
    $check($pagina !== '' && $datos !== '', 'c2 existen la pantalla y la ruta de datos de errores', "{$pagina} · {$datos}");
    if ($failed > 0) {
        return $balance();
    }

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $pestanas = function (string $html): array {
        return preg_match_all('/data-logs-tab="([^"]+)"/', $html, $m) !== false ? $m[1] : [];
    };

    $prefijo = 'zz-agente-logs-' . bin2hex(random_bytes(3));
    $crear = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };
    $token = fn(int $id): string => UserSessions::issueToken($id);

    //El error plantado va en un rotado PROPIO: el registro real no se toca, y borrarlo es borrar un archivo.
    $marca = 'zz-logs-viewer-' . bin2hex(random_bytes(4));
    $referencia = 'ERR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $inexistente = 'ERR-19990101-' . strtoupper(bin2hex(random_bytes(3)));
    $veneno = '<img src=x onerror=alert(1)>';
    $rotado = rtrim((string) constant('LOG_ERRORS_BACKUP_PATH'), '/\\') . "/error.log.{$marca}.json";
    $erroresAbiertos = LogsController::ERRORS_TAB;
    $correos = SystemStatusController::MAIL_LOG_TAB;
    $accesos = LoginAttemptsController::ACCESS_TAB;
    $sesiones = UserSessionsController::SESSIONS_TAB;
    $tokens = UserTokensController::TOKENS_TAB;
    $tareas = SystemStatusController::TASK_RUNS_TAB;

    try {

        $entrada = [
            date('d-m-Y') => [[
                'time' => date('H:i:s') . '.000000',
                'reference' => $referencia,
                'type' => 'ZzLogsViewerException',
                'message' => "{$veneno} {$marca}",
                'code' => 0,
                'file' => "/zz/{$marca}.php",
                'line' => 7,
                'extraData' => ['zz' => $veneno],
                'trace' => [['file' => "/zz/{$marca}.php", 'line' => 7, 'function' => 'zz']],
            ]],
        ];
        $plantado = file_put_contents($rotado, (string) json_encode($entrada)) !== false;
        $check($plantado, 'banco: un rotado propio con un error plantado', $rotado);

        $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT);
        $idOrg = $crear('admin-org', UsersModel::TYPE_USER_ADMIN_ORG);
        $root = $token($idRoot);
        $org = $token($idOrg);
        $check($idRoot > 0 && $idOrg > 0, 'banco: un principal y un administrador de organización de la prueba');

        //─── a · Cada uno ve sus pestañas ──────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Root ve todas las pestañas; un administrador de organización, «Accesos», «Sesiones abiertas» y «Tokens y códigos»');
        $comoRoot = $pedir($pagina, $root);
        $deRoot = $pestanas($comoRoot['body']);
        $check($comoRoot['status'] === 200, 'a1 CANARIO: el principal abre la pantalla', "HTTP {$comoRoot['status']}");
        $check($deRoot === [$erroresAbiertos, $correos, $accesos, $sesiones, $tokens, $tareas], 'a2 y ve Errores, Correos enviados, Accesos, Sesiones abiertas, Tokens y códigos y Tareas programadas, en ese orden', implode(', ', $deRoot));
        $comoOrg = $pedir($pagina, $org);
        $deOrg = $pestanas($comoOrg['body']);
        $check($comoOrg['status'] === 200, 'a3 el administrador de organización abre la pantalla', "HTTP {$comoOrg['status']}");
        $check($deOrg === [$accesos, $sesiones, $tokens], 'a4 y solo ve «Accesos», «Sesiones abiertas» y «Tokens y códigos»', implode(', ', $deOrg));
        $check(array_keys(LogTabRegistry::visibleFor(UsersModel::TYPE_USER_ADMIN_ORG)) === [$accesos, $sesiones, $tokens]
            && array_keys(LogTabRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) === [$erroresAbiertos, $correos, $accesos, $sesiones, $tokens, $tareas], 'a5 visibleFor() dice lo mismo que la pantalla');
        $check(LogTabRegistry::visibleFor(UsersModel::TYPE_USER_GENERAL) === [], 'a6 un usuario general no ve ninguna');
        //El caso que dio 500 (TypeError en scopeNotice): la línea del alcance sale para el recortado y no para root.
        $check(str_contains($comoOrg['body'], 'data-logs-scope'), 'a7 al administrador de organización le dice su alcance');
        $check(!str_contains($comoRoot['body'], 'data-logs-scope'), 'a8 a root, nada');

        //─── b · Una pestaña ajena es un 403, no un vacío ───────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Pedir una pestaña que no es suya da 403');
        $errorComoRoot = $pedir("{$pagina}?tab={$erroresAbiertos}", $root);
        $check($errorComoRoot['status'] === 200 && str_contains($errorComoRoot['body'], 'data-logs-errors-filters'), 'b1 CANARIO: el principal abre la pestaña de errores', "HTTP {$errorComoRoot['status']}");
        $errorComoOrg = $pedir("{$pagina}?tab={$erroresAbiertos}", $org);
        $check($errorComoOrg['status'] === 403, 'b2 el administrador de organización recibe 403 con ?tab=errors', "HTTP {$errorComoOrg['status']}");
        $check(!str_contains($errorComoOrg['body'], $marca) && !str_contains($errorComoOrg['body'], 'data-logs-errors-filters'), 'b3 y su respuesta no trae nada de la pestaña');
        $check($pedir("{$pagina}?tab={$correos}", $org)['status'] === 403, 'b4 lo mismo con la de correos');
        $check($pedir("{$pagina}?tab=zz-no-existe", $root)['status'] === 403, 'b5 una pestaña que no existe también da 403, igual que una ajena');
        $check($pedir("{$datos}?ref={$referencia}", $org)['status'] === 403, 'b6 la ruta de datos de errores da 403 al administrador de organización');
        $sinSesion = $pedir($pagina);
        $check($sinSesion['status'] !== 200, 'b7 sin sesión no se abre', "HTTP {$sinSesion['status']}");

        //─── c · La referencia abre su entrada ──────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] ?ref= abre la entrada; una que no existe lo dice');
        $conRef = $pedir("{$pagina}?tab={$erroresAbiertos}&ref={$referencia}", $root);
        $check($conRef['status'] === 200 && str_contains($conRef['body'], 'data-error-reference="' . $referencia . '"'), 'c3 la referencia plantada abre su entrada', "HTTP {$conRef['status']}");
        $check(str_contains($conRef['body'], $marca) && substr_count($conRef['body'], 'data-error-reference=') === 1, 'c4 y solo esa, con su mensaje');
        $sinRef = $pedir("{$pagina}?tab={$erroresAbiertos}&ref={$inexistente}", $root);
        $check($sinRef['status'] === 200 && str_contains($sinRef['body'], 'data-logs-reference-missing') && !str_contains($sinRef['body'], 'data-error-reference='), 'c5 una referencia inexistente da el mensaje, sin ninguna entrada');
        $check(str_contains($sinRef['body'], __(LogsController::LANG_GROUP, 'No hay ningún error con esa referencia')), 'c6 con su texto');
        $json = $pedir("{$datos}?ref={$referencia}", $root)['json'];
        $check(is_array($json) && ($json['result']['entry']['reference'] ?? null) === $referencia, 'c7 la ruta de datos la encuentra también');
        $lector = new ErrorLogReader();
        $check($lector->findByReference($referencia) !== null && $lector->findByReference($inexistente) === null && $lector->findByReference("{$referencia}' OR 1") === null, 'c8 el lector: la plantada sí, la inexistente y una mal formada no');

        //─── d · Lo que viene del registro sale escapado ────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Un mensaje con HTML sale escapado, en la entrada abierta y en la lista');
        $check(str_contains($veneno, '<img'), 'd0 CANARIO: lo plantado SÍ trae la etiqueta cruda, así que d2 y d4 pueden fallar');
        $check(str_contains($conRef['body'], '&lt;img src=x onerror=alert(1)&gt;'), 'd1 en la entrada abierta llega escapado');
        $check(!str_contains($conRef['body'], $veneno), 'd2 y la etiqueta cruda NO aparece (ni en el mensaje ni en los datos adicionales)');
        $lista = $pedir("{$pagina}?tab={$erroresAbiertos}&" . http_build_query(['text' => $marca]), $root);
        $check($lista['status'] === 200 && str_contains($lista['body'], 'data-error-reference="' . $referencia . '"'), 'd3 la lista filtrada por texto trae la entrada plantada', "HTTP {$lista['status']}");
        $check(!str_contains($lista['body'], $veneno) && str_contains($lista['body'], '&lt;img'), 'd4 y en la lista tampoco sale cruda');

        //─── f · «Otros archivos» solo de la lista ─────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] ?file= fuera de la lista blanca no lee nada');
        $permitido = $pedir("{$pagina}?tab={$erroresAbiertos}&file=deprecations", $root);
        $check($permitido['status'] === 200 && (str_contains($permitido['body'], 'data-logs-plain') || str_contains($permitido['body'], __(LogsController::LANG_GROUP, 'El archivo no existe o está vacío.'))), 'f1 CANARIO: uno de la lista se lee');
        foreach (['../../config/database', '../config/config.php', 'error.plain', 'olds/../../config/database'] as $n => $fuera) {
            $respuesta = $pedir("{$pagina}?tab={$erroresAbiertos}&" . http_build_query(['file' => $fuera]), $root);
            //Ni el contenido ni «no existe»: la vista de archivo no se abre siquiera, sale la lista.
            $check($respuesta['status'] === 200 && !str_contains($respuesta['body'], 'data-logs-plain') && !str_contains($respuesta['body'], __(LogsController::LANG_GROUP, 'El archivo no existe o está vacío.')) && str_contains($respuesta['body'], 'data-logs-errors-filters'), 'f' . ($n + 2) . " «{$fuera}» se ignora: sale la lista de errores, no un archivo");
        }
        $check($lector->tail('../../config/database') === null && $lector->tail('error.plain') === null, 'f6 y el lector no la acepta');

        //─── g · La referencia, también en minúsculas ──────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[g] ?ref en minúsculas encuentra la entrada');
        $minusculas = $pedir("{$pagina}?tab={$erroresAbiertos}&ref=" . strtolower($referencia), $root);
        $check($minusculas['status'] === 200 && str_contains($minusculas['body'], 'data-error-reference="' . $referencia . '"'), 'g1 la referencia en minúsculas abre su entrada');
        $check($lector->findByReference('  ' . strtolower($referencia) . ' ') !== null, 'g2 y el lector la encuentra con espacios alrededor');

        //─── h · Las direcciones viejas redirigen ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[h] Las direcciones viejas llevan a su pestaña');
        $redirige = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [
                CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
            ]);
            curl_exec($handle);
            return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'location' => (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL)];
        };
        $accesosViejos = $ruta('login-attempts-reports');
        foreach (['attempts', 'logged', 'not-logged'] as $vista) {
            $r = $redirige("{$accesosViejos}?{$vista}=yes", $org);
            $check(in_array($r['status'], [301, 302, 303], true) && str_contains($r['location'], 'tab=' . $accesos) && str_contains($r['location'], 'view=' . $vista), "h-{$vista} login-attempts-reports?{$vista}=yes lleva a su sub-vista", "HTTP {$r['status']} {$r['location']}");
        }
        $sinVista = $redirige($accesosViejos, $org);
        $check($sinVista['status'] === 404, 'h4 sin sub-vista, 404 como antes', "HTTP {$sinVista['status']}");

        //─── e · El registro no admite dos pestañas con la misma key ───────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] Dos pestañas con la misma key');
        $previas = LogTabRegistry::swapForTesting();
        try {
            $vacia = fn($request, string $url): string => '';
            LogTabRegistry::register(new LogTab('zz-repetida', 'Zz', 'bug', 'logs-admin-errors', $vacia));
            $lanzo = false;
            try {
                LogTabRegistry::register(new LogTab('zz-repetida', 'Zz otra', 'bug', 'logs-admin-errors', $vacia));
            } catch (\InvalidArgumentException $e) {
                $lanzo = true;
            }
            $check($lanzo, 'e1 la segunda lanza InvalidArgumentException');
            $check(count(LogTabRegistry::all()) === 1 && LogTabRegistry::get('zz-repetida')?->title() === 'Zz', 'e2 y la primera sigue, intacta');
            $malFormada = false;
            try {
                new LogTab('Zz Mal', 'Zz', 'bug', 'logs-admin-errors', $vacia);
            } catch (\InvalidArgumentException $e) {
                $malFormada = true;
            }
            $check($malFormada, 'e3 una key que no va en una URL tal cual también lanza');
        } finally {
            LogTabRegistry::swapForTesting($previas);
        }
        $check(array_keys(LogTabRegistry::all()) === [$erroresAbiertos, $correos, $accesos, $sesiones, $tokens, $tareas], 'e4 el registro real queda como estaba');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //─── z · Limpieza ───────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[z] Limpieza: el rotado plantado y los usuarios');
        if (is_file($rotado)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            unlink($rotado);
        }
        $check(!is_file($rotado), 'z1 no queda el rotado plantado');
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $deLaPrueba = array_map(fn($u) => (int) $u->id, (array) $usuarios->result());
        foreach ($deLaPrueba as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
            $perfiles->delete(['belongsTo' => $id])->execute();
            //`SystemApprovalManager::init()` anota cada usuario nuevo al servir: esa fila no cae con él.
            $aprobaciones = SystemApprovalsMapper::model();
            $aprobaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
            $aprobaciones->delete(new WhereSegment([
                new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
            ]))->execute();
        }
        $usuarios->resetAll();
        //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z2 no queda ningún usuario de la prueba');
    }

    return $balance();

})->setDescription('La vista «Registros»: root ve tres pestañas y un administrador de organización solo «Accesos»; una pestaña ajena da 403 por HTTP; ?ref= abre la entrada plantada y una inexistente lo dice; un mensaje con HTML sale escapado; y dos pestañas con la misma key lanzan. Planta un rotado propio y dos usuarios, y los borra.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
