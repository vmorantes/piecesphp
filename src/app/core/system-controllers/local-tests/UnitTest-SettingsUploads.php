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
    foreach (SettingsController::SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME as $nombreSeo) {
        $opciones[] = SettingsController::seoOptionName($nombreSeo, $lang);
        if ($otro !== null) {
            $opciones[] = SettingsController::seoOptionName($nombreSeo, $otro);
        }
    }
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
        echoTerminal('[l] El logo del cargador: de fábrica la «P» (loader-logo.png); se sube por el recortador; «Eliminar» lo apaga');
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
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'loaderLogo']);
        clearstatcache();
        $check(($r['data']['success'] ?? null) === true && $crudo('loaderLogo') === '' && $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['image']) === null && $crudo(SettingsController::BRAND_IMAGES_DARK_MODE['loaderLogo']['mode']) === null && is_string($logoCargador) && !is_file(basepath($logoCargador)) && is_string($logoCargadorOscuro) && !is_file(basepath($logoCargadorOscuro)), 'l4 «Eliminar» guarda el logo vacío, quita su oscura y su modo y borra los archivos', (string) ($r['data']['message'] ?? '') . ' · ' . var_export($crudo('loaderLogo'), true));
        $check($atributoLogo() === '' && $atributoLogo('-dark') === '', 'l6 DISCRIMINANTE: eliminado, el cargador no pinta logo ni en claro ni en oscuro (no vuelve la «P»)', var_export([$atributoLogo(), $atributoLogo('-dark')], true));
        $pantallaApagada = $pedir($rutaMarca, $jwt, null)['body'];
        $rotuloApagado = escape_html(__(SettingsController::LANG_GROUP, 'Sin imagen: el cargador enseña solo la animación'));
        $check(preg_match('#id="image-loader-logo".*?data-image-card-empty>\s*<img width="400" height="400"[^>]*>\s*<span class="ui muted text">' . preg_quote($rotuloApagado, '#') . '#s', $pantallaApagada) === 1 && preg_match('#data-brand-dark-empty>\s*<img width="400" height="400"[^>]*>\s*<span class="ui muted text">' . preg_quote($rotuloApagado, '#') . '#', $pantallaApagada) === 1, 'l7 la tarjeta apagada: damero de 400 × 400 con su rótulo, en la vista clara y en la oscura (visible)');
        $r = $pedir($rutaMarca, $jwt, ['loaderLogo' => new \CURLFile($imagen('png', 130), 'image/png', 'zz-logo-cargador-2.png')]);
        $logoCargador2 = $valor('loaderLogo');
        $check(($r['data']['success'] ?? null) === true && is_string($logoCargador2) && preg_match($patron('brand', 'loader-logo', 'png'), $logoCargador2) === 1 && $atributoLogo() === $logoCargador2, 'l8 tras eliminarlo, subir otro lo vuelve a pintar', var_export($logoCargador2, true));
        $logoAntes = $crudo('logo');
        $r = $pedir($rutaMarca, $jwt, ['deleteBrandImage' => 'logo']);
        $check(($r['data']['success'] ?? null) !== true && $crudo('logo') === $logoAntes, 'l5 DISCRIMINANTE: «Eliminar» solo vale para el logo del cargador: el logo de la plataforma no se quita', (string) ($r['data']['message'] ?? ''));
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
