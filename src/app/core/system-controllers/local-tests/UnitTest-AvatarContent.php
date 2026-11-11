<?php

//El avatar solo acepta JPEG, PNG o WebP por su CONTENIDO (AvatarModel::isAcceptedImage), en save() y en la ruta
//'push-avatars'. Directo y por HTTP; crea un usuario zz- y la carpeta de su avatar, y los retira.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\AvatarController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\UploadedFile;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/avatar-content', function ($args) {

    echoTerminal("\e[33m[TEST:AvatarContent] El avatar solo acepta JPEG, PNG o WebP por su contenido\e[39m");
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

    $prefijo = 'zz-prueba-avatar-' . bin2hex(random_bytes(3));
    $temporal = sys_get_temp_dir() . "/{$prefijo}";
    $database = (new BaseModel())->getDatabase();
    $usuario = null;
    $carpetaAvatar = null;
    $tipoInvalido = __(FileValidator::LANG_GROUP, 'Tipo de archivo inválido.');
    $modificada = __(AvatarController::LANG_GROUP, 'Imagen de perfil modificada');

    try {
        //RETORNO-IGNORADO: lo comprueba a0.
        mkdir($temporal, 0700, true);
        $lienzo = imagecreatetruecolor(4, 4);
        $escribir = function (string $nombre, string $contenido) use ($temporal): string {
            //RETORNO-IGNORADO: lo comprueba a0.
            file_put_contents("{$temporal}/{$nombre}", $contenido);
            return "{$temporal}/{$nombre}";
        };
        $imagen = function (callable $volcar) use ($lienzo): string {
            ob_start();
            if ($lienzo !== false) {
                //RETORNO-IGNORADO: lo comprueba a0.
                $volcar($lienzo);
            }
            return (string) ob_get_clean();
        };
        $png = $escribir('zz.png', $imagen(fn ($l) => imagepng($l)));
        $jpeg = $escribir('zz.jpg', $imagen(fn ($l) => imagejpeg($l)));
        $webp = $escribir('zz.webp', function_exists('imagewebp') ? $imagen(fn ($l) => imagewebp($l)) : '');
        $html = $escribir('x.jpg', '<html><body><script>/*zz*/</script></body></html>');
        $svg = $escribir('zz-svg.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>/*zz*/</script></svg>');
        $subido = fn (string $ruta, string $tipo): UploadedFile => new UploadedFile($ruta, basename($ruta), $tipo, (int) filesize($ruta), \UPLOAD_ERR_OK);

        echoTerminal('[a] isAcceptedImage(), directo');
        $check(filesize($png) > 0 && filesize($jpeg) > 0 && filesize($webp) > 0 && filesize($html) > 0 && filesize($svg) > 0, 'a0 CANARIO: un PNG, un JPEG y un WebP reales, un HTML y un SVG');
        $check(AvatarModel::isAcceptedImage($subido($html, 'image/jpeg')) === false, 'a1 un HTML llamado x.jpg y declarado image/jpeg se rechaza');
        $check(AvatarModel::isAcceptedImage($subido($svg, 'image/png')) === false, 'a2 un SVG llamado .png se rechaza');
        $check(AvatarModel::isAcceptedImage($subido($png, 'image/png')) === true, 'a3 un PNG se acepta');
        $check(AvatarModel::isAcceptedImage($subido($jpeg, 'image/jpeg')) === true, 'a4 un JPEG se acepta');
        $check(AvatarModel::isAcceptedImage($subido($webp, 'image/webp')) === true, 'a5 un WebP se acepta');
        $check(AvatarModel::isAcceptedImage(new UploadedFile($png, 'zz.png', 'image/png', 0, \UPLOAD_ERR_NO_FILE)) === false, 'a6 una subida con error se rechaza aunque el temporal sea una imagen');

        //──── Por HTTP ────────────────────────────────────────────────────────────────────────────
        $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        if ($base === '') {
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
        $base = rtrim($base, '/');
        $prefijoBase = trim((string) parse_url($base, \PHP_URL_PATH), '/');
        $relativa = function (string $url) use ($prefijoBase): string {
            $url = trim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
            return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? trim(substr($url, strlen($prefijoBase)), '/') : $url;
        };
        $cabeceraToken = SessionToken::headerName();
        $peticion = function (string $ruta, string $jwt, array $campos) use ($base, $cabeceraToken): array {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [
                CURLOPT_URL => "{$base}/{$ruta}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $campos,
                CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
            ]);
            $body = curl_exec($handle);
            $datos = is_string($body) ? json_decode($body, true) : null;
            return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'json' => is_array($datos) ? $datos : null];
        };
        echoTerminal('');
        echoTerminal("[b] Por HTTP, la ruta 'push-avatars'");
        $check($base !== '', 'b0 hay una base HTTP para pedir', $base);

        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Avatar';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuario = (int) $u->id;
        $jwt = UserSessions::issueToken($usuario);
        //La carpeta la crea el terminal y abierta: si la creara Apache, el terminal no podría vaciarla.
        $carpetaAvatar = AvatarModel::getFolderUser($usuario);
        //RETORNO-IGNORADO: lo comprueba b1.
        mkdir($carpetaAvatar, 0777, true);
        //RETORNO-IGNORADO: lo comprueba b1.
        chmod($carpetaAvatar, 0777);
        $enDisco = AvatarModel::getUserAvatarName($usuario);
        $shaEnDisco = function () use ($enDisco): string {
            clearstatcache();
            return is_file($enDisco) ? (string) sha1_file($enDisco) : '';
        };
        $check(is_dir($carpetaAvatar) && is_writable($carpetaAvatar), 'b1 CANARIO: la carpeta del avatar del usuario de prueba existe y se puede vaciar');
        $ruta = $relativa((string) get_route('push-avatars', [], true));
        $subir = fn (string $archivo, string $tipo, string $nombre): array => $peticion($ruta, $jwt, ['user_id' => $usuario, 'image' => new \CURLFile($archivo, $tipo, $nombre)]);

        $r = $subir($png, 'image/png', 'zz.png');
        $check(($r['json']['success'] ?? null) === true && ($r['json']['message'] ?? '') === $modificada && $shaEnDisco() === sha1_file($png), 'b2 un PNG se acepta y queda como avatar', "HTTP {$r['status']} · " . json_encode($r['json']));
        $antes = $shaEnDisco();
        $r = $subir($html, 'image/jpeg', 'x.jpg');
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === false && ($r['json']['message'] ?? '') === $tipoInvalido, 'b3 un HTML llamado x.jpg se rechaza con «Tipo de archivo inválido.»', "HTTP {$r['status']} · " . json_encode($r['json']));
        $check($antes !== '' && $shaEnDisco() === $antes, 'b4 y el avatar en disco no cambia (sha1 antes y después)', "{$antes} · " . $shaEnDisco());
        $r = $subir($svg, 'image/png', 'zz.png');
        $check(($r['json']['success'] ?? null) === false && $shaEnDisco() === $antes, 'b5 un SVG llamado .png se rechaza y el avatar no cambia', "HTTP {$r['status']} · " . json_encode($r['json']));

        echoTerminal('');
        echoTerminal('[c] save(), directo: la comprobación no depende del controlador');
        $copiaHtml = $escribir('x-copia.jpg', (string) file_get_contents($html));
        $check(AvatarModel::save($usuario, $subido($copiaHtml, 'image/jpeg')) === false && $shaEnDisco() === $antes && is_file($copiaHtml), 'c1 save() con un HTML devuelve false y no mueve nada');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach (array_merge(glob("{$temporal}/*") ?: [], is_string($carpetaAvatar) ? (glob("{$carpetaAvatar}/*") ?: []) : []) as $archivo) {
                //RETORNO-IGNORADO: lo comprueba z2.
                unlink($archivo);
            }
            foreach ([$carpetaAvatar, $temporal] as $carpeta) {
                if (is_string($carpeta) && is_dir($carpeta)) {
                    //RETORNO-IGNORADO: lo comprueba z2.
                    rmdir($carpeta);
                }
            }
            if ($usuario !== null) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $usuario, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $usuario])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba', "quedan {$restos}");
        clearstatcache();
        $check(count(array_filter([$carpetaAvatar, $temporal], fn ($c) => is_string($c) && file_exists($c))) === 0, 'z2 no quedan la carpeta del avatar ni el temporal');
    }

    return $balance();

})->setDescription('El avatar solo acepta JPEG, PNG o WebP por su contenido (AvatarModel::isAcceptedImage), en save() y en la ruta push-avatars; el que no lo es se rechaza sin tocar el avatar en disco. Directo y por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
