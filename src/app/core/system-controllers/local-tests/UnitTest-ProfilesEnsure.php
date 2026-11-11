<?php

//El perfil se crea en el alta y al cambiar a un tipo que lo lleva, nunca en cada petición (P266): ensureFor() es
//idempotente y no toca los tipos sin perfil. Por HTTP el alta, con un root zz-agente- propio; todo se retira al final.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/profiles-ensure', function ($args) {

    echoTerminal("\e[33m[TEST:ProfilesEnsure] El perfil se crea en el alta y al cambiar de tipo, una sola vez\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $ruta = (string) parse_url(UsersController::routeName('register-request', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;

    $prefijo = 'zz-agente-perfil-' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, int $tipo) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
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
    $perfiles = function (int $id) use ($database): int {
        $consulta = $database->prepare('SELECT COUNT(*) FROM `' . UserProfileMapper::TABLE . '` WHERE belongsTo = ?');
        $consulta->execute([$id]);
        return (int) $consulta->fetchColumn();
    };

    try {
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $jwt = UserSessions::issueToken($root);

        //──── [a] El alta del panel, por HTTP ─────────────────────────────────────────────────────
        echoTerminal('[a] El alta del panel crea el perfil');
        $nombre = "{$prefijo}-alta";
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'username' => $nombre,
                'email' => "{$nombre}@example.invalid",
                'password' => 'zz-Clave-1',
                'password2' => 'zz-Clave-1',
                'firstname' => 'Zz',
                'secondname' => '',
                'first_lastname' => 'Alta',
                'second_lastname' => '',
                'type' => (string) UsersModel::TYPE_USER_GENERAL,
                'status' => (string) UsersModel::STATUS_USER_ACTIVE,
                'organization' => (string) OrganizationMapper::INITIAL_ID_GLOBAL,
            ],
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $cuerpo = curl_exec($h);
        $estado = (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $datos = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $consulta = $database->prepare('SELECT id FROM `' . UsersModel::TABLE . '` WHERE username = ?');
        $consulta->execute([$nombre]);
        $alta = (int) $consulta->fetchColumn();
        if ($alta > 0) {
            $ids[] = $alta;
        }
        $check($estado === 200 && ($datos['success'] ?? null) === true && $alta > 0, 'a1 el alta de un usuario general responde success', "HTTP {$estado}, " . mb_substr(is_string($cuerpo) ? $cuerpo : '', 0, 160));
        $check($alta > 0 && $perfiles($alta) === 1, 'a2 y tiene perfil, uno', 'perfiles ' . ($alta > 0 ? $perfiles($alta) : '-'));
        echoTerminal(' ');

        //──── [t] El cambio de tipo ─────────────────────────────────────────────────────────────
        echoTerminal('[t] Cambiar a un tipo que lleva perfil lo crea');
        $tipo = $crear('tipo', UsersModel::TYPE_USER_ADMIN_GRAL);
        $check($perfiles($tipo) === 0, 't0 un administrador general recién guardado no tiene perfil (su tipo no lo lleva)');
        $check(UserProfileMapper::ensureFor($tipo) && $perfiles($tipo) === 0, 't1 ensureFor() con un tipo sin perfil dice true y no crea nada');
        $check((new UsersModel())->changeType(UsersModel::TYPE_USER_GENERAL, $tipo) && $perfiles($tipo) === 1, 't2 changeType() a usuario general lo crea', 'perfiles ' . $perfiles($tipo));
        echoTerminal(' ');

        //──── [e] Idempotente ───────────────────────────────────────────────────────────────────
        echoTerminal('[e] Dos llamadas, un perfil');
        $doble = $crear('doble', UsersModel::TYPE_USER_GENERAL);
        $primera = UserProfileMapper::ensureFor($doble);
        $segunda = UserProfileMapper::ensureFor($doble);
        $check($primera && $segunda && $perfiles($doble) === 1, 'e1 ensureFor() dos veces deja un solo perfil', 'perfiles ' . $perfiles($doble));
        $check(!UserProfileMapper::ensureFor(-1), 'e2 con un usuario que no existe dice false');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('El perfil se crea en el alta (por HTTP) y al cambiar a un tipo que lo lleva (changeType), una sola vez aunque se pida dos (ensureFor), y nunca para un tipo sin perfil. Crea un root zz-agente- y lo retira todo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
