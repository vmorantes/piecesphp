<?php

/**
 * GatesTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\LoadFailures;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Distribution;
use Terminal\TestLeftovers;

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
        'unit-tests:core/logs-viewer' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/actions-log-organization' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/user-sessions' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/logs-hierarchy' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/soft-errors' => [self::PERMISSIONS_MATRIX],
        'unit-tests:core/app-key-crypto' => [self::PERMISSIONS_MATRIX],
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
    /**
     * Las carpetas de módulo (ADR 0062 §2): un cambio dentro de una solo elige las suites de su área. Lista POSITIVA: lo
     * que no está aquí es núcleo y fuerza todas, también un módulo nuevo hasta que alguien lo añada.
     */
    const AREA_MODULES = [
        'src/app/classes/API/',
        'src/app/classes/DataImportExportUtility/',
        'src/app/classes/Directory/',
        'src/app/classes/Documents/',
        'src/app/classes/EventsLog/',
        'src/app/classes/Forms/',
        'src/app/classes/MySpace/',
        'src/app/classes/News/',
        'src/app/classes/Newsletter/',
        'src/app/classes/Organizations/',
        'src/app/classes/Publications/',
        'src/app/classes/ReportsManage/',
        'src/app/classes/SystemApprovals/',
        'src/app/classes/PiecesPHP/BuiltIn/',
        'src/app/classes/PiecesPHP/FileManager/',
        'src/app/classes/PiecesPHP/GoogleReCaptchaV3/',
        'src/app/classes/PiecesPHP/LocalizationSystem/',
        'src/app/classes/PiecesPHP/Logs/',
        'src/app/classes/PiecesPHP/Settings/',
        'src/app/classes/PiecesPHP/SystemStatus/',
        'src/app/classes/PiecesPHP/Tokens/',
    ];

    /** «@areas nucleo»: la suite depende solo del núcleo y no corre con un cambio solo de módulos (ADR 0066). */
    const AREA_CORE = 'nucleo';

    /** La documentación no elige ninguna suite (ADR 0062 §2), y tampoco un `.md` de la raíz. */
    const AREA_DOCUMENTATION = ['.agents/', '.claude/', 'source-docs/'];

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
            "\t  areas changed-since=<commit>  no corre nada: imprime un JSON con el núcleo tocado, los módulos tocados y el módulo de cada ruta\r\n",
            "\t    unchanged=<json>  (con areas) {archivo: hash de git hash-object o null}: lo que sigue igual no cuenta como cambio\r\n",
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
        //`areas` no corre ninguna suite ni escribe: su salida es SOLO el JSON, y por eso va antes que nada.
        $arguments = \PiecesPHP\TerminalData::instance()->arguments();
        if (is_array($arguments) && array_key_exists('areas', $arguments)) {
            exit(self::printAreas());
        }

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
        $suiteAreas = [];
        $suiteFiles = [];
        $repoRoot = rtrim(str_replace('\\', '/', basepath('..')), '/');
        foreach (CliActions::listActionNames() as $name) {
            $action = CliActions::get($name);
            if ($action === null || !str_starts_with($action->definedIn(), $suitesDirectory)) {
                continue;
            }
            if ($only !== '' && mb_strpos($name, $only) === false) {
                continue;
            }
            $suites[] = $name;
            $suiteAreas[$name] = self::areasOf($action->definedIn());
            $suiteFiles[$name] = ltrim(str_replace($repoRoot, '', str_replace('\\', '/', $action->definedIn())), '/');
        }
        sort($suites);

        //─── SELECCIÓN POR ÁREA (ADR 0062): solo con changed-since; sin él, gates es el de siempre ───
        $changedSince = \PiecesPHP\TerminalData::instance()->getArgument('changed-since', '');
        $changedSince = is_string($changedSince) ? trim($changedSince) : '';
        if ($changedSince !== '') {
            $changed = self::changedFiles($repoRoot, $changedSince);
            if ($changed === null) {
                echoTerminal("\e[31mERROR:\e[39m changed-since=" . $changedSince . ' no es un commit de este repositorio: no se elige «todas» ni'
                    . ' «ninguna» en silencio.');
                exit(1);
            }
            $changedArtifacts = array_values(array_filter($changed['files'], [self::class, 'isInstrumentArtifact']));
            $reads = self::suiteArtifactReads($suiteFiles, $repoRoot, $changedArtifacts);
            $selected = self::selectByArea($suiteAreas, $suiteFiles, $changed['files'], $reads);
            $byReason = ['area' => 0, 'sin-area' => 0, 'su-archivo' => 0, 'nucleo' => 0, 'artefacto' => 0];
            foreach ($selected as $reason) {
                $byReason[str_starts_with($reason, 'artefacto ') ? 'artefacto' : $reason]++;
            }
            $forcing = self::firstCoreChange($suiteFiles, $changed['files']);
            echoTerminal('   SELECCIÓN POR ÁREA desde ' . $changed['short'] . ': ' . count($changed['files']) . ' archivo(s) cambiado(s); corren '
                . count($selected) . ' (área ' . $byReason['area'] . ', sin área ' . $byReason['sin-area'] . ', su archivo '
                . $byReason['su-archivo'] . ', artefacto ' . $byReason['artefacto'] . ', núcleo ' . $byReason['nucleo'] . '); fuera '
                . (count($suites) - count($selected))
                . ($forcing !== null ? '. Las fuerza todas: ' . $forcing . ' no es documentación ni de un módulo de la lista' : '') . '.');
            foreach ($selected as $suite => $reason) {
                if (str_starts_with($reason, 'artefacto ')) {
                    echoTerminal('      ' . $suite . ': ' . $reason);
                }
            }
            $suites = array_values(array_filter($suites, static fn (string $suite): bool => isset($selected[$suite])));
        }

        if (count($suites) === 0) {
            echoTerminal("\e[31mERROR:\e[39m ninguna suite declarada bajo " . self::SUITES_DIRECTORY . ".");
            exit(1);
        }

        //Con la base sembrada, core/seed-round-trip c3 y core/geo-viewer e0 caen sin defecto que arreglar: se para antes de
        //correr nada y se dice la receta (G18-9).
        if (array_intersect($suites, ['unit-tests:core/seed-round-trip', 'unit-tests:core/geo-viewer']) !== []) {
            $sembradas = array_sum(array_map('count', \Terminal\Seeding\SeedPlanner::seededIds(\Terminal\Seeding\SeedPlanner::order(\Terminal\Seeding\SeedPlanner::discover('all')))));
            $cargadas = GeoLoadFixtureTask::existingCount();
            if ($sembradas > 0 || $cargadas > 0) {
                echoTerminal("\e[31mBASE SEMBRADA:\e[39m aplica la receta 13 (.agents/context/13-recetas.md) antes de verificar"
                    . " ({$sembradas} fila(s) de la siembra y {$cargadas} de la carga del geovisor).");
                exit(1);
            }
        }

        $root = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $failed = [];

        //Cada suite corre en su proceso y hereda la corrida por el entorno: lo que escriban en el registro de errores
        //lleva su marca y se poda al terminar (ADR 0063).
        $testRun = GenericHandler::startTestRun();

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
            $startedAt = hrtime(true);
            $result = self::runSuite($root, $suite);
            $seconds = ' (' . number_format((hrtime(true) - $startedAt) / 1e9, 1, ',', '') . ' s)';

            if ($result['ran'] === false) {
                //Omitida y acabada-sin-decir-nada son indistinguibles desde fuera. Ver T74.
                $failed[] = $suite;
                echoTerminal("   \e[31m[SIN VEREDICTO]\e[39m {$label} {$result['reason']}{$seconds}");
                continue;
            }

            if ($result['failures'] > 0 || $result['exit'] !== 0) {
                $failed[] = $suite;
                echoTerminal("   \e[31m[FALLÓ]\e[39m        {$label} {$result['balance']}{$seconds}");
                //QUÉ CASO CAE: sin él, una intermitente no se puede atribuir (G18).
                foreach ($result['failedCases'] !== [] ? $result['failedCases'] : ["sin caso marcado: código de salida {$result['exit']}"] as $case) {
                    echoTerminal("                    {$case}");
                }
                continue;
            }

            echoTerminal("   \e[32m[PASÓ]\e[39m         {$label} {$result['balance']}{$seconds}");
        }

        //La última suite también se mide: su vuelta no tiene siguiente que lo haga.
        $medirRestos();

        //Se imprime SIEMPRE, también con cero (LEY 18). Una corrida abierta por otro (una gates dentro de otra) no se poda aquí.
        if ($testRun['created']) {
            $pruned = GenericHandler::pruneTestRun($testRun['run']);
            echoTerminal('');
            echoTerminal('   errores de prueba podados: ' . $pruned['entries'] . ' (trazas ' . $pruned['traces'] . ', contadores de suaves '
                . $pruned['soft'] . '; líneas del plano ' . $pruned['plain'] . ', bloques del único ' . $pruned['unique'] . ')'
                . ($pruned['error'] !== null ? " \e[31m— {$pruned['error']}\e[39m" : ''));
        }

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
     * Qué suites corren por lo que cambió (ADR 0062 §2 y 0065), sin tocar git ni la base. En este orden: si algún cambio
     * no es documentación, ni cae en una carpeta de AREA_MODULES, ni es el archivo de una suite, ni es un artefacto de
     * instrumento, todas («nucleo»); si no, las cuyo archivo cambió («su-archivo»), las que no declaran área
     * («sin-area»), las que tienen un área que casa («area»: una carpeta, terminada en `/`, casa por prefijo; un archivo,
     * por igualdad; «nucleo» no casa con ningún cambio de módulo, ADR 0066) y las que leen un artefacto cambiado («artefacto <archivo>»): lo nombran, nombran files/dev sin
     * nombre de archivo y el cambio es de files/dev/, o lo declaran en SUITE_DISTRIBUTION_REQUIREMENTS. Lo que lee cada
     * suite llega calculado (suiteArtifactReads()), para que esto siga sin leer disco.
     *
     * @param array<string,string[]> $suiteAreas Suite => sus áreas (vacía: sin @areas)
     * @param array<string,string> $suiteFiles Suite => su archivo, relativo al repositorio
     * @param string[] $changedFiles Relativos al repositorio
     * @param array<string,array{names:string[],devUnnamed:bool}> $suiteArtifactReads Suite => los nombres de artefacto
     *        que su archivo cita y si cita files/dev sin ninguno; la que falta no lee ninguno
     * @return array<string,string> Suite => motivo, solo las que corren
     */
    public static function selectByArea(array $suiteAreas, array $suiteFiles, array $changedFiles, array $suiteArtifactReads = []): array
    {
        $selected = [];
        if (self::firstCoreChange($suiteFiles, $changedFiles) !== null) {
            foreach (array_keys($suiteAreas) as $suite) {
                $selected[$suite] = 'nucleo';
            }
            return $selected;
        }
        foreach ($suiteAreas as $suite => $areas) {
            if (in_array($suiteFiles[$suite] ?? null, $changedFiles, true)) {
                $selected[$suite] = 'su-archivo';
                continue;
            }
            if ($areas === []) {
                $selected[$suite] = 'sin-area';
                continue;
            }
            foreach (array_diff($areas, [self::AREA_CORE]) as $area) {
                foreach ($changedFiles as $file) {
                    if (str_ends_with($area, '/') ? str_starts_with($file, $area) : $file === $area) {
                        $selected[$suite] = 'area';
                        continue 3;
                    }
                }
            }
            $reads = $suiteArtifactReads[$suite] ?? ['names' => [], 'devUnnamed' => false];
            foreach ($changedFiles as $file) {
                if (!self::isInstrumentArtifact($file)) {
                    continue;
                }
                if (in_array(basename($file), $reads['names'], true)
                    || ($reads['devUnnamed'] && str_starts_with($file, 'files/dev/'))
                    || in_array($file, self::SUITE_DISTRIBUTION_REQUIREMENTS[$suite] ?? [], true)) {
                    $selected[$suite] = 'artefacto ' . $file;
                    continue 2;
                }
            }
        }
        return $selected;
    }

    /**
     * Un artefacto de instrumento (ADR 0065): todo lo de files/dev/ y los PHPStanResult.* de la raíz. Una sola definición
     * para las suites (selectByArea()) y para las capturas (pageAffectingFiles()).
     *
     * @param string $file Relativo al repositorio
     * @return bool
     */
    public static function isInstrumentArtifact(string $file): bool
    {
        return str_starts_with($file, 'files/dev/') || (!str_contains($file, '/') && str_starts_with($file, 'PHPStanResult.'));
    }

    /**
     * Qué artefactos lee cada suite, leyendo su archivo: los nombres base que cita literalmente (de los artefactos que
     * existen y de los que cambiaron), y si cita files/dev sin ninguno de los suyos. Un nombre armado en tiempo de
     * ejecución no se ve (ADR 0065, riesgo aceptado); la verificación entera lo cubre.
     *
     * @param array<string,string> $suiteFiles Suite => su archivo, relativo al repositorio
     * @param string $repoRoot
     * @param string[] $changedArtifacts Relativos al repositorio
     * @return array<string,array{names:string[],devUnnamed:bool}>
     */
    public static function suiteArtifactReads(array $suiteFiles, string $repoRoot, array $changedArtifacts = []): array
    {
        $devNames = [];
        $rootNames = [];
        $devFolder = $repoRoot . '/files/dev';
        if (is_dir($devFolder)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($devFolder, \FilesystemIterator::SKIP_DOTS)) as $entry) {
                if ($entry instanceof \SplFileInfo && $entry->isFile() && !str_starts_with($entry->getFilename(), '.')) {
                    $devNames[] = $entry->getFilename();
                }
            }
        }
        foreach (glob($repoRoot . '/PHPStanResult.*') ?: [] as $path) {
            $rootNames[] = basename($path);
        }
        foreach ($changedArtifacts as $file) {
            if (str_starts_with($file, 'files/dev/')) {
                $devNames[] = basename($file);
            } else {
                $rootNames[] = basename($file);
            }
        }
        $devNames = array_values(array_unique($devNames));
        $names = array_values(array_unique([...$devNames, ...$rootNames]));
        $reads = [];
        foreach ($suiteFiles as $suite => $file) {
            $content = (string) @file_get_contents($repoRoot . '/' . $file);
            $cited = array_values(array_filter($names, static fn (string $name): bool => str_contains($content, $name)));
            $citesDevName = count(array_intersect($cited, $devNames)) > 0;
            $reads[$suite] = ['names' => $cited, 'devUnnamed' => str_contains($content, 'files/dev') && !$citesDevName];
        }
        return $reads;
    }

    /**
     * El primer cambio que fuerza todas las suites: ni documentación, ni de un módulo de la lista, ni el archivo de una
     * suite, ni un artefacto de instrumento (ADR 0065). Null si no hay ninguno.
     *
     * @param array<string,string> $suiteFiles
     * @param string[] $changedFiles
     * @return string|null
     */
    public static function firstCoreChange(array $suiteFiles, array $changedFiles): ?string
    {
        foreach ($changedFiles as $file) {
            $isDocumentation = (!str_contains($file, '/') && str_ends_with(strtolower($file), '.md'))
                || count(array_filter(self::AREA_DOCUMENTATION, static fn (string $folder): bool => str_starts_with($file, $folder))) > 0;
            $inModule = count(array_filter(self::AREA_MODULES, static fn (string $folder): bool => str_starts_with($file, $folder))) > 0;
            if (!$isDocumentation && !$inModule && !in_array($file, $suiteFiles, true) && !self::isInstrumentArtifact($file)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Aparta los artefactos de instrumentos (isInstrumentArtifact()), que no pueden cambiar una pantalla. Lo usa
     * `gates areas` (las capturas de bin/navegador): cambian en cada ronda que verifica y forzaban a recapturarlo todo.
     * La selección de suites no lo usa: allí un artefacto elige las suites que lo leen (selectByArea(), ADR 0065).
     *
     * @param string[] $files Relativos al repositorio
     * @return array{kept:string[],ignored:string[]}
     */
    public static function pageAffectingFiles(array $files): array
    {
        $kept = [];
        $ignored = [];
        foreach ($files as $file) {
            if (self::isInstrumentArtifact($file)) {
                $ignored[] = $file;
            } else {
                $kept[] = $file;
            }
        }
        return ['kept' => $kept, 'ignored' => $ignored];
    }

    /**
     * Qué tocó un cambio: el primer archivo de núcleo (ni documentación, ni de un módulo, ni de una suite) y las carpetas
     * de AREA_MODULES con algún archivo cambiado.
     *
     * @param string[] $changedFiles Relativos al repositorio
     * @param string[] $suiteFiles Los archivos de las suites, relativos al repositorio
     * @return array{core:?string,modules:string[]}
     */
    public static function areasFor(array $changedFiles, array $suiteFiles = []): array
    {
        $modules = array_values(array_filter(self::AREA_MODULES, static function (string $folder) use ($changedFiles): bool {
            return count(array_filter($changedFiles, static fn (string $file): bool => str_starts_with($file, $folder))) > 0;
        }));
        return ['core' => self::firstCoreChange(array_combine($suiteFiles, $suiteFiles) ?: [], $changedFiles), 'modules' => $modules];
    }

    /**
     * La carpeta de AREA_MODULES de cada ruta, por el archivo de la clase de su controlador. Null si no tiene controlador
     * de clase o si su archivo no cae en ninguna.
     *
     * @param array<string|int,array<string,mixed>> $routes Las de get_routes()
     * @param string $repoRoot
     * @return array<string,?string> Nombre de ruta => carpeta
     */
    public static function routeModules(array $routes, string $repoRoot): array
    {
        $modules = [];
        foreach ($routes as $route) {
            $name = $route['name'] ?? null;
            if (!is_string($name)) {
                continue;
            }
            $controller = $route['controller'] ?? null;
            $class = is_string($controller) ? explode(':', $controller)[0] : '';
            $file = $class !== '' && class_exists($class) ? (new \ReflectionClass($class))->getFileName() : false;
            $relative = is_string($file) ? ltrim(str_replace($repoRoot, '', str_replace('\\', '/', $file)), '/') : '';
            $folder = null;
            foreach (self::AREA_MODULES as $module) {
                if ($relative !== '' && str_starts_with($relative, $module)) {
                    $folder = $module;
                    break;
                }
            }
            $modules[$name] = $folder;
        }
        ksort($modules);
        return $modules;
    }

    /**
     * `gates areas changed-since=<commit>`: el JSON para quien recaptura solo lo tocado (bin/navegador).
     *
     * @return int El código de salida
     */
    protected static function printAreas(): int
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $since = \PiecesPHP\TerminalData::instance()->getArgument('changed-since', '');
        $since = is_string($since) ? trim($since) : '';
        $changed = $since !== '' ? self::changedFiles($repoRoot, $since) : null;
        if ($changed === null) {
            //RETORNO-IGNORADO: el error va a la salida de error y el código de salida es el que cuenta.
            fwrite(STDERR, 'ERROR: gates areas pide changed-since=<commit> de este repositorio' . ($since !== '' ? ', y ' . $since . ' no lo es' : '') . ".\n");
            return 1;
        }
        $suitesDirectory = rtrim(str_replace('\\', '/', basepath('')), '/') . '/' . self::SUITES_DIRECTORY . '/';
        $suiteFiles = [];
        foreach (CliActions::listActionNames() as $name) {
            $action = CliActions::get($name);
            if ($action !== null && str_starts_with($action->definedIn(), $suitesDirectory)) {
                $suiteFiles[] = ltrim(str_replace($repoRoot, '', str_replace('\\', '/', $action->definedIn())), '/');
            }
        }
        //Lo que ya estaba sucio al capturar y no ha cambiado desde entonces no es un cambio (bin/navegador lo guarda).
        $unchangedFile = \PiecesPHP\TerminalData::instance()->getArgument('unchanged', '');
        if (is_string($unchangedFile) && $unchangedFile !== '') {
            $unchanged = json_decode((string) @file_get_contents($unchangedFile), true);
            if (!is_array($unchanged)) {
                //RETORNO-IGNORADO: el error va a la salida de error y el código de salida es el que cuenta.
                fwrite(STDERR, 'ERROR: unchanged=' . $unchangedFile . " no es un JSON {archivo: hash}.\n");
                return 1;
            }
            $changed['files'] = array_values(array_filter($changed['files'], static fn (string $file): bool => !array_key_exists($file, $unchanged) || self::blobHash($repoRoot, $file) !== $unchanged[$file]));
        }
        $pageFiles = self::pageAffectingFiles($changed['files']);
        $areas = self::areasFor($pageFiles['kept'], $suiteFiles);
        $routes = get_routes();
        echo json_encode([
            'since' => $changed['short'],
            'core' => $areas['core'],
            'modules' => $areas['modules'],
            'routes' => self::routeModules(is_array($routes) ? $routes : [], $repoRoot),
            'ignored' => $pageFiles['ignored'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        return 0;
    }

    /**
     * El hash de git (sin escribir nada en el repositorio) del contenido actual de un archivo; null si no existe.
     *
     * @param string $repoRoot
     * @param string $file Relativo al repositorio
     * @return string|null
     */
    public static function blobHash(string $repoRoot, string $file): ?string
    {
        if (!is_file($repoRoot . '/' . $file)) {
            return null;
        }
        $output = [];
        //RETORNO-IGNORADO: lo que cuenta es la primera línea, y se comprueba.
        exec('git -C ' . escapeshellarg($repoRoot) . ' hash-object -- ' . escapeshellarg($file) . ' 2>/dev/null', $output);
        return isset($output[0]) ? trim($output[0]) : null;
    }

    /**
     * Las áreas que declara una suite: las líneas `//@areas <ruta>` de su cabecera, antes de CliActions::make.
     *
     * @param string $file La ruta absoluta del archivo de la suite
     * @return string[]
     */
    public static function areasOf(string $file): array
    {
        $source = (string) @file_get_contents($file);
        $head = strstr($source, 'CliActions::make', true);
        $head = $head !== false ? $head : $source;
        //RETORNO-IGNORADO: lo que cuenta son las coincidencias; sin ninguna, la suite no declara área.
        preg_match_all('/^\s*\/\/@areas\s+(\S+)\s*$/m', $head, $matches);
        return array_values(array_unique($matches[1]));
    }

    /**
     * Lo que cambió desde un commit: lo commiteado después (contra el árbol) y lo nuevo sin seguimiento. Null si el commit
     * no existe.
     *
     * @param string $repoRoot
     * @param string $commit
     * @return array{short:string,files:string[]}|null
     */
    protected static function changedFiles(string $repoRoot, string $commit): ?array
    {
        $git = 'git -C ' . escapeshellarg($repoRoot) . ' ';
        $output = [];
        $status = 0;
        //RETORNO-IGNORADO: lo que cuenta es el código de salida y la primera línea, y se comprueban.
        exec($git . 'rev-parse --verify --quiet ' . escapeshellarg($commit . '^{commit}') . ' 2>/dev/null', $output, $status);
        if ($status !== 0 || !isset($output[0])) {
            return null;
        }
        $hash = trim($output[0]);
        $diff = [];
        //RETORNO-IGNORADO: lo que cuenta es el código de salida y las líneas, y se comprueban.
        exec($git . 'diff --name-only ' . escapeshellarg($hash) . ' 2>/dev/null', $diff, $status);
        if ($status !== 0) {
            return null;
        }
        $untracked = [];
        //RETORNO-IGNORADO: las líneas son lo que cuenta; sin ninguna, no hay archivos nuevos.
        exec($git . 'ls-files --others --exclude-standard 2>/dev/null', $untracked);
        $files = array_values(array_unique(array_filter(array_map('trim', array_merge($diff, $untracked)), static fn (string $f): bool => $f !== '')));
        sort($files);
        return ['short' => substr($hash, 0, 8), 'files' => $files];
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
     * @return array{ran: bool, reason: string, balance: string, failures: int, exit: int, failedCases: string[]}
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
        $parsed = self::parseBalance($plain);
        $failures = $parsed['failures'] ?? null;
        $balance = $parsed['balance'] ?? '';

        if ($failures === null) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $plain)), static fn (string $l): bool => $l !== ''));
            $last = count($lines) > 0 ? (string) end($lines) : 'sin salida';
            return [
                'ran' => false,
                'reason' => 'no dice si pasó: no imprimió balance. Última línea: «' . mb_substr($last, 0, 90) . '»',
                'balance' => '',
                'failures' => 0,
                'exit' => $status,
                'failedCases' => [],
            ];
        }

        return [
            'ran' => true,
            'reason' => '',
            'balance' => $balance,
            'failures' => $failures,
            'exit' => $status,
            'failedCases' => self::failedCases($plain),
        ];
    }

    /**
     * El balance de la salida plana de una suite: la frase entera, con sus «, N FALLIDAS» y «, N OMITIDAS», y los fallos,
     * que salen de N/M (o de «Fallaron»), no de esas coletillas. Null si no imprimió balance.
     *
     * @param string $plain
     * @return array{balance:string,failures:int}|null
     */
    public static function parseBalance(string $plain): ?array
    {
        if (preg_match('/BALANCE FINAL:\s*(\d+)\/(\d+)\s*PASADAS(?:,\s*\d+\s*(?:FALLIDAS|OMITIDAS))*/u', $plain, $matched) === 1) {
            return ['balance' => trim($matched[0]), 'failures' => (int) $matched[2] - (int) $matched[1]];
        }
        if (preg_match('/Total:\s*(\d+)\s*\|\s*Pasaron:\s*(\d+)\s*\|\s*Fallaron:\s*(\d+)/u', $plain, $matched) === 1) {
            return ['balance' => trim($matched[0]), 'failures' => (int) $matched[3]];
        }
        return null;
    }

    /**
     * Los casos que cayeron, de la salida plana (sin colores) de una suite: cada línea con «[FALLÓ]» y, si la sigue, su
     * línea de detalle («- …»), en orden. Como mucho $max casos; si hay más, una última línea «… y N más».
     *
     * @param string $plain
     * @param int $max
     * @return string[]
     */
    public static function failedCases(string $plain, int $max = 10): array
    {
        $lines = explode("\n", str_replace("\r", '', $plain));
        $cases = [];
        $count = 0;
        foreach ($lines as $index => $line) {
            if (!str_contains($line, '[FALLÓ]')) {
                continue;
            }
            $count++;
            if ($count > $max) {
                continue;
            }
            $cases[] = trim($line);
            $next = trim($lines[$index + 1] ?? '');
            if (str_starts_with($next, '- ')) {
                $cases[] = '  ' . $next;
            }
        }
        if ($count > $max) {
            $cases[] = '… y ' . ($count - $max) . ' más';
        }
        return $cases;
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
