<?php

/**
 * UnitTest-MapperFinders.php
 *
 * Congela el contrato de los buscadores estáticos de mapper: getBy(),
 * lastModifiedElement() y getByMultipleCriteries().
 *
 * POR QUÉ EXISTE
 * La ventana siguiente toca los ~541 errores de nulabilidad, y buena parte de ellos son
 * llamadas encadenadas a estos buscadores sin comprobar el null. Antes de cambiar esas
 * llamadas hay que tener escrito qué devuelven hoy.
 *
 * POR QUÉ ES DE SOLO LECTURA
 * Estos métodos NO se heredan: están copiados en 26 mappers concretos. Una prueba contra
 * un mapper de juguete no protegería ninguno de los 26 —sería una copia más—, así que la
 * suite recorre mappers REALES y descubre en ejecución un id existente. No inserta, no
 * actualiza y no borra nada.
 *
 * Si una tabla está vacía, el caso «encontrado» se omite y se dice; el caso «no
 * encontrado», que es de donde sale la mitad de los null, se comprueba siempre.
 */

use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/mapper-finders';
$cliTaskDescription = 'Contrato de los buscadores de mapper: getBy, lastModifiedElement, getByMultipleCriteries';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:MapperFinders] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $pasadas = 0;
    $fallidas = 0;
    $omitidas = 0;

    $comprobar = function (bool $condicion, string $nombre, ?string $detalle = null) use (&$pasadas, &$fallidas) {
        if ($condicion) {
            $pasadas++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$nombre}");
        } else {
            $fallidas++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$nombre}");
        }
        if ($detalle !== null) {
            echoTerminal("      - {$detalle}");
        }
        return $condicion;
    };
    $omitir = function (string $nombre, string $motivo) use (&$omitidas) {
        $omitidas++;
        echoTerminal("   \e[33m[OMITIDA]\e[39m {$nombre}");
        echoTerminal("      - {$motivo}");
    };

    /**
     * Un id que con toda seguridad no existe. Se usa el negativo para no depender de
     * cuántas filas haya ni de los AUTO_INCREMENT.
     */
    $idInexistente = -987654321;

    /**
     * Mappers a recorrer. Se eligen por cubrir las formas del buscador que hay en el
     * código, no por ser especiales:
     *
     *   - CountryMapper convierte con `new CountryMapper($result->id)` (segunda consulta)
     *   - PublicationMapper convierte con `objectToMapper($result)` (hidrata en sitio)
     *
     * Las dos formas deben devolver lo mismo; si divergen, esta suite lo dice.
     */
    $mappers = [
        'App\Locations\Mappers\CountryMapper',
        'Publications\Mappers\PublicationMapper',
        'News\Mappers\NewsMapper',
        'EventsLog\Mappers\LogsMapper',
    ];

    /**
     * Descubre un id existente sin escribir nada.
     *
     * @param string $cls
     * @return int|string|null
     */
    $idDeMuestra = function (string $cls) {
        try {
            $model = $cls::model();
            $model->select(['id']);
            $model->execute();
            $filas = $model->result();
            if (!is_array($filas) || count($filas) === 0) {
                return null;
            }
            return $filas[0]->id ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    };

    //──── 1. getBy: no encontrado devuelve null ─────────────────────────────────────────
    echoTerminal('[1/5] getBy() con un id inexistente debe devolver null...');
    foreach ($mappers as $cls) {
        if (!class_exists($cls)) {
            $omitir("getBy null — {$cls}", 'la clase no existe en esta instalación');
            continue;
        }
        try {
            $r = $cls::getBy($idInexistente, 'id');
            $comprobar($r === null, "getBy(inexistente) === null — " . basename(str_replace('\\', '/', $cls)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $comprobar(false, "getBy(inexistente) — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 2. getBy con el flag en false devuelve la fila cruda ──────────────────────────
    echoTerminal('[2/5] getBy() sin el flag debe devolver \stdClass...');
    foreach ($mappers as $cls) {
        if (!class_exists($cls)) { continue; }
        $id = $idDeMuestra($cls);
        if ($id === null) {
            $omitir('getBy stdClass — ' . basename(str_replace('\\', '/', $cls)), 'la tabla no tiene filas');
            continue;
        }
        try {
            $r = $cls::getBy($id, 'id', false);
            $comprobar($r instanceof \stdClass, 'getBy(id, false) devuelve \stdClass — ' . basename(str_replace('\\', '/', $cls)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $comprobar(false, "getBy(id, false) — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 3. getBy con el flag en true devuelve una instancia del mapper ────────────────
    echoTerminal('[3/5] getBy() con el flag debe devolver una instancia del propio mapper...');
    foreach ($mappers as $cls) {
        if (!class_exists($cls)) { continue; }
        $id = $idDeMuestra($cls);
        if ($id === null) {
            $omitir('getBy mapper — ' . basename(str_replace('\\', '/', $cls)), 'la tabla no tiene filas');
            continue;
        }
        try {
            $r = $cls::getBy($id, 'id', true);
            $comprobar($r instanceof $cls, 'getBy(id, true) devuelve ' . basename(str_replace('\\', '/', $cls)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $comprobar(false, "getBy(id, true) — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 4. lastModifiedElement respeta el mismo contrato ──────────────────────────────
    echoTerminal('[4/5] lastModifiedElement() debe respetar el mismo contrato de flag...');
    foreach ($mappers as $cls) {
        if (!class_exists($cls) || !method_exists($cls, 'lastModifiedElement')) {
            continue;
        }
        try {
            $crudo = $cls::lastModifiedElement(false);
            $mapper = $cls::lastModifiedElement(true);
            $nombre = basename(str_replace('\\', '/', $cls));

            $okCrudo = $crudo === null || $crudo instanceof \stdClass;
            $okMapper = $mapper === null || $mapper instanceof $cls;

            $comprobar($okCrudo, "lastModifiedElement(false) es \stdClass o null — {$nombre}",
                'Obtenido: ' . (is_object($crudo) ? get_class($crudo) : gettype($crudo)));
            $comprobar($okMapper, "lastModifiedElement(true) es {$nombre} o null",
                'Obtenido: ' . (is_object($mapper) ? get_class($mapper) : gettype($mapper)));

            //Coherencia entre las dos ramas: o las dos encuentran algo, o ninguna.
            $comprobar(($crudo === null) === ($mapper === null),
                "las dos ramas coinciden en si hay resultado — {$nombre}");
        } catch (\Throwable $e) {
            $comprobar(false, "lastModifiedElement — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 5. getByMultipleCriteries ─────────────────────────────────────────────────────
    echoTerminal('[5/5] getByMultipleCriteries() sin criterios que casen debe devolver null...');
    $conCriterios = [
        'SystemApprovals\Mappers\SystemApprovalsMapper',
    ];
    foreach ($conCriterios as $cls) {
        if (!class_exists($cls) || !method_exists($cls, 'getByMultipleCriteries')) {
            $omitir("getByMultipleCriteries — {$cls}", 'la clase o el método no existen');
            continue;
        }
        try {
            $r = $cls::getByMultipleCriteries([
                ['column' => 'id', 'value' => $idInexistente],
            ]);
            $comprobar($r === null, 'getByMultipleCriteries(sin coincidencia) === null — ' . basename(str_replace('\\', '/', $cls)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $comprobar(false, "getByMultipleCriteries — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }

    /**
     * UsersModel y sus hermanos aceptan `?UserDataPackage $currentUser = null` y caen a
     * `getLoggedFrameworkUser()`, así que DEBEN funcionar sin sesión.
     *
     * Hasta 2026-08-21 no lo hacían: desreferenciaban `->organization` antes de la guarda
     * `if ($currentUser !== null)` que ya existía unas líneas más abajo. Esta prueba
     * afirmaba ese fallo como caracterización; ahora afirma el comportamiento corregido.
     *
     * Sin sesión, el filtro por organización se omite y la consulta se ejecuta igual.
     */
    foreach ([
        'App\\Model\\UsersModel',
    ] as $cls) {
        if (!class_exists($cls) || !method_exists($cls, 'getByMultipleCriteries')) {
            $omitir("getByMultipleCriteries — {$cls}", 'la clase o el método no existen');
            continue;
        }
        try {
            $r = $cls::getByMultipleCriteries([
                ['column' => 'id', 'value' => $idInexistente],
            ]);
            $comprobar($r === null, 'getByMultipleCriteries SIN SESIÓN devuelve null — ' . basename(str_replace('\\', '/', $cls)),
                'Antes reventaba leyendo ->organization sobre null. Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $comprobar(false, "getByMultipleCriteries sin sesión — {$cls}", 'Excepción: ' . $e->getMessage());
        }
    }

    /**
     * Los otros tres métodos de UsersModel con la misma forma. all() y
     * allByMultipleCriteries() devuelven colecciones, no un elemento.
     */
    if (class_exists('App\\Model\\UsersModel')) {
        foreach (['all', 'allByMultipleCriteries'] as $metodo) {
            if (!method_exists('App\\Model\\UsersModel', $metodo)) { continue; }
            try {
                $r = $metodo === 'all'
                    ? \App\Model\UsersModel::all()
                    : \App\Model\UsersModel::allByMultipleCriteries([['column' => 'id', 'value' => $idInexistente]]);
                $comprobar(is_array($r), "UsersModel::{$metodo}() SIN SESIÓN devuelve un array",
                    'Obtenido: ' . gettype($r) . (is_array($r) ? ' de ' . count($r) . ' elementos' : ''));
            } catch (\Throwable $e) {
                $comprobar(false, "UsersModel::{$metodo}() sin sesión", 'Excepción: ' . $e->getMessage());
            }
        }
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$pasadas}/" . ($pasadas + $fallidas) . " PASADAS, {$omitidas} OMITIDAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:MapperFinders] Suite finalizada.', true, "\r\n", $fallidas === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $fallidas === 0,
        'message' => $fallidas === 0
            ? "Contrato de los buscadores de mapper verificado ({$pasadas} comprobaciones, {$omitidas} omitidas)."
            : "{$fallidas} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
