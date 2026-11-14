<?php

//countsBySuccess() (G8) da lo mismo que contar sobre all() con la jerarquía (P133), con un usuario por tipo. Solo lee.
//Con la base limpia puede que ningún actor vea menos que root: la igualdad no discrimina, y la suite lo dice sin fallar.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/login-attempts-counts';
$cliTaskDescription = 'countsBySuccess(): las tres cifras de Accesos con SQL agrupado, iguales a contar sobre all() para cada tipo de usuario';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:LoginAttemptsCounts] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        echoTerminal(' ');
        echoTerminal(str_repeat('=', 80));
        echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
        echoTerminal(str_repeat('=', 80));
        echoTerminal('');
        echoTerminal('[TEST:LoginAttemptsCounts] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
        echoTerminal('');
        return [
            'success' => $failed === 0 && $passed > 0,
            'message' => $failed === 0
                ? "countsBySuccess() igual a contar sobre all() ({$passed} comprobaciones)."
                : "{$failed} comprobaciones fallaron.",
        ];
    };

    //──── 1. Actores ───────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/2] un usuario real de cada tipo, con su organización');

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'CANARIO: hay conexión a la base')) {
        return $balance();
    }
    $tabla = UsersModel::TABLE;
    //Cada actor, de una organización que no sea la global si la hay (H42): con todos en la global, una regla por
    //organización rota en el conteo no se vería.
    $global = OrganizationMapper::INITIAL_ID_GLOBAL;
    $consulta = $database->prepare("SELECT id, type, organization FROM {$tabla} WHERE type = :type AND status != :deleted ORDER BY (organization IS NULL), (organization = {$global}), id LIMIT 1");
    $actores = [];
    foreach (array_keys(UsersModel::TYPES_USERS) as $tipo) {
        $consulta->execute([':type' => $tipo, ':deleted' => UsersModel::STATUS_USER_DELETED]);
        $fila = $consulta->fetch(\PDO::FETCH_OBJ);
        $consulta->closeCursor();
        if (is_object($fila)) {
            $actor = new \stdClass();
            $actor->id = (int) $fila->id;
            $actor->type = (int) $fila->type;
            $actor->organization = is_numeric($fila->organization) ? (int) $fila->organization : null;
            $actores[] = $actor;
        }
    }
    $root = array_values(array_filter($actores, fn($a) => $a->type === UsersModel::TYPE_USER_ROOT))[0] ?? null;
    if (!$check($root !== null, 'CANARIO: hay un usuario root', count($actores) . ' tipo(s) con usuario')) {
        return $balance();
    }
    echoTerminal(' ');

    //──── 2. Las tres cifras ───────────────────────────────────────────────────────────────────
    echoTerminal('[2/2] countsBySuccess($actor) === lo que cuenta all(false, $actor)');

    $totalRoot = null;
    $discrimina = false;
    foreach ($actores as $actor) {
        $filas = LoginAttemptsModel::all(false, $actor);
        $esperado = [
            'total' => count($filas),
            'success' => count(array_filter($filas, fn($e) => $e->success == 1)),
            'error' => count(array_filter($filas, fn($e) => $e->success == 0)),
        ];
        $obtenido = LoginAttemptsModel::countsBySuccess($actor);
        $nombre = "tipo {$actor->type} (usuario {$actor->id}, organización " . var_export($actor->organization, true) . ')';
        $check($obtenido === $esperado, $nombre, 'all(): ' . json_encode($esperado) . ' · agrupado: ' . json_encode($obtenido));
        if ($actor === $root) {
            $totalRoot = $esperado['total'];
        }
    }
    foreach ($actores as $actor) {
        if ($actor !== $root && count(LoginAttemptsModel::all(false, $actor)) < (int) $totalRoot) {
            $discrimina = true;
        }
    }
    echoTerminal($discrimina
        ? '   la base DISCRIMINA: algún actor ve menos intentos que root.'
        : '   la base NO discrimina: todos los actores ven lo mismo que root; la igualdad no prueba la jerarquía.');

    return $balance();

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
