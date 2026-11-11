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
    $opciones = ['backgrounds', 'backgoundProblems', 'logo', 'mailingLogo'];
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
