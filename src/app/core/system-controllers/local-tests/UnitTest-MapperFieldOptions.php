<?php

//Ningún campo de `$fields` lleva una opción que el ORM no conoce: el paquete la ignora en silencio (P205 a, `'dafault'`).
//Recorre los mappers de `scheme-create module=all`; no escribe nada.
//@areas nucleo

use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\SchemeSqlTask;

CliActions::make('unit-tests:core/mapper-field-options', function ($args) {

    echoTerminal("\e[33m[TEST:MapperFieldOptions] Las opciones de los campos de cada mapper son las que el ORM conoce\e[39m");
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

    //La lista del paquete, leída y no copiada: si el ORM gana una opción, la prueba la conoce sola.
    $propiedadOpciones = new \ReflectionProperty(EntityMapper::class, 'defaultFieldOptionsValues');
    $propiedadCampos = new \ReflectionProperty(EntityMapper::class, 'fields');
    $descubiertos = SchemeSqlTask::discover('all');
    $mappers = $descubiertos['mappers'];
    $opciones = $mappers !== [] ? array_keys((array) $propiedadOpciones->getValue($mappers[0])) : [];
    $check(in_array('default', $opciones, true) && !in_array('dafault', $opciones, true), 'c1 CANARIO: el ORM conoce «default» y no «dafault»', implode(', ', $opciones));
    $check(count($mappers) > 20 && $descubiertos['skipped'] === [], 'c2 CANARIO: el descubrimiento de scheme-create da mappers y no descarta ninguno', count($mappers) . ' mappers; descartados: ' . implode(', ', $descubiertos['skipped']));

    $desconocidas = [];
    $campos = 0;
    foreach ($mappers as $mapper) {
        foreach ((array) $propiedadCampos->getValue($mapper) as $campo => $configuracion) {
            $campos++;
            foreach (array_keys(is_array($configuracion) ? $configuracion : []) as $opcion) {
                if (!in_array($opcion, $opciones, true)) {
                    $desconocidas[] = get_class($mapper) . "::\$fields['{$campo}']['{$opcion}']";
                }
            }
        }
    }
    $check($campos > 100, 'c3 CANARIO: se leyeron los campos', "{$campos} campos");
    $check($desconocidas === [], 'a1 ningún campo lleva una opción que el ORM no conoce', implode('; ', $desconocidas));

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las opciones de cada campo de $fields, en todos los mappers que descubre scheme-create module=all, son las de EntityMapper::$defaultFieldOptionsValues del paquete; una desconocida (la errata «dafault») el ORM la ignora en silencio. No escribe nada.')->setEffects([CliActions::EFFECT_NONE])->register();
