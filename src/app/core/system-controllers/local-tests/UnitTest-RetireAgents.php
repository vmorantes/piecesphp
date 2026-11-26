<?php

//Las guardas de retirar_usuarios() (bin/tools/retirar-usuarios.php, la de bin/retirar-agentes): sin escribir, rechaza un
//nombre sin el prefijo y un zz-agente activo. Solo lee la base; no viaja a un clon, y allí gates no la corre.
//@areas nucleo

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/retire-agents', function ($args) {

    echoTerminal("\e[33m[TEST:RetireAgents] Las guardas de retirar_usuarios(): rechazan sin escribir\e[39m");
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

    $tool = dirname(__DIR__, 5) . '/bin/tools/retirar-usuarios.php';
    if (!is_file($tool)) {
        echoTerminal("\e[31m bin/tools/retirar-usuarios.php no está: no viaja a un clon (bin/distribution-exclude.txt) \e[39m");
        return ['success' => false, 'message' => 'sin bin/tools/retirar-usuarios.php'];
    }
    require_once $tool;

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("\e[31m sin conexión a base de datos \e[39m");
        return ['success' => false, 'message' => 'sin base'];
    }
    $count = fn(): int => (int) $db->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '`')->fetchColumn();
    $statusOf = function (string $username) use ($db): ?int {
        $query = $db->prepare('SELECT status FROM `' . UsersModel::TABLE . '` WHERE username = ?');
        $query->execute([$username]);
        $status = $query->fetchColumn();
        return $status === false ? null : (int) $status;
    };
    //Lo que lanza retirar_usuarios(), o null si no lanzó.
    $attempt = function (array $usernames, bool $apply) use ($db): ?string {
        try {
            retirar_usuarios($db, $usernames, $apply);
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }
        return null;
    };

    //El caso tiene que existir para que el rechazo signifique algo: zz-agente-root activo.
    $active = 'zz-agente-root';
    $activeStatus = $statusOf($active);
    $check($activeStatus === UsersModel::STATUS_USER_ACTIVE, "r0 CANARIO: {$active} existe y está activo", 'status: ' . var_export($activeStatus, true));

    $before = $count();
    $rejected = $attempt(['zz-agente-no-existe', 'admin-sin-prefijo'], true);
    $check($rejected !== null && str_contains($rejected, 'solo se retiran usuarios zz-agente-*') && $count() === $before, 'r1 --aplicar con un nombre sin prefijo: se rechaza y la base no cambia', (string) $rejected);

    //En seco a propósito: la guarda va antes de escribir en los dos modos, y así quitarla para probarla no borra a nadie.
    $before = $count();
    $rejected = $attempt(['zz-agente-no-existe', $active], false);
    $check($rejected !== null && str_contains($rejected, 'no está eliminado') && $count() === $before && $statusOf($active) === UsersModel::STATUS_USER_ACTIVE, "r2 {$active}, activo: se rechaza y la base no cambia", (string) $rejected);

    $before = $count();
    $dry = null;
    try {
        $dry = retirar_usuarios($db, ['zz-agente-no-existe'], false);
    } catch (\Throwable $e) {
        $dry = get_class($e) . ': ' . $e->getMessage();
    }
    $check($dry === "zz-agente-no-existe: no existe\nningún usuario que retirar\n" && $count() === $before, 'r3 un nombre que no existe se dice y no es error', (string) $dry);

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las guardas de retirar_usuarios() (bin/retirar-agentes): rechazan sin escribir un nombre sin prefijo zz-agente- y un usuario activo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
