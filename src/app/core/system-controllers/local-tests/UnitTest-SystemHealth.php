<?php

//La Salud de la instalación enseña hechos con su origen y nunca un secreto: si una fila pierde el origen o el HTML
//deja ver una llave o una contraseña, la pantalla miente o filtra (P179).
//@areas src/app/classes/PiecesPHP/SystemStatus/

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\AppVersion;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\SystemHealth;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

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

    //Exportar deja una línea en el registro de acciones: las de la prueba se borran al final (por SQL preparado, como hace
    //UnitTest-DataTransferHub: el mapper no borra a propósito).
    $logsBefore = LogsMapper::model();
    $logsBefore->resetAll();
    $logsBefore->select(['MAX(id) AS maxID'])->execute();
    $maxLogBefore = (int) (((array) (((array) $logsBefore->result())[0] ?? []))['maxID'] ?? 0);
    $exportedText = LogsMapper::MESSAGES[LogsMapper::MSG_SYSTEM_HEALTH_EXPORTED];

    try {

        echoTerminal('[1/4] Las filas');
        $rows = SystemHealth::rows();
        $groups = array_keys(SystemHealth::groupTitles());
        $withoutOrigin = array_filter($rows, fn(array $row): bool => trim($row['origin']) === '');
        $check(count($rows) > 0 && count($withoutOrigin) === 0, 'a1 toda fila tiene origen', count($rows) . ' fila(s), ' . count($withoutOrigin) . ' sin origen');
        $groupsWithRows = array_unique(array_column($rows, 'group'));
        $check(count(array_diff($groups, $groupsWithRows)) === 0, 'a2 todos los bloques tienen filas', implode(', ', array_diff($groups, $groupsWithRows)));
        $fake = SystemHealth::rows(['zz_extension_inexistente']);
        $fakeRow = array_values(array_filter($fake, fn(array $row): bool => $row['label'] === 'ext-zz_extension_inexistente'));
        $check(count($fakeRow) === 1 && $fakeRow[0]['state'] === SystemHealth::STATE_MISSING, 'd1 una extensión inexistente sale como «falta»');
        //CVE-2026-128064: por debajo de la 8.5.13, aviso; desde ella, como siempre.
        $check(SystemHealth::phpVersionState(80512) === SystemHealth::STATE_WARNING, 'v1 PHP 8.5.12 sale en aviso');
        $check(SystemHealth::phpVersionState(80513) === SystemHealth::STATE_INFO && SystemHealth::phpVersionState(80600) === SystemHealth::STATE_INFO, 'v2 la 8.5.13 y la 8.6.0, como siempre (información)');
        $versionRow = array_values(array_filter($rows, fn(array $row): bool => $row['group'] === SystemHealth::GROUP_PHP && str_starts_with($row['origin'], 'PHP_VERSION')));
        $check(count($versionRow) === 1 && $versionRow[0]['state'] === SystemHealth::phpVersionState(PHP_VERSION_ID), 'v3 la fila «Versión de PHP» usa ese estado', (string) json_encode($versionRow, \JSON_UNESCAPED_UNICODE));

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
        $hostsEmpty = SystemStatusRoutes::allowedHostsMissing(get_config('allowed_hosts'));
        $hostsExpected = !$hostsEmpty ? SystemHealth::STATE_OK : (is_local() ? SystemHealth::STATE_INFO : SystemHealth::STATE_WARNING);
        $check(count($hostsRow) === 1 && $hostsRow[0]['state'] === $hostsExpected, 's3 «Dominios permitidos» con el estado que toca (vacía en local: información)', (string) json_encode($hostsRow, \JSON_UNESCAPED_UNICODE));
        $environmentRow = array_values(array_filter($rowsSecurity, fn(array $row): bool => str_starts_with($row['origin'], 'AppEnvironment')));
        $check(count($environmentRow) === 1 && $environmentRow[0]['state'] === (AppEnvironment::isConfigured() ? SystemHealth::STATE_INFO : SystemHealth::STATE_WARNING), 's4 «Entorno», información si hay environment.php', (string) json_encode($environmentRow, \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //H511: la carpeta de claves es la que usa el framework, y una vista cuenta como tabla.
        $keysRow = array_values(array_filter(SystemHealth::rows(), fn(array $row): bool => str_starts_with($row['origin'], 'AppKeyring::directory()')));
        $keysDirectory = \PiecesPHP\Core\Crypto\AppKeyring::directory();
        $check(count($keysRow) === 1 && ($keysDirectory === null ? $keysRow[0]['value'] === 'Falta' : $keysRow[0]['value'] !== 'Falta' && str_ends_with($keysDirectory, ltrim(str_replace('../', '', $keysRow[0]['label']), '/'))), 's5 la carpeta de claves: la de AppKeyring::directory(), y «Falta» solo si no hay', (string) json_encode($keysRow, \JSON_UNESCAPED_UNICODE));
        $check(SystemHealth::missingDeclaredTables(['zz_a', 'zz_b', 'zz_c'], ['zz_a'], ['zz_b']) === ['zz_c'], 's6 una tabla declarada que existe como vista no falta (pura)');
        $base = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        $vista = 'zz_salud_vista_' . bin2hex(random_bytes(3));
        try {
            //DDL: no se deshace con ROLLBACK; se borra en el finally (db-backup antes, ADR 0024).
            $base->exec("CREATE VIEW `{$vista}` AS SELECT 1 AS uno");
            $check(in_array($vista, SystemHealth::databaseViews(), true) && SystemHealth::missingDeclaredTables([$vista], \PiecesPHP\Settings\Controllers\SettingsController::databaseTables(), SystemHealth::databaseViews()) === [], 's7 con una vista de verdad en la base: databaseViews() la ve y no cuenta como tabla que falta');
        } finally {
            $base->exec("DROP VIEW IF EXISTS `{$vista}`");
        }
        $check(!in_array($vista, SystemHealth::databaseViews(), true), 's8 la vista de la prueba ya no está');
        echoTerminal(' ');

        echoTerminal('[2/4] El HTML no deja ver ningún secreto');
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

        echoTerminal('[3/4] Solo root');
        $others = array_filter(array_keys(UsersModel::getTypesUser()), fn($type): bool => (int) $type !== UsersModel::TYPE_USER_ROOT);
        $allowedOthers = array_filter($others, fn($type): bool => Roles::hasPermissions('system-status-health', (int) $type));
        $check(Roles::hasPermissions('system-status-health', UsersModel::TYPE_USER_ROOT) && count($others) > 0 && count($allowedOthers) === 0, 'c1 solo root tiene la ruta system-status-health', 'otros con permiso: ' . implode(', ', $allowedOthers));
        echoTerminal(' ');

        echoTerminal('[4/4] Exportar en Markdown (H510)');
        $synthetic = [
            ['group' => SystemHealth::GROUP_SECURITY, 'label' => 'zz-dato-bien', 'value' => 'zz', 'state' => SystemHealth::STATE_OK, 'origin' => 'zz-origen', 'key' => 'security-environment'],
            ['group' => SystemHealth::GROUP_FOLDERS, 'label' => 'zz-dato-en-aviso', 'value' => "zz|uno\ndos", 'state' => SystemHealth::STATE_MISSING, 'origin' => 'zz|origen', 'key' => 'folder-keys'],
        ];
        $full = SystemHealth::markdown($synthetic, $groupTitles);
        $check(str_starts_with($full, '# ' . __($langGroup, 'Salud de la instalación') . "\n") && str_contains($full, AppVersion::version()) && preg_match('/\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}/', $full) === 1, 'm1 la cabecera: el título, la fecha y hora de generación y la versión del framework');
        $root = rtrim(str_replace('\\', '/', basepath('')), '/');
        //Con un dominio de verdad en la petición: la cabecera no lo toma (las filas sí pueden: «Dominios permitidos»).
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = 'zz-dominio-de-prueba.test';
        try {
            $withHost = SystemHealth::markdown($synthetic, $groupTitles);
        } finally {
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
        $check(!str_contains($withHost, $root) && !str_contains($withHost, dirname($root)) && !str_contains($withHost, 'zz-dominio-de-prueba.test'), 'm2 la cabecera y el armazón, sin rutas absolutas del servidor ni el dominio de la petición');
        $check(str_contains($full, '| zz-dato-en-aviso | ' . __($langGroup, 'Falta') . ' | zz\\|uno<br>dos | zz\\|origen |') && !str_contains($full, "uno\ndos"), 'm3 DISCRIMINANTE: las celdas escapan | y los saltos de línea', (string) json_encode(array_values(array_filter(explode("\n", $full), fn($l) => str_contains($l, 'zz-dato-en-aviso'))), JSON_UNESCAPED_UNICODE));
        $raro = ['group' => SystemHealth::GROUP_PHP, 'label' => 'zz_*nombre*', 'value' => 'a\\b `c` *d* _e_ <f> [g] |h', 'state' => SystemHealth::STATE_OK, 'origin' => 'zz', 'key' => 'php-ini'];
        $rare = SystemHealth::markdown([$raro], $groupTitles, SystemHealth::GROUP_PHP);
        $check(str_contains($rare, '| zz\\_\\*nombre\\* | ') && str_contains($rare, '| a\\\\b \\`c\\` \\*d\\* \\_e\\_ \\<f\\> \\[g\\] \\|h |') && str_contains($rare, '- **zz\\_\\*nombre\\***: '), 'm3b DISCRIMINANTE: celdas y nombres escapan \\ ` * _ < > [ ] y |', (string) json_encode(array_values(array_filter(explode("\n", $rare), fn($l) => str_contains($l, 'zz'))), JSON_UNESCAPED_UNICODE));
        $lines = explode("\n", $full);
        $check(($lines[2] ?? '') === __($langGroup, 'Documento interno: describe esta instalación (versiones, configuración y estado). No lo publiques.'), 'm12 tras el título, la línea de documento interno', (string) ($lines[2] ?? ''));
        $check(SystemHealth::keysLabel('C:\\inst\\secure-keys', 'C:/inst') === '../secure-keys' && SystemHealth::keysLabel('C:\\inst/private\\secure-keys', 'C:/inst') === '../private/secure-keys', 'k1 DISCRIMINANTE: con \\ (Windows), la carpeta de claves bajo la raíz sale relativa', SystemHealth::keysLabel('C:\\inst\\secure-keys', 'C:/inst'));
        $outside = [SystemHealth::keysLabel('D:\\otro\\private\\secure-keys', 'C:/inst'), SystemHealth::keysLabel('/srv/fuera/secure-keys', '/var/www/inst'), SystemHealth::keysLabel(null, '/var/www/inst')];
        $check($outside === ['private/secure-keys', 'secure-keys', 'secure-keys'], 'k2 fuera de la raíz, o sin carpeta, solo su nombre: nunca una ruta absoluta', (string) json_encode($outside));
        $attentionTitle = '## ' . __($langGroup, 'Lo que pide atención');
        $attentionPart = (string) strstr((string) strstr($full, $attentionTitle), "\n## ", true);
        $check(str_contains($full, $attentionTitle) && strpos($full, $attentionTitle) < strpos($full, '## ' . $groupTitles[SystemHealth::GROUP_FRAMEWORK]) && str_contains($attentionPart, 'zz-dato-en-aviso') && !str_contains($attentionPart, 'zz-dato-bien'), 'm4 primero lo que pide atención (solo los avisos y las faltas), después los grupos');
        $order = array_map(fn(string $title): int|false => strpos($full, "\n## {$title}\n"), array_values($groupTitles));
        $check(!in_array(false, $order, true) && $order === array_values(array_unique($order)) && $order == array_values(array_filter($order)) && $order === (function (array $o): array { sort($o); return $o; })($order), 'm5 un bloque por grupo, en el orden de groupTitles()');
        $explanations = SystemHealth::explanations();
        $keysExplanation = $explanations['folder-keys'];
        $check(str_contains($full, '- **zz-dato-en-aviso**: ' . $keysExplanation['measures'] . ' ' . __($langGroup, 'Si está mal:') . ' ' . $keysExplanation['ifWrong'] . ' ' . __($langGroup, 'Falsa alarma:') . ' ' . $keysExplanation['falseAlarm']), 'm6 bajo la tabla, cada fila con qué mide, qué hacer y cuándo es una falsa alarma');
        $onlyAttention = SystemHealth::markdown($synthetic, $groupTitles, SystemHealth::GROUP_ATTENTION);
        $check(str_contains($onlyAttention, $attentionTitle) && !str_contains($onlyAttention, '## ' . $groupTitles[SystemHealth::GROUP_FOLDERS]), 'm7 con group=attention, solo el bloque de atención');
        $onlyFolders = SystemHealth::markdown($synthetic, $groupTitles, SystemHealth::GROUP_FOLDERS);
        $check(str_contains($onlyFolders, '## ' . $groupTitles[SystemHealth::GROUP_FOLDERS]) && !str_contains($onlyFolders, $attentionTitle) && !str_contains($onlyFolders, '## ' . $groupTitles[SystemHealth::GROUP_SECURITY]), 'm8 con un grupo, solo ese grupo');
        $rejected = false;
        try {
            SystemHealth::markdown($synthetic, $groupTitles, 'zz-no-existe');
        } catch (\InvalidArgumentException) {
            $rejected = true;
        }
        $check($rejected, 'm9 DISCRIMINANTE: un grupo que no existe lanza InvalidArgumentException');
        $realRows = SystemHealth::rows();
        $withoutExplanation = array_values(array_filter($realRows, fn(array $row): bool => !isset($explanations[$row['key']])));
        $check($withoutExplanation === [] && array_filter($explanations, fn(array $e): bool => trim($e['measures']) === '' || trim($e['ifWrong']) === '' || trim($e['falseAlarm']) === '') === [], 'm10 toda fila real tiene su explicación, con los tres textos', (string) json_encode(array_column($withoutExplanation, 'label'), JSON_UNESCAPED_UNICODE));
        $realMarkdown = SystemHealth::markdown($realRows, $groupTitles);
        //El valor sale escapado para Markdown: se busca crudo y escapado (como el HTML, crudo y con htmlspecialchars).
        $leakedMarkdown = array_filter($present, fn(string $name): bool => str_contains($realMarkdown, (string) $secrets[$name]) || str_contains($realMarkdown, SystemHealth::escapeMarkdown((string) $secrets[$name])));
        $check($leakedMarkdown === [] && !str_contains($realMarkdown, $root), 'm11 el Markdown real no lleva ningún secreto buscado ni la ruta de la instalación', 'aparecen: ' . implode(', ', $leakedMarkdown));

        $info = get_route_info('system-status-health-export');
        $healthInfo = get_route_info('system-status-health');
        $allowedOthersExport = array_filter($others, fn($type): bool => Roles::hasPermissions('system-status-health-export', (int) $type));
        $check(is_array($info) && ($info['require_login'] ?? false) === true && ($info['method'] ?? null) === ($healthInfo['method'] ?? 'zz') && Roles::hasPermissions('system-status-health-export', UsersModel::TYPE_USER_ROOT) && $allowedOthersExport === [], 'e1 la ruta pide sesión y, como la de la vista, solo root (sin sesión no entra: index.php la corta)', 'otros con permiso: ' . implode(', ', $allowedOthersExport));
        $export = function (?string $group): \Psr\Http\Message\ResponseInterface {
            $query = $group === null ? '' : '?' . http_build_query(['group' => $group]);
            $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz' . $query), new Headers(), [], [], (new StreamFactory())->createStream(''));
            return (new SystemStatusController())->healthExport($request, new ResponseRoute());
        };
        $ok = $export(null);
        $body = (string) $ok->getBody();
        $check($ok->getStatusCode() === 200 && $ok->getHeaderLine('Content-Type') === 'text/markdown; charset=utf-8' && $ok->getHeaderLine('Content-Disposition') === 'attachment; filename="salud-completa-' . date('Y-m-d') . '.md"' && str_starts_with($body, '# '), 'e2 la vista entera: 200, text/markdown y su Content-Disposition', $ok->getStatusCode() . ' · ' . $ok->getHeaderLine('Content-Type') . ' · ' . $ok->getHeaderLine('Content-Disposition'));
        $okGroup = $export(SystemHealth::GROUP_PHP);
        $check($okGroup->getStatusCode() === 200 && $okGroup->getHeaderLine('Content-Disposition') === 'attachment; filename="salud-' . SystemHealth::GROUP_PHP . '-' . date('Y-m-d') . '.md"' && !str_contains((string) $okGroup->getBody(), $attentionTitle), 'e3 un grupo: 200 y su nombre de archivo');
        $bad = $export('zz-no-existe');
        $check($bad->getStatusCode() === 400 && $bad->getHeaderLine('Content-Disposition') === '' && str_contains((string) $bad->getBody(), 'zz-no-existe'), 'e4 DISCRIMINANTE: un grupo que no existe, 400 con su mensaje y sin archivo', $bad->getStatusCode() . ' · ' . (string) $bad->getBody());
        $odd = $export('<zz>"raro"');
        $check($odd->getStatusCode() === 400 && !str_contains((string) $odd->getBody(), 'zz') && (string) $odd->getBody() === __($langGroup, 'Ese bloque no existe en la salud de la instalación.'), 'e4b DISCRIMINANTE: un grupo sin la forma de un bloque, 400 sin repetirlo', (string) $odd->getBody());
        if (LogsRoutes::ENABLE) {
            $logs = LogsMapper::model();
            $logs->resetAll();
            $logs->select(['id', 'textMessage'])->where(new WhereSegment([new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $maxLogBefore)]))->execute();
            $logged = array_filter((array) $logs->result(), fn($l) => (((array) $l)['textMessage'] ?? '') === $exportedText);
            $check(count($logged) === 2, 'l1 cada exportación (e2, e3) queda en el registro de acciones, y las de 400 no', (string) count($logged));
        }
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        $logsDatabase = LogsMapper::model()->getDatabase();
        if ($logsDatabase !== null) {
            $logsDatabase->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE `id` > ? AND `textMessage` = ?')->execute([$maxLogBefore, $exportedText]);
            $remaining = $logsDatabase->prepare('SELECT COUNT(*) FROM `' . LogsMapper::TABLE . '` WHERE `id` > ? AND `textMessage` = ?');
            $remaining->execute([$maxLogBefore, $exportedText]);
            $check((int) $remaining->fetchColumn() === 0, 'z1 no queda ninguna línea de la prueba en el registro de acciones');
        }
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La Salud de la instalación: cada fila con su origen, ninguna llave ni contraseña en el HTML y solo root (P179); la carpeta de claves de verdad y las vistas que cuentan como tablas (H511).')->setEffects([CliActions::EFFECT_DATABASE])->register();
