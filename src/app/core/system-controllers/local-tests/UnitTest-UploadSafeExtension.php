<?php

//La extensión guardada sale del contenido (ProtectedUploads::safeExtension), por moveUploadedToPrivate, por moveTo sin
//extensión y por UploadedFileAdapter::copyTo sin extensión; la que fija el llamador se respeta. Directo y por HTTP; crea entidades, opciones y archivos zz- y los repone.
//@areas nucleo

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/upload-safe-extension', function ($args) {

    echoTerminal("\e[33m[TEST:UploadSafeExtension] La extensión guardada sale del contenido, no del nombre\e[39m");
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

    $prefijo = 'zz-prueba-extension-' . bin2hex(random_bytes(3));
    $temporal = sys_get_temp_dir() . "/{$prefijo}";
    $database = (new BaseModel())->getDatabase();
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    $subidas = (string) get_config('upload_dir');
    $carpetas = [];
    $filas = [];
    $usuario = null;
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $opcionesAntes = [];
    $ogArchivo = null;
    $ogAntes = null;

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
        $png = $escribir('png', $imagen(fn ($l) => imagepng($l)));
        $jpeg = $escribir('jpeg', $imagen(fn ($l) => imagejpeg($l)));
        $webp = $escribir('webp', function_exists('imagewebp') ? $imagen(fn ($l) => imagewebp($l)) : '');
        $svg = $escribir('svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>/*zz*/</script></svg>');
        $html = $escribir('html', '<html><body><script>/*zz*/</script></body></html>');
        $xml = $escribir('xml', '<?xml version="1.0"?><zz/>');
        $pdf = $escribir('pdf', "%PDF-1.4\n%zz\n%%EOF\n");
        $binario = $escribir('bin', random_bytes(64));

        echoTerminal('[a] safeExtension(): las ramas de la regla');
        $check(count(glob("{$temporal}/*") ?: []) === 8 && filesize($png) > 0 && filesize($jpeg) > 0 && filesize($webp) > 0, 'a0 CANARIO: los ocho archivos de contenido conocido y uno desconocido');
        $casos = [
            'a1 un SVG llamado .png es svg (activo, diga lo que diga el nombre)' => [$svg, 'png', 'svg'],
            'a1 un HTML llamado .txt es html' => [$html, 'txt', 'html'],
            'a1 un XML llamado .txt es xml' => [$xml, 'txt', 'xml'],
            'a2 un HTML llamado .htm se queda htm (es una de las suyas)' => [$html, 'htm', 'htm'],
            'a2 un PNG llamado .png se queda png' => [$png, 'png', 'png'],
            'a2 un JPEG llamado .jpg se queda jpg' => [$jpeg, 'jpg', 'jpg'],
            'a2 un JPEG llamado .jpeg se queda jpeg' => [$jpeg, 'jpeg', 'jpeg'],
            'a2 un JPEG llamado .JPG pasa a minúsculas' => [$jpeg, 'JPG', 'jpg'],
            'a3 un PNG llamado .html es png (la canónica)' => [$png, 'html', 'png'],
            'a3 un PDF llamado .doc es pdf' => [$pdf, 'doc', 'pdf'],
            'a3 un JPEG llamado .webp es jpg' => [$jpeg, 'webp', 'jpg'],
            'a4 un binario desconocido conserva la del nombre' => [$binario, 'docx', 'docx'],
            'a4 y se corta a 8' => [$binario, 'abcdefghijk', 'abcdefgh'],
        ];
        foreach ($casos as $nombre => [$archivo, $delNombre, $esperada]) {
            $obtenida = ProtectedUploads::safeExtension($archivo, $delNombre);
            $check($obtenida === $esperada, $nombre, "obtenida {$obtenida}");
        }

        echoTerminal('');
        echoTerminal('[e] UploadedFileAdapter::copyTo() sin extensión, directo');
        $destinoAdaptador = "{$temporal}/adaptador";
        $adaptador = fn (string $archivo, string $nombre): UploadedFileAdapter => new UploadedFileAdapter(['zz'], [], null, ['zz' => ['name' => $nombre, 'type' => 'image/jpeg', 'size' => (int) filesize($archivo), 'tmp_name' => $archivo, 'error' => \UPLOAD_ERR_OK]]);
        $copia = $adaptador($png, 'zz-disfraz.jpg')->copyTo($destinoAdaptador, 'zz-copia-png', null, false);
        $check($copia === "{$destinoAdaptador}/zz-copia-png.png" && is_file($copia) && sha1_file($copia) === sha1_file($png), 'e1 un PNG llamado .jpg se copia .png', $copia);
        $copia = $adaptador($html, 'zz-disfraz.jpg')->copyTo($destinoAdaptador, 'zz-copia-html', null, false);
        $check($copia === "{$destinoAdaptador}/zz-copia-html.html", 'e2 un HTML llamado .jpg se copia .html', $copia);
        $copia = $adaptador($png, 'zz-disfraz.jpg')->copyTo($destinoAdaptador, 'zz-copia-fijada', 'jpg', false);
        $check($copia === "{$destinoAdaptador}/zz-copia-fijada.jpg" && is_file($copia), 'e3 con la extensión fijada por el llamador, se respeta', $copia);
        foreach (glob("{$destinoAdaptador}/*") ?: [] as $archivo) {
            //RETORNO-IGNORADO: lo comprueba z2.
            unlink($archivo);
        }
        if (is_dir($destinoAdaptador)) {
            //RETORNO-IGNORADO: lo comprueba z2.
            rmdir($destinoAdaptador);
        }

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
        $peticion = function (string $ruta, string $jwt, ?array $campos = null) use ($base, $cabeceraToken): array {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [
                CURLOPT_URL => "{$base}/{$ruta}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
            ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $campos] : []));
            $respuesta = (string) curl_exec($handle);
            $corte = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
            $csp = [];
            $disposicion = [];
            foreach (preg_split('/\r?\n/', substr($respuesta, 0, $corte)) ?: [] as $linea) {
                $partes = explode(':', $linea, 2);
                $nombre = strtolower(trim($partes[0]));
                if (count($partes) === 2 && $nombre === 'content-security-policy') {
                    $csp[] = trim($partes[1]);
                } elseif (count($partes) === 2 && $nombre === 'content-disposition') {
                    $disposicion[] = trim($partes[1]);
                }
            }
            $datos = json_decode(substr($respuesta, $corte), true);
            return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'csp' => $csp, 'cd' => $disposicion, 'json' => is_array($datos) ? $datos : null];
        };
        $check($base !== '', 'h0 hay una base HTTP para pedir', $base);

        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Extension';
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
        //La carpeta de la entidad la crea el terminal y abierta: si la creara Apache, el terminal no podría vaciarla.
        $carpetaDe = function (string $dir) use ($subidas, $prefijo, &$carpetas): string {
            $carpeta = append_to_path_system($subidas, "{$dir}/{$prefijo}");
            //RETORNO-IGNORADO: lo comprueba el canario de cada bloque.
            mkdir($carpeta, 0777, true);
            //RETORNO-IGNORADO: lo comprueba el canario de cada bloque.
            chmod($carpeta, 0777);
            $carpetas[] = $carpeta;
            return $carpeta;
        };
        $enDisco = fn (string $carpeta): array => array_values(array_map('basename', glob("{$carpeta}/*") ?: []));

        echoTerminal('');
        echoTerminal('[b] moveUploadedToPrivate(), por Documents (`document` admite cualquier tipo)');
        $database->prepare('INSERT INTO `' . DocumentTypesMapper::TABLE . '` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute(["{$prefijo}-tipo", "{$prefijo}-tipo", 1, $ahora, $usuario, $meta]);
        $tipo = (int) $database->lastInsertId();
        $filas[DocumentTypesMapper::TABLE][] = $tipo;
        $carpetaDocumentos = $carpetaDe(DocumentsController::UPLOAD_DIR);
        $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$tipo, "{$prefijo}-documento", '', '', '', $prefijo, 1, $ahora, $usuario, $meta]);
        $documento = (int) $database->lastInsertId();
        $filas[DocumentsMapper::TABLE][] = $documento;
        $valorDocumento = fn (): string => (string) $database->query('SELECT document FROM `' . DocumentsMapper::TABLE . "` WHERE id = {$documento}")->fetchColumn();
        $rutaDocumentos = $relativa(DocumentsController::routeName('actions-edit', [], true));
        $subirDocumento = fn (string $archivo, string $tipoDeclarado, string $nombre): array => $peticion($rutaDocumentos, $jwt, ['id' => $documento, 'lang' => $lang, 'documentType' => $tipo, 'documentName' => "{$prefijo}-documento", 'document' => new \CURLFile($archivo, $tipoDeclarado, $nombre)]);
        $r = $subirDocumento($html, 'text/plain', 'zz-disfraz.txt');
        $check(($r['json']['success'] ?? null) === true && str_ends_with($valorDocumento(), '/zz-disfraz.html') && $enDisco($carpetaDocumentos) === ['zz-disfraz.html' . ProtectedUploads::suffix()], 'b1 un HTML llamado .txt se guarda .html, en la base y en disco', $valorDocumento() . ' · ' . implode(', ', $enDisco($carpetaDocumentos)));
        $servido = $peticion($valorDocumento(), $jwt);
        $check($servido['status'] === 200 && in_array('sandbox', $servido['csp'], true) && $servido['cd'] === ['attachment'], 'b2 y se sirve en sandbox y como descarga', "HTTP {$servido['status']} · " . json_encode($servido['csp']) . ' · ' . json_encode($servido['cd']));
        $r = $subirDocumento($png, 'text/html', 'zz-disfraz.html');
        $check(($r['json']['success'] ?? null) === true && str_ends_with($valorDocumento(), '/zz-disfraz.png'), 'b3 un PNG llamado .html se guarda .png', $valorDocumento());
        $servido = $peticion($valorDocumento(), $jwt);
        $check($servido['status'] === 200 && !in_array('sandbox', $servido['csp'], true) && $servido['cd'] === [], 'b4 y se sirve como imagen, sin sandbox ni descarga', "HTTP {$servido['status']} · " . json_encode($servido['csp']));

        echoTerminal('');
        echoTerminal('[c] moveTo() sin extensión, por BuiltInBanner (sus imágenes admiten SVG)');
        $carpetaBanner = $carpetaDe(BuiltInBannerController::UPLOAD_DIR);
        $database->prepare('INSERT INTO `' . BuiltInBannerMapper::TABLE . '` (title, content, link, desktopImage, mobileImage, orderPosition, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)')
            ->execute(["{$prefijo}-banner", 'zz-', '', '', '', $prefijo, $ahora, $usuario, BuiltInBannerMapper::ACTIVE, $meta]);
        $banner = (int) $database->lastInsertId();
        $filas[BuiltInBannerMapper::TABLE][] = $banner;
        $valorBanner = fn (): string => (string) $database->query('SELECT desktopImage FROM `' . BuiltInBannerMapper::TABLE . "` WHERE id = {$banner}")->fetchColumn();
        $r = $peticion($relativa(BuiltInBannerController::routeName('actions-edit', [], true)), $jwt, ['id' => $banner, 'lang' => $lang, 'title' => "{$prefijo}-banner", 'desktopImage' => new \CURLFile($svg, 'image/png', 'zz-disfraz.png')]);
        $check(($r['json']['success'] ?? null) === true && str_ends_with($valorBanner(), '/zz-disfraz.svg') && $enDisco($carpetaBanner) === ['zz-disfraz.svg'], 'c1 un SVG llamado .png se guarda .svg, en la base y en disco', $valorBanner() . ' · ' . implode(', ', $enDisco($carpetaBanner)));
        $servido = $peticion($valorBanner(), $jwt);
        $check($servido['status'] === 200 && $servido['csp'] === ['sandbox'], 'c2 y Apache lo sirve en sandbox', "HTTP {$servido['status']} · " . json_encode($servido['csp']));

        echoTerminal('');
        echoTerminal('[d] moveTo() con la extensión FIJADA por el llamador (Settings, imagen Open Graph, que fija la del contenido): se respeta');
        $otros = array_values(array_filter(Config::get_allowed_langs(), fn ($l) => $l !== $lang));
        $idioma = $otros[0] ?? null;
        if ($check(is_string($idioma) && function_exists('imagewebp'), 'd0 CANARIO: hay un idioma que no es el predeterminado y GD escribe WEBP')) {
            //Escribe en las subidas de Configuración y las opciones con sufijo, que se copian y se reponen; open_graph_<idioma>.jpg
            //es VERSIONADO y no puede cambiar (H230). La opción de fondos también se repone: con ella se retira el archivo.
            $opcionesAntes['backgrounds'] = null;
            foreach (['backgrounds', SettingsController::SEO_OPTION_TITLE_APP, SettingsController::SEO_OPTION_OWNER, SettingsController::SEO_OPTION_DESCRIPTION, SettingsController::SEO_OPTION_KEYWORDS, SettingsController::SEO_OPTION_SHARE_TITLE, SettingsController::SEO_OPTION_SHARE_DESCRIPTION, SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE] as $opcion) {
                $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
                $nombreOpcion = $opcion === 'backgrounds' ? $opcion : "{$opcion}_{$idioma}";
                $consulta->execute([$nombreOpcion]);
                $valor = $consulta->fetchColumn();
                $opcionesAntes[$nombreOpcion] = $valor === false ? null : (string) $valor;
            }
            $ogArchivo = basepath("statics/images/open_graph_{$idioma}.jpg");
            $ogAntes = is_file($ogArchivo) ? file_get_contents($ogArchivo) : null;
            $r = $peticion($relativa(SettingsController::routeName('appearance-seo', [], true)), $jwt, ['lang' => $idioma, 'titleApp' => "{$prefijo}-titulo", 'owner' => "{$prefijo}-propietario", 'description' => "{$prefijo}-descripcion", 'open-graph' => new \CURLFile($webp, 'image/jpeg', 'zz-disfraz.jpg')]);
            clearstatcache();
            echoTerminal("      - Settings responde HTTP {$r['status']} · success " . var_export($r['json']['success'] ?? null, true) . ' · ' . mb_substr((string) ($r['json']['message'] ?? ''), 0, 120));
            $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
            $consulta->execute([SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE . "_{$idioma}"]);
            $ogNuevo = (string) $consulta->fetchColumn();
            $versionadoIgual = is_string($ogAntes) ? (string) file_get_contents($ogArchivo) === $ogAntes : !is_file($ogArchivo);
            $check(preg_match('#^' . preg_quote(SettingsController::SETTINGS_UPLOADS_FOLDER, '#') . "/open-graph/open_graph_{$idioma}-[0-9a-f]{10}\\.webp$#", $ogNuevo) === 1 && is_file(basepath($ogNuevo)) && $versionadoIgual, 'd1 un WEBP subido como .jpg se guarda .webp, en las subidas: Settings fija la extensión del contenido y moveTo la respeta; el .jpg versionado no cambia', $ogNuevo);
            //Solo lo de DENTRO de las subidas (si falló, la opción sigue en el de fábrica). En una carpeta de Apache el
            //terminal no borra: se retira con Eliminar de Fondos, que borra lo de dentro de las subidas.
            if (SettingsController::isSettingsUploadPath($ogNuevo) && is_file(basepath($ogNuevo))) {
                if (is_writable(dirname(basepath($ogNuevo)))) {
                    //RETORNO-IGNORADO: lo comprueba d2.
                    unlink(basepath($ogNuevo));
                } else {
                    $fondos = (string) json_encode([$ogNuevo, SettingsController::LOGIN_BACKGROUNDS_FOLDER . '/bg1.jpg']);
                    $database->prepare($opcionesAntes['backgrounds'] === null ? "INSERT INTO `{$tablaOpciones}` (value, name) VALUES (?, ?)" : "UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$fondos, 'backgrounds']);
                    $peticion($relativa(SettingsController::routeName('appearance-backgrounds-delete', [], true)), $jwt, ['background' => $ogNuevo]);
                }
            }
            clearstatcache();
            $check($ogNuevo === '' || !is_file(basepath($ogNuevo)), 'd2 y se retira');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($opcionesAntes as $nombre => $valor) {
                if ($valor === null) {
                    $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
                } else {
                    $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$valor, $nombre]);
                }
            }
            //Solo si una regresión lo reescribió (lo cuenta z3): se borra y se repone, no se sobrescribe, porque sería de Apache.
            $reescrito = is_string($ogArchivo) && is_string($ogAntes) && is_file($ogArchivo) && (string) file_get_contents($ogArchivo) !== $ogAntes;
            if ($reescrito) {
                //RETORNO-IGNORADO: lo comprueba z3.
                unlink($ogArchivo);
            }
            if ($reescrito) {
                //RETORNO-IGNORADO: lo comprueba z3.
                file_put_contents($ogArchivo, $ogAntes);
            }
            foreach (array_merge(glob("{$temporal}/*") ?: [], ...array_map(fn ($c) => glob("{$c}/*") ?: [], $carpetas)) as $archivo) {
                //RETORNO-IGNORADO: lo comprueba z2.
                unlink($archivo);
            }
            foreach (array_merge($carpetas, [$temporal]) as $carpeta) {
                if (is_dir($carpeta)) {
                    //RETORNO-IGNORADO: lo comprueba z2.
                    rmdir($carpeta);
                }
            }
            //Al revés de como se crearon: el documento apunta a su tipo.
            $aprobaciones = SystemApprovalsMapper::model();
            foreach (array_merge(array_reverse($filas, true), [UsersModel::TABLE => $usuario !== null ? [$usuario] : []]) as $tabla => $ids) {
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
            if ($usuario !== null) {
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
        foreach ($filas as $tabla => $ids) {
            foreach ($ids as $id) {
                $restos += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id = " . (int) $id)->fetchColumn();
            }
        }
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba', "quedan {$restos}");
        clearstatcache();
        $check(count(array_filter(array_merge($carpetas, [$temporal]), 'file_exists')) === 0, 'z2 no quedan carpetas ni el temporal');
        $opcionesIguales = true;
        foreach ($opcionesAntes as $nombre => $valor) {
            $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
            $consulta->execute([$nombre]);
            $ahoraValor = $consulta->fetchColumn();
            $opcionesIguales = $opcionesIguales && ($ahoraValor === false ? null : (string) $ahoraValor) === $valor;
        }
        $archivoIgual = !is_string($ogArchivo) || (is_string($ogAntes) ? (string) @file_get_contents($ogArchivo) === $ogAntes : !is_file($ogArchivo));
        $check($opcionesIguales && $archivoIgual, 'z3 las opciones de SEO y la imagen Open Graph de ese idioma quedan como estaban');
    }

    return $balance();

})->setDescription('La extensión con la que se guarda un archivo subido sale de su contenido (ProtectedUploads::safeExtension) por moveUploadedToPrivate y por FileUpload::moveTo sin extensión; la que fija el llamador se respeta. Directo y por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
