<?php

//El explorador de archivos del editor enriquecido: sus URL las da el servidor, no las arma el navegador (armadas a mano
//perdían el prefijo admin/ y daban 404 desde v6.3.0). Por HTTP, como root; siembra un usuario zz- y lo retira.
//@areas src/app/classes/PiecesPHP/FileManager/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/rich-editor-file-manager-urls', function ($args) {

    echoTerminal("\e[33m[TEST:RichEditorFileManagerUrls] El explorador del editor enriquecido abre: el servidor da sus URL\e[39m");
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

    //─── a · El adaptador no arma las URL ─────────────────────────────────────────────────────────────
    echoTerminal('[a] El adaptador lee las URL del servidor y no las arma');
    $rutaJs = 'statics/core/own-plugins/RichEditorAdapterComponent.js';
    $leido = @file_get_contents(basepath($rutaJs));
    $js = is_string($leido) ? $leido : '';
    if ($check($js !== '', 'a1 se puede leer ' . $rutaJs)) {
        $check(!str_contains($js, 'filemanager/rich-editor'), 'a2 no hay una ruta del explorador escrita en el adaptador');
        $check(str_contains($js, 'richEditorFileManagerURLs'), 'a3 y lee la configuración que manda el servidor');
    }
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-explorador-' . bin2hex(random_bytes(3));
    $usuarios = [];

    try {
        $root = new UsersModel();
        $root->username = "{$prefijo}-root";
        $root->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $root->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $root->firstname = 'Zz';
        $root->secondname = '';
        $root->firstLastname = 'Explorador';
        $root->secondLastname = '';
        $root->type = UsersModel::TYPE_USER_ROOT;
        $root->status = UsersModel::STATUS_USER_ACTIVE;
        $root->failedAttempts = 0;
        $root->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $root->createdAt = new \DateTime();
        $root->modifiedAt = $root->createdAt;
        $root->save();
        $usuarios[] = (int) $root->id;
        $jwt = UserSessions::issueToken((int) $root->id);

        //─── b · El servidor las manda y responden ────────────────────────────────────────────────────
        echoTerminal('[b] Una pantalla del panel lleva las dos URL, y las dos responden');
        $panel = $pedir($camino(AdminPanelController::routeName('', [], true)), $jwt);
        $frente = preg_match('/<meta\s+name="front-configurations"\s+value="([^"]*)"/', $panel['body'], $m) === 1
            ? json_decode((string) base64_decode($m[1]), true) : null;
        $check($panel['status'] === 200 && is_array($frente), 'b1 CANARIO: el panel responde y trae su configuración de frente', "HTTP {$panel['status']}");
        $urls = is_array($frente) && is_array($frente['richEditorFileManagerURLs'] ?? null) ? $frente['richEditorFileManagerURLs'] : [];
        foreach (['explorer' => 'filemanager-rich-editor', 'configuration' => 'filemanager-configuration-rich-editor'] as $clave => $sufijo) {
            $url = is_string($urls[$clave] ?? null) ? $urls[$clave] : '';
            $check($url !== '', "b2 richEditorFileManagerURLs.{$clave} viene del servidor", (string) json_encode($urls));
            if ($url !== '') {
                $respuesta = $pedir($camino($url), $jwt);
                $check($respuesta['status'] === 200, "b3 y {$clave} responde 200 (la ruta {$sufijo})", "HTTP {$respuesta['status']} · " . $camino($url));
            }
        }
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            $database->prepare('DELETE FROM `' . UserProfileMapper::TABLE . '` WHERE belongsTo = ?')->execute([$id]);
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda el usuario de la prueba', "usuarios {$quedan}");
    }

    return $balance();

})->setDescription('El explorador de archivos del editor enriquecido: el servidor da sus dos URL (con su prefijo admin/) y responden; el adaptador no las arma. Por HTTP, como root.')->setEffects([CliActions::EFFECT_DATABASE])->register();
