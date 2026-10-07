<?php

//Las otras puertas a lo ajeno: la API de detalle, la edición y los archivos de publicaciones, el avatar, el importador y
//el formulario de edición de usuarios. Por HTTP. Siembra organizaciones, usuarios, publicaciones y archivos zz-, y los retira.

use API\Controllers\APIController;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\ArrayRowSource;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/other-doors', function ($args) {

    echoTerminal("\e[33m[TEST:OtherDoors] Las otras puertas a lo ajeno, por HTTP\e[39m");
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
    if (!$check($base !== '' && $database !== null && function_exists('imagejpeg'), 'p1 hay una base HTTP, conexión a la base y GD para el avatar', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge($jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [], ['X-Requested-With: XMLHttpRequest']),
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            //Con un archivo, multipart; sin él, urlencoded.
            $conArchivo = count(array_filter($post, fn ($v) => $v instanceof \CURLFile)) > 0;
            $opciones[CURLOPT_POSTFIELDS] = $conArchivo ? $post : http_build_query($post);
        }
        curl_setopt_array($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //¿Hay alguna clave de secreto en la respuesta? Solo la presencia: el valor ni se lee ni se imprime.
    $claveSecreta = function ($datos) use (&$claveSecreta): bool {
        if (!is_array($datos)) {
            return false;
        }
        foreach ($datos as $clave => $valor) {
            if (is_string($clave) && preg_match('/^(password|pass|hash|passwordHash|token)$/i', $clave) === 1) {
                return true;
            }
            if ($claveSecreta($valor)) {
                return true;
            }
        }
        return false;
    };
    //Los nombres de usuario que trae la respuesta: si alguno no es de la prueba, se para.
    $usernames = function ($datos) use (&$usernames): array {
        $encontrados = [];
        if (is_array($datos)) {
            foreach ($datos as $clave => $valor) {
                if ($clave === 'username' && is_string($valor)) {
                    $encontrados[] = $valor;
                }
                $encontrados = array_merge($encontrados, $usernames($valor));
            }
        }
        return $encontrados;
    };

    $prefijo = 'zz-prueba-puertas-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaPublicaciones = PublicationMapper::TABLE;
    $carpetaPublicaciones = append_to_path_system((string) get_config('upload_dir'), PublicationsController::UPLOAD_DIR);
    $usuarios = [];
    $claves = [];
    $organizaciones = [];
    $publicaciones = [];
    $noticias = [];
    $archivos = [];
    $carpetas = [];
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    try {
        $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion, int $estado = UsersModel::STATUS_USER_ACTIVE) use ($prefijo, &$usuarios, &$claves): string {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Puertas';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = $estado;
            $u->failedAttempts = 0;
            if ($organizacion !== null) {
                $u->organization = $organizacion;
            }
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
            return SessionToken::generateToken(['id' => (int) $u->id], null, null, false);
        };
        $global = OrganizationMapper::INITIAL_ID_GLOBAL;
        $adminGeneral = $crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, $global);
        $ahora = date('Y-m-d H:i:s');
        foreach (['a', 'b'] as $cual) {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-org-{$cual}", 'zz-', "{$prefijo}-org-{$cual}", $ahora, $usuarios['admin-general'], OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizaciones[$cual] = (int) $database->lastInsertId();
        }
        $generalA = $crearUsuario('general-a', UsersModel::TYPE_USER_GENERAL, $organizaciones['a']);
        $comunicacionesA = $crearUsuario('comunicaciones-a', UsersModel::TYPE_USER_COMUNICACIONES, $organizaciones['a']);
        $comunicacionesB = $crearUsuario('comunicaciones-b', UsersModel::TYPE_USER_COMUNICACIONES, $organizaciones['b']);
        $institucionalA = $crearUsuario('institucional-a', UsersModel::TYPE_USER_INSTITUCIONAL, $organizaciones['a']);
        $encargadoA = $crearUsuario('encargado-a', UsersModel::TYPE_USER_ADMIN_ORG, $organizaciones['a']);
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET meta = ? WHERE id = ?")
            ->execute([json_encode(['baseLang' => 'es', 'langData' => new \stdClass, 'administrator' => $usuarios['encargado-a']]), $organizaciones['a']]);
        $crearUsuario('miembro-b', UsersModel::TYPE_USER_GENERAL, $organizaciones['b']);
        $crearUsuario('miembro-a', UsersModel::TYPE_USER_GENERAL, $organizaciones['a']);

        $categoria = (int) $database->query('SELECT id FROM `' . \Publications\Mappers\PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        //Cada publicación con su aprobación: el listado y la visibilidad pública la exigen.
        $sembrar = function (string $sufijo, string $autor, int $estado, string $aprobacion) use ($database, $tablaPublicaciones, $prefijo, $categoria, $ahora, &$usuarios, &$publicaciones): void {
            $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)"
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
                ->execute(["{$prefijo}-{$sufijo}", 'zz-', 'zz-', $usuarios[$autor], $categoria, '', '', '', "{$prefijo}-{$sufijo}", $ahora, $ahora, $usuarios[$autor], $estado, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $publicaciones[$sufijo] = (int) $database->lastInsertId();
            $database->prepare('INSERT INTO `' . SystemApprovalsMapper::TABLE . '` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(["{$prefijo}-{$sufijo}", (string) $publicaciones[$sufijo], $tablaPublicaciones, $ahora, $ahora, $usuarios[$autor], $aprobacion]);
        };
        $sembrar('borrador-b', 'comunicaciones-b', PublicationMapper::DRAFT, SystemApprovalsMapper::STATUS_APPROVED);
        $sembrar('activa-b', 'comunicaciones-b', PublicationMapper::ACTIVE, SystemApprovalsMapper::STATUS_APPROVED);
        $sembrar('pendiente-b', 'comunicaciones-b', PublicationMapper::ACTIVE, SystemApprovalsMapper::STATUS_PENDING);
        $sembrar('borrador-a', 'comunicaciones-a', PublicationMapper::DRAFT, SystemApprovalsMapper::STATUS_APPROVED);

        echoTerminal('[a] A1 · la API de detalle de publicaciones');
        $detalle = fn (string $sufijo): string => $camino(APIController::routeName('publications-actions', ['context' => 'publications', 'actionType' => 'detail'], true)) . '?id=' . $publicaciones[$sufijo];
        $ajeno = $pedir($detalle('borrador-b'), $generalA);
        $datosAjeno = json_decode($ajeno['body'], true);
        $check($ajeno['status'] === 404 && !str_contains($ajeno['body'], "{$prefijo}-borrador-b"), 'a1 un general de A NO recibe el borrador de B: 404, como la vista pública', "HTTP {$ajeno['status']}");
        $activa = $pedir($detalle('activa-b'), $generalA);
        $datosActiva = json_decode($activa['body'], true);
        $check(is_array($datosActiva['publicationData'] ?? null) && ($datosActiva['publicationData']['title'] ?? null) === "{$prefijo}-activa-b", 'a2 CANARIO: la activa de B sí la recibe', "HTTP {$activa['status']}");
        $ajenos = array_values(array_filter(array_merge($usernames($datosAjeno), $usernames($datosActiva)), fn ($u) => !str_starts_with((string) $u, $prefijo)));
        if (!$check($ajenos === [], 'a3 la respuesta no trae usuarios que no sean de la prueba', count($ajenos) . ' ajeno(s)')) {
            return $balance();
        }
        $check(!$claveSecreta($datosAjeno) && !$claveSecreta($datosActiva), 'a4 ninguna clave de secreto (password, hash, token) en la respuesta');
        $autor = $datosActiva['publicationData']['author'] ?? null;
        $check(is_array($autor) && array_keys($autor) === ['id', 'fullName'], 'a5 el autor sale por lista blanca: id y nombre', is_array($autor) ? implode(',', array_keys($autor)) : var_export($autor, true));

        echoTerminal('');
        echoTerminal('[c] A2 · la edición de una publicación ajena');
        $editar = $camino(PublicationsController::routeName('actions-edit', [], true));
        //`draft` es «yes» o no viene (PublicationsController, parámetro draft): sin él, la edición publica.
        $cuerpo = fn (string $sufijo, string $autor, string $titulo, int $borrador) => array_merge([
            'id' => $publicaciones[$sufijo], 'author' => $usuarios[$autor], 'lang' => 'es', 'title' => $titulo, 'content' => 'zz-',
            'category' => $categoria, 'publicDate' => date('d-m-Y'),
        ], $borrador === 1 ? ['draft' => 'yes'] : []);
        //El título se guarda en langData: se lee por el mapper, recién cargado.
        $tituloDe = function (string $sufijo) use ($publicaciones): ?string {
            $mapper = new PublicationMapper($publicaciones[$sufijo] ?? null);
            return $mapper->id !== null ? $mapper->currentLangData('title') . '|' . $mapper->status : null;
        };
        $antes = $tituloDe('borrador-b');
        $r = $pedir($editar, $comunicacionesA, $cuerpo('borrador-b', 'comunicaciones-a', "{$prefijo}-tomada", 0));
        $check($tituloDe('borrador-b') === $antes, 'c1 comunicaciones de A NO sobrescribe ni publica el borrador de B', "HTTP {$r['status']}");
        $r = $pedir($editar, $comunicacionesA, $cuerpo('borrador-a', 'comunicaciones-a', "{$prefijo}-borrador-a-editada", 1));
        $check(str_starts_with((string) $tituloDe('borrador-a'), "{$prefijo}-borrador-a-editada|"), 'c2 CANARIO: comunicaciones de A edita la suya', "HTTP {$r['status']}: " . mb_substr($r['body'], 0, 300) . ' · ahora ' . $tituloDe('borrador-a'));

        echoTerminal('');
        echoTerminal('[d] A3 · el importador de usuarios con un tipo principal');
        set_config('current_user', (object) ['id' => $usuarios['admin-general']]);
        set_config('pcsphp_current_user_stored', null);
        $informeRoot = (new ImportRunner())->run(new UsersImportDefinition(), new ArrayRowSource(['username', 'email', 'firstname', 'first_lastname', 'type', 'organization'], [["{$prefijo}-importado-root", "{$prefijo}-importado-root@example.com", 'Zz', 'Puertas', (string) UsersModel::TYPE_USER_ROOT, (string) $organizaciones['a']]]));
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $importado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-importado-root"))->fetchColumn();
        $check($importado === 0, 'd1 un administrador general NO crea un principal importando', "creados {$importado}");
        echoTerminal('   motivo: ' . mb_substr((string) json_encode($informeRoot->jsonSerialize()['rows'] ?? [], \JSON_UNESCAPED_UNICODE), 0, 300));
        set_config('current_user', (object) ['id' => $usuarios['admin-general']]);
        set_config('pcsphp_current_user_stored', null);
        //El importador admite el código o el id: con el id no depende de cómo se acuñe el código.
        $codigoA = (string) $organizaciones['a'];
        $informe = (new ImportRunner())->run(new UsersImportDefinition(), new ArrayRowSource(['username', 'email', 'firstname', 'first_lastname', 'type', 'organization'], [["{$prefijo}-importado-general", "{$prefijo}-importado-general@example.com", 'Zz', 'Puertas', (string) UsersModel::TYPE_USER_GENERAL, $codigoA]]));
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $importado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-importado-general"))->fetchColumn();
        $check($importado === 1, 'd2 CANARIO: un usuario general con su organización sí se importa', "creados {$importado}: " . mb_substr((string) json_encode($informe->jsonSerialize(), \JSON_UNESCAPED_UNICODE), 0, 400));

        echoTerminal('');
        echoTerminal('[e] A4 · el avatar de otro');
        //Sin escribir: el canario manda el suyo SIN imagen (la guarda va antes que la imagen) y el rechazo no llega a
        //guardar. Solo una provocación escribe. Un avatar lo escribe el servidor web y el terminal no puede borrarlo.
        $imagen = tempnam(sys_get_temp_dir(), 'zz-avatar-');
        $lienzo = imagecreatetruecolor(4, 4);
        $escrita = $imagen !== false && $lienzo !== false && imagejpeg($lienzo, $imagen);
        $check($escrita, 'e0 hay una imagen de prueba');
        $push = '';
        //AvatarController no lleva el trait de rutas y el registro no guarda el prefijo del grupo: la URL sale del
        //inventario de rutas (files/dev/route-inventory.json), sin escribirla aquí.
        $inventario = json_decode((string) file_get_contents("{$proyecto}/files/dev/route-inventory.json"), true);
        foreach (is_array($inventario) ? $inventario : [] as $ruta) {
            if (($ruta['name'] ?? null) === 'push-avatars') {
                $push = $camino((string) $ruta['url']);
            }
        }
        if ($escrita) {
            $r = $pedir($push, $generalA, ['user_id' => $usuarios['miembro-b'], 'image' => new \CURLFile($imagen, 'image/jpeg', 'avatar.jpg')]);
            $check($r['status'] === 403 && !is_dir(AvatarModel::getFolderUser($usuarios['miembro-b'])), 'e1 un general NO cambia el avatar de otro: 403, y no se escribe nada', "HTTP {$r['status']}");
        }
        $r = $pedir($push, $generalA, ['user_id' => $usuarios['general-a']]);
        $respuesta = json_decode($r['body'], true);
        $check($r['status'] === 200 && is_array($respuesta) && ($respuesta['error'] ?? null) === 'MISSING_OR_UNEXPECTED_PARAMS', 'e2 CANARIO: con el suyo la guarda deja pasar (y sin imagen no se guarda nada)', "HTTP {$r['status']}");
        $check(!is_dir(AvatarModel::getFolderUser($usuarios['general-a'])), 'e3 y no quedó carpeta de avatar');
        $r = $pedir($push, $encargadoA, ['user_id' => $usuarios['miembro-a']]);
        $respuesta = json_decode($r['body'], true);
        $check($r['status'] === 200 && is_array($respuesta) && ($respuesta['error'] ?? null) === 'MISSING_OR_UNEXPECTED_PARAMS', 'e4 CANARIO: el encargado de A pasa la guarda con un miembro de A', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[f] A5 · el archivo privado de una publicación no pública');
        $carpeta = append_to_path_system($carpetaPublicaciones, "{$prefijo}-borrador-b");
        $carpetas[] = $carpeta;
        $creada = is_dir($carpeta) || mkdir($carpeta, 0775, true);
        $privado = append_to_path_system($carpeta, 'zz.txt') . ProtectedUploads::suffix();
        $archivos[] = $privado;
        $escrito = $creada && file_put_contents($privado, "{$prefijo}-contenido") !== false;
        $check($escrito, 'f0 hay un archivo privado sembrado');
        $url = $camino(append_to_url((string) get_config('upload_dir_url'), PublicationsController::UPLOAD_DIR . "/{$prefijo}-borrador-b/zz.txt"));
        $deA = $pedir($url, $generalA);
        $check($deA['status'] !== 200 && !str_contains($deA['body'], "{$prefijo}-contenido"), 'f1 un general de A NO recibe el archivo del borrador de B', "HTTP {$deA['status']}");
        $deB = $pedir($url, $comunicacionesB);
        $check($deB['status'] === 200 && str_contains($deB['body'], "{$prefijo}-contenido"), 'f2 CANARIO: su autor sí', "HTTP {$deB['status']}");
        //Un token firmado que index.php rechaza (aquí, el de un usuario que pasa a inactivo) no es una sesión para el archivo.
        $inactivo = new UsersModel($usuarios['comunicaciones-b']);
        $inactivo->status = UsersModel::STATUS_USER_INACTIVE;
        $inactivo->update();
        $deInactivo = $pedir($url, $comunicacionesB);
        $check($deInactivo['status'] !== 200 && !str_contains($deInactivo['body'], "{$prefijo}-contenido"), 'f3 con el token de un usuario ya inactivo NO lo recibe', "HTTP {$deInactivo['status']}");
        $inactivo->status = UsersModel::STATUS_USER_ACTIVE;
        $inactivo->update();

        echoTerminal('');
        echoTerminal('[g] El formulario de edición de un usuario de otra organización');
        $formulario = $pedir($camino(UsersController::routeName('form-edit', ['id' => $usuarios['miembro-b']], true)), $encargadoA);
        $check($formulario['status'] !== 200 && !str_contains($formulario['body'], "{$prefijo}-miembro-b"), 'g1 el encargado de A NO abre el de un miembro de B', "HTTP {$formulario['status']}");
        $propio = $pedir($camino(UsersController::routeName('form-edit', ['id' => $usuarios['miembro-a']], true)), $encargadoA);
        $check($propio['status'] === 200, 'g2 CANARIO: el encargado de A sí abre el de un miembro de A', "HTTP {$propio['status']}");

        echoTerminal('');
        echoTerminal('[i] H2 · la API de detalle de noticias: solo lo que el listado le daría');
        $categoriaNoticias = NewsCategoryMapper::uncategorizedCategory()->id;
        foreach (['visible' => [NewsMapper::ACTIVE, 0, [UsersModel::TYPE_USER_GENERAL]], 'borrador' => [NewsMapper::ACTIVE, 1, [UsersModel::TYPE_USER_GENERAL]], 'inactiva' => [NewsMapper::INACTIVE, 0, [UsersModel::TYPE_USER_GENERAL]], 'otro-perfil' => [NewsMapper::ACTIVE, 0, [UsersModel::TYPE_USER_INSTITUCIONAL]]] as $sufijo => [$estado, $borrador, $perfiles]) {
            $database->prepare('INSERT INTO `' . NewsMapper::TABLE . '` (newsTitle, profilesTarget, content, category, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute(["{$prefijo}-noticia-{$sufijo}", json_encode($perfiles), 'zz-', $categoriaNoticias, "{$prefijo}-noticia-{$sufijo}", $ahora, $usuarios['admin-general'], $estado, json_encode(['baseLang' => 'es', 'draft' => $borrador, 'langData' => new \stdClass])]);
            $noticias[$sufijo] = (int) $database->lastInsertId();
        }
        $detalleNoticia = fn (int $id): string => $camino(APIController::routeName('news-actions', ['actionType' => 'detail'], true)) . '?id=' . $id;
        $r = $pedir($detalleNoticia($noticias['visible'] ?? 0), $generalA);
        $datos = json_decode($r['body'], true);
        $check($r['status'] === 200 && is_array($datos['newsData'] ?? null), 'i1 CANARIO: un general recibe la noticia dirigida a su tipo', "HTTP {$r['status']}");
        foreach (['borrador' => 'i2', 'inactiva' => 'i3', 'otro-perfil' => 'i4'] as $sufijo => $caso) {
            $r = $pedir($detalleNoticia($noticias[$sufijo] ?? 0), $generalA);
            $check($r['status'] === 404 && !str_contains($r['body'], "{$prefijo}-noticia-{$sufijo}"), "{$caso} NO recibe la noticia {$sufijo}: 404", "HTTP {$r['status']}");
        }
        $inexistente = (int) $database->query('SELECT COALESCE(MAX(id), 0) + 1000 FROM `' . NewsMapper::TABLE . '`')->fetchColumn();
        $r = $pedir($detalleNoticia($inexistente), $generalA);
        $check($r['status'] === 404, 'i5 una que no existe: 404', "HTTP {$r['status']}: " . mb_substr($r['body'], 0, 60));

        echoTerminal('');
        echoTerminal('[j] H1 · un archivo privado que no es de ninguna publicación');
        $huerfana = append_to_path_system($carpetaPublicaciones, "{$prefijo}-huerfana");
        $carpetas[] = $huerfana;
        $privadoSuelto = append_to_path_system($huerfana, 'zz.txt') . ProtectedUploads::suffix();
        $archivos[] = $privadoSuelto;
        $escrito = (is_dir($huerfana) || mkdir($huerfana, 0775, true)) && file_put_contents($privadoSuelto, "{$prefijo}-huerfano") !== false;
        $check($escrito, 'j0 hay un archivo privado en una carpeta que no es de ninguna publicación');
        $urlSuelto = $camino(append_to_url((string) get_config('upload_dir_url'), PublicationsController::UPLOAD_DIR . "/{$prefijo}-huerfana/zz.txt"));
        $r = $pedir($urlSuelto, $generalA);
        $check($r['status'] !== 200 && !str_contains($r['body'], "{$prefijo}-huerfano"), 'j1 un general NO lo recibe', "HTTP {$r['status']}");
        $r = $pedir($urlSuelto, $adminGeneral);
        $check($r['status'] === 200 && str_contains($r['body'], "{$prefijo}-huerfano"), 'j2 CANARIO: el administrador general (CAN_VIEW_ALL) sí', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[h] P25 · el institucional ve lo que aprueba de otras organizaciones');
        $vista = fn (string $sufijo): string => $camino(PublicationsPublicController::routeName('single', ['slug' => (new PublicationMapper($publicaciones[$sufijo] ?? null))->getSlug()], true));
        $pendiente = $pedir($vista('pendiente-b'), $institucionalA);
        //El 301 a la URL verdadera solo sale DENTRO de $allowShow (PublicationsPublicController): también es «la ve».
        $check(in_array($pendiente['status'], [200, 301], true), 'h1 el institucional de A ve la pendiente de B (la aprueba)', "HTTP {$pendiente['status']}");
        $borrador = $pedir($vista('borrador-b'), $institucionalA);
        $check($borrador['status'] === 404, 'h2 pero no el borrador de B: aprobar no es ver borradores', "HTTP {$borrador['status']}");
        $deComunicaciones = $pedir($vista('pendiente-b'), $comunicacionesA);
        $check($deComunicaciones['status'] === 404, 'h3 comunicaciones de A no ve la pendiente de B', "HTTP {$deComunicaciones['status']}");
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if (isset($imagen) && is_string($imagen) && is_file($imagen)) {
            //RETORNO-IGNORADO: un temporal propio de sys_get_temp_dir(); si quedara, no altera la prueba.
            @unlink($imagen);
        }
        $usuariosCreados = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchAll(\PDO::FETCH_COLUMN);
        try {
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    unlink($archivo);
                }
            }
            foreach ($carpetas as $carpeta) {
                if (is_dir($carpeta) && count((array) scandir($carpeta)) === 2) {
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    rmdir($carpeta);
                }
            }
            //Un avatar lo escribe el servidor web y el terminal no puede borrarlo: no se intenta. Se dice cuál queda.
            foreach ($usuariosCreados as $id) {
                if (is_dir(AvatarModel::getFolderUser((int) $id))) {
                    echoTerminal('   queda la carpeta de avatar del usuario ' . (int) $id . ' (del servidor web)');
                }
            }
            //Un inicio de sesión deja intentos de acceso, que atan al usuario por su clave foránea: por si alguno corrió.
            foreach ($usuariosCreados as $id) {
                $database->exec('DELETE FROM `' . \PiecesPHP\UserSystem\ORM\LoginAttemptsModel::TABLE . '` WHERE userID = ' . (int) $id);
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaPublicaciones, $publicaciones], [$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuariosCreados]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($publicaciones as $id) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = " . (int) $id);
            }
            foreach ($noticias as $id) {
                $database->exec('DELETE FROM `' . NewsMapper::TABLE . '` WHERE id = ' . (int) $id);
            }
            foreach ($usuariosCreados as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => (int) $id])->execute();
            }
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, caen las organizaciones y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = " . (int) $id);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaPublicaciones}` WHERE title LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query('SELECT COUNT(*) FROM `' . NewsMapper::TABLE . '` WHERE newsTitle LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
            + count(array_filter($archivos, 'is_file'))
            + count(array_filter($carpetas, 'is_dir'));
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, publicación, archivo ni carpeta de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las otras puertas a lo ajeno: la API de detalle y los archivos de publicaciones no públicas, la API de detalle de noticias, los archivos privados sin publicación, la edición de publicaciones, el avatar de otro, el importador con un tipo principal y el formulario de un usuario de otra organización. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
