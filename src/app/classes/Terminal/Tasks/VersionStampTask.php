<?php

/**
 * VersionStampTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\AppVersion;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * VersionStampTask - Sella el commit del despliegue en src/app/version-stamp.json, para instalaciones sin .git.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class VersionStampTask extends TerminalTaskAbstract
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
            "Escribe el sello de despliegue (" . AppVersion::STAMP_RELATIVE_PATH . ") con el commit, para instalaciones sin .git.\r\n",
            "\tParámetros:\r\n",
            "\t  commit=<40 hex>   el hash completo; sin él, el que da el repositorio\r\n",
            "\tSale con 1 si el hash no es válido o no hay ninguno.",
        ]);
        $this->route = "{$startRoute}/version-stamp[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'version-stamp';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $given = trim((string) TerminalData::instance()->getArgument('commit', ''));
        $problem = self::stamp($given !== '' ? $given : null);
        if ($problem !== null) {
            echoTerminal("\e[31mERROR:\e[39m {$problem}");
            exit(1);
        }
        echoTerminal('Sello escrito en ' . AppVersion::stampPath() . ': ' . AppVersion::commit());
    }

    /**
     * Escribe el sello; null si fue bien, o el motivo si no.
     *
     * @param string|null $commit null: el del repositorio
     * @return string|null
     */
    public static function stamp(?string $commit): ?string
    {
        $commit = $commit ?? AppVersion::gitCommit();
        if ($commit === null) {
            return 'no se dio commit= y el repositorio no da ninguno.';
        }
        if (!AppVersion::isCommitHash($commit)) {
            return "«{$commit}» no es un hash de commit completo (40 caracteres hexadecimales en minúscula).";
        }
        $path = AppVersion::stampPath();
        $json = json_encode(['commit' => $commit, 'stampedAt' => date(\DATE_ATOM)], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($path, $json . "\n") === false || !chmod($path, 0644)) {
            return "no se pudo escribir {$path}.";
        }
        return null;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new VersionStampTask($startRoute, $namePrefix);
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
