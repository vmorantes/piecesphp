<?php

//H373: ninguna página del panel se titula «Dashboard» ni lleva solo el nombre de la plataforma; y la pestaña de
//preferencias, en inglés, sin romper los enlaces viejos. Por HTTP, con un root de prueba que se borra al terminar.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/panel-page-titles', function ($args) {

    echoTerminal("\e[33m[TEST:panel-page-titles] Los títulos de las páginas del panel\e[39m");
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

    //Las páginas que pueden llevar el título por omisión, con su motivo. Hoy, ninguna.
    $declared = [];

    //`base_url` en el terminal es `http://localhost`: no sirve. Como users-screens.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $project = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$project}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$project}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if ($base === '' || $database === null) {
        $check(false, 'hay una base HTTP para pedir y conexión a la base', $base);
        return ['success' => false, 'message' => 'sin base'];
    }
    $basePath = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $header = SessionToken::headerName();
    $request = function (string $path, string $jwt) use ($base, $header): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$header}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'type' => (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE), 'body' => is_string($body) ? $body : ''];
    };
    $titleOf = fn (string $html): ?string => preg_match('/<title>([\s\S]*?)<\/title>/i', $html, $m) === 1 ? trim(html_entity_decode($m[1], \ENT_QUOTES | \ENT_HTML5)) : null;

    $prefix = 'zz-prueba-titulos-' . bin2hex(random_bytes(3));
    $root = new UsersModel();
    $root->username = $prefix;
    $root->email = "{$prefix}@example.invalid";
    $root->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
    $root->firstname = 'Zz';
    $root->secondname = '';
    $root->firstLastname = 'Titulos';
    $root->secondLastname = '';
    $root->type = UsersModel::TYPE_USER_ROOT;
    $root->status = UsersModel::STATUS_USER_ACTIVE;
    $root->failedAttempts = 0;
    $root->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
    $root->createdAt = new \DateTime();
    $root->modifiedAt = $root->createdAt;

    try {
        $root->save();
        $jwt = UserSessions::issueToken((int) $root->id);
        $platform = (string) get_config('title_app');

        //──── 1. Las páginas GET del panel, sin parámetros ───────────────────────────────────
        echoTerminal('[1] Ninguna página del panel se titula «Dashboard» ni lleva solo el nombre de la plataforma');
        //Como los recorredores: sin la lista de rutas prohibidas no se pide nada, porque se pedirían también las que escriben.
        if (!is_file("{$project}/files/dev/forbidden-routes.json")) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m sin files/dev/forbidden-routes.json (no viaja a un clon): no se recorre.");
            return ['success' => true, 'message' => 'omitida: sin la lista de rutas prohibidas'];
        }
        require_once "{$project}/bin/tools/forbidden-routes.php";
        $forbidden = pcsphp_forbidden_patterns($project);
        $allowed = pcsphp_forbidden_allowed($project);
        $pages = 0;
        $wrong = [];
        $skipped = ['prohibidas' => 0, 'sin título HTML' => 0];
        foreach ((array) get_routes() as $name => $route) {
            if (!is_array($route) || !($route['require_login'] ?? false) || !str_contains(strtoupper((string) ($route['method'] ?? '')), 'GET') || (array) ($route['parameters'] ?? []) !== []) {
                continue;
            }
            $url = get_route((string) $name, [], true);
            $path = is_string($url) ? (string) parse_url($url, \PHP_URL_PATH) : '';
            if (!str_contains($path, '/admin/')) {
                continue;
            }
            if (pcsphp_forbidden_match((string) $name, $path, $forbidden, $allowed) !== null) {
                $skipped['prohibidas']++;
                continue;
            }
            $relative = $basePath !== '' && str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
            $response = $request($relative, $jwt);
            $title = $response['status'] === 200 && str_starts_with($response['type'], 'text/html') ? $titleOf($response['body']) : null;
            if ($title === null) {
                $skipped['sin título HTML']++;
                continue;
            }
            $pages++;
            if ((str_contains($title, 'Dashboard') || $title === $platform) && !array_key_exists((string) $name, $declared)) {
                $wrong[] = "{$name} «{$title}»";
            }
        }
        $check($pages > 50, "1a se recorrieron páginas del panel con <title> ({$pages})", "fuera: {$skipped['prohibidas']} prohibidas y {$skipped['sin título HTML']} sin título HTML (JSON, redirecciones, errores)");
        echoTerminal("      - fuera del recorrido: {$skipped['prohibidas']} prohibidas (files/dev/forbidden-routes.json) y {$skipped['sin título HTML']} sin título HTML");
        $check($wrong === [], '1b ninguna con «Dashboard» o solo el nombre de la plataforma', implode(' | ', $wrong));
        $check($skipped['prohibidas'] > 0, '1c la lista de rutas prohibidas se aplicó: hay páginas del panel que no se piden', "{$skipped['prohibidas']} fuera");
        echoTerminal(' ');

        //──── 2. La pestaña de preferencias ──────────────────────────────────────────────────
        echoTerminal('[2] La pestaña de preferencias, en inglés, y el enlace viejo');
        $profile = (string) parse_url(UsersController::routeName('form-profile', [], true), \PHP_URL_PATH);
        $profile = $basePath !== '' && str_starts_with($profile, $basePath) ? substr($profile, strlen($basePath)) : $profile;
        $check(UsersController::PROFILE_PREFERENCES_TAB === 'preferences', '2a la constante es «preferences»');
        foreach (['?tab=preferences' => '2b ?tab=preferences abre Preferencias', '?tab=preferencias' => '2c ?tab=preferencias (un enlace viejo) también'] as $query => $label) {
            $html = $request($profile . $query, $jwt)['body'];
            $check(preg_match('/<div class="ui tab active" data-tab="preferences"/', $html) === 1, $label);
        }
        $home = $request($profile, $jwt)['body'];
        $check(str_contains($home, '?' . UsersController::PROFILE_TAB_PARAM . '=preferences') && !str_contains($home, '?tab=preferencias'), '2d el menú de usuario enlaza a ?tab=preferences');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        if ($root->id !== null) {
            $id = (int) $root->id;
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            $references = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($references as [$table, $column]) {
                $database->prepare("DELETE FROM `{$table}` WHERE `{$column}` = ?")->execute([$id]);
            }
            $database->prepare('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H373: los títulos de las páginas del panel y la pestaña de preferencias en inglés.')->setEffects([CliActions::EFFECT_DATABASE])->register();
