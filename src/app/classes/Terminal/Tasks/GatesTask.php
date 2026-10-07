<?php

/**
 * GatesTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Terminal\CliActions;
use Terminal\Distribution;
use Terminal\TestLeftovers;
use PiecesPHP\Terminal\LoadFailures;
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
 *   - QUÉ SUITES HAY: toda acción declarada bajo `local-tests/` (ver el comentario de la enumeración, más abajo). Una suite nueva entra sola. Hasta el 2026-08-26
 *     fue un prefijo, `unit-tests:core/`, que dejó fuera `functions/systemOutFormatted`. Ver bloque S.
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

    /** Directorio donde viven las suites. QUÉ SUITES HAY sale de aquí, no de una lista. */
    const SUITES_DIRECTORY = 'app/core/system-controllers/local-tests';

    /**
     * Las suites que necesitan algo que NO viaja (ADR 0049), relativo a la raíz. En la distribución, sin eso, dicen
     * `[NO APLICA EN LA DISTRIBUCIÓN]` y no corren; aquí corren y lo que falte es su fallo. Una suite así se añade aquí.
     */
    const SUITE_DISTRIBUTION_REQUIREMENTS = [
        'unit-tests:core/access-without-session' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/backups-screen' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/cors' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/http-client' => ['bin/tools/sumidero-http/router.php'],
        'unit-tests:core/injected-scripts' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/mail-log-screen' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/maintenance-mode' => ['files/dev/route-inventory.json'],
        'unit-tests:core/publications-true-url' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/approval-forms' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/publications-incomplete' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/banners-incomplete' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/login-attempts-organization' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/news-incomplete' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/organizations-incomplete' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/forms-incomplete' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/publications-without-organization' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/users-post-authority' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/other-doors' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/restricted-users' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/auto-approval-status' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/my-organization-profile' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/public-menu-categories' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/route-inventory' => ['files/dev/route-inventory.json'],
        'unit-tests:core/seo-metadata' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/session-revocation' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/site-files' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/sitemap' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/sql-placeholders' => ['bin/censo-sql-concatenado', 'files/dev/sql-concat-declared.json'],
        'unit-tests:core/static-cache' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/static-versions' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/verify-starts-clean' => [TestLeftovers::BASELINE_FILE],
    ];

    /** De dónde sacan la base HTTP las suites que piden por la red, y la variable que la sustituye. */
    const PERMISSIONS_MATRIX = 'files/dev/permissions-matrix.json';
    const BASE_URL_VARIABLE = 'PCSPHP_WALK_BASE';

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

        //LO PRIMERO, antes de leer ningún argumento: las suites escriben en la base de ESTA instalación.
        $entorno = self::localOnly();
        echoTerminal('   ' . $entorno['line']);
        if (!$entorno['ok']) {
            echoTerminal("\e[31m*** {$titleTask}, NO SE CORRE NINGUNA SUITE ***\e[39m");
            exit(2);
        }

        $only = \PiecesPHP\TerminalData::instance()->getArgument('only', '');
        $only = is_string($only) ? trim($only) : '';

        $withExternal = \PiecesPHP\TerminalData::instance()->getArgument('with', '') === 'external';

        //Reescribir la línea base vuelve «limpia» una base sucia de un plumazo: pide motivo y lo grita.
        $leftovers = \PiecesPHP\TerminalData::instance()->getArgument('leftovers', '');
        if (is_string($leftovers) && $leftovers === 'rebase') {
            $motivo = \PiecesPHP\TerminalData::instance()->getArgument('motivo', '');
            $motivo = is_string($motivo) ? trim($motivo) : '';
            if ($motivo === '') {
                echoTerminal("\e[31mERROR:\e[39m reescribir la línea base de los restos exige decir por qué:"
                    . ' bin/cli gates leftovers=rebase motivo="..."');
                exit(1);
            }
            $antesDeRebase = TestLeftovers::sweep();
            $comparacion = TestLeftovers::compareToBaseline($antesDeRebase['pares']);
            echoTerminal("\e[33mATENCIÓN: se está reescribiendo la línea base de los restos de prueba.\e[39m");
            echoTerminal('   Lo que había de más: ' . (count($comparacion['sobran']) > 0 ? TestLeftovers::describe($comparacion['sobran']) : 'nada'));
            echoTerminal('   Lo que había de menos: ' . (count($comparacion['faltan']) > 0 ? TestLeftovers::describe($comparacion['faltan']) : 'nada'));
            echoTerminal('   Motivo: ' . $motivo);
            if (!TestLeftovers::rebaseline($motivo)) {
                echoTerminal("\e[31mERROR:\e[39m no se pudo escribir " . TestLeftovers::BASELINE_FILE);
                exit(1);
            }
            echoTerminal("\e[32mLínea base reescrita:\e[39m " . TestLeftovers::BASELINE_FILE . '. El diff de git la enseña.');
            exit(0);
        }

        //UNA SUITE ES UNA ACCIÓN DECLARADA BAJO `local-tests/`. Nada de prefijos: el prefijo
        //ERA una lista de un elemento y se quedó corta —dejaba fuera `functions/` y `tests:`—.
        $suitesDirectory = rtrim(str_replace('\\', '/', basepath('')), '/') . '/' . self::SUITES_DIRECTORY . '/';
        $suites = [];
        foreach (CliActions::listActionNames() as $name) {
            $action = CliActions::get($name);
            if ($action === null || !str_starts_with($action->definedIn(), $suitesDirectory)) {
                continue;
            }
            if ($only !== '' && mb_strpos($name, $only) === false) {
                continue;
            }
            $suites[] = $name;
        }
        sort($suites);

        if (count($suites) === 0) {
            echoTerminal("\e[31mERROR:\e[39m ninguna suite declarada bajo " . self::SUITES_DIRECTORY . ".");
            exit(1);
        }

        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $failed = [];

        $skipped = [];
        $notApplicable = [];

        //UNA SUITE QUE NO CARGÓ NO REGISTRÓ SU ACCIÓN: la enumeración de arriba no la ve. Sin esto
        //desaparecería en silencio y el total saldría verde. Cuenta como fallo, nunca como omitida.
        foreach (LoadFailures::all(LoadFailures::TYPE_SUITE) as $loadFailure) {
            $failed[] = $loadFailure['file'];
            echoTerminal("   \e[31m[NO CARGÓ]\e[39m     " . LoadFailures::describe($loadFailure));
        }

        //Se imprime SIEMPRE: sin esta línea, «no había restos» y «no se miró» se leen igual (LEY 18).
        //Y no limpia nada, porque la base no es nuestra para vaciarla (ADR 0047 §1).
        $partida = TestLeftovers::sweep();
        $baseDeclarada = TestLeftovers::baseline();
        $comparacionInicial = TestLeftovers::compareToBaseline($partida['pares']);
        //En la distribución la línea base de restos no viaja: sin ella no hay con qué comparar, y no es culpa del clon.
        $partidaNoAplica = $partida['error'] === null && !$baseDeclarada['existe'] && Distribution::isDistribution();
        $partidaSucia = !$partidaNoAplica && (!$comparacionInicial['limpio'] || $partida['error'] !== null || !$baseDeclarada['existe']);

        echoTerminal('');
        echoTerminal('   RESTOS DE PRUEBA: ' . $partida['columnas'] . ' columna(s) de texto de '
            . $partida['tablas'] . ' tabla(s) barridas buscando «' . TestLeftovers::MARK . '» y «' . TestLeftovers::MARK_UNDERSCORE . '», '
            . $partida['omitidas'] . ' omitida(s) por poder guardar un secreto. No ve un registro sin la marca'
            . ', ni nada fuera de la base.');

        if ($partida['error'] !== null) {
            echoTerminal("   \e[31mPUNTO DE PARTIDA: SUCIO\e[39m — no se pudo contar: " . $partida['error']);
        } elseif ($partidaNoAplica) {
            echoTerminal("   \e[33m" . Distribution::notApplicableLine('punto de partida', [TestLeftovers::BASELINE_FILE]) . "\e[39m");
        } elseif (!$baseDeclarada['existe']) {
            echoTerminal("   \e[31mPUNTO DE PARTIDA: SUCIO\e[39m — falta " . TestLeftovers::BASELINE_FILE
                . ', así que no hay con qué comparar. Se crea con: bin/cli gates leftovers=rebase motivo="..."');
        } elseif ($comparacionInicial['limpio']) {
            echoTerminal("   \e[32mPUNTO DE PARTIDA: LIMPIO\e[39m — los restos son exactamente los de la línea base. Esta corrida SÍ vale para avalar una etiqueta.");
        } else {
            echoTerminal("   \e[31mPUNTO DE PARTIDA: SUCIO\e[39m — esta corrida NO vale para avalar una etiqueta.");
            if (count($comparacionInicial['sobran']) > 0) {
                echoTerminal('      sobran:  ' . TestLeftovers::describe($comparacionInicial['sobran']));
            }
            if (count($comparacionInicial['faltan']) > 0) {
                echoTerminal('      faltan:  ' . TestLeftovers::describe($comparacionInicial['faltan'])
                    . '  (algo se llevó parte del banco de pruebas)');
            }
        }
        echoTerminal('');

        $restosPrevios = $partida['pares'];
        $culpables = [];

        $ultimaCorrida = null;
        $medirRestos = function () use (&$restosPrevios, &$culpables, &$ultimaCorrida): void {
            if ($ultimaCorrida === null) {
                return;
            }
            $ahora = TestLeftovers::sweep();
            $crecio = TestLeftovers::growth($restosPrevios, $ahora['pares']);
            if (count($crecio) > 0) {
                $culpables[$ultimaCorrida] = $crecio;
            }
            $restosPrevios = $ahora['pares'];
            $ultimaCorrida = null;
        };

        foreach ($suites as $suite) {
            //Se mide lo que dejó la vuelta ANTERIOR, cualquiera que fuese su rama: así ninguna
            //suite se queda sin medir por haber salido por un `continue`.
            $medirRestos();
            $short = str_replace('unit-tests:', '', $suite);
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

            if (Distribution::isDistribution()) {
                $faltan = Distribution::missing(self::SUITE_DISTRIBUTION_REQUIREMENTS[$suite] ?? []);
                //La matriz solo les da la base HTTP: con PCSPHP_WALK_BASE no la necesitan.
                if ((string) getenv(self::BASE_URL_VARIABLE) !== '') {
                    $faltan = array_values(array_diff($faltan, [self::PERMISSIONS_MATRIX]));
                }
                if (count($faltan) > 0) {
                    $notApplicable[] = $suite;
                    echoTerminal("   \e[33m" . Distribution::notApplicableLine($short, $faltan) . "\e[39m");
                    continue;
                }
            }

            $ultimaCorrida = $short;
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

        //La última suite también se mide: su vuelta no tiene siguiente que lo haga.
        $medirRestos();

        echoTerminal('');
        echoTerminal('   ' . (count($suites) + count(LoadFailures::all(LoadFailures::TYPE_SUITE))) . ' suite(s), ' . count($failed) . ' sin veredicto o con fallos, '
            . count($skipped) . ' no corridas por declarar efectos externos'
            . (count($notApplicable) > 0 ? ', ' . count($notApplicable) . ' que no aplican en la distribución.' : '.'));

        //─── QUIÉN DEJÓ ALGO (ADR 0047 §4) ──────────────────────────────────────────────────────
        if (count($culpables) > 0) {
            echoTerminal('');
            echoTerminal("   \e[31mRESTOS NUEVOS: " . count($culpables) . " suite(s) dejaron algo suyo en la base.\e[39m");
            foreach ($culpables as $suiteCulpable => $crecio) {
                echoTerminal("      \e[31m{$suiteCulpable}\e[39m — " . TestLeftovers::describe($crecio));
                $failed[] = $suiteCulpable;
            }
            //Aproximada mientras las 30 suites que no declaran sus restos no lo hagan, y se dice.
            echoTerminal('      La atribución es por ORDEN DE EJECUCIÓN, aproximada: se nombra a la suite que corrió'
                . ' justo antes de que la cuenta subiera. Será exacta cuando todas declaren sus restos al terminar.');
        }

        //Repetida aquí, pegada al veredicto: es la línea que `bin/verify` lee para calificar la suya.
        echoTerminal('');
        if ($partidaNoAplica) {
            echoTerminal("   \e[33mPUNTO DE PARTIDA: NO APLICA EN LA DISTRIBUCIÓN\e[39m — sin la línea base de restos, que no viaja.");
        } else {
            echoTerminal($partidaSucia
                ? "   \e[31mPUNTO DE PARTIDA: SUCIO\e[39m — esta corrida NO vale para avalar una etiqueta."
                : "   \e[32mPUNTO DE PARTIDA: LIMPIO\e[39m — esta corrida SÍ vale para avalar una etiqueta.");
        }

        if (count($failed) > 0) {
            echoTerminal("\e[31m*** {$titleTask}, tarea finalizada CON FALLOS ***\e[39m");
            exit(1);
        }

        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Si esta instalación permite correr las suites, con la línea que lo dice.
     *
     * **Solo en `local`**, y sin interruptor para saltárselo: las suites crean y borran filas, cambian
     * la configuración del correo y escriben en disco, todo sobre la base de la instalación donde se
     * corren. `bin/cli` viaja a cada clon, así que un `bin/verify` lanzado en un servidor lo haría
     * sobre datos reales. Un «forzar» es justo lo que alguien escribe con prisa en el servidor
     * equivocado. **Sin `environment.php` cuenta como producción**: `AppEnvironment` ya falla cerrado.
     *
     * @return array{ok:bool,line:string}
     */
    public static function localOnly(): array
    {
        if (AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::LOCAL) {
            return ['ok' => true, 'line' => 'entorno: local. Las suites pueden escribir en la base de esta instalación.'];
        }
        $motivo = AppEnvironment::isConfigured()
            ? 'esta instalación se declara «' . AppEnvironment::get() . '»'
            : 'esta instalación no tiene ' . AppEnvironment::FILE_RELATIVE_PATH . ', así que cuenta como producción';
        return [
            'ok' => false,
            'line' => "\e[31mentorno: NO local\e[39m — {$motivo}. Las suites crean, cambian y borran datos en la base"
                . ' de la instalación donde corren, así que aquí no corre ninguna. En una instalación de DESARROLLO,'
                . ' declara `return \'local\';` en src/' . AppEnvironment::FILE_RELATIVE_PATH . '.',
        ];
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
