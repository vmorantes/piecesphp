<?php

/**
 * StaticsProtectMigrateTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Statics\ProtectedUploadsMigration;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationMapper;

/**
 * StaticsProtectMigrateTask.
 *
 * Aplica a TODO uploads la protección por sufijo: primero renombra a privado lo que debe serlo (las carpetas con sesión,
 * las publicaciones no visibles y las carpetas de publications que no son de ninguna) y, solo al final, retira el
 * .htaccess de «reescribir todo» de cada carpeta protegida. Por defecto solo simula.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class StaticsProtectMigrateTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'statics-protect-migrate';

        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $this->description = new StringArray([
            "Aplica la protección por sufijo a todo uploads: primero lo privado, al final retira los .htaccess de cada carpeta.\r\n",
            "\tParámetros:\r\n",
            "\t  --dry-run (por defecto) simula y da el informe por módulo\r\n",
            "\t  --run aplica\r\n",
            "\t  --revert vuelve atrás: repone los .htaccess y después quita los sufijos\r\n",
        ]);
        $this->route = "{$startRoute}/statics-protect-migrate[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $terminal = TerminalData::instance();
        $flag = static fn (string $name): bool => $terminal->getArgument($name) !== null || $terminal->getArgument("--{$name}") !== null;
        $run = $flag('run');
        $revert = $flag('revert');
        if ($run && $revert) {
            echoTerminal("\e[31mERROR:\e[39m --run y --revert a la vez: elige uno.");
            exit(1);
        }
        $mode = $revert ? 'revert' : ($run ? 'run' : 'dry-run');

        [$folders, $roots, $census] = self::inventory();
        $steps = ProtectedUploadsMigration::plan($folders, $roots, $revert);

        //Entre paso y paso, nada privado puede quedar servible: si pasara, el orden estaría roto, y se dice.
        $servable = [];
        $afterStep = $revert ? null : function (array $step) use ($folders, $roots, &$servable): void {
            $servable = array_merge($servable, ProtectedUploadsMigration::servablePrivates($folders, $roots));
        };
        $report = ProtectedUploadsMigration::execute($steps, $mode === 'dry-run', $afterStep);

        $lines = [
            "\e[32m*** Protección de uploads por sufijo · modo {$mode} ***\e[39m",
            "Carpetas protegidas: " . count($roots) . " · pasos: " . count($steps)
                . " · publications: {$census['visibles']} visible(s), {$census['privadas']} privada(s), {$census['huerfanas']} huérfana(s)",
        ];
        $failures = 0;
        foreach ($report as $module => $row) {
            $failures += count($row['conflicts']) + count($row['failed']);
            $lines[] = "- {$module}: a privados {$row['toPrivate']} · a públicos {$row['toPublic']} · sin cambios {$row['unchanged']}"
                . " · .htaccess {$row['htaccess']} · conflictos " . count($row['conflicts']) . ' · fallos ' . count($row['failed']);
            foreach (array_slice(array_merge($row['conflicts'], $row['failed']), 0, 5) as $path) {
                $lines[] = "    \e[31m{$path}\e[39m";
            }
        }
        $lines[] = 'Privados servibles entre pasos: ' . count(array_unique($servable));
        $lines[] = "\e[32m*** Protección de uploads por sufijo, tarea finalizada ***\e[39m";
        echoTerminal(implode("\r\n", $lines));

        if ($failures > 0 || $servable !== []) {
            exit(1);
        }
    }

    /**
     * Las carpetas y su visibilidad, las raíces protegidas y el recuento de publications.
     *
     * @return array{0: array<int, array{module: string, directory: string, public: bool, recursive?: bool}>, 1: array<int, array{module: string, directory: string}>, 2: array{visibles: int, privadas: int, huerfanas: int}}
     */
    private static function inventory(): array
    {
        $publicationsRoot = realpath(append_to_path_system((string) get_config('upload_dir'), PublicationsController::UPLOAD_DIR));
        $folders = [];
        $roots = [];
        $census = ['visibles' => 0, 'privadas' => 0, 'huerfanas' => 0];
        foreach (ProtectFileMiddleware::getPolicies() as $directory => $policy) {
            $module = basename($directory);
            $roots[] = ['module' => $module, 'directory' => $directory];
            if ($directory !== $publicationsRoot) {
                $folders[] = ['module' => $module, 'directory' => $directory, 'public' => false];
                continue;
            }
            //Publications: cada carpeta según su publicación; la que no es de ninguna, privada. Lo suelto en la raíz, también.
            $byFolder = [];
            $model = (new PublicationMapper())->getModel();
            $model->select(['id', 'folder'])->execute();
            $rows = $model->result();
            foreach (is_array($rows) ? $rows : [] as $row) {
                if (is_string($row->folder) && $row->folder !== '') {
                    $byFolder[$row->folder] = (int) $row->id;
                }
            }
            $folders[] = ['module' => $module, 'directory' => $directory, 'public' => false, 'recursive' => false];
            foreach (scandir($directory) ?: [] as $entry) {
                $path = $directory . \DIRECTORY_SEPARATOR . $entry;
                if ($entry === '.' || $entry === '..' || !is_dir($path)) {
                    continue;
                }
                if (isset($byFolder[$entry])) {
                    $visible = (new PublicationMapper($byFolder[$entry]))->isVisibleToPublic();
                    $census[$visible ? 'visibles' : 'privadas']++;
                } else {
                    $visible = false;
                    $census['huerfanas']++;
                }
                $folders[] = ['module' => $module, 'directory' => $path, 'public' => $visible];
            }
        }
        return [$folders, $roots, $census];
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new StaticsProtectMigrateTask($startRoute, $namePrefix);
        $route = new Route(
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
        return $route;
    }

}
