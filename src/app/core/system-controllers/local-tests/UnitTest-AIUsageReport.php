<?php

//El coste estimado de la IA y el «Registro» de Configuración › IA (ADR 0077 §8): los precios, el coste de cada fila y el
//informe por función, proveedor o modelo, y el consumo sin una actualización. Sin red; filas zz-rep- y tablas zz_ que se borran.
//@areas nucleo

use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUsageReport;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/ai-usage-report', function ($args) {

    echoTerminal("\e[33m[TEST:AIUsageReport] Los precios, el coste estimado y el Registro de la IA\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c0 CANARIO: conexión a la base')) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $tabla = AIUsageMapper::TABLE;
    $huella = fn(): string => sha1((string) json_encode($database->query("SELECT * FROM `{$tabla}` ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC)));
    $huellaAntes = $huella();
    $centroAntes = get_config(AIGateway::CONFIG_NAME);
    $borrar = function () use ($database, $tabla): void {
        //RETORNO-IGNORADO: limpieza de la prueba; lo comprueba z1 con la huella.
        $database->prepare("DELETE FROM `{$tabla}` WHERE provider LIKE ?")->execute(['zz-rep-%']);
    };

    try {
        //─── 1 · Los precios ───────────────────────────────────────────────────────────────────────
        echoTerminal('[1] Los precios de un registro guardado: se validan y se guardan');
        $nunca = function (): never {
            throw new InvalidParameterValueException('zz-precio');
        };
        $validos = SettingsController::aiPricesFromInput([
            ['model' => 'zz-m', 'input' => '0.15', 'output' => '0.6'],
            ['model' => 'zz-gratis', 'input' => '', 'output' => '0'],
            ['model' => 'zz-sin', 'input' => '', 'output' => ''],
            ['model' => '', 'input' => '', 'output' => ''],
        ], $nunca);
        $check($validos === ['zz-m' => ['input' => 0.15, 'output' => 0.6], 'zz-gratis' => ['input' => 0.0, 'output' => 0.0]], '1a válidos: el que tiene precios; uno vacío vale 0; sin ningún precio, no cuenta', (string) json_encode($validos));
        $rechazos = [];
        foreach (['negativo' => ['model' => 'zz-m', 'input' => '-1', 'output' => '1'], 'texto' => ['model' => 'zz-m', 'input' => 'diez', 'output' => '1'], '5 decimales' => ['model' => 'zz-m', 'input' => '0.00001', 'output' => '1'], 'más de 10000' => ['model' => 'zz-m', 'input' => '10000.0001', 'output' => '1'], 'sin modelo' => ['model' => '', 'input' => '1', 'output' => '1'], 'Otro… sin escribir' => ['model' => '__other__', 'input' => '1', 'output' => '1']] as $caso => $fila) {
            try {
                SettingsController::aiPricesFromInput([$fila], $nunca);
                $rechazos[$caso] = 'PASA';
            } catch (InvalidParameterValueException $e) {
                $rechazos[$caso] = 'rechazo';
            }
        }
        try {
            SettingsController::aiPricesFromInput([['model' => 'zz-m', 'input' => '1', 'output' => '1'], ['model' => 'zz-m', 'input' => '2', 'output' => '2']], $nunca);
            $rechazos['repetido'] = 'PASA';
        } catch (InvalidParameterValueException $e) {
            $rechazos['repetido'] = 'rechazo';
        }
        $check(array_unique(array_values($rechazos)) === ['rechazo'] && SettingsController::aiPricesFromInput([['model' => 'zz-m', 'input' => '10000', 'output' => '0.0001']], $nunca) === ['zz-m' => ['input' => 10000.0, 'output' => 0.0001]], '1b DISCRIMINANTE: negativo, texto, más de 4 decimales, más de 10000, sin modelo, «Otro…» vacío o repetido: rechazo; 10000 y 0.0001, sí', (string) json_encode($rechazos));
        $guardado = [['id' => 'zz-rep-p', 'type' => 'deepseek', 'alias' => 'Zz Rep', 'models' => [], 'quota' => 0, 'period' => 'accumulated', 'periodLength' => 1, 'prices' => ['zz-viejo' => ['input' => 1.0, 'output' => 1.0]]]];
        $fila = ['id' => 'zz-rep-p', 'type' => 'deepseek', 'alias' => 'Zz Rep', 'quota' => '0', 'period' => 'accumulated', 'periodLength' => '1'];
        $conPrecios = SettingsController::aiProvidersFromInput([$fila + ['prices_sent' => '1', 'prices' => [['model' => 'zz-m', 'input' => '1', 'output' => '2']]]], $guardado);
        $sinEnviar = SettingsController::aiProvidersFromInput([$fila], $guardado);
        $nuevo = SettingsController::aiProvidersFromInput([['id' => '', '_newId' => 'zz-rep-nuevo', 'type' => 'deepseek', 'alias' => 'Zz Nuevo', 'prices_sent' => '1', 'prices' => [['model' => 'zz-m', 'input' => '1', 'output' => '2']]]], $guardado);
        $check(($conPrecios[0]['prices'] ?? null) === ['zz-m' => ['input' => 1.0, 'output' => 2.0]] && ($sinEnviar[0]['prices'] ?? null) === ['zz-viejo' => ['input' => 1.0, 'output' => 1.0]] && !array_key_exists('prices', $nuevo[0] ?? []), '1c un registro guardado guarda los suyos; sin prices_sent conserva los que tenía; uno sin guardar no lleva precios', (string) json_encode([$conPrecios[0]['prices'] ?? null, $sinEnviar[0]['prices'] ?? null, $nuevo[0]['prices'] ?? 'sin']));
        try {
            SettingsController::aiProvidersFromInput([$fila + ['prices_sent' => '1', 'prices' => [['model' => 'zz-m', 'input' => '-5', 'output' => '1']]]], $guardado);
            $rechazoFila = 'PASA';
        } catch (InvalidParameterValueException $e) {
            $rechazoFila = $e->getMessage();
        }
        $check($rechazoFila !== 'PASA' && str_contains($rechazoFila, 'Zz Rep'), '1d un precio malo en el guardado: rechazo, nombrando el registro', $rechazoFila);
        echoTerminal(' ');

        //─── 2 · El coste de una fila ──────────────────────────────────────────────────────────────
        echoTerminal('[2] El coste de una fila');
        $precios = ['zz-rep-p' => ['zz-m' => ['input' => 1.0, 'output' => 2.0]], 'openai' => ['zz-o' => ['input' => 3.0, 'output' => 3.0]]];
        $costes = [
            'con precio' => AIUsageReport::rowCostUsd(['provider' => 'zz-rep-p', 'model' => 'zz-m', 'inputTokens' => 1000, 'outputTokens' => 2000, 'reportedCostUsd' => 0], $precios),
            'sin precio' => AIUsageReport::rowCostUsd(['provider' => 'zz-rep-p', 'model' => 'zz-otro', 'inputTokens' => 1000, 'outputTokens' => 2000, 'reportedCostUsd' => 0], $precios),
            'del proveedor' => AIUsageReport::rowCostUsd(['provider' => 'zz-rep-p', 'model' => 'zz-m', 'inputTokens' => 1000, 'outputTokens' => 2000, 'reportedCostUsd' => 0.7], $precios),
            'vieja, solo tokens' => AIUsageReport::rowCostUsd(['provider' => 'zz-rep-p', 'model' => 'zz-m', 'inputTokens' => 0, 'outputTokens' => 0, 'reportedCostUsd' => 0], $precios),
            'convertido, con su nombre de antes' => AIUsageReport::rowCostUsd(['provider' => AI_OPENAI, 'model' => 'zz-o', 'inputTokens' => 1000000, 'outputTokens' => 0, 'reportedCostUsd' => 0], $precios),
        ];
        $check(abs((float) $costes['con precio'] - 0.005) < 1e-12 && $costes['sin precio'] === null && $costes['del proveedor'] === 0.7 && $costes['vieja, solo tokens'] === null && abs((float) $costes['convertido, con su nombre de antes'] - 3.0) < 1e-12, '2a con precio: entrada y salida por su precio entre un millón; sin precio: null; con el del proveedor: ese; una fila vieja sin entrada ni salida: null; «OpenAI» busca el precio del registro openai', (string) json_encode($costes));
        echoTerminal(' ');

        //─── 3 · El Registro ───────────────────────────────────────────────────────────────────────
        echoTerminal('[3] El Registro: agrupa, filtra por desde, excluye @provider y suma');
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-rep-p', 'type' => 'deepseek', 'alias' => 'Zz Rep', 'prices' => ['zz-m' => ['input' => 1, 'output' => 2]]]]]);
        $borrar();
        $ahora = date('Y-m-d H:i:s');
        $insertar = $database->prepare("INSERT INTO `{$tabla}` (capability, provider, model, periodStart, tokens, inputTokens, outputTokens, reportedCostUsd, requests, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ([
            ['zz-cap-a', 'zz-rep-p', 'zz-m', '2026-11-01 00:00:00', 3000, 1000, 2000, 0, 2, $ahora],
            ['zz-cap-a', 'zz-rep-p', 'zz-m', AIUsageMapper::ACCUMULATED_PERIOD_START, 300, 0, 0, 0, 1, $ahora],
            ['zz-cap-b', 'zz-rep-p', 'zz-sin-precio', '2026-11-01 00:00:00', 500, 200, 300, 0, 1, $ahora],
            ['zz-cap-b', 'zz-rep-q', 'zz-m', '2026-11-01 00:00:00', 100, 50, 50, 0.7, 1, $ahora],
            [AIGateway::PROVIDER_USAGE_ROW, 'zz-rep-p', 'zz-m', '2026-11-01 00:00:00', 999999, 1, 1, 0, 9, $ahora],
            ['zz-cap-a', 'zz-rep-p', 'zz-m', '2024-01-01 00:00:00', 150, 50, 50, 0, 1, '2024-01-02 00:00:00'],
        ] as $f) {
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza.
            $insertar->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7], $f[8], $f[9], $f[9]]);
        }
        $nuestras = fn(array $informe): array => array_values(array_map(fn(array $r): string => "{$r['label']}|{$r['periodStart']}|{$r['requests']}|{$r['total']}|" . ($r['cost'] === null ? '—' : round($r['cost'], 6)) . ($r['partial'] ? '|parcial' : ''), array_filter($informe['rows'], fn(array $r): bool => str_starts_with($r['group'], 'zz-'))));
        $sumaCuadra = fn(array $informe): bool => array_sum(array_column($informe['rows'], 'total')) === $informe['totals']['total'] && array_sum(array_column($informe['rows'], 'requests')) === $informe['totals']['requests'];
        $porFuncion = AIUsageReport::report(AIUsageReport::GROUP_CAPABILITY, AIUsageReport::SINCE_ALL);
        $check($nuestras($porFuncion) === [
            'zz-cap-a|2026-11-01 00:00:00|2|3000|0.005',
            'zz-cap-a|2024-01-01 00:00:00|1|150|0.00015',
            'zz-cap-a|2000-01-01 00:00:00|1|300|—',
            'zz-cap-b|2026-11-01 00:00:00|2|600|0.7|parcial',
        ], '3a por función y periodo, con su coste (parcial si a alguna fila le falta precio); sin la fila @provider (999999)', (string) json_encode($nuestras($porFuncion)));
        $porProveedor = AIUsageReport::report(AIUsageReport::GROUP_PROVIDER, AIUsageReport::SINCE_ALL);
        $check(in_array('Zz Rep|2026-11-01 00:00:00|3|3500|0.005|parcial', $nuestras($porProveedor), true) && in_array('zz-rep-q|2026-11-01 00:00:00|1|100|0.7', $nuestras($porProveedor), true), '3b por proveedor: el sobrenombre del registro; el que ya no existe, con su texto guardado', (string) json_encode($nuestras($porProveedor)));
        $porModelo = AIUsageReport::report(AIUsageReport::GROUP_MODEL, AIUsageReport::SINCE_ALL);
        $check(in_array('zz-sin-precio|2026-11-01 00:00:00|1|500|—', $nuestras($porModelo), true) && in_array('zz-m|2026-11-01 00:00:00|3|3100|0.705', $nuestras($porModelo), true), '3c por modelo, tal cual', (string) json_encode($nuestras($porModelo)));
        $doceMeses = AIUsageReport::report(AIUsageReport::GROUP_CAPABILITY, 12);
        $check(!in_array('zz-cap-a|2024-01-01 00:00:00|1|150|0.00015', $nuestras($doceMeses), true) && count($nuestras($doceMeses)) === 3, '3d «Desde: los últimos 12 meses» deja fuera la fila que no se toca desde 2024', (string) json_encode($nuestras($doceMeses)));
        $check($sumaCuadra($porFuncion) && $sumaCuadra($porProveedor) && $sumaCuadra($porModelo) && $sumaCuadra($doceMeses), '3e la fila de totales suma las filas, en las cuatro vistas');
        $raro = AIUsageReport::report('zz-otro', 7);
        $check(array_column($raro['rows'], 'group') === array_column($porFuncion['rows'], 'group'), '3f un agrupar o un desde que no están en su lista: por función y todo');
        $check($porFuncion['providerCostMissing'] === false, '3g con la tabla al día, el Registro no avisa de ninguna actualización');
        echoTerminal(' ');

        //─── 4 · Sin una actualización de la base ──────────────────────────────────────────────────
        echoTerminal('[4] Sin una actualización: el consumo y la cuota siguen contando (copias zz- de la tabla, sin tocar la real)');
        $database->exec('DROP TABLE IF EXISTS `zz_ai_usage_sin_coste`, `zz_ai_usage_viejo`');
        $database->exec("CREATE TABLE `zz_ai_usage_sin_coste` LIKE `{$tabla}`");
        $database->exec('ALTER TABLE `zz_ai_usage_sin_coste` DROP COLUMN `reportedCostUsd`');
        //La tabla de 2026-11-13-consumo-de-la-ia, con su índice único.
        $database->exec('CREATE TABLE `zz_ai_usage_viejo` (`id` bigint NOT NULL AUTO_INCREMENT, `capability` varchar(64) COLLATE utf8mb4_bin NOT NULL, `provider` varchar(64) COLLATE utf8mb4_bin NOT NULL, `periodStart` datetime NOT NULL, `tokens` bigint NOT NULL, `requests` int NOT NULL, `createdAt` datetime NOT NULL, `updatedAt` datetime NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `capabilityProviderPeriod` (`capability`, `provider`, `periodStart`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'capabilities' => [AIGateway::TRANSLATION_CAPABILITY => ['enabled' => true, 'userTypes' => [], 'quota' => 100, 'period' => 'accumulated']]]);
        $raiz = new \PiecesPHP\UserSystem\ORM\UsersModel();
        $raiz->type = \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT;
        $escalones = [];
        foreach (['zz_ai_usage_sin_coste' => AIGateway::USAGE_STEP_WITHOUT_COST, 'zz_ai_usage_viejo' => AIGateway::USAGE_STEP_OLDEST] as $copia => $esperado) {
            AIGateway::swapUsageTableForTesting($copia);
            $antes = AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY);
            AIGateway::recordUsage(AIGateway::TRANSLATION_CAPABILITY, 'zz-rep-g', 60, 30, 30, 'zz-m', null, 0.5);
            AIGateway::recordUsage(AIGateway::TRANSLATION_CAPABILITY, 'zz-rep-g', 60, 30, 30, 'zz-m', null, 0.5);
            $filas = (int) $database->query("SELECT COUNT(*) FROM `{$copia}`")->fetchColumn();
            $escalones[$copia] = ['escalon' => AIGateway::usageStep(), 'esperado' => $esperado, 'consumo' => AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY) - $antes, 'filas' => $filas, 'limite' => AIGateway::availability(AIGateway::TRANSLATION_CAPABILITY, $raiz)['state']];
        }
        AIGateway::swapUsageTableForTesting(null);
        $bien = fn(array $e): bool => $e['escalon'] === $e['esperado'] && $e['consumo'] === 120 && $e['filas'] === 1 && $e['limite'] === AIGateway::AVAILABILITY_LIMIT;
        $check($bien($escalones['zz_ai_usage_sin_coste']), '4a sin reportedCostUsd (falta 2026-11-16): se anota sin el coste, en una fila, suma 120 y la cuota de 100 queda alcanzada', (string) json_encode($escalones['zz_ai_usage_sin_coste']));
        $check($bien($escalones['zz_ai_usage_viejo']), '4b con la tabla de 2026-11-13 (sin modelo, entrada ni salida): igual, con su índice único', (string) json_encode($escalones['zz_ai_usage_viejo']));
        AIGateway::swapUsageTableForTesting('zz_ai_usage_sin_coste');
        $sinCoste = AIUsageReport::report(AIUsageReport::GROUP_CAPABILITY, AIUsageReport::SINCE_ALL);
        AIGateway::swapUsageTableForTesting('zz_ai_usage_viejo');
        try {
            AIUsageReport::report(AIUsageReport::GROUP_CAPABILITY, AIUsageReport::SINCE_ALL);
            $viejo = 'sin excepción';
        } catch (\PDOException $e) {
            $viejo = 'excepción';
        }
        AIGateway::swapUsageTableForTesting(null);
        $check($sinCoste['providerCostMissing'] === true && array_sum(array_column($sinCoste['rows'], 'total')) === 120 && $viejo === 'excepción', '4c el Registro: sin la columna del coste se arma (y lo avisa); sin columnas más viejas, lanza («El consumo no está disponible.»)', (string) json_encode(['aviso' => $sinCoste['providerCostMissing'], 'total' => array_sum(array_column($sinCoste['rows'], 'total')), 'viejo' => $viejo]));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        AIGateway::swapUsageTableForTesting(null);
        set_config(AIGateway::CONFIG_NAME, $centroAntes);
        try {
            $database->exec('DROP TABLE IF EXISTS `zz_ai_usage_sin_coste`, `zz_ai_usage_viejo`');
        } catch (\Throwable $e) {
            $check(false, 'z0 las copias zz- de la tabla se borran', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
        }
        try {
            $borrar();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
        }
    }
    $quedanCopias = (int) $database->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('zz_ai_usage_sin_coste', 'zz_ai_usage_viejo')")->fetchColumn();
    $check($quedanCopias === 0, 'z2 no queda ninguna copia zz- de la tabla', "quedan {$quedanCopias}");
    $huellaDespues = $huella();
    $check($huellaDespues === $huellaAntes, 'z1 la tabla del consumo, como estaba (sha1)', "{$huellaAntes} → {$huellaDespues}");

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El coste estimado de la IA y el «Registro» de Configuración › IA (ADR 0077 §8), sin red: la validación y el guardado de los precios de un registro, el coste de cada fila (precio, el del proveedor, sin precio y filas viejas) y el informe por función, proveedor o modelo, con «desde», sin las filas @provider y con sus totales. Siembra filas zz-rep- en el consumo y las borra (sha1 igual).')->setEffects([CliActions::EFFECT_DATABASE])->register();
