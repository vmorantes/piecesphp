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
        $failures = [];
        foreach ($statements as $statement) {
            try {
                $database->exec($statement);
                $applied++;
            } catch (\Throwable $exception) {
                $failures[] = mb_substr(str_replace("\n", ' ', $exception->getMessage()), 0, 100);
            }
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
     * Los comentarios se quitan POR LÍNEAS y ANTES de partir: partir primero y descartar los
     * trozos que empiezan por `--` se traga la primera sentencia, que va pegada a la cabecera.
     *
     * @return string[]
     */
    protected static function statementsOf(string $sql): array
    {
        $clean = [];
        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            $clean[] = $line;
        }
        $statements = [];
        foreach (explode(";\n", implode("\n", $clean)) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '') {
                $statements[] = rtrim($chunk, ';') . ';';
            }
        }
        return $statements;
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
