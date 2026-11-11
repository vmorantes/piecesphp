<?php

//Editar un usuario que no existe responde 404, no 500: la autoridad se miraba antes de comprobar que el usuario existía.
//Por HTTP, con un root de prueba zz-agente-ef- que crea y borra con sus sesiones, intentos, registro y perfil.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-edit-not-found', function ($args) {

    echoTerminal("\e[33m[TEST:UsersEditNotFound] Editar un usuario que no existe responde 404\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'p1 PRE: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (int $id): string {
        $url = get_route('users-form-edit', ['id' => $id], true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): int {
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
        curl_exec($handle);
        return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    };

    $tablaUsuarios = UsersModel::TABLE;
    $prefijo = 'zz-agente-ef-' . bin2hex(random_bytes(3));
    $rootPrueba = null;
    try {
        $insertar = $database->prepare("INSERT INTO `{$tablaUsuarios}` (username, password, firstname, firstLastname, email, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertar->execute([$prefijo, password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT), 'ZZ', 'Edición', "{$prefijo}@example.invalid", UsersModel::TYPE_USER_ROOT, UsersModel::STATUS_USER_ACTIVE]);
        $rootPrueba = (int) $database->lastInsertId();
        $jwt = UserSessions::issueToken($rootPrueba);

        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaUsuarios}`")->fetchColumn();
        $estado = $pedir($ruta($inexistente), $jwt);
        $check($estado === 404, "e1 editar el id inexistente {$inexistente} responde 404", "HTTP {$estado}");

        //Un usuario que existe, no Eliminado, de un tipo con rol: la edición se pinta.
        $existente = (int) $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE id > 1 AND id <> {$rootPrueba} AND status <> " . UsersModel::STATUS_USER_DELETED
            . ' AND type IN (' . UsersModel::TYPE_USER_GENERAL . ', ' . UsersModel::TYPE_USER_ADMIN_GRAL . ') ORDER BY id LIMIT 1')->fetchColumn();
        if ($check($existente > 0, 'p2 PRE: hay un usuario general o administrador general que no está Eliminado')) {
            $estado = $pedir($ruta($existente), $jwt);
            $check($estado === 200, "e2 CANARIO: editar el id {$existente}, que existe, responde 200", "HTTP {$estado}");
        }
    } finally {
        if ($rootPrueba !== null) {
            $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$rootPrueba]);
            $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE userID = ?')->execute([$rootPrueba]);
            $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)')->execute([$rootPrueba, $tablaUsuarios, (string) $rootPrueba]);
            $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $rootPrueba]);
            $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$rootPrueba]);
            $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?")->execute([$rootPrueba]);
        }
    }
    $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
    $check($quedan === 0, 'z1 el root de prueba no queda', "quedan {$quedan}");

    return $balance();

})->setDescription('Editar un usuario que no existe responde 404 y no 500, y uno que existe sigue pintándose. Por HTTP, con un root zz-agente-ef- que crea y borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
