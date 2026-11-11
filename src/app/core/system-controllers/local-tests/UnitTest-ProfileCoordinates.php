<?php

//P187: guardar el perfil propio, de una persona o de una organización, deja la posición tal como llega.
//Por HTTP. Crea usuarios y una organización zz- y los retira.
//@areas src/app/classes/MySpace/
//@areas src/app/classes/Organizations/

use MySpace\Controllers\MyProfileController;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/profile-coordinates', function ($args) {

    echoTerminal("\e[33m[TEST:ProfileCoordinates] La posición de un perfil se guarda tal como llega\e[39m");
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
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt, array $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post),
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c2 hay base de datos')) {
        return $balance();
    }
    $cities = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
    $states = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
    $lugar = $database->query("SELECT c.id AS city, s.country AS country FROM `{$cities}` c INNER JOIN `{$states}` s ON s.id = c.state ORDER BY c.id LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
    if (!$check(is_array($lugar), 'c3 hay una ciudad con su país en Ubicaciones')) {
        return $balance();
    }
    $correos = fn(): int => (int) $database->query('SELECT COUNT(*) FROM `pcsphp_mail_log`')->fetchColumn();
    $correosAntes = $correos();

    $prefijo = 'zz-prueba-coordenadas-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $organizaciones = [];
    $crearUsuario = function (string $sufijo, int $tipo, int $organizacion) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Coordenadas';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = $organizacion;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    //Lejos de cualquier desplazamiento de ±0,06°: el sexto decimal basta para verlo.
    [$latitud, $longitud] = [10.987654, -74.812345];

    try {
        //──── [p] La persona ─────────────────────────────────────────────────────────────────
        echoTerminal('[p] El perfil propio de una persona');
        $persona = $crearUsuario('persona', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $guardado = $pedir($camino(MyProfileController::routeName('actions-save-profile', [], true)), UserSessions::issueToken($persona), [
            'jobPosition' => "{$prefijo}-cargo",
            'nationality' => 'Colombiana',
            'country' => (string) $lugar['country'],
            'city' => (string) $lugar['city'],
            'latitude' => (string) $latitud,
            'longitude' => (string) $longitud,
        ]);
        $respuesta = json_decode($guardado['body'], true);
        $check($guardado['status'] === 200 && is_array($respuesta) && ($respuesta['success'] ?? false) === true, 'p1 CANARIO: el formulario guarda', "HTTP {$guardado['status']} · " . mb_substr($guardado['body'], 0, 200));
        $perfil = UserProfileMapper::getProfile($persona);
        $leidas = $perfil !== null ? [(float) $perfil->latitude, (float) $perfil->longitude] : null;
        $check($leidas === [$latitud, $longitud], 'p2 la latitud y la longitud se leen de la base idénticas', (string) json_encode($leidas));

        //──── [o] La organización ────────────────────────────────────────────────────────────
        echoTerminal('[o] El perfil propio de una organización, por su encargado');
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org";
        $o->nit = "{$prefijo}-nit";
        $o->folder = "{$prefijo}-org";
        $o->status = OrganizationMapper::ACTIVE;
        $o->activitySector = 'Sector de prueba';
        $o->informativeEmail = "{$prefijo}-org@example.invalid";
        //Un archivo versionado como logo: nadie lo sustituye, y la limpieza borra la fila sin pasar por el controlador.
        $o->logo = 'statics/images/default-avatar-org.jpg';
        $o->country = (int) $lugar['country'];
        $o->city = (int) $lugar['city'];
        $o->latitude = 4.6;
        $o->longitude = -74.1;
        $o->save();
        $organizaciones[] = (int) $o->id;
        $encargado = $crearUsuario('encargado', UsersModel::TYPE_USER_ADMIN_ORG, (int) $o->id);
        $conEncargado = new OrganizationMapper((int) $o->id);
        $conEncargado->administrator = $encargado;
        $conEncargado->update();
        $guardado = $pedir($camino(MyOrganizationProfileController::routeName('actions-save-profile', [], true)), UserSessions::issueToken($encargado), [
            'name' => "{$prefijo}-org",
            'latitude' => (string) $latitud,
            'longitude' => (string) $longitud,
        ]);
        $respuesta = json_decode($guardado['body'], true);
        $check($guardado['status'] === 200 && is_array($respuesta) && ($respuesta['success'] ?? false) === true, 'o1 CANARIO: el formulario guarda', "HTTP {$guardado['status']} · " . mb_substr($guardado['body'], 0, 200));
        $leida = new OrganizationMapper((int) $o->id);
        $leidas = [(float) $leida->latitude, (float) $leida->longitude];
        $check($leidas === [$latitud, $longitud], 'o2 la latitud y la longitud se leen de la base idénticas', (string) json_encode($leidas));
        $check($correos() === $correosAntes, 'o3 guardar no escribió ningún correo', ($correos() - $correosAntes) . ' nuevos');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            foreach ($organizaciones as $id) {
                $database->prepare('UPDATE `' . OrganizationMapper::TABLE . '` SET modifiedBy = NULL WHERE id = ?')->execute([$id]);
            }
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($usuarios as $id) {
                foreach ($referencias as [$tabla, $columna]) {
                    if ($tabla !== OrganizationMapper::TABLE) {
                        $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
                    }
                }
                $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
                $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
            }
            foreach ($organizaciones as $id) {
                $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([OrganizationMapper::TABLE, (string) $id]);
                $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
        $quedanOrg = (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE nit LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanOrg === 0, 'z2 no queda ninguna organización de la prueba', "quedan {$quedanOrg}");
        $huerfanas = $database->prepare('SELECT COUNT(*) FROM `system_approvals_elements` WHERE (referenceTable = ? AND referenceValue IN (' . implode(',', array_fill(0, max(1, count($usuarios)), '?')) . ')) OR (referenceTable = ? AND referenceValue IN (' . implode(',', array_fill(0, max(1, count($organizaciones)), '?')) . '))');
        $huerfanas->execute(array_merge([UsersModel::TABLE], array_map('strval', array_values($usuarios) ?: [0]), [OrganizationMapper::TABLE], array_map('strval', $organizaciones ?: [0])));
        $check((int) $huerfanas->fetchColumn() === 0, 'z3 no queda ninguna aprobación de lo creado');
    }

    return $balance();

})->setDescription('P187: guardar el perfil propio de una persona (MyProfileController) y el de una organización por su encargado (MyOrganizationProfileController) deja la latitud y la longitud tal como llegan, sin desplazamiento al azar, y sin correo. Por HTTP; crea zz- y lo retira, con sus aprobaciones.')->setEffects([CliActions::EFFECT_DATABASE])->register();
