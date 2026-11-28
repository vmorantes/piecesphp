<?php

//H381: apagadas las aprobaciones, nada toca su tabla e isApproved() dice que sí. Una TEMPORARY TABLE de sesión con su nombre
//la tapa solo en esta conexión (toda lectura falla); se quita en finally y la real no se toca.
//@areas src/app/classes/SystemApprovals/
//@areas src/app/classes/ReportsManage/

use Organizations\OrganizationsRoutes;
use Organizations\Seeds\OrganizationsSeeder;
use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Seeds\UsersSeeder;
use Publications\PublicationsRoutes;
use Publications\Seeds\PublicationsSeeder;
use ReportsManage\Queries\ReportsManageQueries;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/system-approvals-off', function ($args) {

    echoTerminal("\e[33m[TEST:system-approvals-off] Con las aprobaciones apagadas, su tabla ni se lee ni se escribe\e[39m");
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
    $sinError = function (callable $paso) {
        try {
            return ['ok' => true, 'valor' => $paso(), 'error' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'valor' => null, 'error' => get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200)];
        }
    };

    $database = (new BaseModel())->getDatabase();
    $pdoDelGestor = BaseModel::getDb(Config::app_db('default')['db']);
    $pdoDelMapper = SystemApprovalsMapper::model()->getDatabase();
    if (!$check($database !== null && $pdoDelGestor !== null && $pdoDelMapper !== null, 'c1 hay conexión a la base')) {
        return $balance();
    }
    $conexion = fn ($pdo): int => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
    //La temporal solo tapa en su conexión: el gestor, el mapper y esta prueba tienen que compartirla.
    if (!$check($conexion($database) === $conexion($pdoDelGestor) && $conexion($database) === $conexion($pdoDelMapper), 'c2 CANARIO: el gestor de aprobaciones, su mapper y la prueba usan la misma conexión')) {
        return $balance();
    }

    $tabla = SystemApprovalsMapper::TABLE;
    //Sin la tabla (las aprobaciones apagadas desde el principio), la temporal la crea en vez de taparla: la prueba vale igual.
    $existia = $sinError(fn () => $database->query("SELECT 1 FROM `{$tabla}` LIMIT 1")->fetchAll())['ok'];
    $tapada = false;
    $antes = SystemApprovalsRoutes::swapEnabledForTesting(null);
    try {
        $database->exec("CREATE TEMPORARY TABLE `{$tabla}` (zz_sin_columnas INT)");
        $tapada = true;
        $lectura = $sinError(fn () => $database->query("SELECT referenceValue FROM `{$tabla}` LIMIT 1")->fetchAll());
        $check(!$lectura['ok'], 'c3 CANARIO: con la tabla tapada, leerla falla', $lectura['ok'] ? 'no falló' : '');
        SystemApprovalsRoutes::swapEnabledForTesting(true);
        $encendido = $sinError(fn () => SystemApprovalManager::init());
        $check(!$encendido['ok'], 'c4 CANARIO: encendido, init() sí toca la tabla (falla contra la tapada)', $encendido['ok'] ? 'no falló' : '');

        SystemApprovalsRoutes::swapEnabledForTesting(false);
        echoTerminal('');
        echoTerminal('[1] Apagado');
        $r = $sinError(fn () => SystemApprovalManager::init());
        $check($r['ok'], '1a init() vuelve sin tocar la tabla', $r['error']);
        $r = $sinError(fn () => SystemApprovalManager::getInstance()->isApproved(UsersModel::class, UsersModel::SYSTEM_USER_ID));
        $check($r['ok'] && $r['valor'] === true, '1b isApproved() responde aprobado sin leerla (el inicio del panel por tipo, HomeDestinationRegistry)', $r['error'] ?: var_export($r['valor'], true));
        $r = $sinError(fn () => ReportsManageQueries::getResearchersByCountry());
        $check($r['ok'] && is_array($r['valor']), '1c el informe de personas por país no la lee', $r['error']);
        if (!OrganizationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 1d: Organizaciones apagado (sus tablas pueden no existir).");
        } else {
            $r = $sinError(fn () => ReportsManageQueries::getOrganizationsByCountry());
            $check($r['ok'] && is_array($r['valor']), '1d el informe de organizaciones por país no la lee', $r['error']);
        }
        //Con Publicaciones apagado el total sale antes, en 0, y la prueba no vería la guarda.
        if (!PublicationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 1e: Publicaciones apagado (getTotalPublicationsQty vuelve 0 antes de la guarda).");
        } else {
            $aprobadas = $sinError(fn () => ReportsManageQueries::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_APPROVED));
            $todas = $sinError(fn () => ReportsManageQueries::getTotalPublicationsQty('ALL'));
            $pendientes = $sinError(fn () => ReportsManageQueries::getTotalPublicationsQty(SystemApprovalsMapper::STATUS_PENDING));
            $check($aprobadas['ok'] && $todas['ok'] && $pendientes['ok'] && $aprobadas['valor'] === $todas['valor'] && $pendientes['valor'] === 0,
                '1e el total de publicaciones: aprobadas = todas y pendientes 0, sin leerla', ($aprobadas['error'] . $todas['error'] . $pendientes['error']) ?: "aprobadas {$aprobadas['valor']}, todas {$todas['valor']}, pendientes {$pendientes['valor']}");
        }
        $propias = array_merge(array_keys((new UsersSeeder())->ownedRows()), array_keys((new OrganizationsSeeder())->ownedRows()), array_keys((new PublicationsSeeder())->ownedRows()));
        $check(!in_array($tabla, $propias, true), '1f los seeders de usuarios, organizaciones y publicaciones no la cuentan como suya');
        //El oyente de «actualizado» de cada manejador cargado: con el módulo apagado no lee la tabla.
        $manejadores = (new \ReflectionProperty(SystemApprovalManager::class, 'configurations'))->getValue(SystemApprovalManager::getInstance());
        $errores = [];
        foreach ($manejadores as $manejador) {
            $clase = $manejador::getMapperClass();
            $r = $sinError(function () use ($clase) {
                $carga = new $clase();
                BaseEventDispatcher::dispatch($clase, 'updated', $carga);
            });
            if (!$r['ok']) {
                $errores[] = "{$clase}: {$r['error']}";
            }
        }
        $check(count($manejadores) > 0 && $errores === [], '1g el oyente de «actualizado» de cada manejador no la lee (' . count($manejadores) . ' manejador(es))', implode(' · ', $errores));
    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        SystemApprovalsRoutes::swapEnabledForTesting($antes);
        if ($tapada) {
            $database->exec("DROP TEMPORARY TABLE IF EXISTS `{$tabla}`");
        }
        $real = $sinError(fn () => $database->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn());
        $check($existia ? $real['ok'] : !$real['ok'], $existia ? 'z1 la tabla real vuelve a verse en esta conexión' : 'z1 no había tabla, y la temporal ya no está', $real['error']);
    }

    return $balance();

})->setDescription('H381: con las aprobaciones apagadas, init(), isApproved(), los informes por país y de publicaciones, los seeders y el oyente de actualizado no tocan su tabla (tapada en la sesión por una temporal); isApproved responde aprobado.')->setEffects([CliActions::EFFECT_DATABASE])->register();
