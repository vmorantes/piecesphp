<?php

/**
 * SystemAlertsTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * SystemAlertsTask - Los avisos del sistema activos, los mismos de «Avisos del sistema» del panel. Solo lee.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SystemAlertsTask extends TerminalTaskAbstract
{

    const SEVERITY_LABELS = [
        SystemAlert::SEVERITY_DANGER => 'PELIGRO',
        SystemAlert::SEVERITY_WARNING => 'ATENCIÓN',
        SystemAlert::SEVERITY_INFO => 'INFO',
    ];

    const NO_ALERTS = 'Sin avisos activos.';

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
            "Enumera los avisos del sistema activos (los de «Avisos del sistema» del panel), con su gravedad y si están ocultos.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/system-alerts[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'system-alerts';
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
        //Idempotente: en la terminal las rutas del panel pueden no haber registrado todavía los avisos del núcleo.
        SystemStatusRoutes::registerCoreAlerts();
        $lines = self::lines();
        foreach ($lines as $line) {
            echoTerminal($line);
        }
        $total = $lines === [self::NO_ALERTS] ? 0 : count($lines);
        echoTerminal("Total: {$total} aviso(s) activo(s).");
    }

    /**
     * Una línea por aviso activo, en el orden del registro y sin filtrar por audiencia:
     * «[GRAVEDAD] key — mensaje», más « (oculto en el panel)» y « → ruta: <fixRoute>» si aplican.
     *
     * @return string[]
     */
    public static function lines(): array
    {
        $lines = [];
        foreach (SystemAlertRegistry::all() as $alert) {
            //isActive() ya atrapa y registra lo que lance la condición: un aviso roto no tumba la lista.
            if (!SystemAlertRegistry::isActive($alert)) {
                continue;
            }
            try {
                $message = $alert->message();
            } catch (\Throwable $e) {
                log_exception($e);
                continue;
            }
            $line = '[' . (self::SEVERITY_LABELS[$alert->severity()] ?? mb_strtoupper($alert->severity())) . '] ' . $alert->key() . ' — ' . $message;
            if (SystemAlertRegistry::isHidden($alert->key())) {
                $line .= ' (oculto en el panel)';
            }
            if ($alert->fixRoute() !== null) {
                $line .= ' → ruta: ' . $alert->fixRoute();
            }
            $lines[] = $line;
        }
        return count($lines) > 0 ? $lines : [self::NO_ALERTS];
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SystemAlertsTask($startRoute, $namePrefix);
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
