<?php

/**
 * PublicSiteImportTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\PublicSite\PublicSite;
use PiecesPHP\PublicSite\SiteExporter;
use PiecesPHP\PublicSite\SiteImporter;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * PublicSiteImportTask - Importa la carpeta del diseñador como sitio público (ADR 0084): la comprueba entera y, si no hay
 * errores, la coloca de forma atómica en app/view/<sitio>/ y statics/<sitio>/, con respaldo de lo anterior.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PublicSiteImportTask extends TerminalTaskAbstract
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
            "Importa la carpeta del diseñador como sitio público: la comprueba y la coloca sin dejar nada a medias.\r\n",
            "\tParámetros:\r\n",
            "\t  folder=<ruta> La carpeta del paquete del diseñador (la que tiene site/ y pcs.json), relativa a la raíz o absoluta\r\n",
            "\t  site=<nombre> El nombre del sitio: app/view/<nombre>/ y statics/<nombre>/\r\n",
            "\t  check=yes     Solo comprueba; no coloca nada\r\n",
        ]);
        $this->route = "{$startRoute}/public-site-import[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'public-site-import';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * La carpeta del paquete: relativa, contra la raíz del repositorio, como la exportación (bin/cli corre desde src/).
     *
     * @param string $folder
     * @param string $root
     * @return string
     */
    public static function resolveFolder(string $folder, string $root): string
    {
        return SiteExporter::fromRoot($folder, $root);
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $terminal = TerminalData::instance();
        $folder = $terminal->getArgument('folder', '');
        $site = $terminal->getArgument('site', '');
        $onlyCheck = $terminal->getArgument('check', 'no') === 'yes';
        if (!is_string($folder) || $folder === '' || !is_string($site) || $site === '') {
            echoTerminal("\e[31mERROR:\e[39m faltan parámetros. Uso: bin/cli public-site-import folder=<ruta> site=<nombre> [check=yes]");
            exit(1);
        }
        $folder = self::resolveFolder($folder, dirname(rtrim(basepath(), '/')));
        $importer = new SiteImporter($folder, $site);
        $result = $onlyCheck ? ['ok' => true, 'problems' => $importer->check(), 'backup' => null] : $importer->import();
        $errors = self::printProblems($result['problems']);
        if ($onlyCheck || !$result['ok']) {
            echoTerminal($errors === 0 ? "\e[32mComprobado: sin errores.\e[39m" : "\e[31m{$errors} error(es): no se ha tocado nada.\e[39m");
            exit($errors === 0 && $result['ok'] ? 0 : 1);
        }
        echoTerminal("\e[32mImportado:\e[39m app/view/{$site}/ y statics/{$site}/ (" . count(PublicSite::namesIn(PublicSite::viewDirectory($site) . '/pages')) . ' páginas).');
        if ($result['backup'] !== null) {
            echoTerminal("Lo anterior, respaldado en {$result['backup']} (view/ y statics/): para volver, mueve esas carpetas a su sitio.");
        }
        if (get_config(PublicSite::CONFIG_KEY) !== $site) {
            echoTerminal("Para servirlo: \$config['" . PublicSite::CONFIG_KEY . "'] = '{$site}'; en src/app/config/config.php.");
        }
        exit(0);
    }

    /**
     * @param array<int,array{level:string,file:string,line:int,message:string,fix:string}> $problems
     * @return int Los errores
     */
    public static function printProblems(array $problems): int
    {
        $errors = 0;
        foreach ($problems as $problem) {
            $isError = $problem['level'] === 'error';
            $errors += $isError ? 1 : 0;
            $where = $problem['file'] . ($problem['line'] > 0 ? ":{$problem['line']}" : '');
            echoTerminal(($isError ? "\e[31mERROR\e[39m" : "\e[33mAVISO\e[39m") . ($where !== '' ? " {$where}" : '') . ": {$problem['message']}");
            if ($problem['fix'] !== '') {
                echoTerminal("      Qué hacer: {$problem['fix']}");
            }
        }
        return $errors;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new PublicSiteImportTask($startRoute, $namePrefix);
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
