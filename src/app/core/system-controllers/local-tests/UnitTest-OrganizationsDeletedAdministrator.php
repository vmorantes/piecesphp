<?php

//Un encargado dado de baja no se enseña en el perfil de su organización ni en «Mi organización», que pide asignar otro
//(P380): dar de baja no limpia `administrator`, se lee como ausente. Por HTTP, con filas zz- que crea y retira.
//@areas src/app/classes/Organizations/
//@requires-module Directory\DirectoryRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Directory\Controllers\ProfileController;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/organizations-deleted-administrator', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationsDeletedAdministrator] Un encargado dado de baja no se enseña\e[39m");
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
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $sufijo, array $parametros, string $controlador) use ($prefijoBase): string {
        $url = $controlador::routeName($sufijo, $parametros, true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $resumen = fn (array $r): string => "HTTP {$r['status']} «" . mb_substr(trim(strip_tags($r['body'])), 0, 80) . '»';

    $prefijo = 'zz-prueba-org-encargado-baja-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $usuarios = [];
    $organizaciones = [];
    //La marca del formulario de asignar encargado (my-organization-profile-assign-administrator.php).
    $marcaAsignar = 'my-organization-profile-assign-administrator';

    try {
        $crearUsuario = function (string $sufijo, int $tipo, int $organizacion, string $apellido) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = $apellido;
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
        //Completa según profileIsComplete(), para que su perfil público pinte la ficha con el encargado.
        $cities = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
        $states = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
        $lugar = $database->query("SELECT c.id AS city, s.country AS country FROM `{$cities}` c INNER JOIN `{$states}` s ON s.id = c.state ORDER BY c.id LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $crearOrganizacion = function (string $sufijo) use ($prefijo, $lugar, &$organizaciones): int {
            $o = new OrganizationMapper();
            $o->name = "{$prefijo}-{$sufijo}";
            $o->nit = "{$prefijo}-nit-{$sufijo}";
            $o->folder = "{$prefijo}-{$sufijo}";
            $o->status = OrganizationMapper::ACTIVE;
            $o->activitySector = 'Sector de prueba';
            $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.com";
            $o->logo = 'statics/images/default-avatar-org.jpg';
            $o->country = is_array($lugar) ? (int) $lugar['country'] : null;
            $o->city = is_array($lugar) ? (int) $lugar['city'] : null;
            $o->latitude = 4.6;
            $o->longitude = -74.1;
            $o->save();
            $organizaciones[$sufijo] = (int) $o->id;
            return (int) $o->id;
        };
        $asignar = function (int $organizacion, int $usuario): void {
            $o = new OrganizationMapper($organizacion);
            $o->administrator = $usuario;
            $o->update();
        };

        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL, 'Raiz'));
        $conBaja = $crearOrganizacion('con-baja');
        $conActivo = $crearOrganizacion('con-activo');
        $apellidoBaja = 'Encargadobaja' . bin2hex(random_bytes(3));
        $apellidoActivo = 'Encargadoactivo' . bin2hex(random_bytes(3));
        $bajaID = $crearUsuario('enc-baja', UsersModel::TYPE_USER_ADMIN_ORG, $conBaja, $apellidoBaja);
        $activoID = $crearUsuario('enc-activo', UsersModel::TYPE_USER_ADMIN_ORG, $conActivo, $apellidoActivo);
        $asignar($conBaja, $bajaID);
        $asignar($conActivo, $activoID);
        $correoBaja = "{$prefijo}-enc-baja@example.com";
        $correoActivo = "{$prefijo}-enc-activo@example.com";
        $activo = UserSessions::issueToken($activoID);

        $perfil = fn (int $id, string $jwt): array => $pedir($camino('profile', ['organizationID' => $id], OrganizationProfileController::class), $jwt);
        $miOrganizacion = fn (int $id): array => $pedir($camino('my-organization-profile', ['organizationID' => $id], MyOrganizationProfileController::class), $root);
        $enlacePerfil = fn (int $userID): string => $camino('profile', ['userID' => $userID], ProfileController::class);
        //El nombre cubre también el `alt` del avatar; el enlace, el botón a su perfil.
        $muestra = fn (array $r, string $apellido, string $correo, int $userID): bool => str_contains($r['body'], $apellido) || str_contains($r['body'], $correo) || str_contains($r['body'], $enlacePerfil($userID));

        echoTerminal('[a] Antes de la baja: los dos encargados se enseñan (canarios)');
        $perfilAntes = $perfil($conBaja, $root);
        $check($perfilAntes['status'] === 200 && $muestra($perfilAntes, $apellidoBaja, $correoBaja, $bajaID), 'a1 CANARIO: el perfil público enseña al encargado que se va a dar de baja', $resumen($perfilAntes));
        $miAntes = $miOrganizacion($conBaja);
        $check($miAntes['status'] === 200 && str_contains($miAntes['body'], $apellidoBaja) && str_contains($miAntes['body'], $correoBaja) && !str_contains($miAntes['body'], $marcaAsignar), 'a2 CANARIO: «Mi organización» enseña su nombre y correo, sin el formulario de asignar', $resumen($miAntes));

        //La baja, como estaba antes de este arreglo: solo el estado, sin tocar `administrator`. Vale para las ya hechas.
        $modeloUsuarios = UsersModel::model();
        $modeloUsuarios->resetAll();
        //RETORNO-IGNORADO: lo comprueba a3 leyendo la fila.
        $modeloUsuarios->update(['status' => UsersModel::STATUS_USER_DELETED])->where(new WhereSegment([WhereItem::isEqual('id', $bajaID)]))->execute();
        $estadoBaja = (int) $database->query('SELECT status FROM `' . UsersModel::TABLE . '` WHERE id = ' . $bajaID)->fetchColumn();
        //`administrator` es una meta-propiedad (vive en `meta`), no una columna: se lee por el mapeador.
        $encargadoDe = function (int $organizacion): ?int {
            $administrator = (new OrganizationMapper($organizacion))->administrator;
            $id = $administrator instanceof UsersModel ? $administrator->id : $administrator;
            return $id !== null ? (int) $id : null;
        };
        $columna = $encargadoDe($conBaja);
        $check($estadoBaja === UsersModel::STATUS_USER_DELETED && $columna === $bajaID, 'a3 el encargado queda en status 6 y la organización conserva `administrator`', "estado {$estadoBaja}, administrator " . var_export($columna, true));

        echoTerminal('');
        echoTerminal('[b] Encargado dado de baja: se lee como ausente');
        $perfilRoot = $perfil($conBaja, $root);
        $check($perfilRoot['status'] === 200 && str_contains($perfilRoot['body'], "{$prefijo}-con-baja"), 'b1 CANARIO: el perfil público de su organización sigue respondiendo', $resumen($perfilRoot));
        $check(!$muestra($perfilRoot, $apellidoBaja, $correoBaja, $bajaID), 'b2 DISCRIMINANTE: el perfil público, a root, no enseña su nombre, avatar, correo ni enlace a su perfil');
        $perfilOtro = $perfil($conBaja, $activo);
        $check($perfilOtro['status'] === 200 && !$muestra($perfilOtro, $apellidoBaja, $correoBaja, $bajaID), 'b3 el perfil público, al encargado de otra organización, tampoco', $resumen($perfilOtro));
        $miBaja = $miOrganizacion($conBaja);
        $check($miBaja['status'] === 200 && !$muestra($miBaja, $apellidoBaja, $correoBaja, $bajaID), 'b4 DISCRIMINANTE: «Mi organización», a root, no enseña su nombre, avatar, correo ni enlace', $resumen($miBaja));
        $check(str_contains($miBaja['body'], $marcaAsignar), 'b5 DISCRIMINANTE: «Mi organización» enseña el formulario de asignar encargado', $resumen($miBaja));
        $columnaDespues = $encargadoDe($conBaja);
        $check($columnaDespues === $bajaID, 'b6 leer no escribe: `administrator` sigue apuntando al dado de baja', 'administrator ' . var_export($columnaDespues, true));

        echoTerminal('');
        echoTerminal('[c] Encargado activo: se enseña (canarios)');
        $perfilActivo = $perfil($conActivo, $root);
        $check($perfilActivo['status'] === 200 && str_contains($perfilActivo['body'], $apellidoActivo) && str_contains($perfilActivo['body'], $enlacePerfil($activoID)), 'c1 CANARIO: el perfil público enseña al encargado activo y su enlace', $resumen($perfilActivo));
        $miActivo = $miOrganizacion($conActivo);
        $check($miActivo['status'] === 200 && str_contains($miActivo['body'], $apellidoActivo) && str_contains($miActivo['body'], $correoActivo) && !str_contains($miActivo['body'], $marcaAsignar), 'c2 CANARIO: «Mi organización», a root, enseña al encargado activo, sin formulario de asignar', $resumen($miActivo));
        $miPropia = $pedir($camino('my-organization-profile', [], MyOrganizationProfileController::class), $activo);
        $check($miPropia['status'] === 200 && str_contains($miPropia['body'], $apellidoActivo) && !str_contains($miPropia['body'], $marcaAsignar), 'c3 CANARIO: el encargado activo abre su «Mi organización» y se ve', $resumen($miPropia));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($usuarios as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
                //Las sesiones que abre issueToken() para el root y el encargado activo.
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$id]);
            }
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            foreach ($organizaciones as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, OrganizationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $modeloOrganizaciones = OrganizationMapper::model();
            $modeloOrganizaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $modeloOrganizaciones->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanUsuarios = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanSesiones = 0;
        foreach ($usuarios as $id) {
            $quedanSesiones += (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . '` WHERE userID = ' . (int) $id)->fetchColumn();
        }
        $check($quedanUsuarios === 0 && $quedanSesiones === 0, 'z1 no queda ningún usuario ni sesión de la prueba', "quedan {$quedanUsuarios}, sesiones {$quedanSesiones}");
        $quedanOrganizaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%") . ' OR nit LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ($organizaciones as $id) {
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaOrganizaciones) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $check($quedanOrganizaciones === 0 && $quedanAprobaciones === 0, 'z2 no queda ninguna organización ni aprobación de la prueba', "organizaciones {$quedanOrganizaciones}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Un encargado dado de baja (status 6) no se enseña: ni en el perfil público de su organización ni en «Mi organización», que pide asignar otro; dar de baja no limpia `administrator` y leer no escribe; el encargado activo sí se enseña (P380). Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
