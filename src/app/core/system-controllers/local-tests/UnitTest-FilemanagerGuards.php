<?php

//El gestor de archivos: la ruta del editor, cerrada; la de root, entera; statics/filemanager/, sin activar lo activo.
//Crea dos usuarios zz-agente-gestor-* y un archivo zz- que retira en finally.
//@areas src/app/classes/PiecesPHP/FileManager/

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\FileManager\Controllers\FileManagerController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/filemanager-guards', function ($args) {

    echoTerminal("\e[33m[TEST:FilemanagerGuards] La ruta del editor, cerrada; la de root, entera; lo activo, sin activar\e[39m");
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
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::headerName();

    //Un cliente del conector: su sesión PHP y su token CSRF, que el conector pide para subir y borrar.
    $cliente = function (string $conector, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $csrf = '';
        $pedir = function (?array $post, string $query = '', bool $multipart = false) use ($handle, $base, $conector, $jwt, $cabeceraToken, &$csrf): array {
            $cabeceras = ['X-Requested-With: XMLHttpRequest', "{$cabeceraToken}: {$jwt}"];
            if ($csrf !== '') {
                $cabeceras[] = "X-elFinder-CSRF: {$csrf}";
            }
            $opciones = [
                CURLOPT_URL => $base . '/' . ltrim($conector, '/') . $query,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $cabeceras,
                CURLOPT_COOKIEFILE => '',
                CURLOPT_HTTPGET => $post === null,
                CURLOPT_POST => $post !== null,
            ];
            if ($post !== null) {
                $opciones[CURLOPT_POSTFIELDS] = $multipart ? $post : http_build_query($post);
            }
            \Terminal\TestRun::curlSetoptArray($handle, $opciones);
            $body = curl_exec($handle);
            $datos = is_string($body) ? json_decode($body, true) : null;
            if (is_array($datos) && isset($datos['csrf']) && is_string($datos['csrf'])) {
                $csrf = $datos['csrf'];
            }
            return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'json' => is_array($datos) ? $datos : []];
        };
        return ['init' => fn (): array => $pedir(null, '?' . http_build_query(['cmd' => 'open', 'init' => 1, 'target' => '', 'tree' => 1])),
            'upload' => fn (string $target, string $name, string $content, string $mime): array => $pedir(['cmd' => 'upload', 'target' => $target, 'upload[]' => new \CURLStringFile($content, $name, $mime)], '', true),
            'rm' => fn (array $targets): array => $pedir(['cmd' => 'rm', 'targets' => $targets])];
    };

    $prefijo = 'zz-agente-gestor-' . bin2hex(random_bytes(3));
    $marca = 'zz-gestor-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $carpetaDocumentos = basepath('statics/filemanager/documents');
    $htaccess = basepath('statics/filemanager/.htaccess');
    $htaccessAntes = is_file($htaccess) ? file_get_contents($htaccess) : false;
    $subidos = [];
    $general = null;

    try {
        $crear = function (string $nombre, int $tipo) use ($prefijo): int {
            $usuario = new UsersModel();
            $usuario->username = "{$prefijo}-{$nombre}";
            $usuario->email = "{$prefijo}-{$nombre}@example.com";
            $usuario->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $usuario->firstname = 'Zz';
            $usuario->secondname = '';
            $usuario->firstLastname = 'Gestor';
            $usuario->secondLastname = '';
            $usuario->type = $tipo;
            $usuario->status = UsersModel::STATUS_USER_ACTIVE;
            $usuario->failedAttempts = 0;
            $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
            $usuario->createdAt = new \DateTime();
            $usuario->modifiedAt = $usuario->createdAt;
            $usuario->save();
            return (int) $usuario->id;
        };
        $general = $cliente($camino(FileManagerController::routeName('filemanager-configuration-rich-editor', [], true)), UserSessions::issueToken($crear('general', UsersModel::TYPE_USER_GENERAL)));
        $root = $cliente($camino(FileManagerController::routeName('filemanager-configuration', [], true)), UserSessions::issueToken($crear('root', UsersModel::TYPE_USER_ROOT)));

        //Se retira para que tenga que volver a escribirse: si no, el de una corrida anterior taparía que faltan las llamadas.
        //RETORNO-IGNORADO: lo comprueba c1, que lo busca después de abrir el conector.
        @unlink($htaccess);

        echoTerminal('[a] La ruta del editor: lista cerrada y comandos apagados');
        $inicio = ($general['init'])();
        $opciones = $inicio['json']['options'] ?? [];
        $tipos = $opciones['uploadMime'] ?? [];
        $check($inicio['status'] === 200 && isset($inicio['json']['cwd']['hash']), 'a1 CANARIO: un usuario general abre la ruta del editor', "HTTP {$inicio['status']}");
        $check(($tipos['deny'] ?? null) === ['all'], 'a2 niega todo por omisión', (string) json_encode($tipos['deny'] ?? null));
        $check(($tipos['allow'] ?? null) === FileManagerController::EDITOR_UPLOAD_ALLOWED, 'a3 y permite solo la lista cerrada');
        $check(($tipos['firstOrder'] ?? null) === 'deny', 'a4 en el orden deny, allow', (string) json_encode($tipos['firstOrder'] ?? null));
        $apagados = is_array($opciones['disabled'] ?? null) ? $opciones['disabled'] : [];
        $check(array_diff(FileManagerController::EDITOR_DISABLED_COMMANDS, $apagados) === [], 'a5 con sus comandos apagados', (string) json_encode($apagados));
        $check(array_intersect(FileManagerController::EDITOR_UPLOAD_ALLOWED, ['image/svg+xml', 'text/html', 'text/xml', 'application/xml']) === [], 'a6 sin ningún tipo activo en la lista');

        echoTerminal(' ');
        echoTerminal('[b] La ruta de root conserva sus poderes');
        $inicioRoot = ($root['init'])();
        $tiposRoot = $inicioRoot['json']['options']['uploadMime'] ?? [];
        $check($inicioRoot['status'] === 200 && ($tiposRoot['allow'] ?? null) === ['all'] && ($tiposRoot['deny'] ?? null) === [], 'b1 sigue con [\'all\'] y sin negar nada', (string) json_encode($tiposRoot));
        $apagadosRoot = is_array($inicioRoot['json']['options']['disabled'] ?? null) ? $inicioRoot['json']['options']['disabled'] : [];
        $check(array_intersect(['extract', 'archive', 'mkfile', 'put'], $apagadosRoot) === [], 'b2 con esos comandos encendidos', (string) json_encode($apagadosRoot));

        echoTerminal(' ');
        echoTerminal('[c] statics/filemanager/ sirve lo activo sin activarlo');
        $contenido = is_file($htaccess) ? (string) file_get_contents($htaccess) : '';
        $check(str_contains($contenido, 'Content-Security-Policy "sandbox"'), 'c1 el .htaccess vuelve solo, con sandbox (lo escriben el arranque y el conector: cae si faltan los dos)');
        $check(str_contains($contenido, 'Content-Disposition "attachment"'), 'c2 y attachment');
        $check(str_contains($contenido, 'svg') && str_contains($contenido, 'html'), 'c3 y nombra svg y html');

        echoTerminal(' ');
        echoTerminal('[d] Lo que sube un rol sin privilegios por la ruta del editor');
        $objetivo = (string) $inicio['json']['cwd']['hash'];
        $png = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $imagen = ($general['upload'])($objetivo, "{$marca}.png", $png, 'image/png');
        $añadido = $imagen['json']['added'][0] ?? null;
        if (is_array($añadido) && isset($añadido['hash'])) {
            $subidos[] = (string) $añadido['hash'];
        }
        $check(is_array($añadido) && ($añadido['mime'] ?? '') === 'image/png', 'd1 CANARIO: un .png sube', (string) json_encode($imagen['json']['warning'] ?? $imagen['json']['error'] ?? null));
        $vector = ($general['upload'])($objetivo, "{$marca}.svg", '<svg xmlns="http://www.w3.org/2000/svg"/>', 'image/svg+xml');
        $check(($vector['json']['added'] ?? []) === [] && !is_file("{$carpetaDocumentos}/{$marca}.svg"), 'd2 un .svg se rechaza y no llega al disco', (string) json_encode($vector['json']['warning'] ?? $vector['json']['error'] ?? null));
        $disfrazado = ($general['upload'])($objetivo, "{$marca}-texto.jpg", '<!doctype html><html><body><p>zz</p></body></html>', 'image/jpeg');
        $check(($disfrazado['json']['added'] ?? []) === [] && !is_file("{$carpetaDocumentos}/{$marca}-texto.jpg"), 'd3 un .jpg que por dentro es HTML queda fuera por su tipo detectado', (string) json_encode($disfrazado['json']['warning'] ?? $disfrazado['json']['error'] ?? null));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if ($subidos !== [] && $general !== null) {
            //RETORNO-IGNORADO: lo comprueba z1 mirando el disco.
            ($general['rm'])($subidos);
        }
        if (!is_file($htaccess) && is_string($htaccessAntes)) {
            //RETORNO-IGNORADO: lo comprueba z3 mirando el disco.
            file_put_contents($htaccess, $htaccessAntes);
        }
        $check(is_file($htaccess) || !is_string($htaccessAntes), 'z3 el .htaccess sigue en su sitio');
        $restos = glob("{$carpetaDocumentos}/{$marca}*") ?: [];
        foreach ($restos as $resto) {
            //RETORNO-IGNORADO: lo comprueba z1 mirando el disco.
            @unlink($resto);
        }
        $check((glob("{$carpetaDocumentos}/{$marca}*") ?: []) === [], 'z1 no queda ningún archivo de la prueba');
        //`SystemApprovalManager::init()` anota cada usuario nuevo al servir, y el perfil nace con él: esas filas no caen solas.
        $tablaAprobaciones = \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE;
        $consultaUsuarios = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        foreach ($consultaUsuarios->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaUsuarios, (string) $id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z2 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('El gestor de archivos: la ruta del editor lleva lista cerrada y comandos apagados, la de root conserva sus poderes, y statics/filemanager/ lleva su .htaccess con sandbox y attachment.')->setEffects([CliActions::EFFECT_DATABASE])->register();
