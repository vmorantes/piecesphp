<?php

//El avatar sin foto del marco del panel lleva las iniciales (barra, menú de usuario y perfil), con foto la foto, y la barra
//del móvil lleva el logo. Directo y por HTTP; crea usuarios y una foto zz- y los retira.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/panel-avatar', function ($args) {

    echoTerminal("\e[33m[TEST:PanelAvatar] Las iniciales del avatar sin foto y el logo de la barra del móvil\e[39m");
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

    //─── i · Las iniciales, sin HTTP ────────────────────────────────────────────────────────────
    echoTerminal('[i] Las iniciales: nombre y primer apellido; con uno solo, una letra');
    $check(AdminPanelController::avatarInitials('Ana', 'Pérez') === 'AP', 'i1 nombre y apellido: dos letras', AdminPanelController::avatarInitials('Ana', 'Pérez'));
    $check(AdminPanelController::avatarInitials('ángela', 'ñúñez') === 'ÁÑ', 'i2 en mayúscula, también con tilde y eñe', AdminPanelController::avatarInitials('ángela', 'ñúñez'));
    $check(AdminPanelController::avatarInitials('Ana', '') === 'A' && AdminPanelController::avatarInitials('', 'Pérez') === 'P', 'i3 con uno solo, una letra');
    $check(AdminPanelController::avatarInitials('', '') === '' && AdminPanelController::avatarInitials('  ', ' ') === '', 'i4 sin ninguno, vacío');
    $check(AdminPanelController::avatarInitials('  ana ', ' pérez') === 'AP', 'i5 sin los espacios de alrededor');
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que backgrounds-delete.
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
    $ruta = (string) parse_url(AdminPanelController::routeName('', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $pedir = function (string $jwt) use ($base, $ruta): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}"],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //Cuántas veces sale el avatar de iniciales con ese texto: barra, cabecera del menú y perfil.
    $conIniciales = fn (string $html, string $iniciales): int => (int) preg_match_all('#<span class="ui (?:massive )?avatar label" aria-hidden="true">' . preg_quote($iniciales, '#') . '</span>#u', $html);

    $prefijo = 'zz-prueba-avatar-' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, string $nombre, string $apellido) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = $nombre;
        $u->secondname = '';
        $u->firstLastname = $apellido;
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return (int) $u->id;
    };
    $carpetasFoto = [];

    try {
        echoTerminal('[h] El marco del panel, por HTTP');
        $dos = $crear('dos', 'Zz', 'Bermúdez');
        $r = $pedir(UserSessions::issueToken($dos));
        $check($r['status'] === 200 && $conIniciales($r['body'], 'ZB') === 3, 'h1 DISCRIMINANTE: sin foto, «ZB» en la barra, el menú de usuario y el perfil', "HTTP {$r['status']} · " . $conIniciales($r['body'], 'ZB'));
        $check(!str_contains($r['body'], '<span class="ui avatar label" aria-hidden="true"><i class="user outline icon"></i>'), 'h2 y ya no el icono genérico');
        $logo = (string) get_config('logo');
        $check(preg_match('#<span class="mobile only logo" aria-hidden="true"><img[^>]* src="' . preg_quote(escape_html($logo), '#') . '[?"]#', $r['body']) === 1, 'h3 DISCRIMINANTE: la barra lleva el logo, solo en móvil', $logo);
        $uno = $crear('uno', 'Zz', '');
        $r = $pedir(UserSessions::issueToken($uno));
        $check($r['status'] === 200 && $conIniciales($r['body'], 'Z') === 3, 'h4 con solo el nombre, una letra', "HTTP {$r['status']} · " . $conIniciales($r['body'], 'Z'));
        $foto = $crear('foto', 'Zz', 'Fotografía');
        $carpeta = AvatarModel::getFolderUser($foto);
        if (!is_dir($carpeta)) {
            //RETORNO-IGNORADO: lo comprueba h5 (sin carpeta no hay foto).
            mkdir($carpeta, 0775, true);
            $carpetasFoto[] = $carpeta;
        }
        $lienzo = imagecreatetruecolor(8, 8);
        $check($lienzo !== false && imagejpeg($lienzo, AvatarModel::getUserAvatarName($foto)), 'banco: una foto de 8×8 para el tercer usuario');
        $r = $pedir(UserSessions::issueToken($foto));
        $check($r['status'] === 200 && str_contains($r['body'], '<img class="ui avatar image" src="') && $conIniciales($r['body'], 'ZF') === 0, 'h5 DISCRIMINANTE: con foto, la foto y ninguna inicial', "HTTP {$r['status']} · " . $conIniciales($r['body'], 'ZF'));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($ids as $id) {
            $archivo = AvatarModel::getUserAvatarName($id);
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink($archivo);
            }
        }
        foreach ($carpetasFoto as $carpeta) {
            if (is_dir($carpeta) && (glob("{$carpeta}/*") ?: []) === []) {
                //RETORNO-IGNORADO: lo comprueba z1.
                rmdir($carpeta);
            }
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        clearstatcache();
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && array_filter($carpetasFoto, 'is_dir') === [], 'z1 no quedan los usuarios ni la foto', "usuarios {$quedan}");
    }

    return $balance();

})->setDescription('El avatar sin foto del marco del panel lleva las iniciales (nombre y primer apellido; con uno solo, una letra) en la barra, el menú de usuario y el perfil; con foto, la foto; y la barra del móvil lleva el logo. Directo y por HTTP; crea y retira usuarios y una foto zz-.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
