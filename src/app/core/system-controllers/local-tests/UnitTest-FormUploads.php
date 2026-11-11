<?php

//Lo que hacen HOY las subidas de los cuatro módulos (ADR 0068): ruta guardada, disco y lo servido, por HTTP. Lo marcado DEF-n
//es comportamiento de hoy, no correcto. Crea sus entidades y usuario, zz-, y los retira.
//@areas src/app/classes/Documents/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/BuiltIn/
//@areas src/app/classes/Publications/

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Forms\FormUploadHandler;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\AttachmentPublicationMapper;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/form-uploads', function ($args) {

    echoTerminal("\e[33m[TEST:FormUploads] Lo que hacen hoy las subidas de Organizations, Documents, BuiltInBanner y Publications\e[39m");
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
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $controlador, string $sufijo) use ($prefijoBase): string {
        $url = $controlador::routeName($sufijo, [], true);
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $guardar = function (string $ruta, string $jwt, array $campos) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $ruta,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $campos,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'success' => is_array($datos) ? ($datos['success'] ?? null) : null, 'message' => is_array($datos) ? (string) ($datos['message'] ?? '') : '', 'body' => is_string($body) ? $body : ''];
    };
    //Como un navegador con un campo de archivo sin elegir: la parte va, con el nombre vacío (PHP la da como UPLOAD_ERR_NO_FILE).
    $guardarConVacio = function (string $ruta, string $jwt, array $campos, string $vacio) use ($base, $cabeceraToken): array {
        $limite = 'zz' . bin2hex(random_bytes(8));
        $cuerpo = '';
        foreach ($campos as $nombre => $valor) {
            $cuerpo .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$nombre}\"\r\n\r\n{$valor}\r\n";
        }
        $cuerpo .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$vacio}\"; filename=\"\"\r\nContent-Type: application/octet-stream\r\n\r\n\r\n--{$limite}--\r\n";
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $ruta,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", "Content-Type: multipart/form-data; boundary={$limite}"],
        ]);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'success' => is_array($datos) ? ($datos['success'] ?? null) : null, 'message' => is_array($datos) ? (string) ($datos['message'] ?? '') : '', 'body' => is_string($body) ? $body : ''];
    };
    //Lo que se sirve: el estado HTTP y si el cuerpo es el archivo subido, con sesión (cabecera) y sin ella.
    $servido = function (string $relativa, string $sha, ?string $jwt) use ($base, $cabeceraToken): string {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($relativa, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        return $status . (is_string($body) && sha1($body) === $sha ? ' archivo' : ' otro');
    };

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-prueba-subidas-' . bin2hex(random_bytes(3));
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $temporal = sys_get_temp_dir() . "/{$prefijo}";
    $raizSubidas = (string) get_config('upload_dir');
    $sufijoPrivado = ProtectedUploads::suffix();
    //La URL relativa de las subidas de un módulo, como la calcula su controlador.
    $urlDe = fn (string $dir): string => trim(str_replace(base_url(), '', append_to_url((string) get_config('upload_dir_url'), $dir)), '/');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    $usuarios = [];
    $filas = [];
    $carpetas = [];
    $carpetasAlta = [];
    $registroErrores = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
    $lineasRegistro = function () use ($registroErrores): int {
        clearstatcache();
        return is_file($registroErrores) ? count(file($registroErrores) ?: []) : 0;
    };
    //El mensaje de la última entrada del registro, para que un fallo diga qué pasó (la poda de la corrida la retira).
    $ultimoError = function () use ($registroErrores): string {
        clearstatcache();
        $lineas = is_file($registroErrores) ? (file($registroErrores) ?: []) : [];
        $ultima = $lineas !== [] ? json_decode((string) end($lineas), true) : null;
        return is_array($ultima) ? mb_substr((string) ($ultima['type'] ?? '') . ': ' . ($ultima['message'] ?? '') . ' @ ' . basename((string) ($ultima['file'] ?? '')) . ':' . ($ultima['line'] ?? ''), 0, 300) : '';
    };
    $tipoInvalido = __(FileValidator::LANG_GROUP, 'Tipo de archivo inválido.');
    //En disco: el nombre y si lleva el sufijo de lo privado.
    $enDisco = function (string $carpeta): array {
        clearstatcache();
        return array_values(array_map('basename', array_filter(glob("{$carpeta}/*") ?: [], 'is_file')));
    };

    try {
        //RETORNO-IGNORADO: lo comprueba c2.
        mkdir($temporal, 0700, true);
        $f = [];
        foreach (['a', 'b', 'c', 'd'] as $orden => $letra) {
            $f[$letra] = "{$temporal}/zz-subidas-{$letra}.jpg";
            //Un tamaño distinto por letra: cuatro archivos distintos, que lo servido pueda distinguir.
            $lienzo = imagecreatetruecolor(8 + $orden, 8);
            if ($lienzo !== false) {
                //RETORNO-IGNORADO: lo comprueba c2.
                imagejpeg($lienzo, $f[$letra]);
            }
        }
        $f['pdf'] = "{$temporal}/zz-subidas.pdf";
        $f['html'] = "{$temporal}/zz-subidas.html";
        $listo = file_put_contents($f['pdf'], "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n") !== false
            && file_put_contents($f['html'], '<html><body>zz-subidas</body></html>') !== false;
        $sha = array_map(fn (string $ruta): string => is_file($ruta) ? (string) sha1_file($ruta) : '', $f);
        $check($listo && count(array_filter($sha)) === count($f) && count(array_unique($sha)) === count($f), 'c2 banco: cuatro .jpg distintos, un .pdf y un .html');
        $subir = function (string $clave) use ($f): \CURLFile {
            $tipos = ['pdf' => 'application/pdf', 'html' => 'text/html'];
            return new \CURLFile($f[$clave], $tipos[$clave] ?? 'image/jpeg', basename($f[$clave]));
        };

        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Subidas';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $root = (int) $u->id;
        $usuarios[] = $root;
        $jwt = UserSessions::issueToken($root);

        $categoria = (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0);
        $database->prepare('INSERT INTO `' . DocumentTypesMapper::TABLE . '` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute(["{$prefijo}-tipo", "{$prefijo}-tipo", 1, $ahora, $root, $meta]);
        $tipoDocumento = (int) $database->lastInsertId();

        //Cada módulo: su campo probado, los obligatorios del alta (que la edición vuelve a subir para vaciar la carpeta
        //del alta), su tabla, su carpeta de subidas, si guarda privado y lo que se sirve (con sesión · sin sesión).
        $modulos = [
            'Organizations' => [
                'controlador' => OrganizationsController::class,
                'tabla' => OrganizationMapper::TABLE,
                'nombre' => 'name',
                'valorNombre' => "{$prefijo}-organizacion",
                'dir' => OrganizationsController::UPLOAD_DIR,
                'campo' => 'logo',
                'otros' => ['rut' => 'pdf'],
                'campos' => ['lang' => $lang, 'name' => "{$prefijo}-organizacion", 'nit' => "{$prefijo}-nit", 'status' => OrganizationMapper::ACTIVE],
                'privado' => true,
            ],
            'Documents' => [
                'controlador' => DocumentsController::class,
                'tabla' => DocumentsMapper::TABLE,
                'nombre' => 'documentName',
                'valorNombre' => "{$prefijo}-documento",
                'dir' => DocumentsController::UPLOAD_DIR,
                'campo' => 'documentImage',
                'otros' => ['document' => 'pdf'],
                'campos' => ['lang' => $lang, 'documentType' => $tipoDocumento, 'documentName' => "{$prefijo}-documento"],
                'privado' => true,
            ],
            'BuiltInBanner' => [
                'controlador' => BuiltInBannerController::class,
                'tabla' => BuiltInBannerMapper::TABLE,
                'nombre' => 'title',
                'valorNombre' => "{$prefijo}-banner",
                'dir' => BuiltInBannerController::UPLOAD_DIR,
                'campo' => 'desktopImage',
                'otros' => [],
                'campos' => ['lang' => $lang, 'title' => "{$prefijo}-banner"],
                'privado' => false,
            ],
            'Publications' => [
                'controlador' => PublicationsController::class,
                'tabla' => PublicationMapper::TABLE,
                'nombre' => 'title',
                'valorNombre' => "{$prefijo}-publicacion",
                'dir' => PublicationsController::UPLOAD_DIR,
                'campo' => 'ogImage',
                'otros' => ['mainImage' => 'c', 'thumbImage' => 'd'],
                'campos' => ['lang' => $lang, 'baseLang' => $lang, 'author' => $root, 'title' => "{$prefijo}-publicacion", 'content' => 'zz-', 'category' => $categoria, 'publicDate' => date('d-m-Y'), 'draft' => 'yes'],
                'privado' => true,
            ],
        ];

        foreach ($modulos as $modulo => $m) {
            echoTerminal('');
            echoTerminal("[{$modulo}] {$m['campo']}");
            $columna = function (int $id, string $campo) use ($database, $m): string {
                return (string) $database->query("SELECT `{$campo}` FROM `{$m['tabla']}` WHERE id = {$id}")->fetchColumn();
            };
            $sufijo = $m['privado'] ? $sufijoPrivado : '';
            $urlSubidas = $urlDe($m['dir']);

            //a · Alta con archivo
            $campos = $m['campos'] + [$m['campo'] => $subir('a')];
            foreach ($m['otros'] as $otro => $archivo) {
                $campos[$otro] = $subir($archivo);
            }
            $lineasAntes = $lineasRegistro();
            $r = $guardar($camino($m['controlador'], 'actions-add'), $jwt, $campos);
            $id = (int) $database->query("SELECT id FROM `{$m['tabla']}` WHERE `{$m['nombre']}` = " . $database->quote($m['valorNombre']))->fetchColumn();
            if ($id > 0) {
                $filas[$m['tabla']][] = $id;
            }
            $carpetaAlta = $id > 0 ? $columna($id, 'folder') : '';
            $discoAlta = append_to_path_system($raizSubidas, "{$m['dir']}/{$carpetaAlta}");
            if ($carpetaAlta !== '') {
                $carpetasAlta[] = $discoAlta;
            }
            $valorAlta = $id > 0 ? $columna($id, $m['campo']) : '';
            $check($r['success'] === true && $id > 0 && $carpetaAlta !== '', 'a1 el alta con archivo responde success y crea la entidad con su carpeta', "HTTP {$r['status']} · id {$id} · " . mb_substr($r['body'], 0, 200));
            $check($valorAlta === "{$urlSubidas}/{$carpetaAlta}/zz-subidas-a.jpg", 'a2 guarda la ruta en la carpeta de la entidad, con el nombre del archivo subido', $valorAlta);
            $check(in_array("zz-subidas-a.jpg{$sufijo}", $enDisco($discoAlta), true), 'a3 en disco ' . ($m['privado'] ? 'con' : 'sin') . ' el sufijo de lo privado', implode(', ', $enDisco($discoAlta)));
            $esperado = $m['privado'] ? '200 archivo · 403 otro' : '200 archivo · 200 archivo';
            $sirve = $servido($valorAlta, $sha['a'], $jwt) . ' · ' . $servido($valorAlta, $sha['a'], null);
            $check($sirve === $esperado, "a4 se sirve: {$esperado} (con sesión · sin sesión)", $sirve);
            $check($lineasRegistro() === $lineasAntes, 'a5 y no escribe en error.log.jsonl', "{$lineasAntes} → " . $lineasRegistro() . ' · ' . $ultimoError());

            //La carpeta del alta es de Apache y el terminal no puede vaciarla: la entidad pasa a una carpeta del terminal, y
            //la edición con archivo (e) hace que Apache borre de la del alta lo que subió, que queda vacía.
            $carpeta = "{$prefijo}-" . strtolower($modulo);
            $discoCarpeta = append_to_path_system($raizSubidas, "{$m['dir']}/{$carpeta}");
            //RETORNO-IGNORADO: lo comprueba e0.
            mkdir("{$discoCarpeta}/attachments", 0777, true);
            //RETORNO-IGNORADO: lo comprueba e0.
            chmod($discoCarpeta, 0777);
            //RETORNO-IGNORADO: lo comprueba e0.
            chmod("{$discoCarpeta}/attachments", 0777);
            $carpetas[] = $discoCarpeta;
            $database->prepare("UPDATE `{$m['tabla']}` SET folder = ? WHERE id = ?")->execute([$carpeta, $id]);
            $check($id > 0 && $columna($id, 'folder') === $carpeta && is_writable($discoCarpeta), 'e0 CANARIO: la entidad apunta a la carpeta del terminal', $columna($id, 'folder'));
            $edicion = ['id' => $id] + $m['campos'];
            $rutaEdicion = $camino($m['controlador'], 'actions-edit');

            //s · Edición sin archivo: se conserva el anterior
            $r = $guardar($rutaEdicion, $jwt, $edicion);
            $check($r['success'] === true && $columna($id, $m['campo']) === $valorAlta && in_array("zz-subidas-a.jpg{$sufijo}", $enDisco($discoAlta), true), 's1 la edición sin archivo conserva la ruta y el archivo anteriores', "HTTP {$r['status']} · " . $columna($id, $m['campo']) . ' · ' . mb_substr($r['message'], 0, 120));

            //v · Edición con el campo vacío, como lo manda un navegador
            $lineasAntes = $lineasRegistro();
            $r = $guardarConVacio($rutaEdicion, $jwt, $edicion, $m['campo']);
            $check($r['success'] === true && $columna($id, $m['campo']) === $valorAlta && in_array("zz-subidas-a.jpg{$sufijo}", $enDisco($discoAlta), true) && $lineasRegistro() === $lineasAntes, 'v1 un campo vacío se trata como sin archivo: guarda y conserva el anterior', "HTTP {$r['status']} · " . mb_substr($r['message'], 0, 160) . ' · ' . $ultimoError());

            //r · Tipo rechazado
            $lineasAntes = $lineasRegistro();
            $antes = $enDisco($discoAlta);
            $r = $guardar($rutaEdicion, $jwt, $edicion + [$m['campo'] => $subir('html')]);
            $check($r['success'] === false && str_contains($r['message'], $tipoInvalido) && $columna($id, $m['campo']) === $valorAlta, "r1 un .html se rechaza con «{$tipoInvalido}» y la ruta no cambia", "HTTP {$r['status']} · " . mb_substr($r['message'], 0, 160));
            $check($enDisco($discoAlta) === $antes && $enDisco($discoCarpeta) === [], 'r2 ni el disco', implode(', ', $enDisco($discoAlta)) . ' · ' . implode(', ', $enDisco($discoCarpeta)));
            $check($lineasRegistro() === $lineasAntes, 'r3 y no escribe en error.log.jsonl', "{$lineasAntes} → " . $lineasRegistro());

            //e · Edición con archivo: sustituye, borra el anterior (dentro) y va a la carpeta de la entidad
            $campos = $edicion + [$m['campo'] => $subir('b')];
            foreach ($m['otros'] as $otro => $archivo) {
                $campos[$otro] = $subir($archivo);
            }
            $r = $guardar($rutaEdicion, $jwt, $campos);
            $valor = $columna($id, $m['campo']);
            $check($r['success'] === true && $valor === "{$urlSubidas}/{$carpeta}/zz-subidas-b.jpg", 'e1 la edición con archivo guarda la ruta nueva, en la carpeta de la entidad', "HTTP {$r['status']} · {$valor}");
            $check(in_array("zz-subidas-b.jpg{$sufijo}", $enDisco($discoCarpeta), true), 'e2 el nuevo está en disco ' . ($m['privado'] ? 'con' : 'sin') . ' el sufijo', implode(', ', $enDisco($discoCarpeta)));
            $check($enDisco($discoAlta) === [], 'e3 el anterior, dentro de las subidas del módulo, se borra (y la carpeta del alta queda vacía)', implode(', ', $enDisco($discoAlta)));
            $sirve = $servido($valor, $sha['b'], $jwt) . ' · ' . $servido($valor, $sha['b'], null);
            $check($sirve === $esperado, "e4 se sirve: {$esperado}", $sirve);
            foreach ($m['otros'] as $otro => $archivo) {
                $check(str_starts_with($columna($id, $otro), "{$urlSubidas}/{$carpeta}/"), "e5 {$otro} también pasa a la carpeta de la entidad", $columna($id, $otro));
            }
        }

        //p · Publications: un adjunto con dos archivos en el mismo campo
        echoTerminal('');
        echoTerminal('[Publications] adjunto con dos archivos');
        $idPublicacion = (int) ($filas[PublicationMapper::TABLE][0] ?? 0);
        $carpetaPublicacion = "{$prefijo}-publications";
        $discoAdjuntos = append_to_path_system($raizSubidas, PublicationsController::UPLOAD_DIR . "/{$carpetaPublicacion}/attachments");
        $nombreAdjunto = "{$prefijo}-adjunto";
        $r = $guardar($camino(PublicationsController::class, 'actions-edit'), $jwt, ['id' => $idPublicacion] + $modulos['Publications']['campos'] + [
            'attachmentsName_0' => $nombreAdjunto,
            'attachmentsFile_0[0]' => $subir('a'),
            'attachmentsFile_0[1]' => $subir('pdf'),
        ]);
        $adjuntos = $database->query('SELECT id, fileLocation, folder FROM `' . AttachmentPublicationMapper::TABLE . "` WHERE publication = {$idPublicacion}")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        foreach ($adjuntos as $adjunto) {
            $filas[AttachmentPublicationMapper::TABLE][] = (int) $adjunto['id'];
        }
        $unoPorCampo = __(FileValidator::LANG_GROUP, 'Solo se admite un archivo por campo.');
        $check($r['success'] === false && str_contains($r['message'], $unoPorCampo), "p1 dos archivos en un campo se rechazan con «{$unoPorCampo}»", "HTTP {$r['status']} · " . mb_substr($r['message'], 0, 160));
        $check(count($adjuntos) === 0 && $enDisco($discoAdjuntos) === [], 'p2 y no queda fila de adjunto ni archivo en disco (antes, el primero quedaba huérfano: DEF-2)', 'filas ' . count($adjuntos) . ' · en disco: ' . implode(', ', $enDisco($discoAdjuntos)));

        //h · Publications: en el alta, un adjunto rechazado para antes de mover nada y de crear la publicación (H5)
        echoTerminal('');
        echoTerminal('[Publications] alta con un adjunto de tipo no permitido');
        $raizPublicaciones = append_to_path_system($raizSubidas, PublicationsController::UPLOAD_DIR);
        $carpetasPublicaciones = function () use ($raizPublicaciones): array {
            clearstatcache();
            return array_values(array_map('basename', array_filter(glob("{$raizPublicaciones}/*") ?: [], 'is_dir')));
        };
        $tituloH5 = "{$prefijo}-h5";
        $antesH5 = $carpetasPublicaciones();
        $r = $guardar($camino(PublicationsController::class, 'actions-add'), $jwt, ['title' => $tituloH5] + $modulos['Publications']['campos'] + [
            'attachmentsName_0' => "{$prefijo}-adjunto-h5",
            'attachmentsFile_0' => $subir('html'),
        ]);
        $idH5 = (int) $database->query('SELECT id FROM `' . PublicationMapper::TABLE . '` WHERE `title` = ' . $database->quote($tituloH5))->fetchColumn();
        if ($idH5 > 0) {
            $filas[PublicationMapper::TABLE][] = $idH5;
        }
        $check($r['success'] === false && str_contains($r['message'], $tipoInvalido) && $idH5 === 0, "h1 el alta con un adjunto .html se rechaza con «{$tipoInvalido}» y no crea la publicación (antes la creaba: H5)", "HTTP {$r['status']} · id {$idH5} · " . mb_substr($r['message'], 0, 160));
        $nuevasH5 = array_values(array_diff($carpetasPublicaciones(), $antesH5));
        $check($nuevasH5 === [], 'h2 y no aparece ninguna carpeta nueva en las subidas de Publications', implode(', ', $nuevasH5));

        //f · Modo público: si mover falla, FileUpload::moveTo() da [''] (DEF-1). Sin HTTP: aquí move_uploaded_file() falla sin
        //aviso porque el archivo no vino por POST; por HTTP, en local, el aviso aborta antes y no se vería el arreglo.
        echoTerminal('');
        echoTerminal('[modo público] mover falla');
        $destinoPublico = "{$temporal}/publico";
        $filesAntes = $_FILES;
        $_FILES = ['zzPublico' => ['name' => 'zz-subidas-d.jpg', 'type' => 'image/jpeg', 'size' => (int) filesize($f['d']), 'tmp_name' => $f['d'], 'error' => \UPLOAD_ERR_OK]];
        $lanzo = null;
        $devuelto = null;
        try {
            $mover = new \ReflectionMethod(FormUploadHandler::class, 'moveToPublic');
            $devuelto = $mover->invoke(new FormUploadHandler($temporal, 'zz-subidas', FormUploadHandler::MODE_PUBLIC), new FileUpload('zzPublico', [FileValidator::TYPE_ANY]), $destinoPublico, 'zz-subidas/publico', 'zz-publico', null);
        } catch (\Exception $e) {
            $lanzo = $e->getMessage();
        } finally {
            $_FILES = $filesAntes;
        }
        $check($lanzo !== null && str_contains($lanzo, 'No se pudo guardar el archivo subido'), 'f1 mover falla: lanza un error, no devuelve la URL de la carpeta (antes devolvía «zz-subidas/publico»: DEF-1)', $lanzo ?? ('devolvió ' . var_export($devuelto, true)));
        $check(is_file($f['d']), 'f2 y el archivo de origen sigue en su sitio', $f['d']);
        //Lo mismo en MODE_PRIVATE (H6): moveUploadedToPrivate() da '' y antes se devolvía «sin archivo».
        $destinoPrivado = "{$temporal}/privado";
        $lanzoPrivado = null;
        $devueltoPrivado = null;
        try {
            $moverPrivado = new \ReflectionMethod(FormUploadHandler::class, 'moveToPrivateOrFail');
            $devueltoPrivado = $moverPrivado->invoke(new FormUploadHandler($temporal, 'zz-subidas', FormUploadHandler::MODE_PRIVATE), ['name' => 'zz-subidas-d.jpg', 'tmp_name' => $f['d']], $destinoPrivado, 'zz-subidas/privado', 'zz-privado', null);
        } catch (\Exception $e) {
            $lanzoPrivado = $e->getMessage();
        }
        $check($lanzoPrivado !== null && str_contains($lanzoPrivado, 'No se pudo guardar el archivo subido'), 'f3 en el modo privado, mover falla: lanza un error, no devuelve «sin archivo» (H6)', $lanzoPrivado ?? ('devolvió ' . var_export($devueltoPrivado, true)));
        //Y la rama de varios por campo usa ese mismo método (G1): en CLI upload() no llega a mover, así que se mira su texto.
        $archivoPieza = (new \ReflectionClass(FormUploadHandler::class))->getFileName();
        $pieza = is_string($archivoPieza) ? (string) file_get_contents($archivoPieza) : '';
        $inicioVarios = strpos($pieza, 'if ($this->mode === self::MODE_PRIVATE_MULTIPLE) {');
        $finVarios = $inicioVarios === false ? false : strpos($pieza, '$handler = new FileUpload(', $inicioVarios);
        $ramaVarios = $inicioVarios === false || $finVarios === false ? '' : substr($pieza, $inicioVarios, $finVarios - $inicioVarios);
        $check(str_contains($ramaVarios, '->moveToPrivateOrFail(') && !str_contains($ramaVarios, '->moveToPrivate('), 'f4 en MODE_PRIVATE_MULTIPLE también: la rama mueve con moveToPrivateOrFail(), no en silencio (G1)', mb_substr($ramaVarios, 0, 80));
        if (is_dir($destinoPrivado)) {
            //RETORNO-IGNORADO: lo comprueba z3 (el temporal entero).
            rmdir($destinoPrivado);
        }
        if (is_dir($destinoPublico)) {
            //RETORNO-IGNORADO: lo comprueba z3 (el temporal entero).
            rmdir($destinoPublico);
        }

        //t · Publications: una subida en un idioma sin traducción lleva el sufijo del idioma en el nombre
        echoTerminal('');
        $otroIdioma = array_values(array_diff(Config::get_allowed_langs(), [$lang]))[0] ?? null;
        echoTerminal("[Publications] subida en un idioma sin traducción ({$otroIdioma})");
        $check($otroIdioma !== null, 't0 CANARIO: hay un segundo idioma permitido');
        if ($otroIdioma !== null) {
            $r = $guardar($camino(PublicationsController::class, 'actions-edit'), $jwt, ['id' => $idPublicacion, 'lang' => $otroIdioma] + $modulos['Publications']['campos'] + ['ogImage' => $subir('c')]);
            $traducida = (string) ((new PublicationMapper($idPublicacion))->getLangData($otroIdioma, 'ogImage', false, null) ?? '');
            $check($r['success'] === true && $traducida === $urlDe(PublicationsController::UPLOAD_DIR) . "/{$carpetaPublicacion}/zz-subidas-c_{$otroIdioma}.jpg", 't1 guarda la ruta con «_<idioma>» tras el nombre del archivo subido', "HTTP {$r['status']} · {$traducida} · " . mb_substr($r['message'], 0, 120));
            $check(in_array("zz-subidas-c_{$otroIdioma}.jpg{$sufijoPrivado}", $enDisco(append_to_path_system($raizSubidas, PublicationsController::UPLOAD_DIR . "/{$carpetaPublicacion}")), true), 't2 y está en disco con ese nombre', implode(', ', $enDisco(append_to_path_system($raizSubidas, PublicationsController::UPLOAD_DIR . "/{$carpetaPublicacion}"))));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach (glob("{$temporal}/*") ?: [] as $archivo) {
                //RETORNO-IGNORADO: lo comprueba z3.
                unlink($archivo);
            }
            if (is_dir($temporal)) {
                //RETORNO-IGNORADO: lo comprueba z3.
                rmdir($temporal);
            }
            foreach ($carpetas as $carpeta) {
                foreach (array_merge(glob("{$carpeta}/attachments/*") ?: [], glob("{$carpeta}/*") ?: []) as $archivo) {
                    if (is_file($archivo)) {
                        //RETORNO-IGNORADO: lo comprueba z3.
                        unlink($archivo);
                    }
                }
                foreach (["{$carpeta}/attachments", $carpeta] as $vaciar) {
                    if (is_dir($vaciar)) {
                        //RETORNO-IGNORADO: lo comprueba z3.
                        rmdir($vaciar);
                    }
                }
            }
            //La del alta es de Apache: solo se retira si la edición la dejó vacía (lo comprueba e3).
            foreach ($carpetasAlta as $carpeta) {
                if (is_dir($carpeta) && (glob("{$carpeta}/*") ?: []) === []) {
                    //RETORNO-IGNORADO: lo comprueba z3.
                    rmdir($carpeta);
                }
            }
            $aprobaciones = SystemApprovalsMapper::model();
            //Los adjuntos primero: su clave foránea apunta a la publicación.
            $filas = [AttachmentPublicationMapper::TABLE => $filas[AttachmentPublicationMapper::TABLE] ?? []] + $filas;
            foreach (array_merge($filas, [UsersModel::TABLE => $usuarios, DocumentTypesMapper::TABLE => isset($tipoDocumento) ? [$tipoDocumento] : []]) as $tabla => $ids) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                    if ($tabla !== UsersModel::TABLE) {
                        $database->prepare("DELETE FROM `{$tabla}` WHERE id = ?")->execute([$id]);
                    }
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuariosModelo = UsersModel::model();
            $usuariosModelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = 0;
        foreach (array_merge($filas, [DocumentTypesMapper::TABLE => isset($tipoDocumento) ? [$tipoDocumento] : []]) as $tabla => $ids) {
            foreach ($ids as $id) {
                $restos += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id = " . (int) $id)->fetchColumn();
            }
        }
        $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba', "quedan {$restos}");
        clearstatcache();
        $quedan = array_filter(array_merge($carpetas, $carpetasAlta, [$temporal]), fn (string $ruta): bool => file_exists($ruta));
        $check(count($quedan) === 0, 'z3 no quedan carpetas (las del alta, tampoco) ni el temporal', implode(', ', $quedan));
    }

    return $balance();

})->setDescription('Caracteriza por HTTP las subidas de Organizations, Documents, BuiltInBanner y Publications: alta, edición sin y con archivo, tipo rechazado y un adjunto con dos archivos (ADR 0068).')->setEffects([CliActions::EFFECT_DATABASE])->register();
