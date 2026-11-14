<?php

//P320 (H279): los archivos protegidos con sesión aplican la regla de estado del usuario del panel. Crea y borra usuarios
//zz-agente-pfs-* y una organización inactiva, con sus sesiones.
//@areas nucleo

use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/protected-files-session', function ($args) {

    echoTerminal("\e[33m[TEST:ProtectedFilesSession] Los archivos protegidos con sesión, con la regla de estado del panel\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-agente-pfs-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    /** @var array<string,int> $usuarios */
    $usuarios = [];
    $organizacionInactiva = 0;

    $crear = function (string $sufijo, int $tipo, int $estado = UsersModel::STATUS_USER_ACTIVE, ?int $organizacion = null) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = $organizacion ?? OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };

    try {
        $nueva = OrganizationsController::createOrganization(['name' => "{$prefijo}-org", 'nit' => "{$prefijo}-org"]);
        if ($nueva !== null && $nueva->id !== null) {
            $o = new OrganizationMapper((int) $nueva->id);
            $o->status = OrganizationMapper::INACTIVE;
            $o->update();
            $organizacionInactiva = (int) $o->id;
        }
        $check($organizacionInactiva > 0, 'p1 hay una organización inactiva de prueba');

        $casos = [
            'a1 activo → abre' => [$crear('activo', UsersModel::TYPE_USER_GENERAL), true],
            'a2 inactivo → no' => [$crear('inactivo', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_INACTIVE), false],
            'a3 pendiente de aprobar → no' => [$crear('pendiente', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_APPROVED_PENDING), false],
            'a4 rechazado → no' => [$crear('rechazado', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_REJECTED), false],
            'a5 de una organización inactiva → no' => [$crear('org-inactiva', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE, $organizacionInactiva > 0 ? $organizacionInactiva : null), false],
            'a6 root → abre' => [$crear('root', UsersModel::TYPE_USER_ROOT), true],
        ];
        foreach ($casos as $nombre => [$id, $esperado]) {
            $jwt = UserSessions::issueToken($id);
            //CANARIO: el token vale como sesión; lo que decide es la regla de estado, no la firma.
            $valido = UserSessions::jwtIsValid($jwt);
            $check($valido && UserSessions::jwtOpensProtectedFiles($jwt) === $esperado, $nombre, 'jwtIsValid ' . var_export($valido, true));
        }
        $check(UserSessions::jwtOpensProtectedFiles('') === false, 'a7 sin sesión → no');
    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $ids = implode(',', array_map('intval', $usuarios ?: [0]));
        $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})");
        $aprobaciones = SystemApprovalsMapper::model();
        $referencias = [$tablaUsuarios => array_values($usuarios), OrganizationMapper::TABLE => $organizacionInactiva > 0 ? [$organizacionInactiva] : []];
        foreach ($referencias as $tabla => $idsDeTabla) {
            foreach ($idsDeTabla as $id) {
                $aprobaciones->resetAll();
                $aprobaciones->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([
                    new \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem('referenceTable', \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::EQUAL_OPERATOR, $tabla),
                    new \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem('referenceValue', \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::EQUAL_OPERATOR, (string) $id, \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::AND_OPERATOR),
                ]))->execute();
            }
        }
        foreach ($usuarios as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$ids})");
        if ($organizacionInactiva > 0) {
            $database->exec('DELETE FROM `' . OrganizationMapper::TABLE . "` WHERE id = {$organizacionInactiva}");
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . "` WHERE name LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización ni sesión de la prueba', (string) $quedan);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P320 (H279): UserSessions::jwtOpensProtectedFiles(), la regla de los archivos protegidos con sesión: activo y root abren; inactivo, pendiente, rechazado, de una organización inactiva y sin sesión, no. Crea y borra usuarios zz-agente-pfs-* y una organización.')->setEffects([CliActions::EFFECT_DATABASE])->register();
