<?php

//Eliminar un fondo lo quita de `backgrounds` (lista compacta de 1 a 5), rechaza el último y lo ajeno, y no toca los
//archivos de fábrica, versionados (borrar una subida lo prueba settings-uploads). Por HTTP; repone la opción en crudo.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/backgrounds-delete', function ($args) {

    echoTerminal("\e[33m[TEST:BackgroundsDelete] Eliminar un fondo del inicio de sesión\e[39m");
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

    $carpeta = SettingsController::LOGIN_BACKGROUNDS_FOLDER;
    $f = fn(string $nombre): string => "{$carpeta}/{$nombre}";

    //─── t · Las tarjetas y la lista, sin HTTP ──────────────────────────────────────────────────────
    echoTerminal('[t] Las tarjetas de la pantalla y la lista sin un fondo');
    $tarjetas = SettingsController::loginBackgroundCards([$f('bg2.jpg'), $f('bg4.webp')]);
    $check(array_column($tarjetas, 'field') === ['background-2', 'background-4', 'background-1', 'background-3', 'background-5'], 't1 DISCRIMINANTE: las imágenes primero, con su nombre, y las vacías con el primer nombre libre', json_encode(array_column($tarjetas, 'field')) ?: '');
    $check(array_column($tarjetas, 'image') === [$f('bg2.jpg'), $f('bg4.webp'), '', '', ''], 't2 cada imagen en su tarjeta y las demás vacías');
    $cinco = [$f('bg1.jpg'), $f('bg2.jpg'), $f('bg3.jpg'), $f('bg4.jpg'), $f('bg5.jpg')];
    $check(count(SettingsController::loginBackgroundCards($cinco)) === 5 && !in_array('', array_column(SettingsController::loginBackgroundCards($cinco), 'image'), true), 't3 con cinco, ninguna vacía');
    $ajena = SettingsController::loginBackgroundCards(['statics/zz-ajena.jpg', $f('bg1.jpg')]);
    $check(array_column($ajena, 'field') === ['background-2', 'background-1', 'background-3', 'background-4', 'background-5'], 't4 una ruta que no es bg<n> se cambia con un nombre libre, sin chocar', json_encode(array_column($ajena, 'field')) ?: '');
    $check(count(SettingsController::loginBackgroundCards([...$cinco, $f('bg9.jpg')])) === 5, 't5 nunca más de cinco tarjetas');
    $check(SettingsController::backgroundsWithout([3 => $f('bg1.jpg'), 7 => $f('bg3.jpg')], $f('bg1.jpg')) === [$f('bg3.jpg')], 't6 la lista sin uno sale compacta: en JSON es un arreglo');
    $check(SettingsController::backgroundsWithout([$f('bg1.jpg')], $f('bg1.jpg')) === null, 't7 DISCRIMINANTE: quitar el último da null');
    $check(SettingsController::backgroundsWithout([$f('bg1.jpg'), $f('bg2.jpg')], $f('bg9.jpg')) === null && SettingsController::backgroundsWithout([$f('bg1.jpg')], '') === null, 't8 lo que no está, o vacío, da null');
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que previous-file-missing.
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
    $camino = function (string $url) use ($prefijoBase): string {
        $path = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $rutaBorrar = $camino(SettingsController::routeName('appearance-backgrounds-delete', [], true));
    $rutaPantalla = $camino(SettingsController::routeName('appearance-backgrounds', [], true));
    $rutaAcceso = $camino(UsersController::routeName('form-login', [], true));
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, ?string $jwt, ?array $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ['X-Requested-With: XMLHttpRequest'];
        if ($jwt !== null) {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'data' => is_array($datos) ? $datos : []];
    };

    $tabla = (new SettingsModel())->getModel()->getTable();
    $crudo = function () use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute(['backgrounds']);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $poner = function (string $json) use ($database, $tabla, $crudo): void {
        if ($crudo() === null) {
            $database->prepare("INSERT INTO `{$tabla}` (name, value) VALUES (?, ?)")->execute(['backgrounds', $json]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$json, 'backgrounds']);
        }
    };
    $lista = function () use ($crudo): mixed {
        $valor = $crudo();
        return $valor === null ? null : json_decode($valor, true);
    };
    $crudoAntes = $crudo();
    //Ningún archivo de la carpeta de fábrica puede cambiar: están versionados, y Eliminar solo borra subidas.
    $huellas = function () use ($carpeta): array {
        $huellas = [];
        foreach (glob(basepath($carpeta) . '/*') ?: [] as $archivo) {
            $huellas[basename($archivo)] = sha1_file($archivo);
        }
        return $huellas;
    };
    $huellasAntes = $huellas();
    $prefijo = 'zz-prueba-fondos-' . bin2hex(random_bytes(3));
    $ids = [];

    try {
        $check(count($huellasAntes) >= 5, 'banco: la carpeta de fondos tiene sus archivos', (string) count($huellasAntes));
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Fondos';
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

        //─── d · El borrado ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Quita de la lista lo que se pide, deja al menos uno y no toca archivos');
        $poner((string) json_encode([$f('bg1.jpg'), $f('bg2.jpg'), $f('bg3.jpg')]));
        $r = $pedir($rutaBorrar, null, ['background' => $f('bg2.jpg')]);
        $check(($r['data']['success'] ?? null) !== true && $lista() === [$f('bg1.jpg'), $f('bg2.jpg'), $f('bg3.jpg')], 'd0 sin sesión no borra', "HTTP {$r['status']}");
        $r = $pedir($rutaBorrar, $jwt, ['background' => $f('bg2.jpg')]);
        $check($r['status'] === 200 && ($r['data']['success'] ?? null) === true && $lista() === [$f('bg1.jpg'), $f('bg3.jpg')], 'd1 CANARIO: quita el del medio y la lista queda compacta', "HTTP {$r['status']}, " . var_export($crudo(), true));
        $check(str_starts_with((string) $crudo(), '['), 'd2 en crudo es un arreglo JSON, no un objeto', (string) $crudo());
        $check($huellas() === $huellasAntes, 'd3 DISCRIMINANTE: ningún archivo de la carpeta cambió ni desapareció');
        foreach ([
            'el que ya no está' => $f('bg2.jpg'),
            'una ruta fuera de la carpeta' => 'app/config/config.php',
            'una ruta que sale con ..' => $f('../../../../app/config/config.php'),
            'vacío' => '',
        ] as $nombre => $valor) {
            $r = $pedir($rutaBorrar, $jwt, ['background' => $valor]);
            $check(($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? '') === __(SettingsController::LANG_GROUP, 'Ese fondo no está entre los fondos del inicio de sesión.') && $lista() === [$f('bg1.jpg'), $f('bg3.jpg')], "d4 DISCRIMINANTE: {$nombre}, se rechaza y la lista no cambia", "HTTP {$r['status']}, " . mb_substr((string) ($r['data']['message'] ?? ''), 0, 120));
        }
        $r = $pedir($rutaBorrar, $jwt, ['background' => $f('bg1.jpg')]);
        $check(($r['data']['success'] ?? null) === true && $lista() === [$f('bg3.jpg')], 'd5 quita otro y queda uno', var_export($crudo(), true));
        $r = $pedir($rutaBorrar, $jwt, ['background' => $f('bg3.jpg')]);
        $check(($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? '') === __(SettingsController::LANG_GROUP, 'Debe quedar al menos un fondo.') && $lista() === [$f('bg3.jpg')], 'd6 DISCRIMINANTE: el último se rechaza y se queda', "HTTP {$r['status']}, " . var_export($crudo(), true));
        $poner((string) json_encode(['0' => $f('bg1.jpg'), '3' => $f('bg3.jpg')], \JSON_FORCE_OBJECT));
        $r = $pedir($rutaBorrar, $jwt, ['background' => $f('bg1.jpg')]);
        $check(($r['data']['success'] ?? null) === true && $crudo() !== null && str_starts_with((string) $crudo(), '[') && $lista() === [$f('bg3.jpg')], 'd7 una opción guardada con claves sueltas sale compacta', var_export($crudo(), true));
        $check($huellas() === $huellasAntes, 'd8 y sigue sin cambiar ningún archivo');
        echoTerminal(' ');

        //─── l · Lo que lo lee: el acceso y la pantalla ─────────────────────────────────────────────
        echoTerminal('[l] El acceso recibe una lista de 1 a 5, y la pantalla deshabilita Eliminar con una sola');
        $delAcceso = function () use ($pedir, $rutaAcceso): mixed {
            $html = $pedir($rutaAcceso, null, null)['body'];
            return preg_match('/bg-js="([^"]*)"/', $html, $m) === 1 ? json_decode((string) base64_decode($m[1]), true) : 'sin bg-js';
        };
        $botones = function () use ($pedir, $rutaPantalla, $jwt): array {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pedir($rutaPantalla, $jwt, null)['body']);
            $xpath = new \DOMXPath($dom);
            $visibles = 0;
            $deshabilitados = 0;
            foreach ($xpath->query('//*[@data-login-backgrounds]//*[@data-image-card-delete-holder]') ?: [] as $holder) {
                if ($holder instanceof \DOMElement && !$holder->hasAttribute('hidden')) {
                    $visibles++;
                    $boton = $holder->getElementsByTagName('button')->item(0);
                    $deshabilitados += $boton instanceof \DOMElement && $boton->hasAttribute('disabled') ? 1 : 0;
                }
            }
            $tarjetas = $xpath->query('//*[@data-login-backgrounds]//*[@data-image-card]');
            return ['tarjetas' => $tarjetas !== false ? $tarjetas->length : 0, 'visibles' => $visibles, 'deshabilitados' => $deshabilitados];
        };
        foreach ([1 => [$f('bg3.jpg')], 3 => [$f('bg1.jpg'), $f('bg2.jpg'), $f('bg3.jpg')], 5 => $cinco] as $cuantos => $fondos) {
            $poner((string) json_encode($fondos));
            $check($delAcceso() === $fondos, "l1 con {$cuantos}, el acceso recibe esa lista, en orden", json_encode($delAcceso()) ?: '');
            $pantalla = $botones();
            $check($pantalla === ['tarjetas' => 5, 'visibles' => $cuantos, 'deshabilitados' => $cuantos === 1 ? 1 : 0], "l2 con {$cuantos}, cinco tarjetas, {$cuantos} con Eliminar" . ($cuantos === 1 ? ', deshabilitado' : ''), json_encode($pantalla) ?: '');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if ($crudoAntes === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute(['backgrounds']);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$crudoAntes, 'backgrounds']);
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && $crudo() === $crudoAntes && $huellas() === $huellasAntes, 'z1 no queda el usuario, la opción vuelve a su valor en crudo y la carpeta está igual', "usuarios {$quedan}");
    }

    return $balance();

})->setDescription('Eliminar un fondo del inicio de sesión: lo quita de la opción, que queda como lista compacta; rechaza el último y lo que no está en la lista; no toca los archivos de fábrica; el acceso y la pantalla lo leen con 1, 3 y 5. Por HTTP; repone la opción.')->setEffects([CliActions::EFFECT_DATABASE])->register();
