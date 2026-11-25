<?php

//Con DATA_IMPORT_EXPORT_USERS en true (o sin definir), las rutas, el enlace de Ajustes y la fila del centro de usuarios; en
//false, nada de eso (se prueba cambiándola en constants.php). Crea un usuario zz- y lo retira.
//@areas src/app/classes/DataImportExportUtility/
//@requires-module DataImportExportUtility\DataImportExportUtilityRoutes

use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/data-import-export-users-optional', function ($args) {

    echoTerminal("\e[33m[TEST:DataImportExportUsersOptional] Los importadores de usuarios, según DATA_IMPORT_EXPORT_USERS\e[39m");
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
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $ruta = function (string $url) use ($prefijoBase): string {
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $activos = DATA_IMPORT_EXPORT_USERS;
    echoTerminal('   · DATA_IMPORT_EXPORT_USERS: ' . ($activos ? 'true' : 'false'));
    $nombres = ['data-transfer-import-users', 'data-transfer-import-users-action', 'data-transfer-import-users-template', 'data-transfer-export-users', 'data-transfer-export-users-form'];
    $registradas = array_values(array_filter($nombres, fn (string $n): bool => is_array(get_route_info($n))));

    if ($activos) {
        $check($registradas === $nombres, 'r1 las cinco rutas de usuarios están registradas', implode(', ', $registradas));
        $check(array_key_exists('users', DataImportExportUtilityRoutes::importers()) && array_key_exists('users', DataImportExportUtilityRoutes::exporters()), 'r2 y sus definiciones también');
    } else {
        $check($registradas === [], 'r1 ninguna ruta de usuarios registrada', implode(', ', $registradas));
        $check(!array_key_exists('users', DataImportExportUtilityRoutes::importers()) && !array_key_exists('users', DataImportExportUtilityRoutes::exporters()), 'r2 ni sus definiciones');
    }

    $prefijo = 'zz-prueba-users-optional-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $database = (new BaseModel())->getDatabase();
    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Importadores';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        $jwt = UserSessions::issueToken((int) $u->id);

        //El centro, como root: la fila de usuarios enlaza a su importación.
        $centro = $pedir($ruta((string) DataTransferController::routeName('hub')), $jwt);
        //Los patrones de las rutas de usuarios (files/dev/route-inventory.json): están en el centro solo si hay fila.
        $filaUsuarios = str_contains($centro['body'], '/data-transfer/import/users/') || str_contains($centro['body'], '/data-transfer/export/users/');
        $check($centro['status'] === 200, 'h1 el centro de Importar y exportar responde 200', "HTTP {$centro['status']}");
        $check($filaUsuarios === $activos, $activos ? 'h2 con la fila de usuarios' : 'h2 sin la fila de usuarios');

        //El enlace de Ajustes: con la ruta sin registrar, su dirección sale vacía y no se enseña.
        $enlace = null;
        $nombreEnlace = __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Importar usuarios');
        $buscar = function ($nodo) use (&$buscar, &$enlace, $nombreEnlace): void {
            if (!is_array($nodo)) {
                return;
            }
            if (($nodo['name'] ?? null) === $nombreEnlace && array_key_exists('href', $nodo)) {
                $enlace = $nodo;
                return;
            }
            foreach ($nodo as $hijo) {
                $buscar($hijo);
            }
        };
        $buscar(SettingsController::panelGroups());
        $check(is_array($enlace), 'a0 el enlace «Importar usuarios» está en los grupos de Ajustes');
        if (is_array($enlace)) {
            $check($activos ? (string) $enlace['href'] !== '' : ((string) $enlace['href'] === '' && $enlace['visible'] === false), $activos ? 'a1 con dirección' : 'a1 sin dirección y oculto', json_encode($enlace) ?: '');
        }
    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => $id])->execute();
        }
        $usuariosModelo = UsersModel::model();
        $usuariosModelo->resetAll();
        //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
        $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $restos = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($restos === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$restos}");
    }

    return $balance();

})->setDescription('Los importadores y exportadores de usuarios siguen a DATA_IMPORT_EXPORT_USERS: rutas, enlace de Ajustes y fila del centro con la constante en true; nada de eso en false.')->setEffects([CliActions::EFFECT_DATABASE])->register();
