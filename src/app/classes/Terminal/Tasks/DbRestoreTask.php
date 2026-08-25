<?php

/**
 * DbRestoreTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * DbRestoreTask.
 *
 * El inverso de `db-backup`, que no existía: restaurar era `mysql < archivo.sql`, una operación
 * a mano y SIN REGISTRO. Ver T71.
 *
 * DESTRUYE DATOS, así que exige confirmación explícita. Y deja rastro, porque de ese rastro
 * depende que el recorredor de atribución sepa si la base es nueva (LEY 12).
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DbRestoreTask extends TerminalTaskAbstract
{

    /** Dónde queda el rastro. Lo lee `bin/walk-attribute`. */
    const TRACE_RELATIVE_PATH = 'files/dev/last-restore.json';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Restaura la base de datos por defecto desde un volcado. DESTRUYE LOS DATOS ACTUALES.\r\n",
            "\tParámetros:\r\n",
            "\t  file=<ruta>       volcado .sql a aplicar. Obligatorio\r\n",
            "\t  confirm=yes       confirmación explícita. Obligatorio\r\n",
            "\t  database=<nombre> base destino. Por defecto: la configurada\r\n",
        ]);
        $this->route = "{$startRoute}/db-restore[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'db-restore';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Restaurar la base de datos';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $file = TerminalData::instance()->getArgument('file', '');
        $file = is_string($file) ? trim($file) : '';
        $confirm = TerminalData::instance()->getArgument('confirm', '');

        if ($file === '' || !is_file($file)) {
            echoTerminal("\e[31mERROR:\e[39m falta file=<ruta> o no existe. Ejemplo: bin/cli db-restore file=dumps/x.sql confirm=yes");
            exit(1);
        }

        //DESTRUYE DATOS: sin confirmación explícita no se hace nada.
        if ($confirm !== 'yes') {
            echoTerminal("\e[31mERROR:\e[39m esto BORRA los datos actuales. Repite con confirm=yes si es lo que quieres.");
            exit(1);
        }

        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            echoTerminal("\e[31mERROR:\e[39m sin conexión a base de datos.");
            exit(1);
        }

        $target = TerminalData::instance()->getArgument('database', '');
        $target = is_string($target) && trim($target) !== '' ? trim($target) : (string) $database->getDatabaseName();

        $statements = self::statementsOf((string) file_get_contents($file));
        if (count($statements) === 0) {
            echoTerminal("\e[31mERROR:\e[39m el archivo no contiene ninguna sentencia.");
            exit(1);
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($statements) . " sentencia(s) sobre «{$target}».");

        $database->exec("USE `{$target}`");
        $applied = 0;
        $ignored = [];
        $failures = [];
        foreach ($statements as $statement) {
            $reason = self::whyIgnored($statement);
            if ($reason !== null) {
                //NO se calla: un volcado que trae esto esperaba otra cosa, y hay que verlo.
                $ignored[] = mb_substr(str_replace("\n", ' ', $statement), 0, 60) . ' — ' . $reason;
                continue;
            }
            try {
                $database->exec($statement);
                $applied++;
            } catch (\Throwable $exception) {
                $failures[] = mb_substr(str_replace("\n", ' ', $exception->getMessage()), 0, 100);
            }
        }

        foreach ($ignored as $line) {
            echoTerminal("\e[33mIGNORADA:\e[39m {$line}");
        }

        foreach ($failures as $line) {
            echoTerminal("\e[31mFALLO:\e[39m {$line}");
        }
        echoTerminal("\e[94mINFO:\e[39m {$applied} aplicada(s), " . count($failures) . ' fallida(s).');

        self::writeTrace($file, $target, $applied, count($failures));

        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(count($failures) > 0 ? 1 : 0);
    }

    /**
     * Deja el rastro que `bin/walk-attribute` lee para saber si la base es nueva.
     *
     * @return void
     */
    protected static function writeTrace(string $file, string $database, int $applied, int $failed): void
    {
        $path = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/' . self::TRACE_RELATIVE_PATH;
        $trace = [
            'restoredAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'timestamp' => time(),
            'file' => $file,
            'database' => $database,
            'statementsApplied' => $applied,
            'statementsFailed' => $failed,
        ];
        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, json_encode($trace, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . "\n");
        echoTerminal("\e[34mRastro:\e[39m " . self::TRACE_RELATIVE_PATH);
    }

    /**
     * Sentencias de un volcado.
     *
     * SE RECORRE CARÁCTER A CARÁCTER, no se parte por `;`. Partir por el separador es lo que
     * rompía los cuerpos de rutina: un `BEGIN … END` lleva puntos y coma dentro, y el volcado
     * los protege cambiando el separador con `DELIMITER`. Ver T94.
     *
     * Lo mismo vale para un `;` dentro de una cadena o de un identificador entrecomillado: no
     * termina nada, y un partidor ciego no lo sabe.
     *
     * @return string[]
     */
    protected static function statementsOf(string $sql): array
    {
        $statements = [];
        $delimiter = ';';
        $buffer = '';
        $length = mb_strlen($sql, '8bit');
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $keepBlock = false;
        $atLineStart = true;

        for ($i = 0; $i < $length; $i++) {

            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            //─── Dentro de un comentario: solo interesa dónde acaba ───────────────────────
            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $buffer .= $char;
                    $atLineStart = true;
                }
                continue;
            }
            if ($inBlockComment) {
                if ($keepBlock) {
                    $buffer .= $char;
                }
                if ($char === '*' && $next === '/') {
                    if ($keepBlock) {
                        $buffer .= $next;
                    }
                    $i++;
                    $inBlockComment = false;
                    $keepBlock = false;
                }
                continue;
            }

            //─── Dentro de una cadena o de un identificador: nada delimita ────────────────
            if ($inSingle || $inDouble || $inBacktick) {
                $buffer .= $char;
                if ($char === '\\' && $next !== '') {
                    //Una barra invertida se lleva por delante al siguiente, sea cual sea.
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                if ($inSingle && $char === "'") {
                    $inSingle = false;
                } elseif ($inDouble && $char === '"') {
                    $inDouble = false;
                } elseif ($inBacktick && $char === '`') {
                    $inBacktick = false;
                }
                continue;
            }

            //─── DELIMITER: es una directiva del cliente, y solo vale al principio de línea ─
            if ($atLineStart && self::looksLikeDelimiter($sql, $i)) {
                $end = mb_strpos($sql, "\n", $i, '8bit');
                $line = $end === false ? mb_substr($sql, $i, null, '8bit') : mb_substr($sql, $i, $end - $i, '8bit');
                $declared = trim(mb_substr(trim($line), 9, null, '8bit'));
                if ($declared !== '') {
                    $delimiter = $declared;
                }
                $i = $end === false ? $length : $end;
                $atLineStart = true;
                continue;
            }

            //─── Aperturas de comentario ──────────────────────────────────────────────────
            if ($char === '-' && $next === '-' && ($i + 2 >= $length || $sql[$i + 2] === ' ' || $sql[$i + 2] === "\t" || $sql[$i + 2] === "\n" || $sql[$i + 2] === "\r")) {
                $inLineComment = true;
                continue;
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                //`/*!…*/` NO es un comentario: MySQL lo ejecuta. Se conserva tal cual.
                $keepBlock = $i + 2 < $length && $sql[$i + 2] === '!';
                $inBlockComment = true;
                if ($keepBlock) {
                    $buffer .= $char . $next;
                }
                $i++;
                continue;
            }

            //─── Aperturas de cadena e identificador ──────────────────────────────────────
            if ($char === "'") {
                $inSingle = true;
                $buffer .= $char;
                $atLineStart = false;
                continue;
            }
            if ($char === '"') {
                $inDouble = true;
                $buffer .= $char;
                $atLineStart = false;
                continue;
            }
            if ($char === '`') {
                $inBacktick = true;
                $buffer .= $char;
                $atLineStart = false;
                continue;
            }

            //─── ¿Termina aquí la sentencia? ──────────────────────────────────────────────
            if (self::matchesAt($sql, $i, $delimiter)) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement . ';';
                }
                $buffer = '';
                $i += mb_strlen($delimiter, '8bit') - 1;
                $atLineStart = false;
                continue;
            }

            $buffer .= $char;
            $atLineStart = $char === "\n";
        }

        //Lo que quede sin separador final también es una sentencia: un volcado puede no cerrarla.
        $rest = trim($buffer);
        if ($rest !== '') {
            $statements[] = rtrim($rest, ';') . ';';
        }

        return $statements;
    }

    /**
     * Construcciones que se decide NO aplicar, y por qué.
     *
     * Las dos son de un `mysqldump --databases`, y las dos **eligen la base por su cuenta**.
     * Aplicarlas dejaría sin efecto el parámetro `database=`, que es lo único que separa
     * «restaurar la copia de pruebas» de «restaurar encima de la buena». Se ignoran y se dicen
     * en voz alta; callarlas sería peor que fallar. Ver T96.
     *
     * @return string|null La razón, o `null` si la sentencia se aplica.
     */
    protected static function whyIgnored(string $statement): ?string
    {
        $head = mb_strtoupper(ltrim($statement));
        if (str_starts_with($head, 'USE ') || str_starts_with($head, 'USE`')) {
            return 'cambia de base por su cuenta y anularía database=';
        }
        if (str_starts_with($head, 'CREATE DATABASE') || str_starts_with($head, 'DROP DATABASE')) {
            return 'crea o destruye una base entera: eso lo decide quien invoca, no el volcado';
        }
        return null;
    }

    /**
     * ¿Empieza en `$offset` una directiva `DELIMITER`?
     *
     * @return bool
     */
    protected static function looksLikeDelimiter(string $sql, int $offset): bool
    {
        if (mb_strtoupper(mb_substr($sql, $offset, 9, '8bit')) !== 'DELIMITER') {
            return false;
        }
        $after = mb_substr($sql, $offset + 9, 1, '8bit');
        return $after === ' ' || $after === "\t";
    }

    /**
     * ¿Está `$needle` exactamente en `$offset`?
     *
     * @return bool
     */
    protected static function matchesAt(string $haystack, int $offset, string $needle): bool
    {
        return $needle !== '' && mb_substr($haystack, $offset, mb_strlen($needle, '8bit'), '8bit') === $needle;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new DbRestoreTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }

}
