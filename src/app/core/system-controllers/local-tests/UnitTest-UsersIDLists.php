<?php

//Las listas de ids y tipos que UsersModel escribe en el SQL (G1-b): pasan como enteros, así que un valor hostil no
//añade condiciones. Solo lee la base.
//@areas nucleo

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/users-id-lists', function ($args) {

    echoTerminal("\e[33m[TEST:UsersIDLists] Las listas de ids y tipos de UsersModel, como enteros\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'banco: conexión a la base')) {
        return $balance();
    }
    $tabla = UsersModel::TABLE;
    $id = (int) $database->query("SELECT id FROM `{$tabla}` WHERE id != " . UsersModel::SYSTEM_USER_ID . ' ORDER BY id LIMIT 1')->fetchColumn();
    $total = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    if (!$check($id > 0 && $total > 1, 'banco: hay al menos dos usuarios', "id {$id}, total {$total}")) {
        return $balance();
    }
    //Lo que llegaría de una petición: un valor sin tipo. json_decode da `mixed`, como un parámetro de verdad.
    $hostil = json_decode('["' . $id . ') OR (1=1"]', true);
    $ids = static fn (array $filas): array => array_map(fn ($fila) => (int) $fila->id, $filas);

    try {
        $check($ids(UsersModel::getUsersByIDs([$id])) === [$id], 'd0 CANARIO: getUsersByIDs con un entero da ese usuario');
        $resultado = $ids(UsersModel::getUsersByIDs($hostil));
        $check($resultado === [$id], 'd1 DISCRIMINANTE: getUsersByIDs con «' . $id . ') OR (1=1» da solo ese usuario, no todos', count($resultado) . ' fila(s)');
        $tipo = (int) $database->query("SELECT type FROM `{$tabla}` WHERE id = {$id}")->fetchColumn();
        $sinIgnorar = count(UsersModel::getUsersByType($tipo));
        $conHostil = count(UsersModel::getUsersByType($tipo, json_decode('["' . $id . ') OR (1=1"]', true)));
        $check($conHostil === $sinIgnorar - 1, 'd2 getUsersByType: una lista de ignorados hostil solo ignora ese id, no abre la consulta', "sin ignorar {$sinIgnorar}, con la hostil {$conHostil}");
        $porTipos = count(UsersModel::getUsersByTypes(json_decode('["' . $tipo . ') OR (1=1"]', true)));
        $check($porTipos === $sinIgnorar, 'd3 getUsersByTypes: un tipo hostil no abre la consulta a todos los tipos', "por tipos {$porTipos}, del tipo {$sinIgnorar}");
    } catch (\Throwable $e) {
        $check(false, 'd4 sin error de SQL', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
    }

    return $balance();

})->setDescription('Las listas de ids y tipos que UsersModel escribe en el SQL (G1-b) pasan como enteros: un valor como «1) OR (1=1» no añade condiciones. Solo lee la base.')->setEffects([CliActions::EFFECT_NONE])->register();
