<?php

//Un SVG, HTML o XML subido se sirve con CSP sandbox (y, si no es imagen, como descarga) por Apache y por PHP; el resto y los
//estáticos del framework no cambian. Por HTTP. Crea un principal y archivos zz- en dos carpetas de subidas, y los retira.
//@areas nucleo

use Documents\Controllers\DocumentsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/upload-active-types', function ($args) {

    echoTerminal("\e[33m[TEST:UploadActiveTypes] Lo subido que el navegador ejecutaría se sirve en sandbox\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que «Mi organización».
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = trim((string) parse_url($base, \PHP_URL_PATH), '/');
    //La dirección de un archivo bajo src/, sin la base: la de subidas sale de su configuración.
    $relativa = function (string $url) use ($prefijoBase): string {
        $url = trim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? trim(substr($url, strlen($prefijoBase)), '/') : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => "{$base}/{$ruta}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $cuerpo = (string) curl_exec($handle);
        $cabeceras = ['content-security-policy' => [], 'content-disposition' => [], 'content-type' => [], 'piecesphp-protected-file' => []];
        foreach (preg_split('/\r?\n/', substr($cuerpo, 0, (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE))) ?: [] as $linea) {
            $partes = explode(':', $linea, 2);
            $nombre = strtolower(trim($partes[0]));
            if (count($partes) === 2 && array_key_exists($nombre, $cabeceras)) {
                $cabeceras[$nombre][] = trim($partes[1]);
            }
        }
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE)] + $cabeceras;
    };
    $tieneSandbox = fn (array $respuesta): bool => in_array('sandbox', $respuesta['content-security-policy'], true);
    $resumen = fn (array $r): string => "HTTP {$r['status']} · CSP " . json_encode($r['content-security-policy'], JSON_UNESCAPED_SLASHES) . ' · CD ' . json_encode($r['content-disposition']) . ' · CT ' . json_encode($r['content-type'], JSON_UNESCAPED_SLASHES);

    $prefijo = 'zz-prueba-activos-' . bin2hex(random_bytes(3));
    $subidas = (string) get_config('upload_dir');
    $urlSubidas = $relativa((string) get_config('upload_dir_url'));
    $carpetas = [];
    $usuario = null;

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Activos';
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

        $lienzo = imagecreatetruecolor(4, 4);
        ob_start();
        if ($lienzo !== false) {
            //RETORNO-IGNORADO: lo comprueba el canario de cada vía (el .png responde 200).
            imagepng($lienzo);
        }
        $png = (string) ob_get_clean();
        $archivos = [
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>/*zz-activos*/</script></svg>',
            'html' => '<html><body><script>/*zz-activos*/</script></body></html>',
            'xml' => '<?xml version="1.0"?><zz/>',
            'png' => $png,
            'css' => 'body{color:#000}',
        ];
        //V1 la sirve Apache (pública, existe con su nombre); V2, PHP (protegida, en disco con el sufijo).
        $vias = [
            'V1' => ['Apache, carpeta pública', BuiltInBannerController::UPLOAD_DIR, ''],
            'V2' => ['PHP, carpeta protegida', DocumentsController::UPLOAD_DIR, ProtectedUploads::suffix()],
        ];
        foreach ($vias as $via => [$titulo, $dir, $sufijo]) {
            echoTerminal('');
            echoTerminal("[{$via}] {$titulo}");
            $carpeta = append_to_path_system($subidas, "{$dir}/{$prefijo}");
            //RETORNO-IGNORADO: lo comprueba el canario.
            mkdir($carpeta, 0777, true);
            $carpetas[] = $carpeta;
            foreach ($archivos as $extension => $contenido) {
                //RETORNO-IGNORADO: lo comprueba el canario y cada petición.
                file_put_contents("{$carpeta}/zz-activo.{$extension}{$sufijo}", $contenido);
            }
            $r = fn (string $extension): array => $pedir("{$urlSubidas}/{$dir}/{$prefijo}/zz-activo.{$extension}", $jwt);
            $control = $r('png');
            $check($control['status'] === 200 && ($via === 'V1' ? $control['piecesphp-protected-file'] === [] : $control['piecesphp-protected-file'] === ['true']), "{$via}0 CANARIO: el .png responde 200 por la vía que toca", $resumen($control));
            //Apache: la del .htaccess de subidas sustituye a la global. PHP: la global de src/.htaccess se añade, y el
            //navegador aplica las dos.
            $sandbox = fn (array $respuesta): bool => $via === 'V1' ? $respuesta['content-security-policy'] === ['sandbox'] : $tieneSandbox($respuesta);
            $svg = $r('svg');
            $check($svg['status'] === 200 && $sandbox($svg) && $svg['content-disposition'] === [] && str_starts_with($svg['content-type'][0] ?? '', 'image/svg+xml'), "{$via}1 el .svg: sandbox, sin descarga y como image/svg+xml", $resumen($svg));
            foreach (['html', 'xml'] as $extension) {
                $activo = $r($extension);
                $check($activo['status'] === 200 && $sandbox($activo) && $activo['content-disposition'] === ['attachment'], "{$via}2 el .{$extension}: sandbox y descarga", $resumen($activo));
            }
            foreach (['png', 'css'] as $extension) {
                $inerte = $r($extension);
                $check($inerte['status'] === 200 && !$tieneSandbox($inerte) && $inerte['content-disposition'] === [], "{$via}3 el .{$extension}: sin cambios", $resumen($inerte));
            }
        }

        echoTerminal('');
        echoTerminal('[E] Un .svg ESTÁTICO del framework (de statics/, no de subidas)');
        $estaticos = glob(basepath('statics/images') . '/*.svg') ?: [];
        $estatico = $estaticos[0] ?? '';
        if ($check($estatico !== '', 'E0 CANARIO: hay un .svg en statics/images/', $estatico)) {
            $e = $pedir($relativa(baseurl('statics/images/' . basename($estatico))), $jwt);
            $check($e['status'] === 200 && !$tieneSandbox($e) && $e['content-disposition'] === [] && str_starts_with($e['content-type'][0] ?? '', 'image/svg+xml'), 'E1 sin sandbox ni descarga, como image/svg+xml', $resumen($e));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($carpetas as $carpeta) {
            foreach (glob("{$carpeta}/*") ?: [] as $archivo) {
                //RETORNO-IGNORADO: lo comprueba z2.
                unlink($archivo);
            }
            if (is_dir($carpeta)) {
                //RETORNO-IGNORADO: lo comprueba z2.
                rmdir($carpeta);
            }
        }
        try {
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
        $quedan = UsersModel::model();
        $quedan->resetAll();
        $quedan->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $quedan->result()) === 0, 'z1 no queda el usuario de la prueba');
        clearstatcache();
        $check(count(array_filter($carpetas, 'is_dir')) === 0, 'z2 no quedan las carpetas de la prueba', implode(', ', $carpetas));
    }

    return $balance();

})->setDescription('Un SVG, HTML o XML subido se sirve con Content-Security-Policy: sandbox (HTML y XML, además, como descarga) por Apache y por PHP; un .png, un .css y un SVG estático del framework, sin cambios. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
