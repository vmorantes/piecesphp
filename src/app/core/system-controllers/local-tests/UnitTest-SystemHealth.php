<?php

//La Salud de la instalación enseña hechos con su origen y nunca un secreto: si una fila pierde el origen o el HTML
//deja ver una llave o una contraseña, la pantalla miente o filtra (P179).
//@areas src/app/classes/PiecesPHP/SystemStatus/

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Roles;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\SystemHealth;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/system-health', function ($args) {

    echoTerminal("\e[33m[TEST:SystemHealth] La Salud de la instalación: cada fila con su origen, sin secretos y solo para root\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    try {

        echoTerminal('[1/3] Las filas');
        $rows = SystemHealth::rows();
        $groups = array_keys(SystemHealth::groupTitles());
        $withoutOrigin = array_filter($rows, fn(array $row): bool => trim($row['origin']) === '');
        $check(count($rows) > 0 && count($withoutOrigin) === 0, 'a1 toda fila tiene origen', count($rows) . ' fila(s), ' . count($withoutOrigin) . ' sin origen');
        $groupsWithRows = array_unique(array_column($rows, 'group'));
        $check(count(array_diff($groups, $groupsWithRows)) === 0, 'a2 todos los bloques tienen filas', implode(', ', array_diff($groups, $groupsWithRows)));
        $fake = SystemHealth::rows(['zz_extension_inexistente']);
        $fakeRow = array_values(array_filter($fake, fn(array $row): bool => $row['label'] === 'ext-zz_extension_inexistente'));
        $check(count($fakeRow) === 1 && $fakeRow[0]['state'] === SystemHealth::STATE_MISSING, 'd1 una extensión inexistente sale como «falta»');

        //El grupo de seguridad (P319): PHP en las subidas, los dominios permitidos y el entorno.
        $security = fn(array $all): array => array_values(array_filter($all, fn(array $row): bool => $row['group'] === SystemHealth::GROUP_SECURITY));
        $rowsSecurity = $security($rows);
        foreach ($rowsSecurity as $row) {
            echoTerminal("      · {$row['label']}: {$row['state']} · {$row['value']}");
        }
        $phpRows = array_values(array_filter($rowsSecurity, fn(array $row): bool => $row['origin'] === 'ProtectedUploads::writeDenyHtaccess()'));
        $check(count($phpRows) === 2 && count(array_filter($phpRows, fn(array $row): bool => $row['state'] === SystemHealth::STATE_INFO)) === 2, 's1 en la terminal (sin servidor), las dos carpetas de subidas salen como «depende del servidor»', (string) json_encode($phpRows, \JSON_UNESCAPED_UNICODE));
        $previousSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
        try {
            $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4';
            $uploadsRow = array_values(array_filter($security(SystemHealth::rows()), fn(array $row): bool => str_ends_with($row['label'], 'statics/uploads')));
            $check(count($uploadsRow) === 1 && $uploadsRow[0]['state'] === SystemHealth::STATE_OK, 's2 con Apache, statics/uploads: PHP apagado por su .htaccess', (string) json_encode($uploadsRow, \JSON_UNESCAPED_UNICODE));
        } finally {
            if ($previousSoftware === null) {
                unset($_SERVER['SERVER_SOFTWARE']);
            } else {
                $_SERVER['SERVER_SOFTWARE'] = $previousSoftware;
            }
        }
        $hostsRow = array_values(array_filter($rowsSecurity, fn(array $row): bool => $row['origin'] === "\$config['allowed_hosts']"));
        $hostsEmpty = SystemStatusRoutes::allowedHostsMissing(get_config('allowed_hosts'), false);
        $hostsExpected = !$hostsEmpty ? SystemHealth::STATE_OK : (is_local() ? SystemHealth::STATE_INFO : SystemHealth::STATE_WARNING);
        $check(count($hostsRow) === 1 && $hostsRow[0]['state'] === $hostsExpected, 's3 «Dominios permitidos» con el estado que toca (vacía en local: información)', (string) json_encode($hostsRow, \JSON_UNESCAPED_UNICODE));
        $environmentRow = array_values(array_filter($rowsSecurity, fn(array $row): bool => str_starts_with($row['origin'], 'AppEnvironment')));
        $check(count($environmentRow) === 1 && $environmentRow[0]['state'] === (AppEnvironment::isConfigured() ? SystemHealth::STATE_INFO : SystemHealth::STATE_WARNING), 's4 «Entorno», información si hay environment.php', (string) json_encode($environmentRow, \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        echoTerminal('[2/3] El HTML no deja ver ningún secreto');
        $langGroup = SystemStatusController::LANG_GROUP;
        $title = 'Salud de la instalación';
        $groupTitles = SystemHealth::groupTitles();
        ob_start();
        include basepath('app/classes/PiecesPHP/SystemStatus/Views/health.php');
        $html = (string) ob_get_clean();
        $database = get_config('database');
        //Se leen para buscarlos, nunca se imprimen: solo se dice cuántos había y si aparece alguno.
        $secrets = [
            'app_key' => get_config('app_key'),
            'contraseña de la base' => is_array($database) ? ($database['default']['password'] ?? '') : '',
            'contraseña del correo' => MailConfig::getValue('password'),
        ];
        $present = [];
        $leaked = [];
        foreach ($secrets as $name => $secret) {
            if (is_string($secret) && mb_strlen($secret) >= 4) {
                $present[] = $name;
                if (str_contains($html, $secret) || str_contains($html, htmlspecialchars($secret, ENT_QUOTES, 'UTF-8'))) {
                    $leaked[] = $name;
                }
            }
        }
        echoTerminal('   secretos buscados: ' . count($present) . ' (' . implode(', ', $present) . ')');
        $check(str_contains($html, 'data-system-health-row'), 'b0 el HTML pinta las filas');
        //El Resumen: lo que pide atención, o el mensaje de que todo está bien.
        $render = function (array $rows) use ($langGroup, $title, $groupTitles): string {
            ob_start();
            include basepath('app/classes/PiecesPHP/SystemStatus/Views/health.php');
            return (string) ob_get_clean();
        };
        $warning = ['group' => SystemHealth::GROUP_SECURITY, 'label' => 'zz-dato-en-aviso', 'value' => 'zz', 'state' => SystemHealth::STATE_WARNING, 'origin' => 'zz'];
        $okRow = ['group' => SystemHealth::GROUP_SECURITY, 'label' => 'zz-dato-bien', 'value' => 'zz', 'state' => SystemHealth::STATE_OK, 'origin' => 'zz'];
        $withWarning = $render([$okRow, $warning]);
        $check(substr_count($withWarning, 'data-system-health-summary-row') === 1 && str_contains($withWarning, 'zz-dato-en-aviso') && !str_contains($withWarning, 'data-system-health-all-ok'), 'r1 con una fila en aviso, sale en el Resumen (y solo ella)');
        $allOk = $render([$okRow]);
        $check(str_contains($allOk, 'data-system-health-all-ok') && str_contains($allOk, __($langGroup, 'Todo en orden.')) && !str_contains($allOk, 'data-system-health-summary-row'), 'r2 sin ninguna, el mensaje «Todo en orden.»');
        $check(substr_count($html, 'data-system-health-tabs') === 1 && substr_count($html, 'class="ui tab"') + substr_count($html, 'class="ui active tab"') === count($groupTitles) + 1, 'r3 una pestaña por grupo, más el Resumen');
        $check(count($leaked) === 0, 'b1 el HTML no contiene la app_key, ni la contraseña de la base, ni la del correo', count($present) . ' secreto(s) buscados (' . implode(', ', $present) . '); aparecen: ' . implode(', ', $leaked));
        echoTerminal(' ');

        echoTerminal('[3/3] Solo root');
        $others = array_filter(array_keys(UsersModel::getTypesUser()), fn($type): bool => (int) $type !== UsersModel::TYPE_USER_ROOT);
        $allowedOthers = array_filter($others, fn($type): bool => Roles::hasPermissions('system-status-health', (int) $type));
        $check(Roles::hasPermissions('system-status-health', UsersModel::TYPE_USER_ROOT) && count($others) > 0 && count($allowedOthers) === 0, 'c1 solo root tiene la ruta system-status-health', 'otros con permiso: ' . implode(', ', $allowedOthers));
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La Salud de la instalación: cada fila con su origen, ninguna llave ni contraseña en el HTML y solo root (P179).')->setEffects([CliActions::EFFECT_NONE])->register();
