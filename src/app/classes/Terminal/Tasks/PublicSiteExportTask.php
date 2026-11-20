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
            "Arma la carpeta del diseñador de un sitio público importado, lista para seguir trabajando.\r\n",
            "\tParámetros:\r\n",
            "\t  site=<nombre>        El sitio\r\n",
            "\t  destination=<ruta>   La carpeta que se crea (no puede existir ni quedar dentro del proyecto)\r\n",
            "\t  zip=yes              Además, <sitio>-<AAAA-MM-DD>.zip junto al destino\r\n",
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
        if (!is_string($site) || $site === '' || !is_string($destination) || $destination === '') {
            echoTerminal("\e[31mERROR:\e[39m faltan parámetros. Uso: bin/cli public-site-export site=<nombre> destination=<ruta> [zip=yes]");
            exit(1);
        }
        $result = (new SiteExporter($site, $destination, $terminal->getArgument('zip', 'no') === 'yes'))->export();
        PublicSiteImportTask::printProblems($result['problems']);
        if ($result['trace'] !== '') {
            echoTerminal($result['trace']);
        }
        if (!$result['ok']) {
            echoTerminal("\e[31mNo se ha exportado nada.\e[39m");
            exit(1);
        }
        echoTerminal("\e[32mExportado:\e[39m {$destination} ({$result['files']} archivos).");
        if ($result['zip'] !== null) {
            echoTerminal("Zip: {$result['zip']}");
        }
        exit(0);
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
