<?php

//P38: ningún listado manda al navegador su SQL ni las filas crudas, y recordsTotal cuenta con los filtros fijos
//del listado (su alcance) y sin la búsqueda. Solo LEE: los usuarios fijados son los zz de la base local.

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/datatables-scope', function ($args) {

    echoTerminal("\e[33m[TEST:DataTablesScope] Los listados no filtran SQL ni filas, y el total respeta el alcance\e[39m");
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

    //Root y un administrador de organización que ya existen en la base local (zz-prueba-orgadmin-a, org 1).
    $rootID = 1;
    $orgAdminQuery = (new \PiecesPHP\UserSystem\ORM\UsersModel())->getModel()->select(['id', 'organization'])
        ->where("username = 'zz-prueba-orgadmin-a' AND type = " . \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ADMIN_ORG);
    $orgAdminQuery->execute();
    $orgAdmin = $orgAdminQuery->result();
    $orgAdminID = is_array($orgAdmin) && count($orgAdmin) > 0 ? (int) $orgAdmin[0]->id : null;

    $request = function (array $query): RequestRoute {
        $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/datatables'), new Headers(['X-Requested-With' => 'XMLHttpRequest']), [], [], (new StreamFactory())->createStream(''));
        $withQuery = $request->withQueryParams(array_merge(['draw' => 1, 'start' => 0, 'length' => 10], $query));
        return $withQuery instanceof RequestRoute ? $withQuery : $request;
    };
    $search = fn (string $term): array => [
        'columns' => array_fill(0, 20, ['searchable' => 'true']),
        'search' => ['value' => $term, 'regex' => 'false'],
    ];
    $as = function (int $userID): void {
        set_config('current_user', (object) ['id' => $userID]);
        getLoggedFrameworkUser(true);
    };
    $fromController = fn (string $class, string $method) => function (RequestRoute $request) use ($class, $method): array {
        $response = (new $class())->$method($request, new ResponseRoute());
        $values = json_decode((string) $response->getBody(), true);
        return is_array($values) ? $values : [];
    };
    $fromModel = fn (string $method) => function (RequestRoute $request) use ($method): array {
        $result = \PiecesPHP\UserSystem\ORM\LoginAttemptsModel::$method($request);
        return $result instanceof ResultOperations ? json_decode((string) json_encode($result->getValues()), true) : [];
    };

    //Los 22 usos (#320). `scoped`: el total depende de quién mira. `cards`: rawData es HTML de tarjeta.
    $listings = [
        'login-attempts · intentos' => ['call' => $fromModel('getAttempts'), 'scoped' => true],
        'login-attempts · conectados' => ['call' => $fromModel('getLoggedUsers'), 'scoped' => true],
        'login-attempts · no conectados' => ['call' => $fromModel('getNotLoggedUsers'), 'scoped' => true],
        'banner' => ['call' => $fromController(\PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::class, 'dataTables')],
        'newsletter' => ['call' => $fromController(\Newsletter\Controllers\NewsletterController::class, 'dataTables')],
        'news · categorías' => ['call' => $fromController(\News\Controllers\NewsCategoryController::class, 'dataTables')],
        'news' => ['call' => $fromController(\News\Controllers\NewsController::class, 'dataTables')],
        'organizaciones' => ['call' => $fromController(\Organizations\Controllers\OrganizationsController::class, 'dataTables')],
        'aprobaciones' => ['call' => $fromController(\SystemApprovals\Controllers\SystemApprovalsController::class, 'dataTables'), 'scoped' => true, 'query' => ['elapsedDays' => '0']],
        'ubicaciones · estados' => ['call' => $fromController(\PiecesPHP\App\Locations\Controllers\State::class, 'statesDataTables')],
        'ubicaciones · puntos' => ['call' => $fromController(\PiecesPHP\App\Locations\Controllers\Point::class, 'pointsDataTables')],
        'ubicaciones · ciudades' => ['call' => $fromController(\PiecesPHP\App\Locations\Controllers\City::class, 'citiesDataTables')],
        'ubicaciones · países' => ['call' => $fromController(\PiecesPHP\App\Locations\Controllers\Country::class, 'countriesDataTables')],
        'documentos' => ['call' => $fromController(\Documents\Controllers\DocumentsController::class, 'dataTables')],
        'documentos · explorador' => ['call' => $fromController(\Documents\Controllers\DocumentsController::class, 'dataTablesExplorer'), 'cards' => true],
        'perfiles' => ['call' => $fromController(\MySpace\Controllers\AllProfilesController::class, 'dataTables'), 'scoped' => true],
        'formularios · tipos de documento' => ['call' => $fromController(\Forms\DocumentTypes\Controllers\DocumentTypesController::class, 'dataTables')],
        'formularios · categorías' => ['call' => $fromController(\Forms\Categories\Controllers\CategoriesController::class, 'dataTables')],
        'publicaciones · categorías' => ['call' => $fromController(\Publications\Controllers\PublicationsCategoryController::class, 'dataTables'), 'cards' => true],
        'publicaciones' => ['call' => $fromController(\Publications\Controllers\PublicationsController::class, 'dataTables'), 'scoped' => true],
        'registro de acciones' => ['call' => $fromController(\EventsLog\Controllers\LogsController::class, 'dataTables')],
        'usuarios' => ['call' => $fromController(\PiecesPHP\UserSystem\Controllers\UsersController::class, 'dataTablesRequestUsers'), 'scoped' => true, 'cards' => true],
    ];

    $previousUser = get_config('current_user');
    $previousStored = get_config('pcsphp_current_user_stored');
    $totals = [];

    try {
        //─── a · Ni SQL ni filas crudas en la respuesta ──────────────────────────────────────────────────
        echoTerminal('[a] Ni SQL ni filas crudas en la respuesta (' . count($listings) . ' usos, como root)');
        $as($rootID);
        foreach ($listings as $name => $listing) {
            try {
                $values = ($listing['call'])($request($listing['query'] ?? []));
                $sqlKeys = array_values(array_filter(array_keys($values), fn ($k) => str_starts_with((string) $k, 'SQL_')));
                $rawOK = !array_key_exists('rawData', $values)
                    || (($listing['cards'] ?? false) && is_array($values['rawData']) && count(array_filter($values['rawData'], fn ($e) => !is_string($e))) === 0);
                $check(
                    array_key_exists('recordsTotal', $values) && $sqlKeys === [] && $rawOK,
                    "a · {$name}: sin SQL_* ni filas crudas",
                    'claves: ' . implode(',', array_keys($values))
                );
                $totals[$name]['root'] = (int) ($values['recordsTotal'] ?? -1);
            } catch (\Throwable $e) {
                $check(false, "a · {$name}: sin SQL_* ni filas crudas", get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
            }
        }
        echoTerminal(' ');

        //─── b · El total del administrador de organización ─────────────────────────────────────────────
        echoTerminal('[b] Con alcance: el total es lo que el administrador de organización puede ver');
        $check($orgAdminID !== null, 'b0 existe el administrador de organización zz-prueba-orgadmin-a');
        if ($orgAdminID !== null) {
            $as($orgAdminID);
            foreach ($listings as $name => $listing) {
                if (!($listing['scoped'] ?? false)) {
                    continue;
                }
                try {
                    $values = ($listing['call'])($request($listing['query'] ?? []));
                    $total = (int) ($values['recordsTotal'] ?? -1);
                    $visible = (int) ($values['recordsFiltered'] ?? -2);
                    $root = $totals[$name]['root'] ?? -1;
                    $totals[$name]['orgAdmin'] = $total;
                    $check($total === $visible, "b · {$name}: recordsTotal == lo que ve sin búsqueda", "total {$total}, visible {$visible}");
                    $check($total <= $root, "b · {$name}: no más que root", "admin {$total}, root {$root}");
                } catch (\Throwable $e) {
                    $check(false, "b · {$name}: el listado responde", get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
                }
            }
            foreach ($totals as $name => $t) {
                if (isset($t['orgAdmin'])) {
                    echoTerminal("   INFO: {$name}: recordsTotal root " . ($t['root'] ?? -1) . " · administrador de organización {$t['orgAdmin']}");
                }
            }
            //DISCRIMINANTE: si en ningún listado el administrador ve menos que root, (b) no probaría nada.
            $smaller = array_filter($totals, fn ($t) => isset($t['orgAdmin']) && $t['orgAdmin'] < ($t['root'] ?? -1));
            $check(count($smaller) > 0, 'b · DISCRIMINANTE: en algún listado el administrador ve menos que root', (string) json_encode($totals, \JSON_UNESCAPED_UNICODE));
        }
        echoTerminal(' ');

        //─── c · La búsqueda no mueve el total ──────────────────────────────────────────────────────────
        echoTerminal('[c] Con búsqueda: recordsFiltered ≤ recordsTotal y el total no cambia');
        $as($rootID);
        foreach ($listings as $name => $listing) {
            try {
                $plain = ($listing['call'])($request($listing['query'] ?? []));
                $searched = ($listing['call'])($request(array_merge($listing['query'] ?? [], $search('zz-sin-coincidencias-' . 'q'))));
                $check(
                    ($searched['recordsTotal'] ?? null) === ($plain['recordsTotal'] ?? null)
                        && (int) ($searched['recordsFiltered'] ?? PHP_INT_MAX) <= (int) ($searched['recordsTotal'] ?? -1),
                    "c · {$name}: el total no cambia al buscar",
                    'sin búsqueda ' . var_export($plain['recordsTotal'] ?? null, true) . ' · con búsqueda ' . var_export($searched['recordsTotal'] ?? null, true)
                        . ' / filtradas ' . var_export($searched['recordsFiltered'] ?? null, true)
                );
            } catch (\Throwable $e) {
                $check(false, "c · {$name}: el total no cambia al buscar", get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
            }
        }
        echoTerminal(' ');

        //─── d · Tarjetas: HTML sí, filas no ────────────────────────────────────────────────────────────
        echoTerminal('[d] Tarjetas: siguen devolviendo su HTML');
        foreach ($listings as $name => $listing) {
            if (!($listing['cards'] ?? false)) {
                continue;
            }
            $values = ($listing['call'])($request($listing['query'] ?? []));
            $cards = $values['rawData'] ?? null;
            $check(
                is_array($cards) && count($cards) === count($values['data'] ?? []) && count(array_filter($cards, fn ($e) => is_string($e) && str_contains($e, '<'))) === count($cards),
                "d · {$name}: una tarjeta HTML por fila",
                'tarjetas ' . (is_array($cards) ? count($cards) : 'ninguna') . ', filas ' . count($values['data'] ?? [])
            );
        }
        echoTerminal(' ');

        //─── e · debug_sql ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] debug_sql: al log solo en local, nunca a la respuesta');
        $log = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . DIRECTORY_SEPARATOR . 'datatables-sql.log';
        $existed = is_file($log);
        clearstatcache();
        $sizeBefore = $existed ? (int) filesize($log) : 0;
        $options = [
            'request' => $request([]),
            'mapper' => new \PiecesPHP\App\Locations\Mappers\CountryMapper(),
            'columns_order' => ['name'],
            'debug_sql' => true,
            'on_set_data' => fn ($e) => [$e->id],
        ];
        $terminalData = $_SERVER['PCSPHP_TERMINAL_DATA'];
        try {
            $_SERVER['PCSPHP_TERMINAL_DATA']['local'] = false;
            $notLocal = DataTablesHelper::process($options);
        } finally {
            $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;
        }
        clearstatcache();
        $check(!is_local() || ($existed ? (int) filesize($log) : (is_file($log) ? (int) filesize($log) : 0)) === $sizeBefore, 'e1 sin is_local(): no escribe');
        $check(is_local(), 'e2 el proceso de pruebas es local (si no, e3 no prueba nada)');
        $local = DataTablesHelper::process($options);
        clearstatcache();
        $written = is_file($log) ? (string) file_get_contents($log, false, null, $sizeBefore) : '';
        $check(str_contains($written, '"main"') && str_contains($written, '"totalCount"'), 'e3 con is_local(): el SQL va al log', mb_substr($written, 0, 160));
        $keys = array_keys($local->getValues()) + array_keys($notLocal->getValues());
        $check(count(array_filter($keys, fn ($k) => str_starts_with((string) $k, 'SQL_') || $k === 'rawData')) === 0, 'e4 y la respuesta sigue sin SQL ni filas');
        $check(count(DataTablesHelper::rawRows($local)) > 0, 'e5 las filas crudas siguen en el servidor: rawRows()');
        //El log es depuración compartida: se deja como estaba.
        if ($existed) {
            $handle = fopen($log, 'r+');
            if ($handle !== false) {
                //RETORNO-IGNORADO: limpieza del log de depuración; e3 ya midió lo que importaba.
                ftruncate($handle, $sizeBefore);
                fclose($handle);
            }
        } elseif (is_file($log)) {
            //RETORNO-IGNORADO: limpieza del log de depuración que creó esta prueba.
            @unlink($log);
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('current_user', $previousUser);
        set_config('pcsphp_current_user_stored', $previousStored);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P38: los listados no envían su SQL ni filas crudas, y recordsTotal cuenta con su alcance.')->setEffects([CliActions::EFFECT_FILES])->register();
