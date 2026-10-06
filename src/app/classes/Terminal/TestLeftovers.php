<?php

/**
 * TestLeftovers.php
 */

namespace Terminal;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * TestLeftovers - Los restos reconocibles de las pruebas en la base, contados y comparados.
 *
 * Existe por el ADR 0047: la verificación del producto **no empezaba en el mismo sitio cada día**.
 * El 2026-10-03 el primer `bin/verify` falló por un resto de la noche anterior y el segundo pasó,
 * y nadie pudo saber qué era **porque la propia suite se limpió al correrla**. Un verde que puede
 * deberse a que una corrida anterior quitó la basura no afirma nada.
 *
 * **Lo que mide y lo que NO** (LEY 15): barre **todas** las columnas de texto del esquema actual
 * buscando el prefijo `zz-`, menos las que por su nombre pueden guardar un secreto, que no se leen
 * (ADR 0024). Por tanto:
 * - **no ve** un registro de prueba que nadie marcó con el prefijo;
 * - **no ve** nada fuera de la base: archivos, buzón en disco o bandeja de correo;
 * - **no distingue** un dato del banco de pruebas de un resto olvidado: para eso está la línea base.
 *
 * @package     Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TestLeftovers
{

    /**
     * La marca que hace reconocible un registro de prueba. Es lo ÚNICO que los distingue de los
     * datos de quien usa la instalación, y por eso el ADR 0023 la exige al crearlos.
     *
     * @var string
     */
    const MARK = 'zz-';

    /**
     * La otra forma de la marca, con guion bajo (`zz_otp_mail_…`, `zz_recovery_…`): la del guion no la veía, y el
     * 2026-10-05 quedaban así cinco usuarios de pruebas viejas. **En LIKE el `_` es comodín**: se busca escapado.
     *
     * @var string
     */
    const MARK_UNDERSCORE = 'zz_';

    /**
     * @var string
     */
    const BASELINE_FILE = 'files/dev/test-data-baseline.json';

    /**
     * Nombres de columna que NO se leen: pueden guardar una contraseña, un token o una clave, y el
     * ADR 0024 prohíbe pedirlas. Se cuentan y se declaran, para que la cobertura no se exagere.
     *
     * @var string
     */
    const SENSITIVE_PATTERN = '/pass|token|key|secret|hash|salt|clave/i';

    /**
     * @var array<string,int>|null
     */
    protected static $lastSweep = null;

    /**
     * Cuenta los restos por (tabla, columna). La clave es `tabla.columna`.
     *
     * Cuesta unos 3 ms sobre 140 columnas (medido el 2026-10-03, tres pases), y por eso se puede
     * hacer entre una suite y la siguiente sin que la verificación se note más lenta.
     *
     * @return array{pares:array<string,int>, tablas:int, columnas:int, omitidas:int, error:string|null}
     */
    public static function sweep(): array
    {
        $resultado = ['pares' => [], 'tablas' => 0, 'columnas' => 0, 'omitidas' => 0, 'error' => null];

        try {
            //Credenciales de `Config::app_db()`, igual que todo mapper: un `new ActiveRecordModel()`
            //a secas usa los valores por omisión del paquete y la base lo rechaza (medido el 2026-10-03).
            $configuracion = Config::app_db('default');
            $database = (new ActiveRecordModel([
                'driver' => $configuracion['driver'],
                'database' => $configuracion['db'],
                'host' => $configuracion['host'],
                'user' => $configuracion['user'],
                'password' => $configuracion['password'],
                'charset' => $configuracion['charset'],
            ]))->getDatabase();
            if ($database === null) {
                $resultado['error'] = 'sin conexión a la base de datos';
                return $resultado;
            }

            $columnas = $database->query(
                'SELECT `table_name` AS `t`, `column_name` AS `c`'
                . ' FROM `information_schema`.`columns`'
                . ' WHERE `table_schema` = DATABASE()'
                . " AND `data_type` IN ('char','varchar','text','mediumtext','longtext','tinytext')"
                . ' ORDER BY `table_name`, `column_name`'
            );
            if ($columnas === false) {
                $resultado['error'] = 'no se pudo leer el esquema';
                return $resultado;
            }

            $tablas = [];
            foreach ((array) $columnas->fetchAll(\PDO::FETCH_ASSOC) as $columna) {
                $tabla = is_array($columna) ? (string) ($columna['t'] ?? '') : '';
                $nombre = is_array($columna) ? (string) ($columna['c'] ?? '') : '';
                if ($tabla === '' || $nombre === '') {
                    continue;
                }
                $tablas[$tabla] = true;
                if (preg_match(self::SENSITIVE_PATTERN, $nombre) === 1) {
                    $resultado['omitidas']++;
                    continue;
                }
                $resultado['columnas']++;
                //El prefijo va en el patrón y no en un parámetro porque `LIKE` lo necesita pegado;
                //el valor es una constante de esta clase, no viene de ninguna petición.
                $cuenta = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE `{$nombre}` LIKE ? OR `{$nombre}` LIKE ?");
                $cuenta->execute([self::MARK . '%', self::likeLiteral(self::MARK_UNDERSCORE) . '%']);
                $total = (int) $cuenta->fetchColumn();
                if ($total > 0) {
                    $resultado['pares']["{$tabla}.{$nombre}"] = $total;
                }
            }
            $resultado['tablas'] = count($tablas);
        } catch (\Throwable $throwable) {
            //Falla CERRADO y lo DICE: un barrido que no se pudo hacer no es un barrido limpio.
            $resultado['error'] = get_class($throwable) . ': ' . $throwable->getMessage();
        }

        self::$lastSweep = $resultado['pares'];
        return $resultado;
    }

    /**
     * Un texto para LIKE con sus comodines escapados: `_` y `%` cuentan como lo que son.
     *
     * @param string $texto
     * @return string
     */
    public static function likeLiteral(string $texto): string
    {
        return addcslashes($texto, '\\%_');
    }

    /**
     * La línea base: cuántos restos `zz-` hay en cada par cuando la base está «como debe estar».
     *
     * **No es cero, y eso es a propósito.** Medido el 2026-10-03: hay 21 usuarios y una quincena de
     * registros más con el prefijo que son el BANCO DE PRUEBAS, creados a mano desde el 2026-09-15 y
     * que varias suites necesitan. Exigir cero haría que ninguna corrida pudiera decir «partió
     * limpia» y la condición del ADR 0047 §2 sería inalcanzable.
     *
     * @return array{declarado:array<string,int>, existe:bool, metodo:string}
     */
    public static function baseline(): array
    {
        $ruta = self::baselinePath();
        if (!is_file($ruta)) {
            return ['declarado' => [], 'existe' => false, 'metodo' => ''];
        }
        $crudo = (string) @file_get_contents($ruta);
        $datos = $crudo !== '' ? json_decode($crudo, true) : null;
        if (!is_array($datos)) {
            return ['declarado' => [], 'existe' => false, 'metodo' => ''];
        }
        $declarado = [];
        foreach ((array) ($datos['pares'] ?? []) as $clave => $valor) {
            $declarado[(string) $clave] = (int) $valor;
        }
        return [
            'declarado' => $declarado,
            'existe' => true,
            'metodo' => (string) ($datos['metodo'] ?? ''),
        ];
    }

    /**
     * @return string
     */
    public static function baselinePath(): string
    {
        $raiz = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        return $raiz . '/' . self::BASELINE_FILE;
    }

    /**
     * Compara un barrido con la línea base. **Limpio es «exactamente lo declarado»**, no «poco»:
     * un par que creció trae un resto, y uno que bajó se ha llevado algo del banco de pruebas, que
     * es lo que deja a otra suite sin su fixture.
     *
     * @param array<string,int> $pares
     * @return array{limpio:bool, sobran:array<string,array{base:int,ahora:int}>, faltan:array<string,array{base:int,ahora:int}>}
     */
    public static function compareToBaseline(array $pares): array
    {
        $base = self::baseline()['declarado'];
        $sobran = [];
        $faltan = [];
        foreach (array_keys($base + $pares) as $clave) {
            $clave = (string) $clave;
            $esperado = (int) ($base[$clave] ?? 0);
            $ahora = (int) ($pares[$clave] ?? 0);
            if ($ahora > $esperado) {
                $sobran[$clave] = ['base' => $esperado, 'ahora' => $ahora];
            } elseif ($ahora < $esperado) {
                $faltan[$clave] = ['base' => $esperado, 'ahora' => $ahora];
            }
        }
        return ['limpio' => count($sobran) === 0 && count($faltan) === 0, 'sobran' => $sobran, 'faltan' => $faltan];
    }

    /**
     * Qué pares crecieron entre dos barridos. Es la forma que ya funcionó con la bandeja de correo:
     * no se mide el total, se mide la DIFERENCIA y se atribuye.
     *
     * @param array<string,int> $antes
     * @param array<string,int> $ahora
     * @return array<string,array{antes:int,ahora:int}>
     */
    public static function growth(array $antes, array $ahora): array
    {
        $crecieron = [];
        foreach ($ahora as $clave => $total) {
            $previo = (int) ($antes[(string) $clave] ?? 0);
            if ((int) $total > $previo) {
                $crecieron[(string) $clave] = ['antes' => $previo, 'ahora' => (int) $total];
            }
        }
        return $crecieron;
    }

    /**
     * Un resumen de una línea de los pares, para pegarlo en la salida.
     *
     * @param array<string,array{base?:int,ahora?:int,antes?:int}> $pares
     * @return string
     */
    public static function describe(array $pares): string
    {
        $partes = [];
        foreach ($pares as $clave => $cifras) {
            $desde = array_key_exists('base', $cifras) ? (int) $cifras['base'] : (int) ($cifras['antes'] ?? 0);
            $partes[] = "{$clave} " . $desde . '->' . (int) ($cifras['ahora'] ?? 0);
        }
        return implode(', ', $partes);
    }

    /**
     * Reescribe la línea base con lo que hay AHORA en la base.
     *
     * **Es la operación que puede convertir una base sucia en «limpia» de un plumazo**, así que deja
     * su fecha y sus cifras en el archivo, y el diff de git la enseña. No se hace por omisión y la
     * salida de quien la llama tiene que gritarlo.
     *
     * @param string $motivo
     * @return bool
     */
    public static function rebaseline(string $motivo): bool
    {
        $barrido = self::sweep();
        if ($barrido['error'] !== null) {
            return false;
        }
        $datos = [
            'metodo' => 'bin/cli gates leftovers=rebase: COUNT(*) con `LIKE \'' . self::MARK . '%\'` o `LIKE \'zz\\_%\'` (el guion bajo escapado) sobre todas las'
                . ' columnas de texto del esquema actual, menos las que por su nombre pueden guardar un secreto'
                . ' (ADR 0024), que no se leen. Clave: tabla.columna. Solo se escriben los pares con al menos uno.',
            'motivo' => $motivo,
            'medido' => date('Y-m-d H:i:s'),
            'universo' => [
                'tablas' => $barrido['tablas'],
                'columnas_barridas' => $barrido['columnas'],
                'columnas_omitidas_por_sensibles' => $barrido['omitidas'],
            ],
            'que_no_ve' => 'un registro de prueba sin el prefijo; nada fuera de la base (archivos, buzón, bandeja de correo).',
            'pares' => $barrido['pares'],
        ];
        $escrito = @file_put_contents(
            self::baselinePath(),
            json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
        );
        return is_int($escrito) && $escrito > 0;
    }
}
