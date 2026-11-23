<?php

//Eliminar un punto de Ubicaciones (H23), por HTTP: con permiso lo quita; sin permiso, 403; inexistente, 404; y el botón
//sale en la edición solo con la ruta concedida. Siembra un punto y dos usuarios zz-prueba- y los retira.
//@areas nucleo
//@requires-module Organizations\OrganizationsRoutes

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Controllers\Point;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\PointMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/locations-point-delete', function ($args) {

    echoTerminal("\e[33m[TEST:LocationsPointDelete] Eliminar un punto de Ubicaciones\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que access-screens.
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
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, string $jwt, bool $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ] + ($post ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => ''] : []));
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        $datos = json_decode($body, true);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'data' => is_array($datos) ? $datos : []];
    };

    $prefijo = 'zz-prueba-punto-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $puntos = [];
    $existe = fn (int $id): bool => (new PointMapper($id))->id !== null;

    try {
        $ciudad = (int) $database->query('SELECT id FROM `' . CityMapper::PREFIX_TABLE . CityMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        if (!$check($ciudad > 0, 'banco: hay al menos una ciudad donde poner el punto')) {
            return $balance();
        }

        $usuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.invalid";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Punto';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[] = (int) $u->id;
            return (int) $u->id;
        };
        $jwtRoot = UserSessions::issueToken($usuario('root', UsersModel::TYPE_USER_ROOT));
        $jwtGeneral = UserSessions::issueToken($usuario('general', UsersModel::TYPE_USER_GENERAL));

        $p = new PointMapper();
        $p->city = $ciudad;
        $p->address = "{$prefijo}-direccion";
        $p->name = "{$prefijo}-punto";
        $p->longitude = -74.08;
        $p->latitude = 4.6;
        $p->active = 1;
        $p->save();
        $idPunto = (int) $database->query('SELECT id FROM `' . PointMapper::PREFIX_TABLE . PointMapper::TABLE . '` WHERE name = ' . $database->quote("{$prefijo}-punto"))->fetchColumn();
        $puntos[] = $idPunto;
        $check($idPunto > 0 && $existe($idPunto), 'c0 banco: el punto zz- existe', (string) $idPunto);

        $rutaBorrar = $camino(Point::routeName('actions-delete', ['id' => $idPunto], true));
        $rutaEditar = $camino(Point::routeName('forms-edit', ['id' => $idPunto], true));

        //─── b · El botón ───────────────────────────────────────────────────────────────────────────
        $formulario = $pedir($rutaEditar, $jwtRoot, false);
        $check($formulario['status'] === 200 && str_contains($formulario['body'], 'delete-point-button') && str_contains($formulario['body'], htmlspecialchars($rutaBorrar, ENT_QUOTES)), 'b1 DISCRIMINANTE: la edición del punto, con permiso, trae el botón «Eliminar» con su ruta', "HTTP {$formulario['status']}");
        $rutaCiudad = $camino(\PiecesPHP\App\Locations\Controllers\City::routeName('forms-edit', ['id' => $ciudad], true));
        $check(!str_contains($pedir($rutaCiudad, $jwtRoot, false)['body'], 'delete-point-button'), 'b2 y la edición de una ciudad (el mismo formulario) no lo trae');

        //─── r · Los rechazos ──────────────────────────────────────────────────────────────────────
        $sinPermiso = $pedir($rutaBorrar, $jwtGeneral, true);
        $check($sinPermiso['status'] === 403 && $existe($idPunto), 'r1 DISCRIMINANTE: sin permiso, 403, y el punto sigue', "HTTP {$sinPermiso['status']}");
        $inexistente = $pedir($camino(Point::routeName('actions-delete', ['id' => 2147483000], true)), $jwtRoot, true);
        $check($inexistente['status'] === 404, 'r2 DISCRIMINANTE: un punto que no existe, 404', "HTTP {$inexistente['status']}");

        //─── e · Eliminar ──────────────────────────────────────────────────────────────────────────
        $borrado = $pedir($rutaBorrar, $jwtRoot, true);
        $check($borrado['status'] === 200 && ($borrado['data']['success'] ?? null) === true && !$existe($idPunto), 'e1 DISCRIMINANTE: con permiso, lo quita', "HTTP {$borrado['status']} · " . mb_substr((string) ($borrado['data']['message'] ?? $borrado['body']), 0, 160));
        $check(($borrado['data']['values']['redirect'] ?? null) === true && $camino((string) ($borrado['data']['values']['redirect_to'] ?? '')) === $camino(Point::routeName('list', [], true)), 'e2 y lleva al listado de puntos');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($puntos as $id) {
            $database->prepare('DELETE FROM `' . PointMapper::PREFIX_TABLE . PointMapper::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query('SELECT COUNT(*) FROM `' . PointMapper::PREFIX_TABLE . PointMapper::TABLE . '` WHERE name LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no quedan el punto ni los usuarios de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Eliminar un punto de Ubicaciones por HTTP: con permiso lo quita, sin permiso 403, inexistente 404, y el botón solo en la edición de un punto. Siembra un punto y dos usuarios zz-prueba- y los retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
