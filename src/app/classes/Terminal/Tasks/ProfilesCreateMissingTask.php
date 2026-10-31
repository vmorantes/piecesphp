<?php

/**
 * ProfilesCreateMissingTask.php
 */

namespace Terminal\Tasks;

use MySpace\Controllers\Util\ProfileTasksUtilities;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * ProfilesCreateMissingTask - Crea el perfil a los usuarios que deberían tenerlo y no lo tienen.
 *
 * Al actualizar una instalación se corre una vez: desde P266 el perfil se crea en el alta, no en cada petición. Es
 * IDEMPOTENTE (UserProfileMapper::ensureFor()) y sin confirm=yes solo enumera.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ProfilesCreateMissingTask extends TerminalTaskAbstract
{

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     */
    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Crea el perfil a los usuarios de los tipos que lo llevan y no lo tienen. Sin confirm=yes solo los enumera.\r\n",
            "\tParámetros:\r\n",
            "\t  confirm=yes   crea los que faltan. Sin esto, no escribe nada\r\n",
        ]);
        $this->route = "{$startRoute}/profiles-create-missing[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'profiles-create-missing';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * @param RequestRoute|null $requestRoute
     * @param ResponseRoute|null $responseRoute
     * @param array|null $parameters
     * @return void
     */
    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        //Los argumentos de la línea de órdenes se leen por TerminalData; $parameters son los de la ruta.
        $confirm = TerminalData::instance()->getArgument('confirm', '') === 'yes';
        $result = ProfileTasksUtilities::generateMissingProfiles($confirm);

        echoTerminal('Usuarios sin perfil: ' . count($result['missing']) . ($result['missing'] !== [] ? ' (ids ' . implode(', ', $result['missing']) . ')' : '') . '.');
        if (!$confirm) {
            echoTerminal('Sin confirm=yes no se ha creado ninguno.');
            return;
        }
        echoTerminal('Perfiles creados: ' . count($result['created']) . ($result['created'] !== [] ? ' (ids de usuario ' . implode(', ', $result['created']) . ')' : '') . '.');
        foreach ($result['failed'] as $userID) {
            echoTerminal("\e[31mFALLO:\e[39m no se creó el perfil del usuario {$userID}.");
        }
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new ProfilesCreateMissingTask($startRoute, $namePrefix);
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
