<?php

/**
 * UnitTest-MetaPropertyHybrid.php
 *
 * Prueba `MetaProperty` TAL COMO CORRE EN EL FRAMEWORK, que no es como la prueba nadie.
 *
 * `PiecesPHP\Core\Database\Meta\MetaProperty` está declarada dos veces: en el núcleo y en
 * `piecesphp/database`. PSR-4 resuelve por prefijo más largo, así que aquí gana siempre la
 * del núcleo — y no son dos versiones de lo mismo, son dos linajes: la del núcleo se apoya
 * en `EntityMapper` y la del paquete en `ORM`.
 *
 * Lo que se ejecuta aquí es un HÍBRIDO: `MetaProperty` del núcleo llamando a
 * `EntityMapper::validateType()` del paquete. **Esa combinación no la prueba ninguno de los
 * dos repositorios.** La suite del paquete (`UnitTest-MetaUtil`) llama a
 * `MetaProperty::validateType()`, un método estático que en la copia que corre aquí NO
 * EXISTE: pasa allí y sería un fatal aquí.
 *
 * Ya costó una vez. El arreglo de la deprecación de PHP 8.5 —pasar `null` a
 * `DateTime::__construct()`— se aplicó a los dos archivos del paquete, y a este código llegó
 * **de rebote**, por el único hilo que quedaba: la copia del núcleo no construye ninguna
 * fecha, delega en `EntityMapper::validateType()`, y esa clase no está eclipsada. Nadie
 * tendió ese hilo a propósito. La próxima vez puede no haberlo.
 *
 * Todas las comprobaciones son de SOLO LECTURA: ninguna escribe en base de datos, y la de
 * tipo mapper usa `null` a propósito, que es el camino que no llega a instanciar nada.
 *
 * Ver T16 en `.agents/context/18-siguientes-ventanas.md`.
 */

