<?php

//UsersModel::all() según quién pide: sin organización, nadie (antes, SQL roto y 500); con ella, solo los suyos; sin usuario,
//todos. Los usuarios que piden se arman en memoria (sin fila): no escribe nada.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/users-all-organization', function ($args) {

    echoTerminal("\e[33m[TEST:UsersAllOrganization] UsersModel::all(): quién ve a quién, y sin organización nadie\e[39m");
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
    $tabla = UsersModel::TABLE;
    $sistema = UsersModel::SYSTEM_USER_ID;
    //Quien pide, sin fila: UserDataPackage solo se construye desde la base, así que se le dan sus tres datos a mano.
    $pide = function (int $tipo, $organizacion): UserDataPackage {
        $clase = new \ReflectionClass(UserDataPackage::class);
        $paquete = $clase->newInstanceWithoutConstructor();
        foreach (['id' => 0, 'type' => $tipo, 'organization' => $organizacion] as $propiedad => $valor) {
            $clase->getProperty($propiedad)->setValue($paquete, $valor);
        }
        return $paquete;
    };
    $ids = fn(array $filas): array => array_map(fn($fila) => (int) $fila->id, $filas);

    try {
        $todos = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id != {$sistema}")->fetchColumn();
        $organizacion = (int) $database->query("SELECT organization FROM `{$tabla}` WHERE organization IS NOT NULL AND organization > 0 AND id != {$sistema} GROUP BY organization ORDER BY COUNT(*) DESC LIMIT 1")->fetchColumn();
        $deEsa = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE organization = {$organizacion} AND id != {$sistema}")->fetchColumn();
        $check(getLoggedFrameworkUser() === null && $todos > 0 && $organizacion > 0 && $deEsa > 0 && $deEsa < $todos,
            'c1 CANARIO: sin sesión en el terminal, y hay una organización con usuarios que no son todos', "todos {$todos}, organización {$organizacion} con {$deEsa}");
        $check(!OrganizationMapper::canModifyAnyOrganization(UsersModel::TYPE_USER_ADMIN_ORG) && OrganizationMapper::canModifyAnyOrganization(UsersModel::TYPE_USER_ROOT),
            'c2 CANARIO: el administrador de organización no ve todas; root sí');

        foreach (['null' => null, '0' => 0, "''" => ''] as $nombre => $valor) {
            try {
                $resultado = UsersModel::all($pide(UsersModel::TYPE_USER_ADMIN_ORG, $valor));
                $check($resultado === [], "a1 administrador de organización con organización {$nombre}: vacío, sin error", count($resultado) . ' filas');
            } catch (\Throwable $e) {
                $check(false, "a1 administrador de organización con organización {$nombre}: vacío, sin error", get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160));
            }
        }

        $suyos = UsersModel::all($pide(UsersModel::TYPE_USER_ADMIN_ORG, $organizacion));
        $ajenos = array_filter($suyos, fn($fila) => (int) $fila->organization !== $organizacion);
        $check(count($suyos) === $deEsa && $ajenos === [], 'a2 con organización, solo los de la suya', count($suyos) . " de {$deEsa}; ajenos " . count($ajenos));

        $sinUsuario = UsersModel::all();
        $check(count($sinUsuario) === $todos && !in_array($sistema, $ids($sinUsuario), true), 'a3 sin usuario, todos (menos el de sistema)', count($sinUsuario) . " de {$todos}");
        $deRoot = UsersModel::all($pide(UsersModel::TYPE_USER_ROOT, null));
        $check(count($deRoot) === $todos, 'a4 root sin organización, todos: la guarda es solo para quien no ve todas', count($deRoot) . " de {$todos}");
    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('UsersModel::all() según quién pide: un administrador de organización sin organización (nula, 0 o vacía) recibe una lista vacía y no un error de SQL; con organización, solo los de la suya; sin usuario y como root, todos menos el de sistema. Los que piden se arman en memoria; solo lee.')->setEffects([CliActions::EFFECT_NONE])->register();
