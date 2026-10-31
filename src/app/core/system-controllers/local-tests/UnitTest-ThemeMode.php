<?php

//El modo de color del panel por usuario (H99): admin-theme-mode guarda light, dark o auto, rechaza lo demás con 400 y
//header.php lo pone en <html> (sin elegir, auto). Por HTTP, con un usuario zz-agente- propio que se retira al final.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/theme-mode', function ($args) {

    echoTerminal("\e[33m[TEST:ThemeMode] El modo de color del panel: guardar, rechazar, sin sesión y en <html>\e[39m");
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

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $relativa = function (string $url) use ($prefijoBase): string {
        $ruta = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    };
    $ruta = $relativa(AdminPanelController::routeName('theme-mode', [], true));
    $inicio = $relativa(AdminPanelController::routeName('', [], true));
    $peticion = function (string $camino, ?string $jwt, ?array $campos) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($camino, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array_merge(['X-Requested-With: XMLHttpRequest'], $jwt !== null ? [SessionToken::tokenName() . ": {$jwt}"] : []),
        ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $campos] : []));
        $cuerpo = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
    };
    //Solo la etiqueta de apertura de <html>: el atributo no puede venir de otro sitio.
    $html = function (string $cuerpo): string {
        return preg_match('/<html\b[^>]*>/i', $cuerpo, $m) === 1 ? $m[0] : '';
    };

    $prefijo = 'zz-agente-tema-' . bin2hex(random_bytes(3));
    $ids = [];

    try {
        $u = new UsersModel();
        $u->username = $prefijo;
        $u->email = "{$prefijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Tema';
        $u->secondLastname = '';
        //Root: con un general de la organización global, el inicio responde 302 (medido) y h1-h2 no ven el <html>.
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $id = (int) $u->id;
        $ids[] = $id;
        $jwt = UserSessions::issueToken($id);

        //──── [m] Guardar y leer ─────────────────────────────────────────────────────────────
        echoTerminal('[m] Guardar y leer');
        $check(AdminPanelController::themeModeOf($id) === AdminPanelController::THEME_MODE_AUTO && UsersModel::metaValue($id, AdminPanelController::THEME_MODE_META) === null, 'm0 sin guardar, el del sistema (auto)');
        $r = $peticion($inicio, $jwt, null);
        $check($r['status'] === 200 && str_contains($html($r['body']), 'data-theme="auto"'), 'h0 sin guardar, el inicio llega con data-theme="auto"', "HTTP {$r['status']}, " . $html($r['body']));
        foreach (['m1' => 'light', 'm2' => 'dark', 'm3' => 'auto'] as $caso => $valor) {
            $r = $peticion($ruta, $jwt, ['mode' => $valor]);
            $datos = json_decode($r['body'], true);
            $check($r['status'] === 200 && ($datos['success'] ?? null) === true && AdminPanelController::themeModeOf($id) === $valor, "{$caso} guarda «{$valor}» y lo lee", "HTTP {$r['status']}, " . AdminPanelController::themeModeOf($id));
        }
        echoTerminal(' ');

        //──── [i] Lo que no vale ─────────────────────────────────────────────────────────────
        echoTerminal('[i] Lo que no vale');
        $r = $peticion($ruta, $jwt, ['mode' => 'zz-invalido']);
        $check($r['status'] === 400 && AdminPanelController::themeModeOf($id) === 'auto' && UsersModel::metaValue($id, AdminPanelController::THEME_MODE_META) === 'auto', 'i1 un valor inválido da 400 y no cambia lo guardado', "HTTP {$r['status']}, " . var_export(UsersModel::metaValue($id, AdminPanelController::THEME_MODE_META), true));
        $r = $peticion($ruta, $jwt, []);
        $check($r['status'] === 400 && AdminPanelController::themeModeOf($id) === 'auto', 'i2 sin valor, 400 y sin cambio', "HTTP {$r['status']}");
        $r = $peticion($ruta, null, ['mode' => 'dark']);
        $check($r['status'] !== 200 && AdminPanelController::themeModeOf($id) === 'auto', 'i3 sin sesión no entra ni cambia nada', "HTTP {$r['status']}");
        echoTerminal(' ');

        //──── [h] En <html>, desde el servidor ───────────────────────────────────────────────
        echoTerminal('[h] En <html>');
        $peticion($ruta, $jwt, ['mode' => 'dark']);
        $r = $peticion($inicio, $jwt, null);
        $check($r['status'] === 200 && str_contains($html($r['body']), 'data-theme="dark"'), 'h1 oscuro: el inicio llega con data-theme="dark"', "HTTP {$r['status']}, " . $html($r['body']));
        $peticion($ruta, $jwt, ['mode' => 'light']);
        $r = $peticion($inicio, $jwt, null);
        $check($r['status'] === 200 && str_contains($html($r['body']), 'data-theme="light"') && UsersModel::metaValue($id, AdminPanelController::THEME_MODE_META) === 'light', 'h2 claro elegido: se guarda light y llega con data-theme="light"', "HTTP {$r['status']}, " . $html($r['body']));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda el usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('El modo de color del panel (H99): guarda claro, oscuro y el del sistema por la ruta admin-theme-mode y los lee, un valor inválido o ninguno da 400 sin cambiar lo guardado, sin sesión no entra, y el inicio llega con data-theme en <html> (claro, sin atributo). Por HTTP; crea un usuario zz-agente- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
