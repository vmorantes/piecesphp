<?php

//La autoaprobación solo pasa de pendiente a activo: inactivo, bloqueado, rechazado y borrado se quedan como están, también
//en una organización aprobada. Y un pendiente de una organización aprobada sigue quedando activo al navegar (por HTTP).

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\Packages\UsersApprovalHandler;

CliActions::make('unit-tests:core/auto-approval-status', function ($args) {

    echoTerminal("\e[33m[TEST:AutoApprovalStatus] La autoaprobación solo pasa de pendiente a activo\e[39m");
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
    $global = OrganizationMapper::INITIAL_ID_GLOBAL;
    $check(\SystemApprovals\Util\SystemApprovalManager::getInstance()->isApproved(OrganizationMapper::class, $global), 'p2 la organización global está aprobada: es el caso que reactivaba');

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $prefijo = 'zz-prueba-autoaprobacion-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];

    try {
        $crearUsuario = function (string $sufijo, int $estado) use ($prefijo, $global, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Autoaprobacion';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = $global;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $estadoDe = fn (int $id): int => (int) $database->query("SELECT status FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();

        echoTerminal('[a] La autoaprobación, sobre cada estado, en una organización aprobada');
        $casos = [
            'pendiente' => [UsersModel::STATUS_USER_APPROVED_PENDING, UsersModel::STATUS_USER_ACTIVE],
            'inactivo' => [UsersModel::STATUS_USER_INACTIVE, UsersModel::STATUS_USER_INACTIVE],
            'bloqueado' => [UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersModel::STATUS_USER_ATTEMPTS_BLOCK],
            'rechazado' => [UsersModel::STATUS_USER_REJECTED, UsersModel::STATUS_USER_REJECTED],
            'borrado' => [UsersModel::STATUS_USER_DELETED, UsersModel::STATUS_USER_DELETED],
        ];
        $n = 1;
        foreach ($casos as $sufijo => [$antes, $despues]) {
            $id = $crearUsuario($sufijo, $antes);
            UsersApprovalHandler::isAutoApproval(new UsersModel($id));
            $queda = $estadoDe($id);
            $check($queda === $despues, "a{$n} {$sufijo} ({$antes}) queda en {$despues}", "queda en {$queda}");
            $n++;
        }

        echoTerminal('');
        echoTerminal('[b] Lo que se conserva: un pendiente de una organización aprobada queda activo al navegar');
        $id = $crearUsuario('navega', UsersModel::STATUS_USER_APPROVED_PENDING);
        $jwt = SessionToken::generateToken(['id' => $id], null, null, false);
        $perfil = UsersController::routeName('form-profile', [], true);
        $perfil = str_contains($perfil, '://') ? (string) parse_url($perfil, \PHP_URL_PATH) : $perfil;
        $perfil = $prefijoBase !== '' && str_starts_with($perfil, $prefijoBase) ? substr($perfil, strlen($prefijoBase)) : $perfil;
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($perfil, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        //RETORNO-IGNORADO: lo que se mide es el estado en la base, no la respuesta.
        curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $check($estadoDe($id) === UsersModel::STATUS_USER_ACTIVE, 'b1 CANARIO: tras una petición con su sesión queda activo', 'queda en ' . $estadoDe($id));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('La autoaprobación de usuarios solo pasa de pendiente a activo: inactivo, bloqueado, rechazado y borrado se quedan como están, también en una organización aprobada. Y un pendiente de una organización aprobada sigue quedando activo al navegar (por HTTP).')->setEffects([CliActions::EFFECT_DATABASE])->register();
