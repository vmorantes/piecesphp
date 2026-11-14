<?php

//Centro de mando de la IA (ADR 0074): registro, chatHandler() sin llamar a ningún proveedor, recordUsage() y los controles.
//Escribe filas zz- en pcsphp_ai_usage y las borra; CONTENT_TOKENS_USED se repone como estaba.
//@areas nucleo

use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use API\Controllers\APIController;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUnavailableException;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/ai-gateway', function ($args) {

    echoTerminal("\e[33m[TEST:AIGateway] el registro de capacidades y la puerta única de la IA\e[39m");
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
    $lanza = function (callable $accion): ?string {
        try {
            $accion();
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
        return null;
    };
    $capacidad = fn(string $key, string $tipo = AICapability::PROVIDER_CHAT): AICapability => new AICapability($key, fn(): string => "zz {$key}", $tipo);

    $registroPrevio = AICapabilityRegistry::swapForTesting();
    $opciones = ['translationAI', 'OpenAIApiKey', 'MistralAIApiKey', AIGateway::CONFIG_NAME, 'translationAIEnable', APIController::TRANSLATION_AI_USER_TYPES_CONFIG];
    $opcionesPrevias = array_combine($opciones, array_map(fn(string $nombre): mixed => get_config($nombre), $opciones));
    $acumuladoPrevio = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $database = null;
    //Usuarios en memoria, sin guardar: la puerta solo mira su tipo.
    $usuario = function (int $tipo): UsersModel {
        $u = new UsersModel();
        $u->type = $tipo;
        return $u;
    };
    $root = $usuario(UsersModel::TYPE_USER_ROOT);
    $general = $usuario(UsersModel::TYPE_USER_GENERAL);
    $centro = fn(array $valor) => set_config(AIGateway::CONFIG_NAME, $valor);

    try {
        //Sin ai_center: todo como hoy, también en lo que ya probaba la ronda 1.
        set_config(AIGateway::CONFIG_NAME, false);

        //─── a · El registro ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] AICapabilityRegistry');
        AICapabilityRegistry::swapForTesting([]);
        AICapabilityRegistry::register($capacidad('zz-uno'));
        AICapabilityRegistry::register($capacidad('zz-dos', AICapability::PROVIDER_SPEECH));
        $check(array_keys(AICapabilityRegistry::all()) === ['zz-uno', 'zz-dos'], 'a1 all() en el orden de registro');
        $check($lanza(fn() => AICapabilityRegistry::register($capacidad('zz-uno'))) !== null, 'a2 una key repetida lanza');
        AICapabilityRegistry::replace(new AICapability('zz-uno', fn(): string => 'zz sustituida', AICapability::PROVIDER_CHAT));
        $check(array_keys(AICapabilityRegistry::all()) === ['zz-uno', 'zz-dos'] && AICapabilityRegistry::get('zz-uno')?->name() === 'zz sustituida', 'a3 replace() la sustituye en su sitio');
        $check($lanza(fn() => AICapabilityRegistry::replace($capacidad('zz-no-esta'))) !== null, 'a4 replace() de una que no está lanza');
        $primera = AICapabilityRegistry::remove('zz-dos');
        $segunda = AICapabilityRegistry::remove('zz-dos');
        $check($primera && !$segunda && AICapabilityRegistry::get('zz-dos') === null, 'a5 remove(): true la primera vez, false después');
        $check($lanza(fn() => $capacidad('Zz Mal')) !== null && $lanza(fn() => new AICapability('zz-tipo', fn(): string => '', 'zz')) !== null, 'a6 una key o un tipo de proveedor que no valen lanzan');
        $check($lanza(fn() => new AICapability('zz-cuota', fn(): string => '', AICapability::PROVIDER_CHAT, true, [], -1)) !== null && $lanza(fn() => new AICapability('zz-periodo', fn(): string => '', AICapability::PROVIDER_CHAT, true, [], 0, 'zz')) !== null, 'a7 una cuota negativa o un periodo desconocido lanzan');
        echoTerminal(' ');

        //─── b · chatHandler(), sin llamar al proveedor ─────────────────────────────────────────────────
        echoTerminal('[b] AIGateway::chatHandler()');
        AICapabilityRegistry::register($capacidad('zz-chat'));
        AICapabilityRegistry::register($capacidad('zz-voz', AICapability::PROVIDER_SPEECH));
        set_config('OpenAIApiKey', 'zz-sin-clave');
        set_config('MistralAIApiKey', 'zz-sin-clave');
        set_config('translationAI', null);
        $check(AIGateway::chatHandler('zz-chat', $root) === null, 'b1 sin proveedor configurado: null');
        set_config('translationAI', AI_OPENAI);
        $check(AIGateway::chatHandler('zz-chat', $root) instanceof OpenAIHandlerAdapter, 'b2 con OpenAI: su adaptador');
        set_config('translationAI', AI_MISTRAL);
        $check(AIGateway::chatHandler('zz-chat', $root) instanceof MistralHandlerAdapter, 'b3 con Mistral: su adaptador');
        $check($lanza(fn() => AIGateway::chatHandler('zz-no-registrada', $root)) !== null, 'b4 una capacidad no registrada lanza');
        $check($lanza(fn() => AIGateway::chatHandler('zz-voz', $root)) !== null, 'b5 una capacidad de voz lanza');
        $check(isset($registroPrevio['translation']) && $registroPrevio['translation']->providerType() === AICapability::PROVIDER_CHAT, 'b6 la traducción está registrada, como capacidad de chat');
        echoTerminal(' ');

        //─── c · recordUsage(), en la tabla y en el acumulado ───────────────────────────────────────────
        echoTerminal('[c] AIGateway::recordUsage()');
        $database = (new BaseModel())->getDatabase();
        $existe = $database?->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el COUNT que sigue.
        $existe?->execute([AIUsageMapper::TABLE]);
        $hayTabla = $existe !== null && $existe !== false && ((int) $existe->fetchColumn()) > 0;
        if ($check($database !== null && $hayTabla, 'c0 la tabla del consumo existe', AIUsageMapper::TABLE . ': aplica ' . AIUsageMapper::UPDATE_FILE)) {
            $fila = function (string $capability) use ($database): ?array {
                $consulta = $database->prepare('SELECT tokens, requests FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ? AND periodStart = ?');
                //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el fetch que sigue.
                $consulta->execute([$capability, 'zz-proveedor', AIGateway::periodStart($capability)]);
                $leida = $consulta->fetch(\PDO::FETCH_ASSOC);
                return is_array($leida) ? ['tokens' => (int) $leida['tokens'], 'requests' => (int) $leida['requests']] : null;
            };
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 7);
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 5);
            $check($fila('zz-chat') === ['tokens' => 12, 'requests' => 2], 'c1 dos llamadas suman tokens y peticiones en la misma fila', (string) json_encode($fila('zz-chat')));
            AIGateway::recordUsage('zz-voz', 'zz-proveedor', 3);
            $check($fila('zz-voz') === ['tokens' => 3, 'requests' => 1] && $fila('zz-chat') === ['tokens' => 12, 'requests' => 2], 'c2 otra capacidad, otra fila', (string) json_encode([$fila('zz-voz'), $fila('zz-chat')]));
            $acumulado = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
            $check(($acumulado['zz-proveedor'] ?? null) === 15, 'c3 el acumulado de siempre también sube, desde una clave que no existía y sin aviso', (string) json_encode($acumulado['zz-proveedor'] ?? null));
            $check($lanza(fn() => AIGateway::recordUsage('zz-no-registrada', 'zz-proveedor', 1)) !== null, 'c4 una capacidad no registrada lanza');
            echoTerminal(' ');

            //─── d · Los controles, cada uno con su rechazo ─────────────────────────────────────────────────
            echoTerminal('[d] AIGateway::denialReason() y chatHandler()');
            $motivo = [
                1 => 'La inteligencia artificial está apagada.',
                2 => 'Esta función de inteligencia artificial está apagada.',
                3 => 'No tienes permiso para usar esta función de inteligencia artificial.',
                4 => 'Se ha alcanzado la cuota de esta función de inteligencia artificial en este periodo.',
            ];
            $borrarZz = static function () use ($database): void {
                //RETORNO-IGNORADO: limpieza de la prueba entre casos; lo que importa lo miden los casos.
                $database->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability LIKE ?')->execute(['zz-%']);
            };
            $borrarZz();
            set_config(AIGateway::CONFIG_NAME, false);
            $check(AIGateway::isEnabled() && AIGateway::denialReason('zz-chat', $root) === null && AIGateway::denialReason('zz-chat', $general) === $motivo[3], 'd0 sin ai_center: encendida y con los por omisión de la capacidad (sin tipos: solo root)');
            $check(AIGateway::settingsFor('zz-chat') === ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'period' => AICapability::PERIOD_ACCUMULATED, 'periodLength' => 1, 'periodAnchor' => AIUsageMapper::ACCUMULATED_PERIOD_START], 'd0b settingsFor() sin ai_center: los por omisión', (string) json_encode(AIGateway::settingsFor('zz-chat')));
            set_config('translationAI', AI_OPENAI);
            $centro(['enabled' => false]);
            $lanzada = null;
            try {
                AIGateway::chatHandler('zz-chat', $root);
            } catch (AIUnavailableException $e) {
                $lanzada = $e->getMessage();
            }
            $check(AIGateway::denialReason('zz-chat', $root) === $motivo[1] && $lanzada === $motivo[1], 'd1 general apagado: motivo 1, y chatHandler() lanza sin crear el adaptador (con OpenAI configurado)', var_export($lanzada, true));
            $centro(['capabilities' => ['zz-chat' => ['enabled' => false]]]);
            $check(AIGateway::denialReason('zz-chat', $root) === $motivo[2], 'd2 capacidad apagada: motivo 2');
            $centro(['capabilities' => ['zz-chat' => ['userTypes' => [UsersModel::TYPE_USER_ADMIN_GRAL]]]]);
            $check(AIGateway::denialReason('zz-chat', $general) === $motivo[3] && AIGateway::denialReason('zz-chat', $root) === null, 'd3 tipo no permitido: motivo 3; root pasa igual');
            $check(AIGateway::denialReason('zz-chat', $usuario(UsersModel::TYPE_USER_ADMIN_GRAL)) === null, 'd3b el tipo de la lista pasa');
            $check(AIGateway::denialReason('zz-chat') === $motivo[3], 'd3c sin usuario (en la terminal no hay sesión): motivo 3');
            $paquete = new \PiecesPHP\UserSystem\UserDataPackage(1);
            $centro(['capabilities' => ['zz-chat' => ['userTypes' => []]]]);
            $check((int) $paquete->type === UsersModel::TYPE_USER_ROOT && AIGateway::denialReason('zz-chat', $paquete) === null, 'd3d el usuario conectado (UserDataPackage, el de getLoggedFrameworkUser()): se lee su tipo (root, con la lista vacía, pasa)');
            $centro(['capabilities' => ['zz-chat' => ['quota' => 10]]]);
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 9);
            $antesDeLaCuota = AIGateway::denialReason('zz-chat', $root);
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 1);
            $check($antesDeLaCuota === null && AIGateway::usage('zz-chat') === 10 && AIGateway::denialReason('zz-chat', $root) === $motivo[4], 'd4 cuota: con 9 de 10 pasa; al llegar a 10 (usage == quota), motivo 4, también para root', var_export($antesDeLaCuota, true) . ' · usage ' . AIGateway::usage('zz-chat'));
            $centro(['enabled' => false, 'capabilities' => ['zz-chat' => ['enabled' => false, 'userTypes' => [], 'quota' => 10]]]);
            $check(AIGateway::denialReason('zz-chat', $general) === $motivo[1], 'd5 el orden: con todo apagado y la cuota llena, el motivo 1');
            $centro(['capabilities' => ['zz-chat' => ['enabled' => false, 'userTypes' => [], 'quota' => 10]]]);
            $check(AIGateway::denialReason('zz-chat', $general) === $motivo[2], 'd5b sin el general: el motivo 2');
            $borrarZz();
            echoTerminal(' ');

            //─── e · Un campo no válido se ignora y se anota UNA vez ────────────────────────────────────────
            echoTerminal('[e] Un campo no válido');
            AICapabilityRegistry::register($capacidad('zz-invalido'));
            $registro = basepath('app/logs/error.log.jsonl');
            clearstatcache(true, $registro);
            $desde = is_file($registro) ? (int) filesize($registro) : 0;
            $centro(['capabilities' => ['zz-invalido' => ['quota' => -5, 'period' => 'zz-nunca', 'enabled' => true]]]);
            $primera = AIGateway::settingsFor('zz-invalido');
            $segunda = AIGateway::settingsFor('zz-invalido');
            clearstatcache(true, $registro);
            $nuevo = is_file($registro) ? (string) file_get_contents($registro, false, null, $desde) : '';
            $avisosCuota = substr_count($nuevo, 'zz-invalido.quota');
            $avisosPeriodo = substr_count($nuevo, 'zz-invalido.period');
            $check($primera['quota'] === 0 && $primera['period'] === AICapability::PERIOD_ACCUMULATED && $primera['enabled'] === true && $segunda === $primera, 'e1 se ignoran (vale el de debajo) y lo válido del mismo bloque se aplica', (string) json_encode($primera));
            $check($avisosCuota === 1 && $avisosPeriodo === 1, 'e2 y cada uno se anota UNA vez, aunque se lea dos veces', "cuota {$avisosCuota}, periodo {$avisosPeriodo}");
            echoTerminal(' ');

            //─── f · Lo heredado de la traducción, y lo guardado lo pisa ────────────────────────────────────
            echoTerminal('[f] Los heredados de «translation»');
            AICapabilityRegistry::register($registroPrevio['translation']);
            set_config(AIGateway::CONFIG_NAME, false);
            set_config('translationAIEnable', false);
            set_config(APIController::TRANSLATION_AI_USER_TYPES_CONFIG, [UsersModel::TYPE_USER_ADMIN_ORG]);
            $heredado = AIGateway::settingsFor('translation');
            $check($heredado['enabled'] === false && AIGateway::denialReason('translation', $root) === $motivo[2], 'f1 translationAIEnable en false: la capacidad, apagada', (string) json_encode($heredado));
            $check($heredado['userTypes'] === [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_ORG] && $heredado['quota'] === 0, 'f2 translationAIUserTypes: quién la usa (root siempre); la cuota no se hereda (0)', (string) json_encode($heredado['userTypes']));
            set_config('translationAIEnable', true);
            $check(AIGateway::denialReason('translation', $general) === $motivo[3] && AIGateway::denialReason('translation', $usuario(UsersModel::TYPE_USER_ADMIN_ORG)) === null, 'f3 con la casilla encendida: pasa el tipo heredado y no el general');
            $centro(['capabilities' => ['translation' => ['enabled' => false, 'userTypes' => [UsersModel::TYPE_USER_GENERAL]]]]);
            $guardado = AIGateway::settingsFor('translation');
            $check($guardado['enabled'] === false && $guardado['userTypes'] === [UsersModel::TYPE_USER_GENERAL], 'f4 lo guardado en ai_center pisa lo heredado', (string) json_encode($guardado));
            AICapabilityRegistry::remove('translation');
            echoTerminal(' ');

            //─── g · periodStart() de los cuatro periodos, con relojes fijos ────────────────────────────────
            echoTerminal('[g] AIGateway::periodStart()');
            $en = fn(string $fecha): int => (int) strtotime($fecha);
            $periodo = function (array $ajustes, string $ahora) use ($centro, $en): string {
                $centro(['capabilities' => ['zz-chat' => $ajustes]]);
                return AIGateway::periodStart('zz-chat', $en($ahora));
            };
            $casos = [
                'g1 acumulado: el ancla por omisión' => [['period' => 'accumulated'], '2026-11-13 15:00:00', '2000-01-01 00:00:00'],
                'g2 acumulado con ancla propia' => [['period' => 'accumulated', 'periodAnchor' => '2026-03-04 05:06:07'], '2026-11-13 15:00:00', '2026-03-04 05:06:07'],
                'g3 mensual: el día 1 del mes' => [['period' => 'monthly'], '2026-11-13 15:00:00', '2026-11-01 00:00:00'],
                'g4 mensual: el último segundo del mes sigue en él' => [['period' => 'monthly'], '2026-11-30 23:59:59', '2026-11-01 00:00:00'],
                'g5 mensual: cambia el mes' => [['period' => 'monthly'], '2026-12-01 00:00:00', '2026-12-01 00:00:00'],
                'g6 mensual con ancla dentro del mes: el ancla' => [['period' => 'monthly', 'periodAnchor' => '2026-11-05 10:00:00'], '2026-11-13 15:00:00', '2026-11-05 10:00:00'],
                'g7 cada 7 días' => [['period' => 'every-days', 'periodLength' => 7, 'periodAnchor' => '2026-11-01 08:00:00'], '2026-11-13 12:00:00', '2026-11-08 08:00:00'],
                'g8 cada 7 días: un segundo antes del cambio' => [['period' => 'every-days', 'periodLength' => 7, 'periodAnchor' => '2026-11-01 08:00:00'], '2026-11-15 07:59:59', '2026-11-08 08:00:00'],
                'g9 cada 7 días: el cambio' => [['period' => 'every-days', 'periodLength' => 7, 'periodAnchor' => '2026-11-01 08:00:00'], '2026-11-15 08:00:00', '2026-11-15 08:00:00'],
                'g10 cada mes con ancla el 31: febrero, su último día' => [['period' => 'every-months', 'periodLength' => 1, 'periodAnchor' => '2026-01-31 00:00:00'], '2026-02-28 12:00:00', '2026-02-28 00:00:00'],
                'g11 cada mes con ancla el 31: antes del 28 de febrero, sigue enero' => [['period' => 'every-months', 'periodLength' => 1, 'periodAnchor' => '2026-01-31 00:00:00'], '2026-02-15 00:00:00', '2026-01-31 00:00:00'],
                'g12 cada mes con ancla el 31: marzo vuelve al 31' => [['period' => 'every-months', 'periodLength' => 1, 'periodAnchor' => '2026-01-31 00:00:00'], '2026-03-31 00:00:00', '2026-03-31 00:00:00'],
                'g13 cada mes con ancla el 31: el 30 de marzo sigue en febrero' => [['period' => 'every-months', 'periodLength' => 1, 'periodAnchor' => '2026-01-31 00:00:00'], '2026-03-30 23:59:59', '2026-02-28 00:00:00'],
                'g14 cada 3 meses con ancla el 31: abril, su último día' => [['period' => 'every-months', 'periodLength' => 3, 'periodAnchor' => '2026-01-31 00:00:00'], '2026-05-01 00:00:00', '2026-04-30 00:00:00'],
            ];
            foreach ($casos as $nombre => [$ajustes, $ahora, $esperado]) {
                $obtenido = $periodo($ajustes, $ahora);
                $check($obtenido === $esperado, $nombre, "{$ahora} → {$obtenido}, esperado {$esperado}");
            }
            echoTerminal(' ');

            //─── h · recordUsage() en periodos distintos y usage() por proveedor ────────────────────────────
            echoTerminal('[h] recordUsage() y usage()');
            $borrarZz();
            $centro(['capabilities' => ['zz-chat' => ['period' => 'every-days', 'periodLength' => 1, 'periodAnchor' => '2026-01-01 00:00:00']]]);
            $inicioA = AIGateway::periodStart('zz-chat');
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 4);
            AIGateway::recordUsage('zz-chat', 'zz-otro', 6);
            $usoA = [AIGateway::usage('zz-chat'), AIGateway::usage('zz-chat', 'zz-proveedor'), AIGateway::usage('zz-chat', 'zz-otro')];
            $centro(['capabilities' => ['zz-chat' => ['period' => 'every-days', 'periodLength' => 1, 'periodAnchor' => '2026-01-01 12:34:56']]]);
            $inicioB = AIGateway::periodStart('zz-chat');
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 2);
            $usoB = AIGateway::usage('zz-chat');
            $filas = (int) $database->query('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . "` WHERE capability = 'zz-chat'")->fetchColumn();
            $check($usoA === [10, 4, 6], 'h1 usage() suma los proveedores, o da uno solo', (string) json_encode($usoA));
            $check($inicioA !== $inicioB && $usoB === 2 && $filas === 3, 'h2 otro periodo, otras filas: su consumo empieza de cero', "{$inicioA} / {$inicioB} · uso {$usoB} · filas {$filas}");
            $borrarZz();
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AICapabilityRegistry::swapForTesting($registroPrevio);
        foreach ($opcionesPrevias as $nombre => $valor) {
            set_config($nombre, $valor);
        }
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumuladoPrevio);
        try {
            $database = (new BaseModel())->getDatabase();
            $borrar = $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability LIKE ?');
            //RETORNO-IGNORADO: limpieza de la prueba; si la tabla no existe no hay nada que borrar.
            $borrar?->execute(['zz-%']);
        } catch (\Throwable) {
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El centro de mando de la IA (ADR 0074): el registro, chatHandler() sin llamar a ningún proveedor, recordUsage() y, desde la ronda 2, los controles de la puerta (general, por capacidad, quién la usa, cuota y su orden), los campos no válidos, lo heredado de la traducción, los cuatro periodos y usage().')->setEffects([CliActions::EFFECT_DATABASE])->register();
