<?php

//Sustituir una imagen cuyo anterior ya no está guarda sin error (G7-22), y el texto sobre el patrocinador (P258) sale
//escapado en el pie y, vacío, no sale. Por HTTP; repone las opciones en crudo y retira el .jpg que crea en las subidas.
//@areas src/app/classes/PiecesPHP/Settings/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/previous-file-missing', function ($args) {

    echoTerminal("\e[33m[TEST:PreviousFileMissing] Sustituir una imagen cuyo archivo anterior ya no está\e[39m");
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

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $ruta = (string) parse_url(SettingsController::routeName('appearance-brand-images', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;

    $opcion = 'mailingLogo';
    $tabla = (new SettingsModel())->getModel()->getTable();
    $crudo = function () use ($database, $tabla, $opcion): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$opcion]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $crudoAntes = $crudo();
    $opcionTexto = SettingsController::PARTNERS_TEXT_CONFIG;
    $crudoTexto = function () use ($database, $tabla, $opcionTexto): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$opcionTexto]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $crudoTextoAntes = $crudoTexto();
    $opcionIngles = SettingsController::partnersTextOptionName('en');
    $crudoIngles = function () use ($database, $tabla, $opcionIngles): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$opcionIngles]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $crudoInglesAntes = $crudoIngles();
    //Lo que crea la prueba va a las subidas de Configuración (H230); el .png versionado no se toca.
    $creado = '';
    $crudoFondosAntes = (function () use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute(['backgrounds']);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    })();
    $versionado = basepath('statics/images/mailing-logo.png');
    $huellaVersionado = is_file($versionado) ? sha1_file($versionado) : null;
    $prefijo = 'zz-prueba-anterior-' . bin2hex(random_bytes(3));
    $ids = [];
    $imagen = tempnam(sys_get_temp_dir(), 'zz-img-');

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Anterior';
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

        //El anterior apunta a un archivo que ya no está.
        $check(SettingsModel::setConfigValue($opcion, "statics/images/{$prefijo}-no-existe.png"), 'banco: el logo anterior apunta a un archivo que no existe');
        $lienzo = imagecreatetruecolor(8, 8);
        $check($lienzo !== false && is_string($imagen) && imagejpeg($lienzo, $imagen), 'banco: una imagen .jpg de 8×8');

        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [$opcion => new \CURLFile((string) $imagen, 'image/jpeg', 'zz-logo.jpg')],
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $cuerpo = curl_exec($h);
        $estado = (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE);
        $datos = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $check($estado === 200 && ($datos['success'] ?? null) === true, 'p1 guarda sin error aunque el anterior no esté', "HTTP {$estado}, " . mb_substr(is_string($cuerpo) ? $cuerpo : '', 0, 200));
        clearstatcache();
        $creado = (string) $crudo();
        $check(preg_match('#^' . preg_quote(SettingsController::SETTINGS_UPLOADS_FOLDER, '#') . '/brand/mailing-logo-[0-9a-f]{10}\.jpg$#', $creado) === 1 && is_file(basepath($creado)), 'p2 y la opción apunta al nuevo, en las subidas', var_export($crudo(), true));

        //──── [t] El texto sobre el patrocinador ─────────────────────────────────────────────
        $peticion = function (string $camino, array|string|null $campos) use ($base, $jwt): array {
            $h = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($h, [
                CURLOPT_URL => $base . '/' . ltrim($camino, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
            ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $campos] : []));
            $cuerpo = curl_exec($h);
            return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
        };
        $inicio = (string) parse_url(AdminPanelController::routeName('', [], true), \PHP_URL_PATH);
        $inicio = $prefijoBase !== '' && str_starts_with($inicio, $prefijoBase) ? substr($inicio, strlen($prefijoBase)) : $inicio;
        //Solo la parte desplegada del pie del menú, que es donde va el texto.
        $pie = function (string $html): string {
            $desde = strpos($html, '<div class="footer">');
            $hasta = $desde === false ? false : strpos($html, '</nav>', $desde);
            return $desde === false || $hasta === false ? '' : substr($html, $desde, $hasta - $desde);
        };
        $textoDeFabrica = '<strong>' . __(LANG_GROUP, 'Con el apoyo de') . '</strong>';
        $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcionTexto]);
        $check(str_contains($pie($peticion($inicio, null)['body']), $textoDeFabrica), 't0 sin la opción, el texto de fábrica');
        $r = $peticion($ruta, [$opcionTexto => "{$prefijo} & \"c\" <b>zz-negrita</b>"]);
        $datos = json_decode($r['body'], true);
        $check($r['status'] === 200 && ($datos['success'] ?? null) === true && $crudoTexto() === "{$prefijo} & \"c\" zz-negrita", 't1 guarda el texto sin etiquetas', "HTTP {$r['status']}, " . var_export($crudoTexto(), true));
        $r = $peticion($inicio, null);
        $bloque = $pie($r['body']);
        $check($r['status'] === 200 && str_contains($bloque, "<strong>{$prefijo} &amp; &quot;c&quot; zz-negrita</strong>"), 't2 el pie del menú lo pinta escapado', "HTTP {$r['status']}");
        $r = $peticion($ruta, [$opcionTexto => '']);
        $sinTexto = $pie($peticion($inicio, null)['body']);
        $check($r['status'] === 200 && $crudoTexto() === '' && $sinTexto !== '' && !str_contains($sinTexto, $prefijo) && !str_contains($sinTexto, $textoDeFabrica), 't3 vacío, el pie no lo pinta', var_export($crudoTexto(), true) . ' · ' . mb_substr(preg_replace('/\s+/', ' ', $sinTexto) ?? '', 0, 600));

        //──── [t] Uno por idioma (H225): el suyo, si no el del idioma por omisión, si no el de fábrica ──────────
        $previoBase = get_config($opcionTexto);
        $previoIngles = get_config($opcionIngles);
        $idiomaPrevio = get_config('app_lang');
        try {
            //En memoria (set_config no escribe en la base): el finally repone lo de antes.
            set_config($opcionTexto, 'zz-A');
            set_config($opcionIngles, 'zz-B');
            $check(SettingsController::partnersText('en') === 'zz-B' && SettingsController::partnersText('es') === 'zz-A', 't4 DISCRIMINANTE: con «zz-A» en el principal y «zz-B» en inglés, cada idioma da el suyo',
                SettingsController::partnersText('en') . ' / ' . SettingsController::partnersText('es'));
            set_config($opcionIngles, null);
            $check(SettingsController::partnersText('en') === 'zz-A', 't5 sin el del inglés, el del idioma principal', SettingsController::partnersText('en'));
            set_config($opcionTexto, null);
            $deFabricaEs = SettingsController::partnersText('es');
            set_config('app_lang', 'en');
            $deFabricaEn = SettingsController::partnersText('en');
            set_config('app_lang', $idiomaPrevio);
            $check($deFabricaEs === 'Con el apoyo de' && $deFabricaEn === 'With the support of', 't6 sin ninguno, el de fábrica en el idioma de la petición', "{$deFabricaEs} / {$deFabricaEn}");
            set_config($opcionTexto, '');
            $check(SettingsController::partnersText('es') === '' && SettingsController::partnersText('en') === '', 't7 el principal vacío y sin inglés: vacío en los dos (no se pinta)');
        } finally {
            set_config($opcionTexto, $previoBase === false ? null : $previoBase);
            set_config($opcionIngles, $previoIngles === false ? null : $previoIngles);
            set_config('app_lang', $idiomaPrevio);
        }
        //Por HTTP: el formulario manda uno por idioma, y el menú en /en/ pinta el inglés.
        $inicioIngles = '/en/' . ltrim($inicio, '/');
        $r = $peticion($ruta, null);
        $check($r['status'] === 200 && str_contains($r['body'], 'name="partnersText[es]"') && str_contains($r['body'], 'name="partnersText[en]"'), 't8 la pantalla tiene un campo por idioma activo', "HTTP {$r['status']}");
        $r = $peticion($ruta, http_build_query([$opcionTexto => ['es' => "{$prefijo}-es", 'en' => "{$prefijo}-en"]]));
        $enIngles = $pie($peticion($inicioIngles, null)['body']);
        $enEspanol = $pie($peticion($inicio, null)['body']);
        $check($r['status'] === 200 && $crudoTexto() === "{$prefijo}-es" && $crudoIngles() === "{$prefijo}-en" && str_contains($enIngles, "<strong>{$prefijo}-en</strong>") && str_contains($enEspanol, "<strong>{$prefijo}-es</strong>"),
            't8 guarda los dos, y el menú pinta el inglés en /en/ y el principal en /', "HTTP {$r['status']} · en: " . var_export(str_contains($enIngles, "{$prefijo}-en"), true));
        $r = $peticion($ruta, http_build_query([$opcionTexto => ['es' => "{$prefijo}-es", 'en' => '']]));
        $enIngles = $pie($peticion($inicioIngles, null)['body']);
        $check($r['status'] === 200 && $crudoIngles() === null && str_contains($enIngles, "<strong>{$prefijo}-es</strong>"), 't9 el inglés vacío borra su opción y el menú en /en/ vuelve al principal',
            "HTTP {$r['status']} · fila: " . var_export($crudoIngles(), true));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if (is_string($imagen) && is_file($imagen)) {
            //RETORNO-IGNORADO: el temporal es propio; lo que importa es lo de abajo.
            unlink($imagen);
        }
        //Solo lo de DENTRO de las subidas. En una carpeta de Apache el terminal no borra: se retira con Eliminar de
        //Fondos, que borra lo de dentro de las subidas; la opción de fondos se repone después.
        if (SettingsController::isSettingsUploadPath($creado) && is_file(basepath($creado)) && isset($jwt)) {
            if (is_writable(dirname(basepath($creado)))) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink(basepath($creado));
            } else {
                $fondos = json_encode([$creado, SettingsController::LOGIN_BACKGROUNDS_FOLDER . '/bg1.jpg']);
                $database->prepare($crudoFondosAntes === null ? "INSERT INTO `{$tabla}` (value, name) VALUES (?, ?)" : "UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$fondos, 'backgrounds']);
                $rutaBorrar = (string) parse_url(SettingsController::routeName('appearance-backgrounds-delete', [], true), \PHP_URL_PATH);
                $rutaBorrar = $prefijoBase !== '' && str_starts_with($rutaBorrar, $prefijoBase) ? substr($rutaBorrar, strlen($prefijoBase)) : $rutaBorrar;
                $h = \Terminal\TestRun::curlInit();
                \Terminal\TestRun::curlSetoptArray($h, [
                    CURLOPT_URL => $base . '/' . ltrim($rutaBorrar, '/'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query(['background' => $creado]),
                    CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
                ]);
                //RETORNO-IGNORADO: lo comprueba z1.
                curl_exec($h);
                clearstatcache();
            }
        }
        if ($crudoFondosAntes === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute(['backgrounds']);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoFondosAntes, 'backgrounds']);
        }
        if ($crudoInglesAntes === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcionIngles]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoInglesAntes, $opcionIngles]);
        }
        if ($crudoTextoAntes === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcionTexto]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoTextoAntes, $opcionTexto]);
        }
        if ($crudoAntes === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcion]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoAntes, $opcion]);
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
        $check(($creado === '' || !is_file(basepath($creado))) && $crudo() === $crudoAntes && ($huellaVersionado === null || sha1_file($versionado) === $huellaVersionado), 'z1 no queda el .jpg, la opción vuelve a su valor y el .png versionado está intacto');
        $check($crudoTexto() === $crudoTextoAntes, 'z2 el texto sobre el patrocinador vuelve a su valor', var_export($crudoTexto(), true));
    }

    return $balance();

})->setDescription('Sustituir una imagen cuyo archivo anterior ya no está guarda sin error, y el texto sobre el patrocinador se guarda sin etiquetas, sale escapado en el pie y vacío no sale. Por HTTP, con el logo de los correos; lo repone todo.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
