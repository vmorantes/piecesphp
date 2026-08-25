<?php

//Los dos caminos de LECTURA que abortaban y que impidieron cerrar E2-a. Ver T97.

use App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\Routing\RequestRoute;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use News\Mappers\NewsCategoryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/read-paths-survive', function ($args) {

    echoTerminal("\e[33m[TEST:ReadPathsSurvive] Caminos de lectura que no pueden abortar\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    //─── 1/2 · DataTablesHelper con una petición SIN parámetros ─────────────────────────
    echoTerminal('[1/2] DataTablesHelper: una petición sin parámetros de DataTables');

    //Una RequestRoute de verdad: `process()` valida el tipo, así que un doble no sirve.
    $peticion = function (array $query): RequestRoute {
        $request = new RequestRoute(
            'GET',
            (new UriFactory())->createUri('http://localhost/datatables'),
            new Headers(),
            [],
            [],
            (new StreamFactory())->createStream('')
        );
        $conQuery = $request->withQueryParams($query);
        return $conQuery instanceof RequestRoute ? $conQuery : $request;
    };
    $peticionVacia = $peticion([]);

    $opciones = [
        'select_fields' => NewsCategoryMapper::fieldsToSelect(),
        'columns_order' => ['idPadding', 'name', 'color'],
        'custom_order' => ['idPadding' => 'DESC'],
        'mapper' => new NewsCategoryMapper(),
        'request' => $peticionVacia,
        'on_set_data' => fn ($e) => $e,
    ];

    DataTablesHelper::setTablePrefixOnOrder(false);
    DataTablesHelper::setTablePrefixOnSearch(false);

    $error = null;
    $resultado = null;
    try {
        $resultado = DataTablesHelper::process($opciones);
    } catch (\Throwable $exception) {
        $error = get_class($exception) . ': ' . $exception->getMessage();
    }

    $check($error === null, 'no lanza con la petición vacía', (string) $error);
    $comoArray = json_decode((string) json_encode($resultado), true);
    $valores = is_array($comoArray) ? ($comoArray['values'] ?? []) : [];
    //No basta con que no lance: tiene que haber LLEGADO AL FINAL y traer los datos.
    $check(is_array($valores) && array_key_exists('data', $valores) && ($comoArray['success'] ?? false) === true,
        'y llega al final: trae «data» y se declara con éxito',
        is_array($valores) ? 'claves: ' . implode(', ', array_keys($valores)) . ' | success=' . var_export($comoArray['success'] ?? null, true) : 'sin values');

    //Con búsqueda y sin `columns` es el caso que de verdad usa el parámetro.
    $peticionBuscando = $peticion(['search' => ['value' => 'zzz', 'regex' => 'false']]);
    $error2 = null;
    try {
        DataTablesHelper::process(array_merge($opciones, ['request' => $peticionBuscando]));
    } catch (\Throwable $exception) {
        $error2 = get_class($exception) . ': ' . $exception->getMessage();
    }
    $check($error2 === null, 'ni buscando sin declarar columnas, que es cuando se usan', (string) $error2);
    echoTerminal(' ');

    //─── 2/2 · CountryMapper con una región a NULL ──────────────────────────────────────
    echoTerminal('[2/2] CountryMapper: una región a NULL no puede tumbar el formulario');

    //Si no hay ningún país sin región, esta prueba no mide nada: falla en vez de pasar.
    $sinRegion = (int) $db->query("SELECT COUNT(*) FROM `locations_countries` WHERE `region` IS NULL OR TRIM(`region`) = ''")->fetchColumn();
    $check($sinRegion > 0, 'hay al menos un país sin región: el caso existe en los datos',
        "países sin región={$sinRegion}");

    $error3 = null;
    $opciones2 = [];
    try {
        $opciones2 = CountryMapper::allRegionsForSelect();
    } catch (\Throwable $exception) {
        $error3 = get_class($exception) . ': ' . $exception->getMessage();
    }
    $check($error3 === null, 'allRegionsForSelect() no aborta', (string) $error3);

    //Con el defecto puesto esto también valdría —la excepción deja el array vacío—, así que
    //no basta: hay que exigir que la función haya LLEGADO a producir su opción por defecto.
    $check($opciones2 !== [], 'y devuelve algo: al menos la opción por defecto',
        'opciones=' . count($opciones2));

    $nulos = 0;
    foreach ($opciones2 as $clave => $valor) {
        if ($valor === null || $clave === null) {
            $nulos++;
        }
    }
    $check($nulos === 0, 'y ninguna opción lleva un nulo', "nulos={$nulos}");
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los dos caminos de lectura que abortaban: DataTablesHelper sin parámetros y una región a NULL.')->setEffects([CliActions::EFFECT_DATABASE])->register();
