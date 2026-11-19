<?php

//P136: un perfil oculto por su dueño no sale en el Directorio ni en el mapa, y su página da 404 a los demás; el dueño y
//root la siguen viendo. Por HTTP (Directorio y página) y en proceso (mapa). Siembra zz- y lo retira entero.
//@areas src/app/classes/Directory/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use Directory\Controllers\AllProfilesController;
use Directory\Controllers\ProfileController;
use Directory\DirectoryRoutes;
use Directory\Geo\PersonsGeoSource;
use MySpace\Controllers\MyProfileController;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/profile-visibility', function ($args) {

    echoTerminal("\e[33m[TEST:ProfileVisibility] Un perfil oculto no sale en el Directorio ni en el mapa, y su página solo la ven su dueño y root\e[39m");
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

    //Los textos de «Mi perfil» y de «Perfil de organización» nombran solo lo encendido: un clon con el Directorio o el mapa
    //apagado no promete lo que no tiene. Por tokens, en es o en, y sin red.
    echoTerminal('[t] Los textos de visibilidad, según el Directorio y el mapa');
    $nombra = fn(?string $texto, string $token): bool => $texto !== null && str_contains(mb_strtolower($texto), $token);
    foreach (['persona' => [MyProfileController::class, 'publicVisibilityTexts'], 'organización' => [MyOrganizationProfileController::class, 'publicVisibilityTexts']] as $quien => $textos) {
        foreach ([[true, true], [true, false], [false, true], [false, false]] as [$directorio, $mapa]) {
            $r = $textos($directorio, $mapa);
            $caso = "{$quien}, Directorio " . ($directorio ? 'sí' : 'no') . ', mapa ' . ($mapa ? 'sí' : 'no');
            $detalle = (string) json_encode($r, JSON_UNESCAPED_UNICODE);
            if (!$directorio && !$mapa) {
                //La casilla de la organización sigue: decide además si se ve su ficha.
                $casilla = $quien === 'persona' ? $r['publicVisibleLabel'] === null : $r['publicVisibleLabel'] !== null && !$nombra($r['publicVisibleLabel'], 'directo') && !$nombra($r['publicVisibleLabel'], 'map');
                $check($r['description'] === '' && $casilla, "t DISCRIMINANTE: {$caso}: ni frase ni promesa" . ($quien === 'persona' ? ', ni casilla' : ''), $detalle);
                continue;
            }
            $bien = true;
            foreach ([$r['description'], $r['publicVisibleLabel']] as $texto) {
                $bien = $bien && $texto !== null && $texto !== '' && $nombra($texto, 'directo') === $directorio && $nombra($texto, 'map') === $mapa;
            }
            $check($bien, "t DISCRIMINANTE: {$caso}: la frase y la casilla nombran solo lo encendido", $detalle);
        }
    }
    //El cableado, por tokens: la función pura no ve qué se le pasa ni qué hace la vista con un null.
    $compacto = fn (string $relative): string => (string) preg_replace('/\s+/', '', (string) file_get_contents(basepath($relative)));
    $vista = $compacto('app/classes/MySpace/Views/my-profile/my-profile.php');
    $guarda = (string) strstr((string) strstr($vista, '<?phpif($publicVisibleLabel!==null):?>'), '<?phpendif;?>', true);
    $check(substr_count($vista, 'name="publicVisible"') === 2 && substr_count($guarda, 'name="publicVisible"') === 2, 't2 DISCRIMINANTE: la casilla de la persona y su oculto «0», los dos dentro de la guarda: sin ella no se envía nada', (string) substr_count($guarda, 'name="publicVisible"'));
    $persona = $compacto('app/classes/MySpace/Controllers/MyProfileController.php');
    $check(str_contains($persona, 'self::publicVisibilityTexts(DirectoryRoutes::ENABLE,GeoRoutes::ENABLE&&GeoSourceRegistry::get(PersonsGeoSource::KEY)!==null)'), 't3 la persona: Directorio primero, y el mapa solo con su fuente registrada');
    $check(str_contains($persona, 'if($publicVisible!==null){$mapper->publicVisible=$publicVisible;'), 't4 DISCRIMINANTE: sin el campo, el guardado de la persona no toca la visibilidad');
    $organizacion = $compacto('app/classes/Organizations/Controllers/MyOrganizationProfileController.php');
    $check(str_contains($organizacion, 'self::publicVisibilityTexts(DirectoryRoutes::ENABLE,GeoRoutes::ENABLE&&GeoSourceRegistry::get(OrganizationsGeoSource::KEY)!==null)') && str_contains($organizacion, 'if(in_array($publicVisible,[\'0\',\'1\'],true)&&'), 't5 la organización: el mismo orden, y solo guarda con 0 o 1');
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que view-escapes.
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
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-visible-' . bin2hex(random_bytes(3));
    $nombre = 'Zzvisible' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, int $tipo) use ($prefijo, $nombre, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = $nombre;
        $u->secondname = '';
        $u->firstLastname = $sufijo;
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return (int) $u->id;
    };
    $visibilidad = function (int $userID, int $valor): void {
        $m = UserProfileMapper::model();
        $m->resetAll();
        $m->update(['publicVisible' => $valor])->where(['belongsTo' => $userID])->execute();
    };
    $enDirectorio = function (string $jwtRoot) use ($pedir, $camino, $nombre): bool {
        $parametros = ['draw' => 1, 'start' => 0, 'length' => 50, 'search' => ['value' => $nombre, 'regex' => 'false'], 'order' => [['column' => 0, 'dir' => 'asc']], 'columns' => []];
        for ($c = 0; $c < 6; $c++) {
            $parametros['columns'][] = ['data' => $c, 'name' => '', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
        }
        $r = $pedir($camino(AllProfilesController::routeName('datatables', [], true)) . '?' . http_build_query($parametros), $jwtRoot);
        return $r['status'] === 200 && str_contains($r['body'], $nombre);
    };
    //El mapa es el geovisor: los puntos de su fuente de personas, en todo el mundo y buscando el nombre.
    $enMapa = function () use ($nombre): bool {
        $puntos = (new PersonsGeoSource())->points(new GeoQuery(null, 0, [PersonsGeoSource::FILTER_SEARCH => $nombre]));
        return str_contains((string) json_encode($puntos->points(), JSON_UNESCAPED_UNICODE), $nombre);
    };

    try {
        $duenio = $crear('duenio', UsersModel::TYPE_USER_GENERAL);
        $otro = $crear('otro', UsersModel::TYPE_USER_ADMIN_GRAL);
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $perfil = UserProfileMapper::createProfile($duenio);
        $check($perfil !== null && $perfil->id !== null, 'banco: el dueño tiene su perfil', (string) json_encode($ids));
        $m = UserProfileMapper::model();
        $m->resetAll();
        $m->update(['latitude' => 4.6, 'longitude' => -74.1])->where(['belongsTo' => $duenio])->execute();
        //El mapa solo enseña lo aprobado: las filas de aprobación se crean al arrancar, y la prueba ya arrancó.
        SystemApprovalManager::init();
        $jwt = ['duenio' => UserSessions::issueToken($duenio), 'otro' => UserSessions::issueToken($otro), 'root' => UserSessions::issueToken($root)];
        $pagina = $camino(ProfileController::routeName('profile', ['userID' => $duenio], true));

        echoTerminal('[v] Visible, como hasta ahora');
        //El Directorio y la página de perfil son del módulo Directorio; el mapa lee la fuente directamente.
        if (!DirectoryRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m v1: esta instalación tiene Directorio apagado y no hay Directorio ni página de perfil.");
        } else {
            $check($enDirectorio($jwt['root']), 'v1 CANARIO: visible, sale en el Directorio');
        }
        $check($enMapa(), 'v2 CANARIO: visible, sale en el mapa');
        if (!DirectoryRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m v3: esta instalación tiene Directorio apagado y no hay Directorio ni página de perfil.");
        } else {
            $r = $pedir($pagina, $jwt['otro']);
            $check($r['status'] === 200, 'v3 CANARIO: visible, otro usuario ve su página', "HTTP {$r['status']}");
        }

        echoTerminal('[o] Oculto por su dueño');
        $visibilidad($duenio, 0);
        $check(!$enMapa(), 'o2 oculto, no sale en el mapa');
        if (!DirectoryRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m o1, o3, o4 y o5: esta instalación tiene Directorio apagado y no hay Directorio ni página de perfil.");
        } else {
            $check(!$enDirectorio($jwt['root']), 'o1 oculto, no sale en el Directorio');
            $r = $pedir($pagina, $jwt['otro']);
            $check($r['status'] === 404, 'o3 oculto, su página da 404 a otro usuario', "HTTP {$r['status']}");
            $r = $pedir($pagina, $jwt['duenio']);
            $check($r['status'] === 200, 'o4 oculto, su dueño sí la ve', "HTTP {$r['status']}");
            $r = $pedir($pagina, $jwt['root']);
            $check($r['status'] === 200, 'o5 oculto, root sí la ve', "HTTP {$r['status']}");
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Todo lo que apunta a los usuarios de la prueba, por sus claves foráneas, y sus filas de aprobación.
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, perfil, sesión ni aprobación de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('P136: un perfil oculto no sale en el Directorio ni en el mapa, y su página da 404 a otro usuario y 200 al dueño y a root; visible, sale. Por HTTP y en proceso; siembra zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
