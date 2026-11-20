<?php

//Un encargado dado de baja (status 6) no gana los permisos de encargado: ni grantsAdministratorPermissions() ni la
//comprobación de «Mi organización». Por HTTP lo que se alcanza y en proceso lo que no, con filas zz- que crea y retira.
//@areas src/app/classes/Organizations/

use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/organizations-deleted-administrator-permissions', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationsDeletedAdministratorPermissions] Un encargado dado de baja no gana permisos de encargado\e[39m");
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
    $url = MyOrganizationProfileController::routeName('my-organization-profile', [], true);
    $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
    $rutaMiOrganizacion = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    $pedir = function (string $jwt) use ($base, $rutaMiOrganizacion, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($rutaMiOrganizacion, '/'),
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

    $prefijo = 'zz-prueba-org-enc-permisos-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $organizaciones = [];
    //La comprobación de «Mi organización» es protegida: se llama como la llaman sus tres consumidores de permiso.
    $administratorIDOf = function (OrganizationMapper $organization): ?int {
        $metodo = new \ReflectionMethod(MyOrganizationProfileController::class, 'administratorIDOf');
        $valor = $metodo->invoke(null, $organization);
        return is_int($valor) ? $valor : null;
    };

    try {
        $crearUsuario = function (string $sufijo, int $tipo, int $organizacion) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.invalid";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Permisos';
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
        $crearOrganizacion = function (string $sufijo) use ($prefijo, &$organizaciones): int {
            $o = new OrganizationMapper();
            $o->name = "{$prefijo}-{$sufijo}";
            $o->nit = "{$prefijo}-nit-{$sufijo}";
            $o->folder = "{$prefijo}-{$sufijo}";
            $o->status = OrganizationMapper::ACTIVE;
            $o->activitySector = 'Sector de prueba';
            $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.invalid";
            $o->logo = 'statics/images/default-avatar-org.jpg';
            $o->save();
            $organizaciones[$sufijo] = (int) $o->id;
            return (int) $o->id;
        };
        $asignar = function (int $organizacion, int $usuario): void {
            $o = new OrganizationMapper($organizacion);
            $o->administrator = $usuario;
            $o->update();
        };

        $conActivo = $crearOrganizacion('con-activo');
        $conBaja = $crearOrganizacion('con-baja');
        $activoID = $crearUsuario('enc-activo', UsersModel::TYPE_USER_ADMIN_ORG, $conActivo);
        $miembroID = $crearUsuario('miembro', UsersModel::TYPE_USER_ADMIN_ORG, $conActivo);
        $bajaID = $crearUsuario('enc-baja', UsersModel::TYPE_USER_ADMIN_ORG, $conBaja);
        $asignar($conActivo, $activoID);
        $asignar($conBaja, $bajaID);
        $activo = UserSessions::issueToken($activoID);
        $miembro = UserSessions::issueToken($miembroID);
        //El token se emite ANTES de la baja: el caso de quien tenía sesión.
        $baja = UserSessions::issueToken($bajaID);

        echoTerminal('[a] Antes de la baja (canarios)');
        $check(OrganizationMapper::grantsAdministratorPermissions($bajaID, new OrganizationMapper($conBaja)), 'a1 CANARIO: el encargado aún activo recibe los permisos de encargado');
        $check($administratorIDOf(new OrganizationMapper($conBaja)) === $bajaID, 'a2 CANARIO: «Mi organización» lo reconoce como encargado');
        $r = $pedir($baja);
        $check($r['status'] === 200, 'a3 CANARIO: por HTTP abre su «Mi organización»', $resumen($r));

        $modeloUsuarios = UsersModel::model();
        $modeloUsuarios->resetAll();
        //RETORNO-IGNORADO: lo comprueba a4 leyendo la fila.
        $modeloUsuarios->update(['status' => UsersModel::STATUS_USER_DELETED])->where(new WhereSegment([WhereItem::isEqual('id', $bajaID)]))->execute();
        $estadoBaja = (int) $database->query("SELECT status FROM `{$tablaUsuarios}` WHERE id = {$bajaID}")->fetchColumn();
        $administrator = (new OrganizationMapper($conBaja))->administrator;
        $administratorID = $administrator instanceof UsersModel ? (int) $administrator->id : (is_int($administrator) ? $administrator : null);
        $check($estadoBaja === UsersModel::STATUS_USER_DELETED && $administratorID === $bajaID, 'a4 el encargado queda en status 6 y la organización conserva `administrator`', "estado {$estadoBaja}, administrator " . var_export($administratorID, true));

        echoTerminal('');
        echoTerminal('[b] Encargado dado de baja: sin permisos de encargado');
        $check(!OrganizationMapper::grantsAdministratorPermissions($bajaID, new OrganizationMapper($conBaja)), 'b1 DISCRIMINANTE: grantsAdministratorPermissions() no se los da (en proceso: por HTTP no tiene sesión)');
        $check($administratorIDOf(new OrganizationMapper($conBaja)) === null, 'b2 DISCRIMINANTE: «Mi organización» no lo reconoce como encargado (en proceso: por HTTP no tiene sesión)');
        $r = $pedir($baja);
        echoTerminal("   medido: dado de baja con su token de antes, {$resumen($r)}");
        $check($r['status'] !== 200, 'b3 por HTTP, con su token de antes, no abre «Mi organización» (lo corta la sesión, antes que estas guardas)', $resumen($r));

        echoTerminal('');
        echoTerminal('[c] Encargado activo: conserva sus permisos (canarios)');
        $check(OrganizationMapper::grantsAdministratorPermissions($activoID, new OrganizationMapper($conActivo)), 'c1 CANARIO: grantsAdministratorPermissions() se los da');
        $check($administratorIDOf(new OrganizationMapper($conActivo)) === $activoID, 'c2 CANARIO: «Mi organización» lo reconoce como encargado');
        $r = $pedir($activo);
        $check($r['status'] === 200, 'c3 CANARIO: por HTTP abre su «Mi organización»', $resumen($r));
        $r = $pedir($miembro);
        $check($r['status'] === 403 && !OrganizationMapper::grantsAdministratorPermissions($miembroID, new OrganizationMapper($conActivo)), 'c4 un miembro que no es el encargado no abre «Mi organización» ni recibe los permisos', $resumen($r));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $ids = $usuarios !== [] ? implode(',', array_map('intval', $usuarios)) : '0';
            //Usuarios a la global, luego las organizaciones (soltando modifiedBy), luego los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . " WHERE id IN ({$ids})");
            $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})");
            foreach ($organizaciones as $id) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaOrganizaciones, (string) $id]);
                $database->prepare("UPDATE `{$tablaOrganizaciones}` SET modifiedBy = NULL WHERE id = ?")->execute([$id]);
                $database->prepare("DELETE FROM `{$tablaOrganizaciones}` WHERE id = ?")->execute([$id]);
            }
            foreach ($usuarios as $id) {
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $id]);
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
                $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?")->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $ids = $usuarios !== [] ? implode(',', array_map('intval', $usuarios)) : '0';
        $quedan = [
            'usuarios' => (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn(),
            'organizaciones' => (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn(),
            'sesiones' => (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})")->fetchColumn(),
            'perfiles' => (int) $database->query("SELECT COUNT(*) FROM user_system_profile WHERE belongsTo IN ({$ids})")->fetchColumn(),
        ];
        $check(array_sum($quedan) === 0, 'z1 no queda ningún usuario, organización, sesión ni perfil de la prueba', json_encode($quedan) ?: '');
    }

    return $balance();

})->setDescription('Un encargado dado de baja (status 6) no recibe los permisos de encargado: grantsAdministratorPermissions() y la comprobación de «Mi organización» lo leen como ausente; el activo los conserva y un miembro no los tiene. En proceso lo que por HTTP no se alcanza (el dado de baja no tiene sesión). Con filas zz- que crea y retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
