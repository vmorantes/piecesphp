<?php

/**
 * DataTransferImportTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\SpreadsheetRowSource;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * DataTransferImportTask - Importación por terminal con un importador registrado (sustituye a la importación «exógena» por GET).
 *
 * Las credenciales generadas van a un archivo fuera del proyecto, con permisos 0600; la consola solo imprime su ruta.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DataTransferImportTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Importa un archivo xlsx o csv con un importador registrado. Todo o nada: si una fila falla, no se guarda ninguna.\r\n",
            "\tParámetros:\r\n",
            "\t  definition=<key>        el importador (p. ej. users)\r\n",
            "\t  file=<ruta>              el archivo .xlsx o .csv\r\n",
            "\t  as-user=<id>             usuario en cuyo nombre se importa; su tipo debe poder usar el importador\r\n",
            "\t  credentials-out=<ruta>   obligatorio si el importador genera credenciales: archivo nuevo, fuera del proyecto\r\n",
            "\tSale con 0 si se guardó y con 1 si no.",
        ]);
        $this->route = "{$startRoute}/data-transfer-import[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'data-transfer-import';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        echoTerminal("\e[32m*** Importación por terminal ***\e[39m");

        $data = TerminalData::instance();
        $key = (string) $data->getArgument('definition', '');
        $file = (string) $data->getArgument('file', '');
        $asUser = (string) $data->getArgument('as-user', '');
        $credentialsOut = (string) $data->getArgument('credentials-out', '');

        //TODAS LAS NEGATIVAS ANTES DE IMPORTAR: una importación hecha no se deshace.
        $importers = DataImportExportUtilityRoutes::importers();
        if (!array_key_exists($key, $importers)) {
            self::fail("no hay ningún importador «{$key}». Registrados: " . implode(', ', array_keys($importers)) . '.');
        }
        /** @var ImportDefinition $definition */
        $definition = new $importers[$key]();

        $user = ctype_digit($asUser) ? UsersModel::getUsersByIDs([(int) $asUser]) : [];
        $user = count($user) > 0 ? $user[0] : null;
        if ($user === null) {
            self::fail("as-user «{$asUser}» no es un usuario existente.");
        }
        if (!in_array((int) $user->type, $definition->allowedUserTypes(), true)) {
            self::fail("el usuario {$asUser} no puede usar el importador «{$key}».");
        }

        $extension = mb_strtolower(pathinfo($file, \PATHINFO_EXTENSION));
        if (!is_file($file) || !in_array($extension, array_map('mb_strtolower', $definition->acceptedExtensions()), true)) {
            self::fail("file debe ser un archivo existente con extensión " . implode(' o ', $definition->acceptedExtensions()) . '.');
        }
        if ($extension === 'csv' && !DataTransferController::isTextCsv($file)) {
            self::fail('el archivo no es un CSV de texto.');
        }
        if ((int) filesize($file) > $definition->maxSizeMB() * 1000 * 1000) {
            self::fail("el archivo supera {$definition->maxSizeMB()} MB.");
        }

        if ($definition->mayProduceArtifacts()) {
            $problem = self::credentialsOutProblem($credentialsOut);
            if ($problem !== null) {
                self::fail("credentials-out: {$problem}");
            }
        }

        set_config('current_user', (object) ['id' => (int) $user->id]);
        set_config('pcsphp_current_user_stored', null);

        $report = (new ImportRunner())->run($definition, SpreadsheetRowSource::fromFile($file, $extension));

        echoTerminal("Filas: {$report->totalRows()} · válidas: {$report->validRows()} · inválidas: {$report->invalidRows()}");
        foreach ($report->headerErrors() as $error) {
            echoTerminal("  \e[31m-\e[39m {$error}");
        }
        foreach ($report->rowResults() as $row) {
            foreach ($row->errors() as $error) {
                echoTerminal("  \e[31mFila {$row->position()}:\e[39m {$error}");
            }
        }

        $artifacts = $report->artifacts();
        if ($report->persisted() && $artifacts !== null) {
            if (!self::writeCredentials($credentialsOut, $artifacts->content())) {
                echoTerminal("\e[31mERROR:\e[39m la importación se guardó, pero no se pudo escribir credentials-out. Las contraseñas generadas se han perdido: restablécelas.");
                exit(1);
            }
            echoTerminal("Credenciales en: {$credentialsOut}");
        }

        if (!$report->persisted()) {
            echoTerminal("\e[31mNo se guardó ninguna fila.\e[39m");
            exit(1);
        }
        echoTerminal("\e[32mImportación guardada.\e[39m");
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new DataTransferImportTask($startRoute, $namePrefix);
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

    /**
     * @param string $path
     * @return string|null null si la ruta vale
     */
    public static function credentialsOutProblem(string $path): ?string
    {
        if (trim($path) === '') {
            return 'es obligatorio con este importador, porque genera contraseñas.';
        }
        if (file_exists($path)) {
            return 'el archivo ya existe; indica uno nuevo.';
        }
        $directory = realpath(dirname($path));
        if ($directory === false || !is_dir($directory) || !is_writable($directory)) {
            return 'su carpeta no existe o no se puede escribir.';
        }
        //Todo el repositorio, no solo src/: nada con contraseñas puede acabar donde git o Apache lo vean.
        $project = realpath(dirname(basepath()));
        $project = $project !== false ? $project : dirname(basepath());
        if ($directory === $project || str_starts_with($directory . '/', rtrim($project, '/') . '/')) {
            return 'no puede estar dentro del proyecto.';
        }
        return null;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool
     */
    private static function writeCredentials(string $path, string $content): bool
    {
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            return false;
        }
        //0600 antes de escribir: el contenido nunca existe con permisos más abiertos.
        $restricted = chmod($path, 0600);
        $written = $restricted && fwrite($handle, $content) === strlen($content);
        $closed = fclose($handle);
        return $written && $closed;
    }

    /**
     * @param string $message
     * @return never
     */
    private static function fail(string $message): never
    {
        echoTerminal("\e[31mERROR:\e[39m {$message}");
        exit(1);
    }
}
