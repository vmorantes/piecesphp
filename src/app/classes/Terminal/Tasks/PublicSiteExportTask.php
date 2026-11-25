<?php

/**
 * PublicSiteExportTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\PublicSite\SiteExporter;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * PublicSiteExportTask - Arma desde el proyecto la carpeta del diseñador del sitio público (ADR 0084), con su kit, sus
 * datos de ejemplo y lo de su agente, sin nada de la metodología del proyecto.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PublicSiteExportTask extends TerminalTaskAbstract
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
            "Arma la carpeta del diseñador del sitio público, lista para seguir trabajando, o deja solo el sitio en files/design/.\r\n",
            "\tOrigen (uno de los tres; from= con un sitio ya importado de ese nombre no se combina: falla):\r\n",
            "\t  from=<carpeta>       Una carpeta con site/ y pcs.json (relativa a la raíz o absoluta), p. ej. files/design/adaptado;\r\n",
            "\t                       no hace falta un sitio importado. site= da el nombre (si falta, el de su pcs.json)\r\n",
            "\t  site=<nombre>        Un sitio importado\r\n",
            "\t  site=<nombre>        Si no está importado y no hay from: el sitio de ejemplo del kit, con ese nombre\r\n",
            "\tDestino:\r\n",
            "\t  destination=<ruta>   La carpeta que se crea (relativa a la raíz o absoluta); no puede existir. Fuera del proyecto: el paquete entero del\r\n",
            "\t                       diseñador (con DESIGN.md y PROJECT.md). Dentro de files/design/: solo site/, pcs.json y\r\n",
            "\t                       DESIGN.md. En otro sitio del proyecto, no se exporta\r\n",
            "\tOpciones:\r\n",
            "\t  project-file=<ruta>  Las reglas del proyecto para el paquete (por omisión files/design/PROJECT.md), dentro del\r\n",
            "\t                       repositorio. Fuera del proyecto ese archivo tiene que existir, con la línea «## Pendientes conocidos»\r\n",
            "\t  zip=yes              Además, <sitio>-<AAAA-MM-DD>.zip junto al destino (solo fuera del proyecto)\r\n",
        ]);
        $this->route = "{$startRoute}/public-site-export[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'public-site-export';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $terminal = TerminalData::instance();
        $site = $terminal->getArgument('site', '');
        $destination = $terminal->getArgument('destination', '');
        $from = $terminal->getArgument('from', '');
        $projectFile = $terminal->getArgument('project-file', '');
        $from = is_string($from) && $from !== '' ? $from : null;
        $projectFile = is_string($projectFile) && $projectFile !== '' ? $projectFile : null;
        //Con from= y sin site=, el nombre que trae la carpeta en su pcs.json; sin él, «faltan parámetros» no diría qué falta.
        if ((!is_string($site) || $site === '') && $from !== null) {
            $site = self::siteFromFolder($from, dirname(rtrim(basepath(), '/')));
            if ($site === null) {
                echoTerminal("\e[31mERROR:\e[39m la carpeta {$from} no tiene pcs.json (o no dice su nombre): indica site=<nombre>");
                exit(1);
            }
        }
        if (!is_string($site) || $site === '' || !is_string($destination) || $destination === '') {
            echoTerminal("\e[31mERROR:\e[39m faltan parámetros. Uso: bin/cli public-site-export (site=<nombre> | from=<carpeta> [site=<nombre>]) destination=<ruta> [project-file=<ruta>] [zip=yes]");
            exit(1);
        }
        $result = (new SiteExporter($site, $destination, $terminal->getArgument('zip', 'no') === 'yes', from: $from, projectFile: $projectFile))->export();
        PublicSiteImportTask::printProblems($result['problems']);
        if ($result['trace'] !== '') {
            echoTerminal($result['trace']);
        }
        if (!$result['ok']) {
            echoTerminal("\e[31mNo se ha exportado nada.\e[39m");
            exit(1);
        }
        echoTerminal("\e[32mExportado:\e[39m " . ($result['destination'] ?? $destination) . " ({$result['files']} archivos), desde {$result['origin']}.");
        if ($result['zip'] !== null) {
            echoTerminal("Zip: {$result['zip']}");
        }
        exit(0);
    }

    /**
     * El nombre del sitio que dice el pcs.json de `$from` (relativa a `$root` o absoluta); null si no lo hay o no trae `name`.
     */
    public static function siteFromFolder(string $from, string $root): ?string
    {
        $folder = str_starts_with($from, '/') ? $from : rtrim($root, '/') . '/' . $from;
        $manifest = is_file("{$folder}/pcs.json") ? json_decode((string) file_get_contents("{$folder}/pcs.json"), true) : null;
        $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
        return is_string($name) && $name !== '' ? $name : null;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new PublicSiteExportTask($startRoute, $namePrefix);
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
