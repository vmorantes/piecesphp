<?php

//La pantalla del geovisor (C11, ronda 2): su ruta, lo que pinta la vista con las fuentes visibles, la tarjeta exigida
//al registrar y la carga de puntos de ida y vuelta. En proceso; la carga escribe zz-carga- y lo retira.
//@areas src/app/classes/Directory/
//@areas src/app/classes/Organizations/

use Directory\Geo\PersonsGeoSource;
use Organizations\Geo\OrganizationsGeoSource;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\Geo\Controllers\GeoController;
use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoPointsResult;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceRegistry;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\SeedPlanner;
use Terminal\Tasks\GeoLoadFixtureTask;

CliActions::make('unit-tests:core/geo-viewer', function ($args) {

    echoTerminal("\e[33m[TEST:GeoViewer] La pantalla del geovisor: ruta, vista, tarjetas y carga de puntos\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    //Una fuente falsa: clave, ruta de permiso, filtros y vista de tarjeta a elección.
    $fuente = function (string $key, string $permiso, array $filtros, string $tarjeta): GeoSource {
        return new class($key, $permiso, $filtros, $tarjeta) implements GeoSource {
            public function __construct(private string $clave, private string $permiso, private array $filtros, private string $tarjeta)
            {
            }
            public function key(): string
            {
                return $this->clave;
            }
            public function title(): string
            {
                return "Título {$this->clave}";
            }
            public function permissionRoute(): string
            {
                return $this->permiso;
            }
            public function marker(): string
            {
                return GeoSource::MARKER_DOT;
            }
            public function colorVariable(): string
            {
                return '--zz-color';
            }
            public function filters(): array
            {
                return $this->filtros;
            }
            public function filterOptions(string $key, string $search, int $limit): array
            {
                return [];
            }
            public function points(GeoQuery $query): GeoPointsResult
            {
                return new GeoPointsResult([], 0);
            }
            public function pointDetail(int $id): ?array
            {
                return null;
            }
            public function thumbnailSource(int $id): ?string
            {
                return null;
            }
            public function cardView(): string
            {
                return $this->tarjeta;
            }
        };
    };
    $tarjetaPersonas = (new PersonsGeoSource())->cardView();
    $tarjetaOrganizaciones = (new OrganizationsGeoSource())->cardView();
    $pinta = function (array $fuentes): string {
        return (string) (new HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/Geo/Views/'))->render('viewer', GeoController::viewData($fuentes), false);
    };

    //──── [a] La ruta ─────────────────────────────────────────────────────────────────────
    echoTerminal('[a] La ruta de la pantalla');
    $rutas = get_routes();
    $pantalla = $rutas['geo-admin-viewer'] ?? null;
    $puntos = $rutas['geo-admin-points'] ?? null;
    $check(is_array($pantalla) && is_array($puntos), 'a1 existen geo-admin-viewer y geo-admin-points');
    $check(is_array($pantalla) && strtoupper((string) ($pantalla['method'] ?? '')) === 'GET' && ($pantalla['require_login'] ?? false) === true, 'a2 la pantalla es GET y exige sesión', (string) json_encode([$pantalla['method'] ?? null, $pantalla['require_login'] ?? null]));
    $rolesPantalla = is_array($pantalla) ? (array) ($pantalla['roles_allowed'] ?? []) : [];
    $rolesPuntos = is_array($puntos) ? (array) ($puntos['roles_allowed'] ?? []) : [];
    sort($rolesPantalla);
    sort($rolesPuntos);
    $check(count($rolesPuntos) === 5 && $rolesPantalla === $rolesPuntos, 'a3 sus roles son exactamente los cinco de geo-admin-points', (string) json_encode([$rolesPantalla, $rolesPuntos]));
    echoTerminal(' ');

    $originales = GeoSourceRegistry::swapForTesting();
    try {
        //──── [b] Lo que pinta la vista ───────────────────────────────────────────────────────
        echoTerminal('[b] La vista, con dos fuentes que declaran la misma clave de filtro');
        //La primera la ve también GENERAL (la ruta de la pantalla); la segunda, solo root.
        $a = $fuente('zz-geo-a', 'geo-admin-viewer', [
            new GeoFilter('zz-comun', 'zz-rotulo-primera', GeoFilter::TYPE_TEXT),
            new GeoFilter('zz-opciones', 'zz-rotulo-opciones', GeoFilter::TYPE_OPTIONS, [7 => '<b>zz-escapado</b>']),
        ], $tarjetaPersonas);
        $b = $fuente('zz-geo-b', 'admin-cropper-testing', [
            new GeoFilter('zz-comun', 'zz-rotulo-segunda', GeoFilter::TYPE_TEXT),
            new GeoFilter('zz-fechas', 'zz-rotulo-fechas', GeoFilter::TYPE_DATE_RANGE),
        ], $tarjetaOrganizaciones);
        GeoSourceRegistry::register($a);
        GeoSourceRegistry::register($b);
        $html = $pinta(GeoSourceRegistry::visibleFor(UsersModel::TYPE_USER_ROOT));
        $check(substr_count($html, 'data-geo-filter="zz-comun"') === 1, 'b1 un solo campo para la clave que declaran las dos', substr_count($html, 'data-geo-filter="zz-comun"') . ' campos');
        $check(str_contains($html, 'zz-rotulo-primera') && !str_contains($html, 'zz-rotulo-segunda'), 'b2 con el rótulo de la primera en el orden de registro');
        $check(str_contains($html, 'data-geo-filter="zz-opciones"') && str_contains($html, 'data-geo-filter="zz-fechas"') && substr_count($html, 'calendar-js') === 2, 'b3 los filtros de cada fuente: opciones de una y el rango de fechas, con sus dos calendarios, de la otra');
        $check(substr_count($html, '<template data-geo-card=') === 2 && str_contains($html, '<template data-geo-card="zz-geo-a">') && str_contains($html, '<template data-geo-card="zz-geo-b">'), 'b4 una plantilla de tarjeta por fuente', substr_count($html, '<template data-geo-card=') . ' plantillas');
        $check(str_contains($html, 'data-geo-field="jobPosition"') && str_contains($html, 'data-geo-href="url"'), 'b5 la plantilla lleva la vista de su fuente, con sus data-geo-field y su enlace');
        $check(substr_count($html, 'data-geo-source-toggle=') === 2 && str_contains($html, 'Título zz-geo-a') && str_contains($html, 'Título zz-geo-b'), 'b6 la leyenda, con las dos');
        $check(str_contains($html, '<template data-geo-marker="dot">') && str_contains($html, '<template data-geo-marker="avatar">'), 'b7 las dos plantillas de marcador del geovisor');
        $soloUna = $pinta(GeoSourceRegistry::visibleFor(UsersModel::TYPE_USER_GENERAL));
        $check(substr_count($soloUna, '<template data-geo-card=') === 1 && str_contains($soloUna, 'data-geo-source-toggle="zz-geo-a"') && !str_contains($soloUna, 'zz-geo-b') && !str_contains($soloUna, 'zz-fechas'), 'b8 con una sola visible, solo lo suyo: ni la tarjeta, ni la leyenda ni los filtros de la otra');
        $ninguna = $pinta([]);
        $check(str_contains($ninguna, 'No tiene acceso a ningún dato del mapa.') && !str_contains($html, 'No tiene acceso a ningún dato del mapa.'), 'b9 sin fuentes visibles, la frase; con fuentes, no');
        echoTerminal(' ');

        //──── [c] La tarjeta, exigida al registrar ─────────────────────────────────────────────
        echoTerminal('[c] register() exige una tarjeta legible');
        GeoSourceRegistry::swapForTesting([]);
        $rota = $fuente('zz-geo-rota', 'admin', [], basepath('app/classes/PiecesPHP/Geo/Views/zz-no-existe.php'));
        $rechaza = false;
        try {
            GeoSourceRegistry::register($rota);
        } catch (\InvalidArgumentException $e) {
            $rechaza = str_contains($e->getMessage(), 'zz-geo-rota') && str_contains($e->getMessage(), 'zz-no-existe.php');
        }
        $check($rechaza && GeoSourceRegistry::get('zz-geo-rota') === null, 'c1 una tarjeta que no existe: lanza con la clave y la ruta, y no la registra');
        $carpeta = $fuente('zz-geo-carpeta', 'admin', [], basepath('app/classes/PiecesPHP/Geo/Views'));
        $rechazaCarpeta = false;
        try {
            GeoSourceRegistry::register($carpeta);
        } catch (\InvalidArgumentException $e) {
            $rechazaCarpeta = true;
        }
        $check($rechazaCarpeta, 'c2 una carpeta no es una tarjeta');
        GeoSourceRegistry::register($a);
        $check(GeoSourceRegistry::get('zz-geo-a') === $a, 'c3 CANARIO: con una tarjeta que existe, registra');
        $sustitutaRota = $fuente('zz-geo-a', 'admin', [], basepath('app/classes/PiecesPHP/Geo/Views/zz-no-existe.php'));
        $rechazaSustituta = false;
        try {
            GeoSourceRegistry::replace($sustitutaRota);
        } catch (\InvalidArgumentException $e) {
            $rechazaSustituta = str_contains($e->getMessage(), 'zz-no-existe.php');
        }
        $check($rechazaSustituta && GeoSourceRegistry::get('zz-geo-a') === $a, 'c4 replace() también exige la tarjeta: lanza y deja la que había');
        echoTerminal(' ');

        //──── [d] El escapado de las opciones ─────────────────────────────────────────────────
        echoTerminal('[d] Las opciones de un filtro salen escapadas');
        $check(str_contains($html, '&lt;b&gt;zz-escapado&lt;/b&gt;') && !str_contains($html, '<b>zz-escapado</b>'), 'd1 «<b>» en el texto de una opción sale como texto, no como marcado');
    } catch (\Throwable $e) {
        $check(false, 'la vista y el registro corren enteros', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        GeoSourceRegistry::swapForTesting($originales);
    }
    echoTerminal(' ');

    //──── [e] La carga de puntos, de ida y vuelta ──────────────────────────────────────────
    echoTerminal('[e] geo-load-fixture: simular no escribe, escribir y limpiar dejan la base como estaba');
    if (!$check(GeoLoadFixtureTask::existingCount() === 0, 'e0 no hay carga escrita antes de empezar', GeoLoadFixtureTask::existingCount() . ' filas zz-carga-; límpiala con bin/cli geo-load-fixture limpiar=yes')) {
        return $balance();
    }
    $tablas = ['pcsphp_users', 'user_system_profile', 'organizations_elements', 'system_approvals_elements', 'login_attempts', 'pcsphp_users_otp_secrets', 'time_on_platform'];
    $cuentas = fn(): array => array_combine($tablas, array_map([SeedPlanner::class, 'countRows'], $tablas));
    $inicio = $cuentas();
    try {
        $simulacion = GeoLoadFixtureTask::simulate(20);
        $check($cuentas() === $inicio && count($simulacion) > 0, 'e1 simular no cambia ninguna cuenta');
        GeoLoadFixtureTask::write(20);
        $escritas = $cuentas();
        $check(GeoLoadFixtureTask::existingCount() === 20 && $escritas['user_system_profile'] - $inicio['user_system_profile'] === 16 && $escritas['system_approvals_elements'] - $inicio['system_approvals_elements'] === 20, 'e2 CANARIO: escribir 20 da 16 personas con perfil, 4 organizaciones y 20 aprobaciones', (string) json_encode(array_map(fn($t) => $escritas[$t] - $inicio[$t], $tablas)));
        $dentro = (new PersonsGeoSource())->points(new GeoQuery(GeoLoadFixtureTask::BBOX, 0, ['search' => GeoLoadFixtureTask::PREFIX . 'persona']));
        $check($dentro->total() === 15, 'e3 las personas de la carga salen en el mapa, dentro de su encuadre: 15 de 16 por su nombre (la de «<b>zz-carga-html</b>» no lo lleva)', "total {$dentro->total()}");
        GeoLoadFixtureTask::clean();
        $check($cuentas() === $inicio && GeoLoadFixtureTask::existingCount() === 0, 'e4 limpiar deja cada tabla con su cuenta de antes y 0 filas zz-carga-', (string) json_encode([$inicio, $cuentas()]));
        $vacio = GeoLoadFixtureTask::clean();
        $check($cuentas() === $inicio && is_array($vacio), 'e5 limpiar sin nada escrito no borra nada y no falla');
    } catch (\Throwable $e) {
        $check(false, 'la carga corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if (GeoLoadFixtureTask::existingCount() > 0) {
            GeoLoadFixtureTask::clean();
        }
        $check(GeoLoadFixtureTask::existingCount() === 0, 'z1 no queda ninguna fila de la carga');
    }

    return $balance();

})->setDescription('El geovisor (C11, ronda 2): la ruta de la pantalla con los roles de los puntos, la vista con un filtro por clave, una tarjeta y una fila de leyenda por fuente visible y las opciones escapadas, register() exige una tarjeta legible, y geo-load-fixture de ida y vuelta (simular no escribe; escribir y limpiar dejan las cuentas). En proceso; escribe zz-carga- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