use App\Model\UsersModel;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/meta-property-hybrid';
$cliTaskDescription = 'MetaProperty tal como se ejecuta en el framework, no como lo prueba el paquete';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:MetaPropertyHybrid] Iniciando suite...', true, "\r\n", '33');
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

    /**
     * Ruta del archivo donde vive realmente una clase, relativa a la raíz del repositorio.
     *
     */
    $fileOf = function (string $className): string {
        //`class_exists()` estrecha string a class-string; sin él la reflexión no está justificada.
        if (!class_exists($className)) {
            return '';
        }
        $file = (new \ReflectionClass($className))->getFileName();
        if (!is_string($file)) {
            return '';
        }
        $file = str_replace('\\', '/', $file);
        $root = str_replace('\\', '/', dirname(rtrim(str_replace('\\', '/', basepath('')), '/')));
        return ltrim(str_replace($root, '', $file), '/');
    };

    /**
     * Vigilante de deprecaciones. Hoy corremos por debajo de 8.5 y no salta ninguna; el
     * valor de esto es que empieza a morder EL DÍA que se suba el piso, sin que nadie
     * tenga que acordarse de volver aquí.
     */
    $deprecations = [];
    set_error_handler(function (int $number, string $message) use (&$deprecations) {
        if ($number === \E_DEPRECATED || $number === \E_USER_DEPRECATED) {
            $deprecations[] = $message;
        }
        //`false` deja que siga el manejador de siempre: esta suite observa, no secuestra.
        return false;
    });

    try {

        //──── 1. Quién gana la resolución ───────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('1) Identidad de la clase que se ejecuta', true, "\r\n", '36');

        $metaFile = $fileOf(MetaProperty::class);
        $check(
            mb_strpos($metaFile, 'src/app/core/psr4/') === 0,
            'MetaProperty resuelve al archivo DEL NÚCLEO, no al del paquete',
            $metaFile
        );

        $entityFile = $fileOf(EntityMapper::class);
        $check(
            mb_strpos($entityFile, 'src/vendor/piecesphp/database/') === 0,
            'EntityMapper resuelve al archivo DEL PAQUETE: el híbrido es real',
            $entityFile
        );

        $check(
            !method_exists(MetaProperty::class, 'validateType'),
            'MetaProperty NO tiene validateType() estática aquí',
            'Es lo que hace inaplicable la suite del paquete, que sí la llama. Si algún día '
                . 'los dos linajes se unifican, esta comprobación falla A PROPÓSITO: obliga a '
                . 'decidir qué pasa con esta suite en vez de dejarla mintiendo.'
        );

        //──── 2. La API que EntityMapperExtensible exige ────────────────────────────────
        echoTerminal(' ');
        echoTerminal('2) La API que el núcleo consume', true, "\r\n", '36');

        $required = ['getInternalName', 'setInternalName', 'getType'];
        $missing = array_values(array_filter($required, fn (string $method) => !method_exists(MetaProperty::class, $method)));
        $check(
            count($missing) === 0,
            'existen los métodos que EntityMapperExtensible::addMetaProperty() llama',
            count($missing) === 0 ? implode(', ', $required) : 'faltan: ' . implode(', ', $missing)
        );

        $property = new MetaProperty(MetaProperty::TYPE_TEXT, 'valor', false);
        $mapper = (new \ReflectionClass(EntityMapperExtensible::class))->newInstanceWithoutConstructor();
        $mapper->addMetaProperty($property, 'myField');

        $check(
            $property->getInternalName() === 'myField',
            'addMetaProperty() bautiza la propiedad con el nombre del campo',
            'getInternalName() = ' . var_export($property->getInternalName(), true)
        );

        $message = '';
        try {
            $property->setValue([1, 2]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
        }
        $check(
            mb_strpos($message, 'myField') !== false,
            'el mensaje de error NOMBRA el campo',
            $message !== '' ? $message : 'no lanzó ninguna excepción'
        );

        //──── 3. La ruta de fecha: por donde llegó de rebote el arreglo de 8.5 ──────────
        echoTerminal(' ');
        echoTerminal('3) Fechas — la ruta que delega en el paquete', true, "\r\n", '36');

        $date = new MetaProperty(MetaProperty::TYPE_DATE, null, true);
        $check(
            $date->getValue() === null,
            'un TYPE_DATE anulable se construye con null sin lanzar',
            'getValue() = ' . var_export($date->getValue(), true)
        );

        $date->setValue(null);
        $check(
            $date->getValue() === null,
            'setValue(null) sobre un TYPE_DATE anulable se acepta',
            'getValue() = ' . var_export($date->getValue(), true)
        );

        $date->setValue('2026-01-02 03:04:05');
        $check(
            $date->getValue() === '2026-01-02 03:04:05',
            'una fecha se guarda TAL CUAL, sin convertirse en DateTime',
            'Es la divergencia con la copia del paquete, que sí convierte. getValue() = '
                . var_export($date->getValue(), true)
        );

        $check(
            $date->getValueToSQL() === '2026-01-02 03:04:05',
            'getValueToSQL() devuelve la cadena que espera el SQL',
            'getValueToSQL() = ' . var_export($date->getValueToSQL(), true)
        );

        //──── 4. Tipo mapper con null: el camino que no toca base de datos ──────────────
        echoTerminal(' ');
        echoTerminal('4) TYPE_MAPPER anulable', true, "\r\n", '36');

        $mapperProperty = new MetaProperty(MetaProperty::TYPE_MAPPER, null, true, UsersModel::class, 'id');
        $mapperProperty->setValue(null);
        $check(
            $mapperProperty->getValue() === null,
            'null en un campo mapper anulable se acepta y NO instancia nada',
            'La copia del núcleo envuelve la conversión en if ($value !== null). La del '
                . 'paquete no lo hace, y hay campos así declarados en OrganizationMapper y '
                . 'UserProfileMapper.'
        );

        //──── 5. Ninguna deprecación por el camino ──────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('5) Deprecaciones', true, "\r\n", '36');

        $check(
            count($deprecations) === 0,
            'nada de lo anterior emitió una deprecación',
            count($deprecations) === 0
                ? 'PHP ' . PHP_VERSION
                : implode(' | ', array_slice($deprecations, 0, 3))
        );

    } finally {
        restore_error_handler();
    }

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:MetaPropertyHybrid] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El híbrido MetaProperty+EntityMapper se comporta como se espera ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->register();
