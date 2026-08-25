<?php

/**
 * GatesTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * GatesTask.
 *
 * Corre TODAS las suites y termina en fallo si alguna NO CORRIÓ.
 *
 * Existe por la LEY 13: `scheme-sql-round-trip` se omitió a sí misma durante un día entero
 * —el paquete instalado no traía `createScript()`— y su omisión se reportó dos veces como
 * «8/8» leyendo el número esperado en vez del impreso. Una suite omitida no es un dato
 * neutro: es una puerta que no se abrió. Ver T74.
 *
 * DOS COSAS SE DERIVAN, NINGUNA SE ENUMERA (LEY 11):
 *
 *   - QUÉ SUITES HAY: `CliActions::listActionNames()` filtrado por prefijo. Una suite nueva
 *     entra sola; no hay lista que actualizar ni que se pueda quedar corta.
 *   - SI CORRIÓ: se exige la línea de balance que toda suite imprime al terminar. Sin
 *     balance, la suite no llegó al final, y da igual el motivo. No se buscan mensajes de
 *     omisión concretos: eso sería otra lista a mano.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GatesTask extends TerminalTaskAbstract
{

    /** Prefijo de las acciones que son suites. */
    const SUITE_PREFIX = 'unit-tests:core/';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Corre todas las suites y FALLA si alguna no corrió.\r\n",
            "\tParámetros:\r\n",
            "\t  only=<trozo>   corre solo las suites cuyo nombre lo contenga. Por defecto: todas\r\n",
            "\t  with=external  incluye las que declaran salida a la red o envío de correo\r\n",
        ]);
        $this->route = "{$startRoute}/gates[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'gates';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Corredor de puertas';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $only = \PiecesPHP\TerminalData::instance()->getArgument('only', '');
        $only = is_string($only) ? trim($only) : '';

        $withExternal = \PiecesPHP\TerminalData::instance()->getArgument('with', '') === 'external';

        $suites = array_values(array_filter(
            CliActions::listActionNames(),
            static fn (string $name): bool => str_starts_with($name, self::SUITE_PREFIX)
                && ($only === '' || mb_strpos($name, $only) !== false)
        ));
        sort($suites);

        if (count($suites) === 0) {
            echoTerminal("\e[31mERROR:\e[39m ninguna suite registrada con el prefijo «" . self::SUITE_PREFIX . "».");
            exit(1);
        }

        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $failed = [];

        $skipped = [];

        foreach ($suites as $suite) {
            $short = mb_substr($suite, mb_strlen(self::SUITE_PREFIX));
            $label = str_pad($short, 24);
            $action = CliActions::get($suite);
            $effects = $action !== null ? $action->getEffects() : null;

            //SIN DECLARACIÓN NO SE CORRE. El estado por defecto es «no sé qué hace esto».
            if ($effects === null) {
                $failed[] = $suite;
                echoTerminal("   \e[31m[SIN DECLARAR]\e[39m  {$label} no dice qué hace fuera de sí misma: setEffects() en su registro");
                continue;
            }

            $external = array_values(array_intersect($effects, CliActions::EFFECTS_EXTERNAL));
            if (count($external) > 0 && !$withExternal) {
                $skipped[] = $suite;
                echoTerminal("   \e[33m[NO SE CORRE]\e[39m   {$label} declara «" . implode(', ', $external)
                    . "». Para incluirla: bin/cli gates with=external");
                continue;
            }
            if (count($external) > 0) {
                echoTerminal("   \e[33mAVISO:\e[39m {$short} va a SALIR AL EXTERIOR: " . implode(', ', $external));
            }

            $result = self::runSuite($root, $suite);

            if ($result['ran'] === false) {
                //Omitida y acabada-sin-decir-nada son indistinguibles desde fuera. Ver T74.
                $failed[] = $suite;
                echoTerminal("   \e[31m[SIN VEREDICTO]\e[39m {$label} {$result['reason']}");
                continue;
            }

            if ($result['failures'] > 0 || $result['exit'] !== 0) {
                $failed[] = $suite;
                echoTerminal("   \e[31m[FALLÓ]\e[39m        {$label} {$result['balance']}");
                continue;
            }

            echoTerminal("   \e[32m[PASÓ]\e[39m         {$label} {$result['balance']}");
        }

        echoTerminal('');
        echoTerminal('   ' . count($suites) . ' suite(s), ' . count($failed) . ' sin veredicto o con fallos, '
            . count($skipped) . ' no corridas por declarar efectos externos.');

        if (count($failed) > 0) {
            echoTerminal("\e[31m*** {$titleTask}, tarea finalizada CON FALLOS ***\e[39m");
            exit(1);
        }

        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Corre una suite en su propio proceso y dice si LLEGÓ AL FINAL.
     *
     * En proceso aparte a propósito: una suite que llame a `exit()` se llevaría por delante al
     * corredor, y varias tocan la base —`db-restore` la restaura entera—.
     *
     * @return array{ran: bool, reason: string, balance: string, failures: int, exit: int}
     */
    protected static function runSuite(string $root, string $suite): array
    {
        $binary = (string) getenv('PCSPHP_PHP_BIN');
        $command = escapeshellcmd($root . '/bin/cli') . ' ' . escapeshellarg($suite) . ' 2>&1';
        if ($binary !== '') {
            $command = 'PCSPHP_PHP_BIN=' . escapeshellarg($binary) . ' ' . $command;
        }

        $output = [];
        $status = 0;
        exec($command, $output, $status);

        $plain = preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $output));
        $plain = is_string($plain) ? $plain : '';

        //LA PRUEBA DE QUE CORRIÓ ES SU BALANCE. Sin él no llegó al final, y el motivo da igual.
        $failures = null;
        $balance = '';

        if (preg_match('/BALANCE FINAL:\s*(\d+)\/(\d+)\s*PASADAS(?:,\s*(\d+)\s*OMITIDAS)?/u', $plain, $matched) === 1) {
            $balance = trim($matched[0]);
            $failures = (int) $matched[2] - (int) $matched[1];
        } elseif (preg_match('/Total:\s*(\d+)\s*\|\s*Pasaron:\s*(\d+)\s*\|\s*Fallaron:\s*(\d+)/u', $plain, $matched) === 1) {
            $balance = trim($matched[0]);
            $failures = (int) $matched[3];
        }

        if ($failures === null) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $plain)), static fn (string $l): bool => $l !== ''));
            $last = count($lines) > 0 ? (string) end($lines) : 'sin salida';
            return [
                'ran' => false,
                'reason' => 'no dice si pasó: no imprimió balance. Última línea: «' . mb_substr($last, 0, 90) . '»',
                'balance' => '',
                'failures' => 0,
                'exit' => $status,
            ];
        }

        return [
            'ran' => true,
            'reason' => '',
            'balance' => $balance,
            'failures' => $failures,
            'exit' => $status,
        ];
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new GatesTask($startRoute, $namePrefix);
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
