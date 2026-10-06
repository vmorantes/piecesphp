<?php

/**
 * SessionsRevokeAllTask.php
 */

namespace Terminal\Tasks;

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SessionsRevokeAllTask.
 *
 * Mueve la marca global de sesión a ahora (ADR 0026): ningún token nacido antes vale, para NADIE. Solo existe en el
 * terminal, a propósito: echar a todo el mundo no se ofrece en una pantalla.
 *
 * La marca vive en `pcsphp_app_config`, que `index.php` vuelca en la configuración en cada petición; la lee
 * `SessionToken::minimumDateCreated()`.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SessionsRevokeAllTask extends TerminalTaskAbstract
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
            "Cierra las sesiones de TODOS los usuarios: mueve la marca global de sesión a ahora.\r\n",
            "\tParámetros:\r\n",
            "\t  confirm=yes       confirmación explícita. Obligatorio\r\n",
        ]);
        $this->route = "{$startRoute}/sessions-revoke-all[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'sessions-revoke-all';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Cerrar las sesiones de todos los usuarios';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        //ECHA A TODO EL MUNDO: sin confirmación explícita no se hace nada.
        $confirm = TerminalData::instance()->getArgument('confirm', '');
        if ($confirm !== 'yes') {
            echoTerminal("\e[31mERROR:\e[39m esto cierra la sesión de TODOS los usuarios. Repite con confirm=yes si es lo que quieres.");
            exit(1);
        }

        $previous = SessionToken::minimumDateCreated()->format('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');

        if (!SettingsModel::setConfigValue(SessionToken::MINIMUM_DATE_CONFIG, $now, true)) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo guardar la marca en «" . SessionToken::MINIMUM_DATE_CONFIG . "».");
            exit(1);
        }

        //Relectura: lo que cuenta es lo que leerá la próxima petición, no lo que se quiso escribir.
        $stored = SettingsModel::getConfigValue(SessionToken::MINIMUM_DATE_CONFIG);
        if ($stored !== $now) {
            echoTerminal("\e[31mERROR:\e[39m la marca guardada no es la escrita: " . var_export($stored, true));
            exit(1);
        }

        //Cota superior: sin almacén de sesiones no se sabe quién tenía una abierta, sí quién podía tenerla.
        $model = UsersModel::model();
        $model->resetAll();
        $model->select(['status', 'COUNT(id) AS total'])->groupBy('status')->execute();
        $result = $model->result();
        $users = 0;
        foreach (is_array($result) ? $result : [] as $row) {
            if (!in_array((int) $row->status, UsersModel::STATUSES_INACTIVE_EQUIVALENT)) {
                $users += (int) $row->total;
            }
        }

        LogsMapper::addLog(LogsMapper::MSG_REVOKE_ALL_SESSIONS, [
            '%users%' => (string) $users,
        ]);

        echoTerminal("\e[94mINFO:\e[39m marca global: {$previous} → {$now}");
        echoTerminal("\e[94mINFO:\e[39m pierden la sesión todos los que tuvieran una abierta: hasta {$users} usuario(s) no inactivos.");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SessionsRevokeAllTask($startRoute, $namePrefix);
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
