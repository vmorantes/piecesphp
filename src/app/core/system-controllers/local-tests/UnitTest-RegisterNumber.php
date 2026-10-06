<?php

//El número de registro de los listados (P95): con ceros a la izquierda, sin recortar nunca, y ordenado por el id
//real. Se prueba la EXPRESIÓN contra la base, sin filas: una fila con un id alto subiría el AUTO_INCREMENT para siempre.

use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/register-number', function ($args) {

    echoTerminal("\e[33m[TEST:RegisterNumber] El número de registro: con ceros, sin recortar y ordenado por el id real\e[39m");
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

    $db = (new BaseModel())->getDatabase();
    if (!$check($db !== null, 'p0 hay conexión a la base')) {
        return $balance();
    }

    //─── a · La expresión, la del código ────────────────────────────────────────────────────────────
    echoTerminal('[a] La expresión que pinta el número, tomada de un mapper real y evaluada sin tabla');
    $tabla = NewsCategoryMapper::TABLE;
    $expresion = '';
    foreach (NewsCategoryMapper::fieldsToSelect() as $campo) {
        if (is_string($campo) && str_ends_with($campo, ' AS idPadding')) {
            $expresion = substr($campo, 0, -strlen(' AS idPadding'));
        }
    }
    $check($expresion !== '' && str_contains($expresion, "{$tabla}.id"), 'a0 el mapper tiene su expresión idPadding, sobre su id', $expresion);
    $evaluar = function (string $plantilla, int $id) use ($db, $tabla): string {
        $sql = 'SELECT ' . str_replace("{$tabla}.id", (string) $id, $plantilla);
        return (string) $db->query($sql)->fetchColumn();
    };
    foreach ([42 => '00042', 99999 => '99999', 100000 => '100000', 123456 => '123456', 1 => '00001'] as $id => $esperado) {
        $check($evaluar($expresion, $id) === $esperado, "a {$id} → {$esperado}", $evaluar($expresion, $id));
    }
    //CANARIO: la expresión de antes da 12345 para el 123456, que es el número de OTRO registro. Es el defecto.
    $check($evaluar("LPAD({$tabla}.id, 5, 0)", 123456) === '12345', 'a CANARIO: la expresión vieja recorta el 123456 a 12345');

    //─── b · Lo que ordena es el id real ────────────────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[b] Las dos preferencias de orden: el id real, calificado con su tabla');
    foreach ([NewsMapper::class => NewsMapper::ORDER_BY_PREFERENCE, SystemApprovalsMapper::class => SystemApprovalsMapper::ORDER_BY_PREFERENCE] as $clase => $orden) {
        $tablaOrden = $clase::TABLE;
        $check(($orden[0] ?? null) === "`{$tablaOrden}`.`id` DESC", "b1 {$clase}: primero, el id real de `{$tablaOrden}`", (string) ($orden[0] ?? ''));
        $check(!str_contains(implode(' ', $orden), 'idPadding'), "b2 {$clase}: y ningún idPadding");
    }
    //El listado público de noticias usa su preferencia tal cual en el ORDER BY: se ejecuta con su tabla, y ordena por número.
    //Con el SELECT del mapper, como el listado: las otras claves del orden son alias de ese SELECT.
    $filas = $db->query('SELECT ' . implode(', ', NewsMapper::fieldsToSelect()) . ' FROM `' . NewsMapper::TABLE . '` ORDER BY ' . implode(', ', NewsMapper::ORDER_BY_PREFERENCE))->fetchAll(\PDO::FETCH_ASSOC);
    $ids = array_map(fn (array $fila): int => (int) $fila['id'], $filas);
    $ordenados = $ids;
    rsort($ordenados, \SORT_NUMERIC);
    $check($ids === $ordenados, 'b3 el ORDER BY de noticias se ejecuta y ordena por número, de mayor a menor', count($ids) . ' fila(s)');

    return $balance();

})->setDescription('El número de registro de los listados: la expresión del código da 00042 para el 42 y el número entero desde el 100.000 —la vieja recortaba el 123456 a 12345—, y las preferencias de orden van por el id real. Se prueba la expresión, sin filas.')->setEffects([CliActions::EFFECT_NONE])->register();
