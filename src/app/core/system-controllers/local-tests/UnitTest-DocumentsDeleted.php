<?php

//Un documento eliminado no vuelve por la interfaz (P380): responde como un id que no existe y no sale en los listados.
//@areas src/app/classes/Documents/
//@requires-module Documents\DocumentsRoutes
//@requires-module Forms\DocumentTypes\DocumentTypesRoutes
//@requires-module Organizations\OrganizationsRoutes

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/documents-deleted', function ($args) {

    echoTerminal("\e[33m[TEST:DocumentsDeleted] Un documento eliminado responde como uno que no existe\e[39m");
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
    $cabeceraToken = SessionToken::headerName();
    $relativa = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $camino = fn (string $sufijo, array $parametros = []): string => $relativa(DocumentsController::routeName($sufijo, $parametros, true));
    $pedir = function (string $path, string $jwt, ?array $campos = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ];
        if ($campos !== null) {
            //Multipart, como el FormData del navegador.
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $campos;
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $mensaje = fn (array $r): string => (string) (json_decode($r['body'], true)['message'] ?? '');
    $exito = fn (array $r): bool => $r['status'] === 200 && (json_decode($r['body'], true)['success'] ?? null) === true;
    $resumen = fn (array $r): string => "HTTP {$r['status']} «" . ($mensaje($r) !== '' ? $mensaje($r) : mb_substr(trim(strip_tags($r['body'])), 0, 80)) . '»';

    $prefijo = 'zz-prueba-documentos-eliminados-' . bin2hex(random_bytes(3));
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    $tablaDocumentos = DocumentsMapper::TABLE;
    $tablaTipos = DocumentTypesMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $raizDocumentos = append_to_path_system((string) get_config('upload_dir'), DocumentsController::UPLOAD_DIR);
    $urlDocumentos = trim(str_replace(base_url(), '', append_to_url((string) get_config('upload_dir_url'), DocumentsController::UPLOAD_DIR)), '/');
    $usuarios = [];
    $documentos = [];
    $carpetas = [];
    $tipo = 0;

    try {
        $crearUsuario = function (string $sufijo, int $tipoUsuario) use ($prefijo, &$usuarios): string {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Documentos';
            $u->secondLastname = '';
            $u->type = $tipoUsuario;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return UserSessions::issueToken((int) $u->id);
        };
        $root = $crearUsuario('root', UsersModel::TYPE_USER_ROOT);
        //El dueño no edita ni elimina todos: lo que le deja tocar su documento es haberlo creado.
        $duenio = $crearUsuario('duenio', UsersModel::TYPE_USER_GENERAL);
        $check(!in_array(UsersModel::TYPE_USER_GENERAL, DocumentsMapper::CAN_EDIT_ALL) && !in_array(UsersModel::TYPE_USER_GENERAL, DocumentsMapper::CAN_DELETE_ALL), 'p2 el tipo del dueño no edita ni elimina todos: la autoría es lo que le da acceso');

        $database->prepare("INSERT INTO `{$tablaTipos}` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-tipo", "{$prefijo}-tipo", 1, $ahora, $usuarios['root'], $meta]);
        $tipo = (int) $database->lastInsertId();

        //Cada documento con su carpeta y un archivo privado (con el sufijo en disco), que sirve PHP tras el middleware.
        $sembrar = function (string $sufijo) use ($database, $prefijo, $tablaDocumentos, $tipo, $ahora, $usuarios, $meta, $raizDocumentos, $urlDocumentos, &$documentos, &$carpetas): array {
            $carpeta = "{$prefijo}-{$sufijo}";
            $disco = append_to_path_system($raizDocumentos, $carpeta);
            $carpetas[] = $disco;
            $sembrado = (is_dir($disco) || mkdir($disco, 0775, true)) && file_put_contents(ProtectedUploads::privatePath(append_to_path_system($disco, 'zz.txt')), "{$prefijo}-contenido-{$sufijo}") !== false;
            $database->prepare("INSERT INTO `{$tablaDocumentos}` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$tipo, "{$prefijo}-{$sufijo}", 'zz-descripcion', "{$urlDocumentos}/{$carpeta}/zz.txt", '', $carpeta, DocumentsMapper::STATUS_ACTIVE, $ahora, $usuarios['duenio'], $meta]);
            $id = (int) $database->lastInsertId();
            $documentos[$sufijo] = $id;
            return ['id' => $id, 'archivo' => "{$urlDocumentos}/{$carpeta}/zz.txt", 'sembrado' => $sembrado];
        };
        $activo = $sembrar('activo');
        $eliminado = $sembrar('eliminado');
        $check($activo['sembrado'] && $eliminado['sembrado'] && $activo['id'] > 0 && $eliminado['id'] > 0, 'p3 dos documentos sembrados, cada uno con su archivo privado');
        $fila = fn (int $id): array => (array) ($database->query("SELECT documentName, description, status FROM `{$tablaDocumentos}` WHERE id = {$id}")->fetch(\PDO::FETCH_ASSOC) ?: []);
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaDocumentos}`")->fetchColumn();
        $editar = fn (int $id, string $jwt, string $nombre): array => $pedir($camino('actions-edit'), $jwt, ['id' => (string) $id, 'lang' => $lang, 'documentType' => (string) $tipo, 'documentName' => $nombre, 'description' => 'zz-descripcion']);
        $eliminar = fn (int $id, string $jwt): array => $pedir($camino('actions-delete', ['id' => $id]), $jwt, ['id' => (string) $id]);
        $formulario = fn (int $id, string $jwt): array => $pedir($camino('forms-edit', ['id' => $id]), $jwt);

        echoTerminal('[a] Canarios: un documento ACTIVO se abre, se guarda y se sirve su archivo');
        foreach (['root' => $root, 'dueño' => $duenio] as $quien => $jwt) {
            $abierto = $formulario($activo['id'], $jwt);
            $check($abierto['status'] === 200 && str_contains($abierto['body'], "{$prefijo}-activo"), "a1 {$quien} abre su formulario", $resumen($abierto));
            $guardado = $editar($activo['id'], $jwt, "{$prefijo}-activo");
            $check($exito($guardado), "a2 {$quien} lo guarda", $resumen($guardado));
            $archivo = $pedir($activo['archivo'], $jwt);
            $check($archivo['status'] === 200 && str_contains($archivo['body'], "{$prefijo}-contenido-activo"), "a3 {$quien} recibe su archivo", "HTTP {$archivo['status']}");
        }
        $archivoEliminadoActivo = $pedir($eliminado['archivo'], $duenio);
        $check($archivoEliminadoActivo['status'] === 200 && str_contains($archivoEliminadoActivo['body'], "{$prefijo}-contenido-eliminado"), 'a4 el archivo del que se va a eliminar se sirve mientras está activo', "HTTP {$archivoEliminadoActivo['status']}");
        $check((int) ($fila($activo['id'])['status'] ?? -1) === DocumentsMapper::STATUS_ACTIVE, 'a5 y sigue ACTIVO');

        echoTerminal('');
        echoTerminal('[b] Eliminado, responde como un id que no existe');
        $borrado = $eliminar($eliminado['id'], $duenio);
        $check($exito($borrado) && (int) ($fila($eliminado['id'])['status'] ?? -1) === DocumentsMapper::STATUS_INACTIVE, 'b1 el dueño lo elimina: queda INACTIVO (borrado suave, la fila sigue)', $resumen($borrado));
        foreach (['root' => $root, 'dueño' => $duenio] as $quien => $jwt) {
            $formularioNada = $formulario($inexistente, $jwt);
            $formularioBorrado = $formulario($eliminado['id'], $jwt);
            $check($formularioBorrado['status'] === $formularioNada['status'] && $formularioBorrado['status'] !== 200 && !str_contains($formularioBorrado['body'], "{$prefijo}-eliminado"), "b2 {$quien}: su formulario responde como el de un id que no existe", "eliminado HTTP {$formularioBorrado['status']}, inexistente HTTP {$formularioNada['status']}");
            $guardarNada = $editar($inexistente, $jwt, "{$prefijo}-resucitado");
            $guardarBorrado = $editar($eliminado['id'], $jwt, "{$prefijo}-resucitado");
            $check($guardarBorrado['status'] === $guardarNada['status'] && $mensaje($guardarBorrado) === $mensaje($guardarNada) && !$exito($guardarBorrado), "b3 DISCRIMINANTE {$quien}: su POST de edición responde como el de un id que no existe", 'eliminado ' . $resumen($guardarBorrado) . ', inexistente ' . $resumen($guardarNada));
            $estado = $fila($eliminado['id']);
            $check((int) ($estado['status'] ?? -1) === DocumentsMapper::STATUS_INACTIVE && ($estado['documentName'] ?? '') === "{$prefijo}-eliminado", "b4 {$quien}: sigue INACTIVO y sin el nombre enviado", json_encode($estado) ?: '');
            $borrarNada = $eliminar($inexistente, $jwt);
            $borrarOtraVez = $eliminar($eliminado['id'], $jwt);
            $check($borrarOtraVez['status'] === $borrarNada['status'] && $mensaje($borrarOtraVez) === $mensaje($borrarNada) && !$exito($borrarOtraVez), "b5 {$quien}: eliminarlo otra vez responde como eliminar un id que no existe", 'eliminado ' . $resumen($borrarOtraVez) . ', inexistente ' . $resumen($borrarNada));
            //Sin arreglo en el módulo: la carpeta se protege solo con sesión (protected-files.php), que no mira el documento.
            $archivoBorrado = $pedir($eliminado['archivo'], $jwt);
            echoTerminal("   [MEDIDA] {$quien}: el archivo del eliminado responde HTTP {$archivoBorrado['status']}" . (str_contains($archivoBorrado['body'], "{$prefijo}-contenido-eliminado") ? ', con su contenido' : ', sin su contenido'));
        }

        echoTerminal('');
        echoTerminal('[c] Los listados no lo devuelven; el activo, sí');
        $listados = [
            'all' => $camino('ajax-all') . '?per_page=1000',
            'all?id' => $camino('ajax-all') . '?id=' . $eliminado['id'],
            'datatables' => $camino('datatables') . '?draw=1&start=0&length=1000',
            'datatables-explorer' => $camino('datatables-explorer') . '?draw=1&start=0&length=1000',
            'search-dropdown' => $camino('search-dropdown') . '?search=' . rawurlencode($prefijo),
        ];
        foreach ($listados as $nombre => $url) {
            $listado = $pedir($url, $root);
            $cuerpo = json_decode($listado['body'], true) !== null ? json_encode(json_decode($listado['body'], true), \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES) : $listado['body'];
            if ($nombre !== 'all?id') {
                $check($listado['status'] === 200 && str_contains((string) $cuerpo, "{$prefijo}-activo"), "c1 CANARIO {$nombre}: el activo sale", "HTTP {$listado['status']}");
            }
            $check($listado['status'] === 200 && !str_contains((string) $cuerpo, "{$prefijo}-eliminado"), "c2 {$nombre}: el eliminado no sale", "HTTP {$listado['status']}");
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($carpetas as $carpeta) {
                if (is_dir($carpeta)) {
                    foreach (glob($carpeta . '/*') ?: [] as $archivo) {
                        //RETORNO-IGNORADO: lo comprueba z2 mirando si queda la carpeta.
                        unlink($archivo);
                    }
                    //RETORNO-IGNORADO: lo comprueba z2 mirando si queda la carpeta.
                    rmdir($carpeta);
                }
            }
            foreach ($documentos as $id) {
                $database->exec("DELETE FROM `{$tablaDocumentos}` WHERE id = " . (int) $id);
            }
            if ($tipo > 0) {
                $database->exec("DELETE FROM `{$tablaTipos}` WHERE id = {$tipo}");
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $patron = $database->quote("{$prefijo}%");
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaDocumentos}` WHERE documentName LIKE {$patron} OR folder LIKE {$patron}")->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaTipos}` WHERE documentTypeName LIKE {$patron}")->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE {$patron}")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún documento, tipo ni usuario de la prueba', "quedan {$quedan}");
        $check(array_filter($carpetas, 'is_dir') === [], 'z2 no queda ninguna carpeta de archivos de la prueba', implode(', ', array_filter($carpetas, 'is_dir')));
    }

    return $balance();

})->setDescription('Un documento eliminado (borrado suave) responde como un id que no existe en su formulario de edición, su acción de edición y al eliminarlo otra vez, como root y como su dueño, y no sale en all, datatables, datatables-explorer ni search-dropdown; un activo se abre, se guarda y su archivo se sirve (P380). Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
