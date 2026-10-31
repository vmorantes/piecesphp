<?php

//Sustituir una imagen cuyo anterior ya no está guarda sin error (G7-22), y el texto sobre el patrocinador (P258) sale
//escapado en el pie y, vacío, no sale. Por HTTP; repone las opciones en crudo y borra el .jpg que crea.
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
    //El logo de los correos versionado es .png: el .jpg que crea la prueba es suyo, y no puede existir antes.
    $creado = basepath('statics/images/mailing-logo.jpg');
    $versionado = basepath('statics/images/mailing-logo.png');
    $huellaVersionado = is_file($versionado) ? sha1_file($versionado) : null;
    $prefijo = 'zz-prueba-anterior-' . bin2hex(random_bytes(3));
    $ids = [];
    $imagen = tempnam(sys_get_temp_dir(), 'zz-img-');

    try {
        if (!$check(!is_file($creado), 'banco: statics/images/mailing-logo.jpg no existe antes')) {
            return $balance();
        }
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
            CURLOPT_HTTPHEADER => [SessionToken::tokenName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $cuerpo = curl_exec($h);
        $estado = (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE);
        $datos = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $check($estado === 200 && ($datos['success'] ?? null) === true, 'p1 guarda sin error aunque el anterior no esté', "HTTP {$estado}, " . mb_substr(is_string($cuerpo) ? $cuerpo : '', 0, 200));
        clearstatcache();
        $check(is_file($creado) && $crudo() !== null && str_contains((string) $crudo(), 'mailing-logo.jpg'), 'p2 y la opción apunta al nuevo', var_export($crudo(), true));

        //──── [t] El texto sobre el patrocinador ─────────────────────────────────────────────
        $peticion = function (string $camino, ?array $campos) use ($base, $jwt): array {
            $h = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($h, [
                CURLOPT_URL => $base . '/' . ltrim($camino, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [SessionToken::tokenName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
            ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $campos] : []));
            $cuerpo = curl_exec($h);
            return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
        };
        $inicio = (string) parse_url(AdminPanelController::routeName('', [], true), \PHP_URL_PATH);
        $inicio = $prefijoBase !== '' && str_starts_with($inicio, $prefijoBase) ? substr($inicio, strlen($prefijoBase)) : $inicio;
        //Solo la parte desplegada del pie del menú, que es donde va el texto.
        $pie = function (string $html): string {
            $desde = strpos($html, '<div class="footer">');
            $hasta = $desde === false ? false : strpos($html, '<div class="collapsed only">', $desde);
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
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if (is_string($imagen) && is_file($imagen)) {
            //RETORNO-IGNORADO: el temporal es propio; lo que importa es lo de abajo.
            unlink($imagen);
        }
        if (is_file($creado)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            unlink($creado);
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
        $check(!is_file($creado) && $crudo() === $crudoAntes && ($huellaVersionado === null || sha1_file($versionado) === $huellaVersionado), 'z1 no queda el .jpg, la opción vuelve a su valor y el .png versionado está intacto');
        $check($crudoTexto() === $crudoTextoAntes, 'z2 el texto sobre el patrocinador vuelve a su valor', var_export($crudoTexto(), true));
    }

    return $balance();

})->setDescription('Sustituir una imagen cuyo archivo anterior ya no está guarda sin error, y el texto sobre el patrocinador se guarda sin etiquetas, sale escapado en el pie y vacío no sale. Por HTTP, con el logo de los correos; lo repone todo.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
