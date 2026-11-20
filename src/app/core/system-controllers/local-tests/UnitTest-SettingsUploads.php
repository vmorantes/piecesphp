<?php

//Las imágenes de Configuración van a statics/uploads/settings/<tipo>/ con su huella y nunca escriben ni borran una de
//fábrica (H230). Por HTTP; repone las opciones en crudo y retira lo que crea en las subidas.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/settings-uploads', function ($args) {

    echoTerminal("\e[33m[TEST:SettingsUploads] Las imágenes de Configuración, en sus subidas y sin tocar las de fábrica\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que backgrounds-delete.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null && function_exists('imagewebp'), 'banco: hay una base HTTP, conexión a la base y GD escribe WEBP', $base)) {
        return $balance();
    }
    //─── t · El tope del servidor (UPLOAD_ERR_INI_SIZE): en proceso, con un $_FILES simulado; PHP lo entrega sin archivo ───
    //En el terminal set_lang() no recarga los diccionarios: el inglés se mira en su diccionario (como g6 de login-attempts-organization).
    $check(Config::get_lang() === 'es' && SettingsController::megabytesText(2 * 1024 * 1024) === '2' && SettingsController::megabytesText(512 * 1024) === '0,5' && SettingsController::megabytesText((int) \PiecesPHP\Core\Forms\FileValidator::iniSizeToBytes('1G')) === '1024', 't1 DISCRIMINANTE: los MB del texto, con el decimal del idioma: «2M» da 2, «512K» da 0,5 en español y «1G» da 1024', SettingsController::megabytesText(512 * 1024));
    $check(lang(SettingsController::LANG_GROUP, ',', 'en') === '.' && str_replace('.', lang(SettingsController::LANG_GROUP, ',', 'en'), '0.5') === '0.5', 't1b en inglés, el decimal es «.»: 0.5', lang(SettingsController::LANG_GROUP, ',', 'en'));
    $topeServidor = \PiecesPHP\Core\Forms\FileValidator::iniSizeToBytes((string) ini_get('upload_max_filesize'));
    $esperadoTope = $topeServidor !== null ? strReplaceTemplate(__(SettingsController::LANG_GROUP, 'El archivo pesa más de lo que admite el servidor (${size} MB).'), ['${size}' => SettingsController::megabytesText($topeServidor)]) : null;
    $filesAntes = $_FILES;
    $postAntes = $_POST;
    $longitudAntes = $_SERVER['CONTENT_LENGTH'] ?? null;
    $_POST = [];
    $_FILES['zzTope'] = ['name' => 'zz-tope.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => \UPLOAD_ERR_INI_SIZE, 'size' => 0];
    $_FILES['zzFormulario'] = ['name' => 'zz-tope.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => \UPLOAD_ERR_FORM_SIZE, 'size' => 0];
    $_FILES['zzParcial'] = ['name' => 'zz-tope.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => \UPLOAD_ERR_PARTIAL, 'size' => 0];
    $porTope = SettingsController::storeDirectBrandUpload('zzTope', 'loader-screen', true);
    $porFormulario = SettingsController::storeDirectBrandUpload('zzFormulario', 'loader-animation', false);
    $porParcial = SettingsController::storeDirectBrandUpload('zzParcial', 'loader-screen', true);
    $_POST = ['MAX_FILE_SIZE' => (string) (512 * 1024)];
    $porFormularioConTope = SettingsController::storeDirectBrandUpload('zzFormulario', 'loader-animation', false);
    $_POST = ['MAX_FILE_SIZE' => 'zz'];
    $porFormularioTopeRaro = SettingsController::storeDirectBrandUpload('zzFormulario', 'loader-animation', false);
    //post_max_size: PHP deja $_FILES y $_POST vacíos; solo queda CONTENT_LENGTH.
    $topePost = \PiecesPHP\Core\Forms\FileValidator::iniSizeToBytes((string) ini_get('post_max_size'));
    $_FILES = [];
    $_POST = [];
    $_SERVER['CONTENT_LENGTH'] = (string) ((int) $topePost + 1);
    $porEnvio = SettingsController::postSizeErrorMessage();
    $_SERVER['CONTENT_LENGTH'] = (string) (int) $topePost;
    $enElTope = SettingsController::postSizeErrorMessage();
    $_SERVER['CONTENT_LENGTH'] = (string) ((int) $topePost + 1);
    $_POST = ['zz' => '1'];
    $conCuerpo = SettingsController::postSizeErrorMessage();
    $_FILES = $filesAntes;
    $_POST = $postAntes;
    if ($longitudAntes === null) {
        unset($_SERVER['CONTENT_LENGTH']);
    } else {
        $_SERVER['CONTENT_LENGTH'] = $longitudAntes;
    }
    $check($esperadoTope !== null && $porTope['path'] === null && $porTope['error'] === $esperadoTope, 't2 DISCRIMINANTE: UPLOAD_ERR_INI_SIZE dice el tope del servidor (upload_max_filesize = ' . (string) ini_get('upload_max_filesize') . ') y no guarda nada', $porTope['error']);
    $check($porFormulario['path'] === null && $porFormulario['error'] === __(SettingsController::LANG_GROUP, 'El archivo pesa más de lo que admite el formulario.') && $porFormularioTopeRaro['error'] === $porFormulario['error'], 't2b UPLOAD_ERR_FORM_SIZE sin MAX_FILE_SIZE (o con uno que no es un número): el texto del formulario, sin cifra', $porFormulario['error']);
    $check($porFormularioConTope['error'] === strReplaceTemplate(__(SettingsController::LANG_GROUP, 'El archivo pesa más de lo que admite el formulario (${size} MB).'), ['${size}' => '0,5']), 't2c DISCRIMINANTE: con MAX_FILE_SIZE = 524288, su propio tope: 0,5 MB', $porFormularioConTope['error']);
    $check($porParcial['path'] === null && $porParcial['error'] === __(SettingsController::LANG_GROUP, 'Información faltante o inesperada.'), 't3 otro error de subida sigue con «Información faltante o inesperada.»', $porParcial['error']);
    $check($topePost !== null && $porEnvio === strReplaceTemplate(__(SettingsController::LANG_GROUP, 'Lo enviado pesa más de lo que admite el servidor (${size} MB).'), ['${size}' => SettingsController::megabytesText($topePost)]), 't4 DISCRIMINANTE: pasar de post_max_size (' . (string) ini_get('post_max_size') . ') sin $_FILES ni cuerpo dice su tope', var_export($porEnvio, true));
    $check($enElTope === null && $conCuerpo === null, 't4b justo en el tope, o con cuerpo, no es ese caso: null', var_export([$enElTope, $conCuerpo], true));

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::headerName();
    /** @param array<string,mixed>|null $post Con un CURLFile va en multipart */
    $pedir = function (string $ruta, ?string $jwt, ?array $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ['X-Requested-With: XMLHttpRequest'];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        $multipart = $post !== null && array_filter($post, fn ($valor) => $valor instanceof \CURLFile) !== [];
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $multipart ? $post : http_build_query($post)] : []));
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'data' => is_array($datos) ? $datos : []];
    };

    $tabla = (new SettingsModel())->getModel()->getTable();
    $crudo = function (string $nombre) use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$nombre]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $poner = function (string $nombre, ?string $crudoNuevo) use ($database, $tabla, $crudo): void {
        if ($crudoNuevo === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$nombre]);
        } elseif ($crudo($nombre) === null) {
            $database->prepare("INSERT INTO `{$tabla}` (name, value) VALUES (?, ?)")->execute([$nombre, $crudoNuevo]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoNuevo, $nombre]);
        }
    };
    //Una lista se guarda en JSON; un texto, en crudo.
    $valor = function (string $nombre) use ($crudo): mixed {
        $c = $crudo($nombre);
        $decodificado = $c === null ? null : json_decode($c, true);
        return is_array($decodificado) ? $decodificado : $c;
    };

    $lang = Config::get_default_lang();
    $otros = array_values(array_filter(Config::get_allowed_langs(), fn ($l) => $l !== $lang));
    $otro = is_string($otros[0] ?? null) ? $otros[0] : null;
    $opciones = ['backgrounds', 'backgoundProblems', 'logo', 'mailingLogo', 'loaderAnimation', SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['image'], SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['mode'], SettingsController::BRAND_IMAGES_DARK_MODE['logo']['image'], SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode'], 'partners', SettingsController::BRAND_IMAGES_DARK_MODE['partners']['image'], SettingsController::BRAND_IMAGES_DARK_MODE['partners']['mode'], SettingsController::BRAND_IMAGES_INVERT_ON_DARK['partners'], 'loaderLogo', SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image'], SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['mode']];
    foreach (['loaderScreen', 'loaderScreenMobile'] as $pantallaCarga) {
        array_push($opciones, $pantallaCarga, SettingsController::BRAND_IMAGES_DARK_MODE[$pantallaCarga]['image'], SettingsController::BRAND_IMAGES_DARK_MODE[$pantallaCarga]['mode']);
    }
    foreach (SettingsController::SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME as $nombreSeo) {
        $opciones[] = SettingsController::seoOptionName($nombreSeo, $lang);
        if ($otro !== null) {
            $opciones[] = SettingsController::seoOptionName($nombreSeo, $otro);
        }
    }
    array_push($opciones, ...array_values(SettingsController::LOADER_LAYERS_ENABLED));
    $opciones = array_values(array_unique($opciones));
    $antes = [];
    foreach ($opciones as $nombre) {
        $antes[$nombre] = $crudo($nombre);
    }

    //Los archivos de fábrica de las dos carpetas: ninguno puede cambiar ni desaparecer en toda la suite.
    $carpetasFabrica = [basepath('statics/images'), basepath(SettingsController::LOGIN_BACKGROUNDS_FOLDER)];
    $huellasFabrica = function () use ($carpetasFabrica): array {
        $huellas = [];
        foreach ($carpetasFabrica as $carpeta) {
            foreach (glob("{$carpeta}/*") ?: [] as $archivo) {
                if (is_file($archivo)) {
                    $huellas[$archivo] = sha1_file($archivo);
                }
            }
        }
        return $huellas;
    };
    $fabricaAntes = $huellasFabrica();

    //Las subidas que falten se crean aquí, con setgid y escritura de grupo: lo que Apache escriba dentro lo puede retirar el
    //terminal. Si ya las creó Apache (suyas, sin escritura de grupo), se retira por la aplicación: ver $retirar.
    $raiz = basepath(SettingsController::SETTINGS_UPLOADS_FOLDER);
    $carpetasCreadas = [];
    foreach (['', '/login-backgrounds', '/brand', '/open-graph'] as $sub) {
        if (!is_dir($raiz . $sub) && is_writable(dirname($raiz . $sub))) {
            //RETORNO-IGNORADO: lo comprueba el banco de abajo.
            mkdir($raiz . $sub, 0775);
            //RETORNO-IGNORADO: lo comprueba el banco de abajo.
            chmod($raiz . $sub, 02775);
            $carpetasCreadas[] = $raiz . $sub;
        }
    }
    $enSubidas = function () use ($raiz): array {
        $archivos = [];
        foreach (['login-backgrounds', 'brand', 'open-graph'] as $tipo) {
            foreach (glob("{$raiz}/{$tipo}/*") ?: [] as $archivo) {
                $archivos[] = $archivo;
            }
        }
        sort($archivos);
        return $archivos;
    };
    $subidasAntes = $enSubidas();

    $imagenes = [];
    $imagen = function (string $formato, int $rojo) use (&$imagenes): string {
        $lienzo = imagecreatetruecolor(8, 8);
        $archivo = (string) tempnam(sys_get_temp_dir(), 'zz-img-');
        if ($lienzo !== false) {
            imagefill($lienzo, 0, 0, (int) imagecolorallocate($lienzo, max(0, min(255, $rojo)), 40, 90));
            match ($formato) {
                'webp' => imagewebp($lienzo, $archivo),
                'png' => imagepng($lienzo, $archivo),
                default => imagejpeg($lienzo, $archivo),
            };
        }
        $imagenes[] = $archivo;
        return $archivo;
    };
    $patron = fn (string $tipo, string $nombre, string $extension): string => '#^' . preg_quote(SettingsController::SETTINGS_UPLOADS_FOLDER . "/{$tipo}/{$nombre}-", '#') . '[0-9a-f]{10}\.' . $extension . '$#';

    $prefijo = 'zz-prueba-subidas-' . bin2hex(random_bytes(3));
    $ids = [];
    $jwt = null;
    $rutaBorrar = $camino(SettingsController::routeName('appearance-backgrounds-delete', [], true));
    $f = fn (string $nombre): string => SettingsController::LOGIN_BACKGROUNDS_FOLDER . "/{$nombre}";
    //Lo que Apache escribe en una carpeta suya el terminal no lo puede borrar: se retira por la aplicación, con Eliminar de
    //Fondos, que borra cualquier archivo de dentro de las subidas (deleteSettingsImage()). La opción se repone después.
    $retirar = function (string $archivo) use ($raiz, $poner, $pedir, $rutaBorrar, $f, &$jwt): bool {
        if (is_writable(dirname($archivo))) {
            return unlink($archivo);
        }
        $relativa = SettingsController::SETTINGS_UPLOADS_FOLDER . substr($archivo, strlen($raiz));
        if (is_string($jwt)) {
            $poner('backgrounds', (string) json_encode([$relativa, $f('bg1.jpg')]));
            $pedir($rutaBorrar, $jwt, ['background' => $relativa]);
        }
        clearstatcache();
        return !is_file($archivo);
    };

    try {
        //─── g · Las carpetas que se crean al subir ─────────────────────────────────────────────────
        echoTerminal('[g] Las carpetas que crea la subida quedan con escritura de grupo, aunque el umask la quite');
        //En una carpeta propia: la de las subidas puede ser de Apache y no admitir al terminal.
        $propia = dirname($raiz) . '/zz-prueba-carpetas-' . bin2hex(random_bytes(4));
        $anidada = "{$propia}/brand";
        $umaskAntes = umask(0022);
        try {
            $creada = SettingsController::makeGroupWritableFolder($anidada);
        } finally {
            umask($umaskAntes);
        }
        clearstatcache();
        $permisos = fn(string $carpeta): string => is_dir($carpeta) ? sprintf('%o', fileperms($carpeta) & 07777) : 'no existe';
        $check($creada && $permisos($propia) === '2775' && $permisos($anidada) === '2775', 'g1 DISCRIMINANTE: la carpeta y la que faltaba por encima, 2775', "{$permisos($propia)} · {$permisos($anidada)}");
        $check(SettingsController::makeGroupWritableFolder($anidada) && $permisos($anidada) === '2775', 'g2 sobre una que ya existe, nada cambia');
        foreach ([$anidada, $propia] as $carpeta) {
            if (is_dir($carpeta)) {
                //RETORNO-IGNORADO: lo comprueba g3.
                rmdir($carpeta);
            }
        }
        clearstatcache();
        $check(!is_dir($propia), 'g3 y la suite la retira');
        echoTerminal(' ');

        $check(is_dir(dirname($raiz)), 'banco: existe la carpeta de subidas');
        $check(count($fabricaAntes) > 10 && is_string($otro), 'banco: hay archivos de fábrica que vigilar y un segundo idioma', count($fabricaAntes) . ' · ' . var_export($otro, true));
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
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
        $ids[] = (int) $u->id;
        $jwt = UserSessions::issueToken((int) $u->id);

        $rutaFondos = $camino(SettingsController::routeName('appearance-backgrounds', [], true));
        $rutaMarca = $camino(SettingsController::routeName('appearance-brand-images', [], true));
        $rutaSeo = $camino(SettingsController::routeName('appearance-seo', [], true));
        $rutaCorreo = $camino(AdminPanelController::routeName('design-sheet-resources', ['source' => 'mail-users-template'], true));
        $rutaPanel = $camino(AdminPanelController::routeName('', [], true));

        //─── u · Las subidas ────────────────────────────────────────────────────────────────────────
        echoTerminal('[u] Cada subida va a su carpeta de subidas, con huella, y la de fábrica no cambia');
        $poner('backgrounds', (string) json_encode([$f('bg1.jpg'), $f('bg2.jpg'), $f('bg3.jpg'), $f('bg4.jpg'), $f('bg5.jpg')]));
        $r = $pedir($rutaFondos, $jwt, ['background-1' => new \CURLFile($imagen('jpg', 10), 'image/jpeg', 'zz-fondo.jpg')]);
        $lista = $valor('backgrounds');
        $nuevo1 = is_array($lista) && is_string($lista[0] ?? null) ? $lista[0] : '';
        $check(($r['data']['success'] ?? null) === true && preg_match($patron('login-backgrounds', 'bg1', 'jpg'), $nuevo1) === 1 && is_file(basepath($nuevo1)), 'u1 un fondo, en uploads/settings/login-backgrounds/bg1-<huella>.jpg, en su lugar de la lista', "HTTP {$r['status']} · {$nuevo1}");
        $check($huellasFabrica() === $fabricaAntes, 'u2 DISCRIMINANTE: con la misma extensión, el bg1.jpg de fábrica sigue igual');
        $r = $pedir($rutaFondos, $jwt, ['background-2' => new \CURLFile($imagen('webp', 20), 'image/webp', 'zz-fondo.webp')]);
        $lista = $valor('backgrounds');
        $nuevo2 = is_array($lista) && is_string($lista[1] ?? null) ? $lista[1] : '';
        $check(($r['data']['success'] ?? null) === true && preg_match($patron('login-backgrounds', 'bg2', 'webp'), $nuevo2) === 1, 'u3 otro fondo en WEBP, con su extensión', $nuevo2);
        $check(is_file(basepath($f('bg2.jpg'))) && $huellasFabrica() === $fabricaAntes, 'u4 DISCRIMINANTE: con otra extensión, el bg2.jpg de fábrica no se borra');
        $r = $pedir($rutaFondos, $jwt, ['background-1' => new \CURLFile($imagen('jpg', 30), 'image/jpeg', 'zz-fondo.jpg')]);
        $lista = $valor('backgrounds');
        $nuevo3 = is_array($lista) && is_string($lista[0] ?? null) ? $lista[0] : '';
        clearstatcache();
        $check(($r['data']['success'] ?? null) === true && $nuevo3 !== $nuevo1 && is_file(basepath($nuevo3)) && !is_file(basepath($nuevo1)), 'u5 DISCRIMINANTE: sustituir una subida borra la anterior, que sí es de las subidas', "{$nuevo1} → {$nuevo3}");
        $r = $pedir($rutaFondos, $jwt, ['problems-background' => new \CURLFile($imagen('jpg', 40), 'image/jpeg', 'zz-problemas.jpg')]);
        $problemas = $valor('backgoundProblems');
        $check(($r['data']['success'] ?? null) === true && is_string($problemas) && preg_match($patron('login-backgrounds', 'problems-background', 'jpg'), $problemas) === 1 && $huellasFabrica() === $fabricaAntes, 'u6 DISCRIMINANTE: el fondo de los problemas de inicio guarda su opción con la ruta nueva; el de fábrica intacto', var_export($problemas, true));
        $r = $pedir($rutaMarca, $jwt, ['logo' => new \CURLFile($imagen('png', 50), 'image/png', 'zz-logo.png')]);
        $logo = $valor('logo');
        $check(($r['data']['success'] ?? null) === true && is_string($logo) && preg_match($patron('brand', 'logo', 'png'), $logo) === 1 && is_file(basepath($logo)) && $huellasFabrica() === $fabricaAntes, 'u7 DISCRIMINANTE: el logo, en uploads/settings/brand/; el logo.png de fábrica intacto', var_export($logo, true));
        $seo = [
            'lang' => $lang,
            'titleApp' => "{$prefijo}-titulo",
            'owner' => "{$prefijo}-propietario",
            'description' => "{$prefijo}-descripcion",
        ];
        $r = $pedir($rutaSeo, $jwt, $seo + ['open-graph' => new \CURLFile($imagen('jpg', 60), 'image/jpeg', 'zz-og.jpg')]);
        $og = $valor(SettingsController::seoOptionName(SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE, $lang));
        $check(($r['data']['success'] ?? null) === true && is_string($og) && preg_match($patron('open-graph', 'open_graph', 'jpg'), $og) === 1 && is_file(basepath($og)) && $huellasFabrica() === $fabricaAntes, 'u8 DISCRIMINANTE: la imagen Open Graph, en uploads/settings/open-graph/; el open_graph.jpg de fábrica intacto', "HTTP {$r['status']} · " . var_export($og, true));
        echoTerminal(' ');

        //─── a · La animación de carga (H345) ───────────────────────────────────────────────────────
        echoTerminal('[a] La animación de carga: GIF o SVG por su contenido, el SVG por lista blanca, hasta 2 MB, con el nombre del servidor');
        $temporal = function (string $contenido) use (&$imagenes): string {
            $archivo = (string) tempnam(sys_get_temp_dir(), 'zz-anim-');
            if (file_put_contents($archivo, $contenido) === false) {
                throw new \RuntimeException('No se pudo escribir el temporal de la animación: ' . $archivo);
            }
            $imagenes[] = $archivo;
            return $archivo;
        };
        $svgFabrica = (string) file_get_contents(basepath('statics/images/loader-animation.svg'));
        $gifFabrica = (string) file_get_contents(basepath('statics/images/loader-animation.gif'));
        $poner('loaderAnimation', null);
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal(str_replace('Cargando', 'Zz cargando', $svgFabrica)), 'image/png', 'zz-animacion.png')]);
        $animacion = $valor('loaderAnimation');
        $check(($r['data']['success'] ?? null) === true && is_string($animacion) && preg_match($patron('brand', 'loader-animation', 'svg'), $animacion) === 1 && is_file(basepath($animacion)), 'a1 DISCRIMINANTE: un SVG permitido, mandado como «zz-animacion.png», se guarda como SVG y con el nombre del servidor', "HTTP {$r['status']} · " . var_export($animacion, true));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal($gifFabrica), 'image/svg+xml', 'zz-animacion.svg')]);
        $gif = $valor('loaderAnimation');
        clearstatcache();
        $check(($r['data']['success'] ?? null) === true && is_string($gif) && preg_match($patron('brand', 'loader-animation', 'gif'), $gif) === 1 && is_string($animacion) && !is_file(basepath($animacion)), 'a2 un GIF mandado como SVG se guarda como GIF, y la subida anterior se borra', var_export($gif, true));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal('<svg xmlns="http://www.w3.org/2000/svg"><rect onload="zz()"/></svg>'), 'image/svg+xml', 'zz.svg')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'El SVG trae algo que no está permitido (código, enlaces o recursos externos): se rechaza entero.'), 'a3 DISCRIMINANTE: un SVG con onload se rechaza entero, con su motivo, y la opción no cambia', (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($imagen('png', 80), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La animación tiene que ser un GIF o un SVG.'), 'a4 DISCRIMINANTE: un PNG mandado como GIF se rechaza por su contenido', (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal($gifFabrica . str_repeat("\0", SettingsController::LOADER_ANIMATION_MAX_BYTES)), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La animación pesa más de 2 MB.'), 'a5 DISCRIMINANTE: un GIF válido de más de 2 MB se rechaza por su tamaño', (string) ($r['data']['message'] ?? ''));
        $lienzoEnorme = substr($gifFabrica, 0, 6) . pack('vv', SettingsController::LOADER_ANIMATION_MAX_SIDE + 1, 64) . substr($gifFabrica, 10);
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal($lienzoEnorme), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La animación no puede pasar de 4096 × 4096 píxeles.'), 'a11 DISCRIMINANTE: un GIF que declara un lienzo de más de 4096 de ancho se rechaza', (string) ($r['data']['message'] ?? ''));
        //Un lienzo de 10 × 10 con un fotograma de 20000 × 20000 (la prueba de la auditoría): el lienzo solo no basta.
        $fotogramaEnorme = 'GIF89a' . pack('vv', 10, 10) . "\x80\x00\x00" . "\x00\x00\x00\xFF\x00\x00" . "\x21\xF9\x04\x00\x0A\x00\x00\x00" . "\x2C" . pack('vvvv', 0, 0, 20000, 20000) . "\x00" . "\x02\x02\x4C\x01\x00" . "\x3B";
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal($fotogramaEnorme), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La animación no puede pasar de 4096 × 4096 píxeles.'), 'a12 DISCRIMINANTE: un fotograma de 20000 × 20000 en un lienzo de 10 × 10 se rechaza', (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal(substr($gifFabrica, 0, -1)), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'El GIF está incompleto o mal formado.'), 'a13 DISCRIMINANTE: el GIF de fábrica sin su último byte (el trailer) se rechaza', (string) ($r['data']['message'] ?? ''));
        //El SVG, solo del principal (ROLES_SVG_BRAND_IMAGES); un administrador general entra a la pantalla y sube GIF.
        $admin = new UsersModel();
        $admin->username = "{$prefijo}-admin";
        $admin->email = "{$prefijo}-admin@example.invalid";
        $admin->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $admin->firstname = 'Zz';
        $admin->secondname = '';
        $admin->firstLastname = 'Subidas';
        $admin->secondLastname = '';
        $admin->type = UsersModel::TYPE_USER_ADMIN_GRAL;
        $admin->status = UsersModel::STATUS_USER_ACTIVE;
        $admin->failedAttempts = 0;
        $admin->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $admin->createdAt = new \DateTime();
        $admin->modifiedAt = $admin->createdAt;
        $admin->save();
        $ids[] = (int) $admin->id;
        $jwtAdmin = UserSessions::issueToken((int) $admin->id);
        $svgPermitido = str_replace('Cargando', 'Zz cargando', $svgFabrica);
        $r = $pedir($rutaMarca, $jwtAdmin, ['loaderAnimation' => new \CURLFile($temporal($svgPermitido), 'image/svg+xml', 'zz-animacion.svg')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderAnimation') === $gif && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'Solo el usuario principal puede subir una animación SVG; sube un GIF.'), 'a14 DISCRIMINANTE: un administrador general con un SVG permitido: rechazo con su motivo, y la opción no cambia', "HTTP {$r['status']} · " . (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($temporal($svgPermitido), 'image/svg+xml', 'zz-animacion.svg')]);
        $svgRoot = $valor('loaderAnimation');
        $check(($r['data']['success'] ?? null) === true && is_string($svgRoot) && preg_match($patron('brand', 'loader-animation', 'svg'), $svgRoot) === 1, 'a15 el principal, con el mismo SVG: se guarda', var_export($svgRoot, true));
        $r = $pedir($rutaMarca, $jwtAdmin, ['loaderAnimation' => new \CURLFile($temporal($gifFabrica), 'image/gif', 'zz-animacion.gif')]);
        $gif = $valor('loaderAnimation');
        $check(($r['data']['success'] ?? null) === true && is_string($gif) && preg_match($patron('brand', 'loader-animation', 'gif'), $gif) === 1, 'a16 el administrador general, con un GIF: se guarda', "HTTP {$r['status']} · " . var_export($gif, true));
        $aceptaAnimacion = function (?string $token) use ($pedir, $rutaMarca): string {
            return preg_match('#<input type="file" id="image-loader-animation-file" accept="([^"]*)"#', $pedir($rutaMarca, $token, null)['body'], $m) === 1 ? $m[1] : '(sin campo)';
        };
        $aceptaAdmin = $aceptaAnimacion($jwtAdmin);
        $aceptaRoot = $aceptaAnimacion($jwt);
        $check($aceptaAdmin === 'image/gif,.gif' && str_contains($aceptaRoot, 'image/svg+xml'), 'a17 el campo de la animación: sin SVG para el administrador general, con SVG para el principal', "{$aceptaAdmin} · {$aceptaRoot}");
        $oscuraAnimacion = SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['image'];
        $oscuraAntes = $valor($oscuraAnimacion);
        $r = $pedir($rutaMarca, $jwtAdmin, [$oscuraAnimacion => new \CURLFile($temporal($svgPermitido), 'image/svg+xml', 'zz-oscura.svg')]);
        $check(($r['data']['success'] ?? null) === false && $valor($oscuraAnimacion) === $oscuraAntes && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'Solo el usuario principal puede subir una animación SVG; sube un GIF.'), 'a18 DISCRIMINANTE: un administrador general con un SVG permitido en loaderAnimationDark: rechazo con su motivo, y la opción no cambia', "HTTP {$r['status']} · " . (string) ($r['data']['message'] ?? ''));
        $opcionOscura = SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['image'];
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['mode'], 'same');
        $r = $pedir($rutaMarca, $jwt, [$opcionOscura => new \CURLFile($temporal((string) file_get_contents(basepath('statics/images/loader-animation-dark.svg'))), 'image/svg+xml', 'zz-oscura.svg')]);
        $oscura = $valor($opcionOscura);
        $check(($r['data']['success'] ?? null) === true && is_string($oscura) && preg_match($patron('brand', 'loader-animation-dark', 'svg'), $oscura) === 1, 'a6 la oscura, en su opción y con su nombre', var_export($oscura, true));
        $check($crudo(SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['mode']) === 'image', 'a8 DISCRIMINANTE: subir la oscura de la animación guarda su modo «Otra imagen» en la misma petición', var_export($crudo(SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['mode']), true));
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode'], null);
        $r = $pedir($rutaMarca, $jwt, [SettingsController::BRAND_IMAGES_DARK_MODE['logo']['image'] => new \CURLFile($imagen('png', 90), 'image/png', 'zz-logo-oscuro.png')]);
        $logoOscuro = $valor(SettingsController::BRAND_IMAGES_DARK_MODE['logo']['image']);
        $check(($r['data']['success'] ?? null) === true && is_string($logoOscuro) && preg_match($patron('brand', 'logo-dark', 'png'), $logoOscuro) === 1 && $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode']) === 'image', 'a9 DISCRIMINANTE: subir el logo oscuro (por el recortador) lo guarda y guarda «Otra imagen»', var_export([$logoOscuro, $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode'])], true));
        $r = $pedir($rutaMarca, $jwt, [SettingsController::BRAND_IMAGES_DARK_MODE['logo']['image'] => new \CURLFile($imagen('png', 100), 'image/png', 'zz-logo-oscuro.png')]);
        $check(($r['data']['success'] ?? null) === true && $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode']) === 'image', 'a10 una segunda oscura con el modo ya en «Otra imagen» también se guarda sin error', (string) ($r['data']['message'] ?? ''));
        $panel = $pedir($rutaPanel, $jwt, null);
        $check($panel['status'] === 200 && preg_match('#<html[^>]* data-loader-animation="' . preg_quote(is_string($gif) ? $gif : '', '#') . '"#', $panel['body']) === 1, 'a7 el <html> del panel lleva la animación subida en data-loader-animation', "HTTP {$panel['status']}");
        echoTerminal(' ');

        //─── d · La vista previa oscura enseña la oscura EFECTIVA (#3104) ───────────────────────────────
        echoTerminal('[d] La vista previa oscura y su «Descargar», lo mismo que pintan el cargador y brandImageTag()');
        $vistaOscura = function (string $html, string $tarjeta): array {
            $documento = new \DOMDocument();
            @$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $x = new \DOMXPath($documento);
            $img = $x->query("//*[@id='{$tarjeta}-dark']//div[contains(@class,'checkered')]//img")?->item(0);
            $descarga = $x->query("//*[@id='{$tarjeta}-dark']//a[@data-image-card-download]")?->item(0);
            $sinSello = fn (string $url): string => (string) preg_replace('/\?cacheStamp=.*$/', '', $url);
            return [
                'src' => $img instanceof \DOMElement ? $sinSello($img->getAttribute('src')) : '',
                'descarga' => $descarga instanceof \DOMElement ? $sinSello($descarga->getAttribute('href')) : '',
                'invertida' => $img instanceof \DOMElement && str_contains($img->getAttribute('class'), 'content-image-card-dark-invert'),
            ];
        };
        $modoDe = fn (string $clave): string => SettingsController::BRAND_IMAGES_DARK_MODE[$clave]['mode'];
        $oscuraDe = fn (string $clave): string => SettingsController::BRAND_IMAGES_DARK_MODE[$clave]['image'];
        $poner('loaderAnimation', null);
        $poner($oscuraDe('loaderAnimation'), null);
        $poner($modoDe('loaderAnimation'), null);
        $poner('logo', 'statics/images/logo.png');
        $poner($oscuraDe('logo'), null);
        $poner($modoDe('logo'), null);
        //Una bandera ajena: la de fábrica ya trae su oscura (P377) y d3 necesita una sin ella.
        $poner('partners', 'statics/images/framework/developer_by_logo.png');
        $poner($oscuraDe('partners'), null);
        $poner($modoDe('partners'), 'image');
        $pantalla = $pedir($rutaMarca, $jwt, null)['body'];
        $animacion = $vistaOscura($pantalla, 'image-loader-animation');
        $check($animacion['src'] === 'statics/images/loader-animation-dark.svg' && $animacion['descarga'] === $animacion['src'] && str_contains($pantalla, 'data-loader-animation-dark="statics/images/loader-animation-dark.svg"'), 'd1 la animación de fábrica sin oscura guardada: vista previa, «Descargar» y cargador, loader-animation-dark.svg', json_encode($animacion) ?: '');
        $logoOscuro = $vistaOscura($pantalla, 'image-logo');
        $check($logoOscuro['src'] === 'statics/images/white-logo.png' && $logoOscuro['descarga'] === $logoOscuro['src'], 'd2 el logo de fábrica sin oscura guardada: white-logo.png', json_encode($logoOscuro) ?: '');
        $bandera = $vistaOscura($pantalla, 'image-partners');
        $check($bandera['src'] === 'statics/images/framework/developer_by_logo.png' && $bandera['descarga'] === $bandera['src'] && !$bandera['invertida'], 'd3 DISCRIMINANTE: «Otra imagen» sin oscura (una bandera ajena): la clara, y «Descargar» también la clara (antes, sin enlace)', json_encode($bandera) ?: '');
        $poner('partners', 'statics/images/partners.png');
        $poner($modoDe('partners'), null);
        //Con la opción vieja a 0 guardada: la oscura de fábrica gana igual, como en el logo.
        $poner(SettingsController::BRAND_IMAGES_INVERT_ON_DARK['partners'], '0');
        $banderaDeFabrica = $vistaOscura($pedir($rutaMarca, $jwt, null)['body'], 'image-partners');
        $check($banderaDeFabrica['src'] === 'statics/images/partners-dark.png' && $banderaDeFabrica['descarga'] === $banderaDeFabrica['src'] && !$banderaDeFabrica['invertida'], 'd5 DISCRIMINANTE: la bandera de fábrica sin modo: su oscura de fábrica, partners-dark.png (P377)', json_encode($banderaDeFabrica) ?: '');
        $poner($modoDe('logo'), 'invert');
        $logoInvertido = $vistaOscura($pedir($rutaMarca, $jwt, null)['body'], 'image-logo');
        $check($logoInvertido['src'] === 'statics/images/logo.png' && $logoInvertido['descarga'] === 'statics/images/logo.png' && $logoInvertido['invertida'], 'd4 DISCRIMINANTE: con «Invertir en blanco», la clara con el filtro, y «Descargar» la clara (antes, white-logo.png)', json_encode($logoInvertido) ?: '');
        echoTerminal(' ');

        //─── l · El «Logo del cargador» (H345): opcional, vacío de fábrica y con «Eliminar» ─────────────
        echoTerminal('[l] El logo del cargador: de fábrica la «P» (loader-logo.png); se sube por el recortador; ya no se elimina');
        $atributoLogo = function (string $sufijo = '') use ($pedir, $rutaPanel, &$jwt): ?string {
            $html = $pedir($rutaPanel, $jwt, null)['body'];
            return preg_match('#<html[^>]* data-loader-logo' . $sufijo . '="([^"]*)"#', $html, $m) === 1 ? $m[1] : null;
        };
        foreach (['loaderLogo', SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image'], SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['mode']] as $opcionLogoCargador) {
            $poner($opcionLogoCargador, null);
        }
        $check($atributoLogo() === 'statics/images/loader-logo.png' && $atributoLogo('-dark') === 'statics/images/white-logo.png', 'l1 DISCRIMINANTE: sin opción, el cargador pinta la «P» de fábrica y, en oscuro, white-logo.png', var_export([$atributoLogo(), $atributoLogo('-dark')], true));
        $r = $pedir($rutaMarca, $jwt, ['loaderLogo' => new \CURLFile($imagen('png', 110), 'image/png', 'zz-logo-cargador.png')]);
        $logoCargador = $valor('loaderLogo');
        $check(($r['data']['success'] ?? null) === true && is_string($logoCargador) && preg_match($patron('brand', 'loader-logo', 'png'), $logoCargador) === 1 && $atributoLogo() === $logoCargador, 'l2 subido por el recortador, en uploads/settings/brand/loader-logo-<huella>.png, y el cargador lo recibe', var_export($logoCargador, true));
        $r = $pedir($rutaMarca, $jwt, [SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image'] => new \CURLFile($imagen('png', 120), 'image/png', 'zz-logo-cargador-oscuro.png')]);
        $logoCargadorOscuro = $valor(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image']);
        $check(($r['data']['success'] ?? null) === true && is_string($logoCargadorOscuro) && $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['mode']) === 'image', 'l3 su oscura, con «Otra imagen»', var_export($logoCargadorOscuro, true));
        $mensajeSinEliminar = __(SettingsController::LANG_GROUP, 'Las imágenes de marca ya no se eliminan: cámbiala por otra o apágala con su interruptor en «Qué enseña el cargador».');
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'loaderLogo']);
        clearstatcache();
        $check($r['status'] === 400 && ($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? null) === $mensajeSinEliminar && $crudo('loaderLogo') === $logoCargador && is_string($logoCargador) && is_file(basepath($logoCargador)) && is_string($logoCargadorOscuro) && is_file(basepath($logoCargadorOscuro)), 'l4 DISCRIMINANTE: deleteBrandImage, en desuso, responde 400 con su motivo y no toca la opción ni los archivos', "HTTP {$r['status']} · " . (string) ($r['data']['message'] ?? ''));
        //Lo que guardaba el «Eliminar» de antes, '', sigue apagando hasta que la actualización lo limpie (BRAND_IMAGES_EMPTY_IS_OFF).
        $poner('loaderLogo', '');
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image'], null);
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['mode'], null);
        $check($atributoLogo() === '' && $atributoLogo('-dark') === '', 'l6 DISCRIMINANTE: guardado vacío (lo de antes), el cargador no pinta logo ni en claro ni en oscuro (no vuelve la «P»)', var_export([$atributoLogo(), $atributoLogo('-dark')], true));
        $pantallaApagada = $pedir($rutaMarca, $jwt, null)['body'];
        $rotuloApagado = escape_html(__(SettingsController::LANG_GROUP, 'Sin imagen: el cargador enseña solo la animación'));
        $check(preg_match('#id="image-loader-logo".*?data-image-card-empty>\s*<img width="400" height="400"[^>]*>\s*<span class="ui muted text">' . preg_quote($rotuloApagado, '#') . '#s', $pantallaApagada) === 1 && preg_match('#data-brand-dark-empty>\s*<img width="400" height="400"[^>]*>\s*<span class="ui muted text">' . preg_quote($rotuloApagado, '#') . '#', $pantallaApagada) === 1, 'l7 la tarjeta apagada: damero de 400 × 400 con su rótulo, en la vista clara y en la oscura (visible)');
        $r = $pedir($rutaMarca, $jwt, ['loaderLogo' => new \CURLFile($imagen('png', 130), 'image/png', 'zz-logo-cargador-2.png')]);
        $logoCargador2 = $valor('loaderLogo');
        $check(($r['data']['success'] ?? null) === true && is_string($logoCargador2) && preg_match($patron('brand', 'loader-logo', 'png'), $logoCargador2) === 1 && $atributoLogo() === $logoCargador2, 'l8 tras guardarlo vacío, subir otro lo vuelve a pintar', var_export($logoCargador2, true));
        $logoAntes = $crudo('logo');
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'logo']);
        $check($r['status'] === 400 && ($r['data']['success'] ?? null) === false && $crudo('logo') === $logoAntes, 'l5 tampoco el logo de la plataforma: 400 y no se quita', "HTTP {$r['status']} · " . (string) ($r['data']['message'] ?? ''));
        echoTerminal(' ');

        //─── p · La pantalla de carga (H417) y «Eliminar» de la animación ───────────────────────────────
        echoTerminal('[p] La pantalla de carga: GIF, PNG, JPG, WebP o SVG (solo root) por su contenido, hasta 3 MB; vacía, apagada');
        //Los data-loader-* del <html> del panel, sin escapar: lo que lee GenericLoaderElement.js.
        $atributosCargador = function () use ($pedir, $rutaPanel, &$jwt): array {
            $html = $pedir($rutaPanel, $jwt, null)['body'];
            $etiqueta = preg_match('#<html[^>]*>#', $html, $m) === 1 ? $m[0] : '';
            $atributos = [];
            if (preg_match_all('# (data-loader-[a-z-]+)="([^"]*)"#', $etiqueta, $todos, \PREG_SET_ORDER) === false) {
                return $atributos;
            }
            foreach ($todos as $par) {
                $atributos[$par[1]] = html_entity_decode($par[2], \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
            }
            return $atributos;
        };
        $gifPequeno = function (): string {
            $lienzo = imagecreatetruecolor(8, 8);
            ob_start();
            if ($lienzo !== false) {
                imagegif($lienzo);
            }
            return (string) ob_get_clean();
        };
        $oscuraPantalla = SettingsController::BRAND_IMAGES_DARK_MODE['loaderScreen']['image'];
        $modoPantalla = SettingsController::BRAND_IMAGES_DARK_MODE['loaderScreen']['mode'];
        foreach (['loaderScreen', 'loaderScreenMobile'] as $pantallaCarga) {
            foreach ([$pantallaCarga, SettingsController::BRAND_IMAGES_DARK_MODE[$pantallaCarga]['image'], SettingsController::BRAND_IMAGES_DARK_MODE[$pantallaCarga]['mode']] as $opcionPantalla) {
                $poner($opcionPantalla, null);
            }
        }
        //Los interruptores, de fábrica (encendidos): la base local puede traerlos apagados; se reponen al final.
        foreach (SettingsController::LOADER_LAYERS_ENABLED as $opcionCapa) {
            $poner($opcionCapa, null);
        }
        $at = $atributosCargador();
        $check(($at['data-loader-screen'] ?? null) === 'statics/images/loader-screen.svg' && ($at['data-loader-screen-dark'] ?? null) === 'statics/images/loader-screen-dark.svg' && ($at['data-loader-screen-mobile'] ?? null) === 'statics/images/loader-screen-mobile.svg' && ($at['data-loader-screen-mobile-dark'] ?? null) === 'statics/images/loader-screen-mobile-dark.svg' && ($at['data-loader-screen-invert'] ?? null) === '0', 'p1 DISCRIMINANTE: sin opción, las pantallas de fábrica (SVG animadas) y sus oscuras, en escritorio y en móvil', json_encode($at) ?: '');
        //Los sha1 de fábrica, en proceso: sin opción es la de fábrica; con otra ruta, no.
        $configPantallas = [get_config('loaderScreen'), get_config('loaderScreenMobile')];
        set_config('loaderScreen', false);
        set_config('loaderScreenMobile', false);
        SettingsController::clearBrandImageCache();
        $deFabrica = SettingsController::isFactoryBrandImage('loaderScreen') && SettingsController::isFactoryBrandImage('loaderScreenMobile') && SettingsController::brandImageDarkImage('loaderScreen') === 'statics/images/loader-screen-dark.svg' && SettingsController::brandImageDarkImage('loaderScreenMobile') === 'statics/images/loader-screen-mobile-dark.svg';
        set_config('loaderScreen', 'statics/images/loader-logo.png');
        SettingsController::clearBrandImageCache();
        $ajena = SettingsController::isFactoryBrandImage('loaderScreen') || SettingsController::brandImageDarkImage('loaderScreen') !== '';
        set_config('loaderScreen', $configPantallas[0]);
        set_config('loaderScreenMobile', $configPantallas[1]);
        SettingsController::clearBrandImageCache();
        $sha1Pantallas = array_map(fn (string $n): string => (string) sha1_file(basepath("statics/images/{$n}.svg")), ['loader-screen', 'loader-screen-mobile']);
        $check($deFabrica && !$ajena && $sha1Pantallas === [SettingsController::FACTORY_BRAND_IMAGES['loaderScreen'][0], SettingsController::FACTORY_BRAND_IMAGES['loaderScreenMobile'][0]], 'p1b DISCRIMINANTE: isFactoryBrandImage() reconoce las dos de fábrica por su sha1 y les da su oscura de fábrica; otra imagen no es de fábrica ni hereda la oscura', json_encode([$deFabrica, $ajena, $sha1Pantallas]) ?: '');
        //Un usuario principal que las descargue y las vuelva a subir: pasan la lista blanca de SVG tal cual.
        $rechazos = array_map(fn (string $n): ?string => \PiecesPHP\Settings\Uploads\SvgWhitelistValidator::rejectionReason((string) file_get_contents(basepath("statics/images/{$n}.svg"))), ['loader-screen', 'loader-screen-dark', 'loader-screen-mobile', 'loader-screen-mobile-dark']);
        $check($rechazos === [null, null, null, null], 'p1c las cuatro SVG de fábrica pasan SvgWhitelistValidator', json_encode($rechazos) ?: '');
        //Sin franja (PO): ninguna regla de helpers.scss da a la caja otro aspecto con pantalla; el fondo calculado se mide en el navegador.
        $hojaCargador = (string) file_get_contents(basepath('statics/core/sass/helpers.scss'));
        $check($hojaCargador !== '' && preg_match('/-with-screen[^{]*\.#\{\$globalClassPcs\}-box\s*\{/', $hojaCargador) !== 1, 'p1d DISCRIMINANTE: con pantalla, la caja no tiene regla propia en helpers.scss (sin franja)');
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($imagen('png', 140), 'image/gif', 'zz-pantalla.gif')]);
        $pantalla = $valor('loaderScreen');
        $at = $atributosCargador();
        $check(($r['data']['success'] ?? null) === true && is_string($pantalla) && preg_match($patron('brand', 'loader-screen', 'png'), $pantalla) === 1 && ($at['data-loader-screen'] ?? null) === $pantalla, 'p2 DISCRIMINANTE: un PNG mandado como «zz-pantalla.gif» se guarda como PNG, con el nombre del servidor, y el cargador lo recibe', "HTTP {$r['status']} · " . var_export($pantalla, true) . ' · ' . (string) ($r['data']['message'] ?? ''));
        $check(($at['data-loader-screen-mobile'] ?? null) === '' && ($at['data-loader-screen-mobile-dark'] ?? null) === '' && ($at['data-loader-screen-mobile-invert'] ?? null) === '0', 'p3 DISCRIMINANTE: con una de escritorio propia y sin la de móvil, la de móvil va vacía (ni la de fábrica ni su oscura): en vertical, la de escritorio recortada', json_encode($at) ?: '');
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($imagen('jpg', 150), 'image/png', 'zz-pantalla.png')]);
        $jpg = $valor('loaderScreen');
        clearstatcache();
        $check(($r['data']['success'] ?? null) === true && is_string($jpg) && preg_match($patron('brand', 'loader-screen', 'jpg'), $jpg) === 1 && is_string($pantalla) && !is_file(basepath($pantalla)), 'p4 un JPG, con su extensión, y la subida anterior se borra', var_export($jpg, true));
        //Una foto con EXIF (GPS) y un comentario: su URL va en las cabeceras públicas, así que se guarda sin ellos (ImageMetadataStripper).
        $jpgBase = (string) file_get_contents($imagen('jpg', 155));
        $gps = 'zz-gps-4.6097-74.0817';
        $jpgExif = "\xFF\xD8" . "\xFF\xE1" . pack('n', 2 + strlen("Exif\0\0MM\0*{$gps}")) . "Exif\0\0MM\0*{$gps}" . "\xFF\xFE" . pack('n', 2 + strlen($gps)) . $gps . substr($jpgBase, 2);
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($jpgExif), 'image/jpeg', 'zz-foto.jpg')]);
        $jpgGuardado = $valor('loaderScreen');
        $bytesGuardados = is_string($jpgGuardado) && is_file(basepath($jpgGuardado)) ? (string) file_get_contents(basepath($jpgGuardado)) : '';
        $medidaGuardada = $bytesGuardados !== '' ? getimagesizefromstring($bytesGuardados) : false;
        $check(($r['data']['success'] ?? null) === true && $bytesGuardados !== '' && !str_contains($bytesGuardados, $gps) && !str_contains($bytesGuardados, "Exif\0\0") && is_array($medidaGuardada) && $medidaGuardada[0] === 8 && $medidaGuardada[1] === 8, 'p26 DISCRIMINANTE: un JPG con EXIF y comentario se guarda sin ellos, y sigue siendo de 8 × 8', var_export($jpgGuardado, true) . ' · ' . strlen($bytesGuardados) . ' bytes · ' . (str_contains($bytesGuardados, $gps) ? 'con el GPS' : 'sin el GPS'));
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($imagen('webp', 160), 'image/jpeg', 'zz-pantalla.jpg')]);
        $webp = $valor('loaderScreen');
        $check(($r['data']['success'] ?? null) === true && is_string($webp) && preg_match($patron('brand', 'loader-screen', 'webp'), $webp) === 1, 'p5 un WebP, por su contenido', var_export($webp, true));
        $r = $pedir($rutaMarca, $jwt, ['loaderScreenMobile' => new \CURLFile($temporal($gifPequeno()), 'image/png', 'zz-movil.png')]);
        $movil = $valor('loaderScreenMobile');
        $at = $atributosCargador();
        $check(($r['data']['success'] ?? null) === true && is_string($movil) && preg_match($patron('brand', 'loader-screen-mobile', 'gif'), $movil) === 1 && ($at['data-loader-screen-mobile'] ?? null) === $movil && ($at['data-loader-screen'] ?? null) === $webp, 'p6 con las dos propias, la de móvil (un GIF) en su opción y en su atributo, y la de escritorio sigue', var_export($movil, true));
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal('zz no es una imagen'), 'image/png', 'zz-pantalla.png')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderScreen') === $webp && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La pantalla de carga tiene que ser un GIF, PNG, JPG, WebP o SVG.'), 'p7 DISCRIMINANTE: un texto mandado como PNG se rechaza por su contenido y la opción no cambia', (string) ($r['data']['message'] ?? ''));
        $check(($pedir($rutaMarca, $jwt, ['loaderAnimation' => new \CURLFile($imagen('png', 170), 'image/png', 'zz.png')])['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La animación tiene que ser un GIF o un SVG.'), 'p8 la animación sigue sin admitir PNG: las fijas son solo de la pantalla de carga');
        //Un GIF y un PNG de pocos bytes que DECLARAN un lienzo mayor que el lado máximo: se miden en la cabecera, sin decodificar.
        $gifGrande = substr($gifPequeno(), 0, 6) . pack('vv', SettingsController::LOADER_ANIMATION_MAX_SIDE + 1, 8) . substr($gifPequeno(), 10);
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($gifGrande), 'image/gif', 'zz.gif')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderScreen') === $webp && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La pantalla de carga no puede pasar de 4096 × 4096 píxeles.'), 'p9 DISCRIMINANTE: un GIF de ' . strlen($gifGrande) . ' bytes que declara un lienzo de ' . (SettingsController::LOADER_ANIMATION_MAX_SIDE + 1) . ' de ancho se rechaza', (string) ($r['data']['message'] ?? ''));
        $pngBase = (string) file_get_contents($imagen('png', 180));
        $pngGrande = substr($pngBase, 0, 16) . pack('N', SettingsController::LOADER_ANIMATION_MAX_SIDE + 1) . substr($pngBase, 20);
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($pngGrande), 'image/png', 'zz.png')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderScreen') === $webp && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La pantalla de carga no puede pasar de 4096 × 4096 píxeles.'), 'p10 DISCRIMINANTE: un PNG de ' . strlen($pngGrande) . ' bytes que declara ' . (SettingsController::LOADER_ANIMATION_MAX_SIDE + 1) . ' de ancho se rechaza', (string) ($r['data']['message'] ?? ''));
        //El tope: un PNG válido con relleno al final, sin decodificar nada; uno justo por encima y otro justo por debajo.
        $tope = SettingsController::LOADER_SCREEN_MAX_BYTES;
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($pngBase . str_repeat("\0", $tope + 1 - strlen($pngBase))), 'image/png', 'zz.png')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderScreen') === $webp && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La pantalla de carga pesa más de 3 MB.'), 'p11 DISCRIMINANTE: un PNG de ' . ($tope + 1) . ' bytes (el tope más uno) se rechaza por su peso', (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($pngBase . str_repeat("\0", $tope - 1 - strlen($pngBase))), 'image/png', 'zz.png')]);
        $justo = $valor('loaderScreen');
        $check(($r['data']['success'] ?? null) === true && is_string($justo) && preg_match($patron('brand', 'loader-screen', 'png'), $justo) === 1 && (string) file_get_contents(basepath($justo)) === $pngBase, 'p12 un PNG de ' . ($tope - 1) . ' bytes (el tope menos uno) se acepta: se guarda la imagen byte a byte, sin el relleno que seguía a IEND', (string) ($r['data']['message'] ?? '') . ' · ' . var_export($justo, true));
        //El SVG de la pantalla, solo del principal.
        $r = $pedir($rutaMarca, $jwtAdmin, ['loaderScreen' => new \CURLFile($temporal($svgPermitido), 'image/svg+xml', 'zz.svg')]);
        $check(($r['data']['success'] ?? null) === false && $valor('loaderScreen') === $justo && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'Solo el usuario principal puede subir una pantalla de carga SVG; sube un GIF, PNG, JPG o WebP.'), 'p13 DISCRIMINANTE: un administrador general con un SVG permitido: rechazo con su motivo', (string) ($r['data']['message'] ?? ''));
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($temporal($svgPermitido), 'image/svg+xml', 'zz.svg')]);
        $svgPantalla = $valor('loaderScreen');
        $check(($r['data']['success'] ?? null) === true && is_string($svgPantalla) && preg_match($patron('brand', 'loader-screen', 'svg'), $svgPantalla) === 1, 'p14 el principal, con el mismo SVG: se guarda', var_export($svgPantalla, true));
        $r = $pedir($rutaMarca, $jwtAdmin, ['loaderScreen' => new \CURLFile($imagen('png', 190), 'image/png', 'zz.png')]);
        $pngAdmin = $valor('loaderScreen');
        $check(($r['data']['success'] ?? null) === true && is_string($pngAdmin) && preg_match($patron('brand', 'loader-screen', 'png'), $pngAdmin) === 1, 'p15 el administrador general, con un PNG: se guarda', (string) ($r['data']['message'] ?? ''));
        $aceptaPantalla = fn (?string $token): string => preg_match('#<input type="file" id="image-loader-screen-file" accept="([^"]*)"#', $pedir($rutaMarca, $token, null)['body'], $m) === 1 ? $m[1] : '(sin campo)';
        $aceptaAdmin = $aceptaPantalla($jwtAdmin);
        $aceptaRoot = $aceptaPantalla($jwt);
        $check(str_contains($aceptaAdmin, 'image/webp') && !str_contains($aceptaAdmin, 'svg') && str_contains($aceptaRoot, 'image/svg+xml'), 'p16 el campo de la pantalla: GIF, PNG, JPG y WebP; SVG solo para el principal', "{$aceptaAdmin} · {$aceptaRoot}");
        //Los tres modos de oscuro, con las opciones de siempre.
        $r = $pedir($rutaMarca, $jwt, [$oscuraPantalla => new \CURLFile($imagen('png', 200), 'image/png', 'zz-oscura.png')]);
        $oscura = $valor($oscuraPantalla);
        $at = $atributosCargador();
        $check(($r['data']['success'] ?? null) === true && is_string($oscura) && preg_match($patron('brand', 'loader-screen-dark', 'png'), $oscura) === 1 && $crudo($modoPantalla) === 'image' && ($at['data-loader-screen-dark'] ?? null) === $oscura && ($at['data-loader-screen-invert'] ?? null) === '0', 'p17 DISCRIMINANTE: su oscura guarda «Otra imagen» y el cargador la recibe en data-loader-screen-dark', var_export([$oscura, $at['data-loader-screen-dark'] ?? null], true));
        $pedir($rutaMarca, $jwt, ['darkMode' => 'loaderScreen', 'mode' => 'invert']);
        $at = $atributosCargador();
        $check(($at['data-loader-screen-invert'] ?? null) === '1' && ($at['data-loader-screen-dark'] ?? null) === '', 'p18 con «Invertir en blanco», data-loader-screen-invert="1" y sin oscura', json_encode($at) ?: '');
        $pedir($rutaMarca, $jwt, ['darkMode' => 'loaderScreen', 'mode' => 'same']);
        $at = $atributosCargador();
        $check(($at['data-loader-screen-invert'] ?? null) === '0' && ($at['data-loader-screen-dark'] ?? null) === '', 'p19 con «Igual», ni oscura ni inversión', json_encode($at) ?: '');
        //loaderHtmlAttributes() con todo, y lo mismo por `?loader=yes`, que lee el área de pruebas.
        $json = $pedir($rutaMarca . '?loader=yes', $jwt, null)['data'];
        $nombres = [];
        foreach (['animation', 'logo', 'screen', 'screen-mobile'] as $capa) {
            array_push($nombres, "data-loader-{$capa}", "data-loader-{$capa}-dark", "data-loader-{$capa}-invert");
        }
        foreach (array_keys(SettingsController::LOADER_LAYERS_ENABLED) as $capa) {
            $nombres[] = "data-loader-{$capa}-enabled";
        }
        $check(array_keys($at) === $nombres && ($json['attributes'] ?? null) === $at, 'p20 el <html> lleva los doce data-loader-* de las imágenes y los tres de los interruptores, en orden, y `?loader=yes` devuelve los mismos', json_encode([array_keys($at), $json['attributes'] ?? null]) ?: '');
        //Ya no hay «Eliminar»: la pantalla y su oscura siguen; deleteBrandImage responde 400.
        $pantallaAntes = $valor('loaderScreen');
        $pedir($rutaMarca, $jwt, ['darkMode' => 'loaderScreen', 'mode' => 'image']);
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'loaderScreen']);
        clearstatcache();
        $at = $atributosCargador();
        $check($r['status'] === 400 && ($r['data']['message'] ?? null) === $mensajeSinEliminar && $valor('loaderScreen') === $pantallaAntes && is_string($pantallaAntes) && is_file(basepath($pantallaAntes)) && is_string($oscura) && is_file(basepath($oscura)) && ($at['data-loader-screen'] ?? null) === $pantallaAntes && ($at['data-loader-screen-dark'] ?? null) === $oscura, 'p21 DISCRIMINANTE: deleteBrandImage de la pantalla: 400, y la pantalla, su oscura y sus archivos siguen', "HTTP {$r['status']} · " . json_encode($at));
        foreach (['loaderScreen', $oscuraPantalla, $modoPantalla] as $opcionPantalla) {
            $poner($opcionPantalla, null);
        }
        $poner('loaderAnimation', null);
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['image'], null);
        $poner(SettingsController::BRAND_IMAGES_DARK_MODE['loaderAnimation']['mode'], null);
        $at = $atributosCargador();
        $check(($at['data-loader-animation'] ?? null) === 'statics/images/loader-animation.svg' && ($at['data-loader-animation-dark'] ?? null) === 'statics/images/loader-animation-dark.svg', 'p22 sin opción, la animación de fábrica y su oscura de fábrica', json_encode($at) ?: '');
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'loaderAnimation']);
        $at = $atributosCargador();
        $check($r['status'] === 400 && $crudo('loaderAnimation') === null && ($at['data-loader-animation'] ?? null) === 'statics/images/loader-animation.svg' && $huellasFabrica() === $fabricaAntes, 'p23 DISCRIMINANTE: deleteBrandImage de la animación: 400, sin opción escrita, y el cargador sigue con la de fábrica', "HTTP {$r['status']} · " . json_encode($at));
        $vistaMarca = $pedir($rutaMarca, $jwt, null)['body'];
        $check(!str_contains($vistaMarca, 'data-image-card-delete') && preg_match('#data-loader-test="screen">#', $vistaMarca) === 1, 'p24 DISCRIMINANTE: la pantalla de Imágenes de marca no tiene ningún «Eliminar»; con pantalla, «Con la pantalla de carga» se puede lanzar', substr_count($vistaMarca, 'data-image-card-delete') . ' «Eliminar»');
        //Sin ninguna pantalla solo queda lo guardado vacío por el «Eliminar» de antes: sin opción, la de fábrica.
        $poner('loaderScreen', '');
        $poner('loaderScreenMobile', '');
        $check(preg_match('#data-loader-test="screen" disabled#', $pedir($rutaMarca, $jwt, null)['body']) === 1, 'p25 con las dos guardadas vacías (sin ninguna pantalla), «Con la pantalla de carga» va deshabilitado');
        echoTerminal(' ');

        //─── i · Los interruptores del cargador: encendidos de fábrica, apagan sin borrar ─────────────
        echoTerminal('[i] «Qué enseña el cargador»: un interruptor por capa, guardado al cambiarlo; apagar no borra la imagen');
        foreach (SettingsController::LOADER_LAYERS_ENABLED as $opcionCapa) {
            $poner($opcionCapa, null);
        }
        $r = $pedir($rutaMarca, $jwt, ['loaderScreen' => new \CURLFile($imagen('png', 210), 'image/png', 'zz-pantalla-i.png')]);
        $pantallaI = $valor('loaderScreen');
        $at = $atributosCargador();
        $encendidas = array_filter(array_keys(SettingsController::LOADER_LAYERS_ENABLED), fn (string $capa): bool => ($at["data-loader-{$capa}-enabled"] ?? null) === '1');
        $check(count($encendidas) === 3 && is_string($pantallaI) && ($at['data-loader-screen'] ?? null) === $pantallaI, 'i1 DISCRIMINANTE: sin opción, las tres capas (animación, logo y pantalla) encendidas (data-loader-*-enabled="1")', json_encode($at) ?: '');
        $vistaMarca = $pedir($rutaMarca, $jwt, null)['body'];
        $interruptores = preg_match_all('#<div class="ui toggle checkbox">\s*<input type="checkbox" id="loader-layer-([a-z]+)" data-loader-layer="\1"[^>]* checked>#', $vistaMarca, $m) === false ? [] : $m[1];
        $check($interruptores === array_keys(SettingsController::LOADER_LAYERS_ENABLED), 'i2 la pestaña Cargador: un `ui toggle checkbox` por capa, los tres marcados', implode(',', $interruptores));
        $imagenDeCapa = ['animation' => 'loaderAnimation', 'logo' => 'loaderLogo', 'screen' => 'loaderScreen'];
        foreach (SettingsController::LOADER_LAYERS_ENABLED as $capa => $opcionCapa) {
            $imagenAntes = $crudo($imagenDeCapa[$capa]);
            $atributoAntes = $atributosCargador()["data-loader-{$capa}"] ?? null;
            $r = $pedir($rutaMarca, $jwt, ['loaderLayer' => $capa, 'enabled' => '0']);
            $at = $atributosCargador();
            $sinBorrar = $crudo($imagenDeCapa[$capa]) === $imagenAntes && ($at["data-loader-{$capa}"] ?? null) === $atributoAntes;
            $check(($r['data']['success'] ?? null) === true && $crudo($opcionCapa) === '0' && ($at["data-loader-{$capa}-enabled"] ?? null) === '0' && $sinBorrar, "i3 DISCRIMINANTE: apagar «{$capa}» guarda {$opcionCapa} = 0 y data-loader-{$capa}-enabled=\"0\", sin borrar ni cambiar su imagen", (string) ($r['data']['message'] ?? '') . ' · ' . var_export([$crudo($opcionCapa), $atributoAntes, $at["data-loader-{$capa}"] ?? null], true));
            $r = $pedir($rutaMarca, $jwt, ['loaderLayer' => $capa, 'enabled' => '1']);
            $check(($r['data']['success'] ?? null) === true && $crudo($opcionCapa) === '1' && ($atributosCargador()["data-loader-{$capa}-enabled"] ?? null) === '1', "i4 encender «{$capa}» guarda 1");
        }
        //El área de pruebas solo ofrece la pantalla si hay una que enseñar: interruptor encendido y una en disco (la de fábrica aquí).
        $ofreceAhora = function () use ($pedir, $rutaMarca, &$jwt): array {
            $vista = $pedir($rutaMarca, $jwt, null)['body'];
            $datos = $pedir($rutaMarca . '?loader=yes', $jwt, null)['data'];
            return [
                'disponible' => $datos['screenAvailable'] ?? null,
                'con' => preg_match('#data-loader-test="screen"( disabled aria-describedby="loader-playground-screen-([a-z]+)")?>#', $vista, $m) === 1 ? ($m[2] ?? 'habilitado') : 'sin botón',
                'sin' => preg_match('#data-loader-test="no-screen"( hidden)?>#', $vista, $m2) === 1 ? (($m2[1] ?? '') === '' ? 'visible' : 'oculto') : 'sin botón',
                'motivoApagada' => preg_match('#id="loader-playground-screen-off" data-loader-screen-off( hidden)?>#', $vista, $m3) === 1 ? (($m3[1] ?? '') === '' ? 'visible' : 'oculto') : 'sin motivo',
            ];
        };
        $poner('loaderScreen', null);
        $poner('loaderScreenMobile', null);
        $poner(SettingsController::LOADER_LAYERS_ENABLED['screen'], null);
        $encendida = $ofreceAhora();
        $poner(SettingsController::LOADER_LAYERS_ENABLED['screen'], '0');
        $apagada = $ofreceAhora();
        $poner(SettingsController::LOADER_LAYERS_ENABLED['screen'], null);
        $check($encendida === ['disponible' => true, 'con' => 'habilitado', 'sin' => 'visible', 'motivoApagada' => 'oculto'], 'i11 con el interruptor encendido y las pantallas de fábrica, «Con» y «Sin la pantalla de carga» se ofrecen', json_encode($encendida) ?: '');
        $check($apagada === ['disponible' => false, 'con' => 'off', 'sin' => 'oculto', 'motivoApagada' => 'visible'], 'i12 DISCRIMINANTE: con el interruptor apagado, «Con la pantalla de carga» deshabilitado con su motivo («enciende el interruptor») y «Sin» oculto; `?loader=yes` dice screenAvailable=false', json_encode($apagada) ?: '');
        //El mensaje y el porcentaje no tienen interruptor (PO): los decide quien abre el cargador.
        $r = $pedir($rutaMarca, $jwt, ['loaderLayer' => 'message', 'enabled' => '0']);
        $r2 = $pedir($rutaMarca, $jwt, ['loaderLayer' => 'percent', 'enabled' => '0']);
        $at = $atributosCargador();
        $check(array_keys(SettingsController::LOADER_LAYERS_ENABLED) === ['animation', 'logo', 'screen'] && ($r['data']['success'] ?? null) === false && ($r2['data']['success'] ?? null) === false && $crudo('loaderMessageEnabled') === null && $crudo('loaderPercentEnabled') === null && !array_key_exists('data-loader-message-enabled', $at) && !array_key_exists('data-loader-percent-enabled', $at) && !str_contains($vistaMarca, 'data-loader-layer="message"') && !str_contains($vistaMarca, 'data-loader-layer="percent"'), 'i10 DISCRIMINANTE: sin interruptor de mensaje ni de porcentaje: se rechazan, no se guardan, no van en el <html> ni en la pestaña');
        $r = $pedir($rutaMarca, $jwt, ['loaderLayer' => 'zz-capa', 'enabled' => '0']);
        $r2 = $pedir($rutaMarca, $jwt, ['loaderLayer' => 'animation', 'enabled' => 'zz']);
        $check(($r['data']['success'] ?? null) === false && ($r2['data']['success'] ?? null) === false && $crudo('zz-capaEnabled') === null && $crudo(SettingsController::LOADER_LAYERS_ENABLED['animation']) === '1', 'i5 DISCRIMINANTE: una capa fuera de la lista, o un valor que no es 1 ni 0, se rechaza y no escribe nada', (string) ($r['data']['message'] ?? '') . ' · ' . (string) ($r2['data']['message'] ?? ''));
        //La actualización de lo guardado vacío, sobre la base y dentro de una transacción que se deshace: el SQL de verdad.
        $sqlInterruptores = (string) file_get_contents("{$proyecto}/databases/actualizaciones/2026-11-19-interruptores-del-cargador.sql");
        $sentencias = \Terminal\Tasks\DbRestoreTask::statementsOf($sqlInterruptores);
        $imagenesCargador = ['loaderAnimation', 'loaderLogo', 'loaderScreen', 'loaderScreenMobile'];
        $fotoAntes = array_map($crudo, array_merge($imagenesCargador, array_values(SettingsController::LOADER_LAYERS_ENABLED)));
        $database->beginTransaction();
        try {
            $poner('loaderAnimation', '');
            $poner('loaderLogo', '""');
            $poner('loaderScreen', '');
            $poner('loaderScreenMobile', null);
            $poner(SettingsController::LOADER_LAYERS_ENABLED['animation'], null);
            $poner(SettingsController::LOADER_LAYERS_ENABLED['logo'], '1');
            $poner(SettingsController::LOADER_LAYERS_ENABLED['screen'], null);
            foreach ($sentencias as $sentencia) {
                $database->exec($sentencia);
            }
            $check(count($sentencias) === 3 && $crudo('loaderAnimationEnabled') === '0' && $crudo('loaderLogoEnabled') === '0' && $crudo('loaderScreenEnabled') === '0' && $crudo('loaderAnimation') === null && $crudo('loaderLogo') === null && $crudo('loaderScreen') === null, 'i6 DISCRIMINANTE: el SQL pasa la animación (\'\'), el logo (\'""\', y con su interruptor ya a 1) y la pantalla (\'\', sin la de móvil) a su interruptor a 0 y borra las opciones vacías', var_export(array_map($crudo, ['loaderAnimationEnabled', 'loaderLogoEnabled', 'loaderScreenEnabled', 'loaderAnimation', 'loaderLogo', 'loaderScreen']), true));
            $poner('loaderAnimation', 'zz-propia.gif');
            $poner(SettingsController::LOADER_LAYERS_ENABLED['animation'], null);
            $poner('loaderScreen', '');
            $poner('loaderScreenMobile', 'zz-movil.png');
            $poner(SettingsController::LOADER_LAYERS_ENABLED['screen'], null);
            foreach ($sentencias as $sentencia) {
                $database->exec($sentencia);
            }
            $check($crudo('loaderAnimation') === 'zz-propia.gif' && $crudo('loaderAnimationEnabled') === null && $crudo('loaderScreen') === null && $crudo('loaderScreenMobile') === 'zz-movil.png' && $crudo('loaderScreenEnabled') === null, 'i7 DISCRIMINANTE: una imagen propia no se toca; con la de móvil guardada, la pantalla vacía se borra pero el interruptor no se apaga (escondería la de móvil)', var_export(array_map($crudo, ['loaderAnimation', 'loaderAnimationEnabled', 'loaderScreen', 'loaderScreenMobile', 'loaderScreenEnabled']), true));
        } finally {
            $database->rollBack();
        }
        $check(array_map($crudo, array_merge($imagenesCargador, array_values(SettingsController::LOADER_LAYERS_ENABLED))) === $fotoAntes, 'i8 y la transacción se deshace: las opciones, como antes');
        //Lo que lee loaderLayerEnabled(): la base devuelve el texto 0 decodificado como el entero 0.
        $configCapa = get_config(SettingsController::LOADER_LAYERS_ENABLED['logo']);
        $lecturas = [];
        foreach ([[false, true], [0, false], ['0', false], [1, true], ['1', true]] as [$guardado, $esperado]) {
            set_config(SettingsController::LOADER_LAYERS_ENABLED['logo'], $guardado);
            $lecturas[] = SettingsController::loaderLayerEnabled('logo') === $esperado;
        }
        set_config(SettingsController::LOADER_LAYERS_ENABLED['logo'], $configCapa);
        $check(!in_array(false, $lecturas, true) && SettingsController::loaderLayerEnabled('zz-capa') === true, 'i9 sin opción, encendida; 0 y «0», apagada; 1 y «1», encendida', json_encode($lecturas) ?: '');
        echoTerminal(' ');

        //─── s · La medida real de una imagen, robusta (revisión de B.c) ─────────────────────────────
        echoTerminal('[s] imageFileSize(): nada no finito, vacío o fuera de tope tumba la pantalla; la medida de reserva');
        $medida = function (string $nombre, string $contenido): ?array {
            $relativa = 'app/cache/zz-medida-' . bin2hex(random_bytes(3)) . '-' . $nombre;
            if (file_put_contents(basepath($relativa), $contenido) === false) {
                return ['width' => -1, 'height' => -1];
            }
            try {
                return SettingsController::imageFileSize($relativa);
            } finally {
                //RETORNO-IGNORADO: un temporal propio en app/cache/, que git ignora.
                unlink(basepath($relativa));
            }
        };
        $svgDe = fn (string $atributos): string => '<svg xmlns="http://www.w3.org/2000/svg" ' . $atributos . '><rect width="1" height="1"/></svg>';
        $check($medida('normal.svg', $svgDe('width="276.892" height="77.528" viewBox="0 0 276.892 77.528"')) === ['width' => 277, 'height' => 78], 's1 un SVG con width y height: su medida, redondeada');
        $check($medida('infinito.svg', $svgDe('width="1e999" height="10"')) === null, 's2 DISCRIMINANTE: width="1e999" (no finito) da la reserva, sin aviso de PHP');
        $check($medida('viewbox.svg', $svgDe('viewBox="0 0 1e30 10"')) === null, 's3 DISCRIMINANTE: un viewBox de 1e30 (pasa del tope) da la reserva');
        $check($medida('vacio.svg', '') === null, 's4 DISCRIMINANTE: un SVG de 0 bytes da la reserva, sin error de loadXML');
        $check($medida('vacio.gif', '') === null, 's5 DISCRIMINANTE: un GIF de 0 bytes da la reserva, sin aviso de getimagesize');
        $check($medida('porcentaje.svg', $svgDe('width="100%" height="100%" viewBox="0 0 200 50"')) === ['width' => 200, 'height' => 50], 's6 DISCRIMINANTE: width="100%" no es una medida: se cae al viewBox');
        $check($medida('grande.svg', $svgDe('width="5000" height="10"')) === null, 's7 más de ' . SettingsController::LOADER_ANIMATION_MAX_SIDE . ' px da la reserva');
        $check($medida('fabrica.gif', (string) file_get_contents(basepath('statics/images/loader-animation.gif'))) === ['width' => 429, 'height' => 120], 's8 el GIF de fábrica: su medida', json_encode($medida('fabrica.gif', (string) file_get_contents(basepath('statics/images/loader-animation.gif')))) ?: '');
        echoTerminal(' ');

        //─── b · El borrado, confinado ──────────────────────────────────────────────────────────────
        echoTerminal('[b] Solo se borra lo que está dentro de las subidas');
        $check(!SettingsController::deleteSettingsImage('statics/images/logo.png') && $huellasFabrica() === $fabricaAntes, 'b1 DISCRIMINANTE: una ruta de fábrica no se borra');
        //Un señuelo fuera del repositorio, alcanzado con «..»: si el confinamiento fallara, se borra el señuelo y nada versionado.
        $senuelo = (string) tempnam(sys_get_temp_dir(), 'zz-senuelo-');
        $imagenes[] = $senuelo;
        $profundidad = count(array_filter(explode('/', str_replace('\\', '/', (string) realpath(basepath(SettingsController::SETTINGS_UPLOADS_FOLDER . '/brand')))), fn ($parte) => $parte !== ''));
        $fuera = SettingsController::SETTINGS_UPLOADS_FOLDER . '/brand/' . str_repeat('../', $profundidad) . ltrim(str_replace('\\', '/', $senuelo), '/');
        $check(realpath(basepath($fuera)) === realpath($senuelo) && !SettingsController::deleteSettingsImage($fuera) && is_file($senuelo), 'b2 DISCRIMINANTE: una ruta que sale de las subidas con .. no se borra', $fuera);
        $copia = SettingsController::copyIntoSettingsUploads(basepath('statics/images/logo.png'), 'brand', 'zz-prueba');
        $check(is_string($copia) && preg_match($patron('brand', 'zz-prueba', 'png'), $copia) === 1 && SettingsController::deleteSettingsImage($copia) && !is_file(basepath($copia)), 'b3 una de las subidas sí se borra', var_export($copia, true));
        $r = $pedir($rutaBorrar, $jwt, ['background' => $nuevo2]);
        clearstatcache();
        $lista = $valor('backgrounds');
        $check(($r['data']['success'] ?? null) === true && is_array($lista) && !in_array($nuevo2, $lista, true) && !is_file(basepath($nuevo2)), 'b4 DISCRIMINANTE: Eliminar un fondo subido lo quita de la lista y borra su archivo', "HTTP {$r['status']}");
        $r = $pedir($rutaBorrar, $jwt, ['background' => $f('bg3.jpg')]);
        $lista = $valor('backgrounds');
        $check(($r['data']['success'] ?? null) === true && is_array($lista) && !in_array($f('bg3.jpg'), $lista, true) && $huellasFabrica() === $fabricaAntes, 'b5 DISCRIMINANTE: Eliminar uno de fábrica lo quita de la lista y el archivo se queda', "HTTP {$r['status']}");
        echoTerminal(' ');

        //─── v · Una opción vieja se sigue leyendo ──────────────────────────────────────────────────
        echoTerminal('[v] Una opción que apunta a la carpeta de fábrica se lee tal cual');
        $poner('logo', 'statics/images/logo.png');
        $panel = $pedir($rutaPanel, $jwt, null);
        $check($panel['status'] === 200 && preg_match('#<span class="logo"><img[^>]* src="statics/images/logo\.png[?"]#', $panel['body']) === 1, 'v1 el logo de una instalación anterior sale en el menú', "HTTP {$panel['status']}");
        echoTerminal(' ');

        //─── m · El logo de los correos ─────────────────────────────────────────────────────────────
        echoTerminal('[m] El logo de los correos: la subida si existe; si no, el de fábrica, como siempre');
        $fijo = 'statics/images/mailing-logo.png';
        $configAntes = get_config('mailingLogo');
        set_config('mailingLogo', false);
        $check(mailing_logo() === $fijo && get_config('mailing_logo') === $fijo, 'm1 sin opción, la ruta fija de siempre, la misma que get_config(\'mailing_logo\')', mailing_logo());
        set_config('mailingLogo', $configAntes);
        $poner('mailingLogo', null);
        $correo = $pedir($rutaCorreo, $jwt, null);
        $check($correo['status'] === 200 && str_contains($correo['body'], $fijo), 'm2 DISCRIMINANTE: instalación de fábrica, el correo lleva el de siempre', "HTTP {$correo['status']}");
        $r = $pedir($rutaMarca, $jwt, ['mailingLogo' => new \CURLFile($imagen('jpg', 70), 'image/jpeg', 'zz-correo.jpg')]);
        $correoLogo = $valor('mailingLogo');
        $correo = $pedir($rutaCorreo, $jwt, null);
        $check(($r['data']['success'] ?? null) === true && is_string($correoLogo) && preg_match($patron('brand', 'mailing-logo', 'jpg'), $correoLogo) === 1 && str_contains($correo['body'], $correoLogo) && !str_contains($correo['body'], $fijo), 'm3 DISCRIMINANTE: con un logo subido en JPG, el correo lleva la subida', var_export($correoLogo, true));
        $check($huellasFabrica() === $fabricaAntes, 'm4 y el mailing-logo.png de fábrica está intacto');
        //Un JPG de 12 bits: GD no lo decodifica y antes se guardaba con sus bytes, EXIF incluido; ahora pasa por ImageMetadataStripper.
        $jpg12 = (string) file_get_contents($imagen('jpg', 75));
        $sof = strpos($jpg12, "\xFF\xC0");
        $jpg12[(int) $sof + 4] = chr(12);
        $gpsLogo = 'zz-gps-4.6097-74.0817';
        $tiffLogo = "MM\0*" . pack('N', 8) . pack('n', 2) . pack('nnNN', 0x010F, 2, strlen($gpsLogo) + 1, 38) . pack('nnNn', 0x0112, 3, 1, 6) . "\0\0" . pack('N', 0) . $gpsLogo . "\0";
        $jpg12Exif = "\xFF\xD8\xFF\xE1" . pack('n', 2 + strlen("Exif\0\0{$tiffLogo}")) . "Exif\0\0{$tiffLogo}" . substr($jpg12, 2);
        $r = $pedir($rutaMarca, $jwt, ['mailingLogo' => new \CURLFile($temporal($jpg12Exif), 'image/jpeg', 'zz-correo-12.jpg')]);
        $logo12 = $valor('mailingLogo');
        $bytes12 = is_string($logo12) && is_file(basepath($logo12)) ? (string) file_get_contents(basepath($logo12)) : '';
        $check($sof !== false && @imagecreatefromstring($jpg12) === false && ($r['data']['success'] ?? null) === true && $bytes12 !== '' && !str_contains($bytes12, $gpsLogo) && str_contains($bytes12, "Exif\0\0MM\0*" . pack('N', 8) . pack('n', 1) . pack('nnNn', 0x0112, 3, 1, 6)), 'm6 DISCRIMINANTE: un JPG que GD no decodifica se guarda sin su EXIF (el texto del Make fuera) y con la orientación en un Exif mínimo', "HTTP {$r['status']} · " . (string) ($r['data']['message'] ?? '') . ' · ' . strlen($bytes12) . ' bytes');
        $r = $pedir($rutaMarca, $jwt, ['mailingLogo' => new \CURLFile($temporal(substr($jpg12Exif, 0, -2)), 'image/jpeg', 'zz-correo-roto.jpg')]);
        $check(($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? null) === __(SettingsController::LANG_GROUP, 'La imagen está incompleta o mal formada.') && $valor('mailingLogo') === $logo12, 'm7 DISCRIMINANTE: si además el recorrido no llega al EOI, se rechaza con su motivo y la opción no cambia', (string) ($r['data']['message'] ?? ''));
        $poner('mailingLogo', SettingsController::SETTINGS_UPLOADS_FOLDER . '/brand/zz-prueba-no-existe.png');
        $correo = $pedir($rutaCorreo, $jwt, null);
        $check($correo['status'] === 200 && str_contains($correo['body'], $fijo), 'm5 DISCRIMINANTE: con la opción a un archivo que no existe, el de fábrica', "HTTP {$correo['status']}");
        echoTerminal(' ');

        //─── o · La Open Graph de cada idioma ───────────────────────────────────────────────────────
        echoTerminal('[o] La copia de la Open Graph de un idioma va a las subidas, y el «repone» no sale de ellas');
        if (is_string($otro)) {
            $nombreOtro = SettingsController::seoOptionName(SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE, $otro);
            $poner($nombreOtro, null);
            $pantalla = $pedir($rutaSeo, $jwt, null);
            $ogOtro = $valor($nombreOtro);
            $check($pantalla['status'] === 200 && is_string($ogOtro) && preg_match($patron('open-graph', "open_graph_{$otro}", 'jpg'), $ogOtro) === 1 && is_file(basepath($ogOtro)) && $huellasFabrica() === $fabricaAntes, "o1 DISCRIMINANTE: sin fila de {$otro}, la copia va a uploads/settings/open-graph/ y open_graph_{$otro}.jpg de fábrica no cambia", var_export($ogOtro, true));
            $falta = SettingsController::SETTINGS_UPLOADS_FOLDER . "/open-graph/zz-prueba-falta-{$otro}.jpg";
            $poner($nombreOtro, $falta);
            $pedir($rutaSeo, $jwt, null);
            clearstatcache();
            $check(is_file(basepath($falta)), 'o2 la fila apunta a las subidas y el archivo falta: se repone');
            $fuera = "statics/images/zz-prueba-falta-{$otro}.jpg";
            $poner($nombreOtro, $fuera);
            $pedir($rutaSeo, $jwt, null);
            clearstatcache();
            $check(!is_file(basepath($fuera)) && $huellasFabrica() === $fabricaAntes, 'o3 DISCRIMINANTE: la fila apunta fuera de las subidas: no se escribe nada');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Lo que la suite dejó en las subidas: todo lo que no estaba antes. Antes de reponer las opciones y de borrar el
        //usuario, que $retirar necesita.
        $retirados = array_values(array_diff($enSubidas(), $subidasAntes));
        foreach ($retirados as $archivo) {
            $retirar($archivo);
        }
        foreach ($antes as $nombre => $crudoAntes) {
            $poner($nombre, $crudoAntes);
        }
        foreach ($imagenes as $archivo) {
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: el temporal es propio.
                unlink($archivo);
            }
        }
        foreach (array_reverse($carpetasCreadas) as $carpeta) {
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
        $restauradas = array_filter($antes, fn ($crudoAntes, $nombre) => $crudo((string) $nombre) !== $crudoAntes, \ARRAY_FILTER_USE_BOTH);
        $quedan = array_values(array_diff($enSubidas(), $subidasAntes));
        $check($quedan === [] && array_filter($carpetasCreadas, 'is_dir') === [], 'z1 no queda nada de la suite en las subidas (' . count($retirados) . ' retirados)', implode(', ', $quedan));
        $check($restauradas === [], 'z2 las opciones vuelven a su valor en crudo', implode(', ', array_keys($restauradas)));
        $check($huellasFabrica() === $fabricaAntes, 'z3 ningún archivo de fábrica cambió ni desapareció');
    }

    return $balance();

})->setDescription('Las imágenes de Configuración (fondos, problemas de inicio, marca, Open Graph y logo de los correos) se guardan en statics/uploads/settings/<tipo>/<nombre>-<huella>.<ext> y nunca escriben ni borran un archivo de fábrica; el borrado del anterior y Eliminar solo actúan dentro de las subidas; una opción vieja se sigue leyendo. Por HTTP; repone las opciones y retira lo que crea.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
