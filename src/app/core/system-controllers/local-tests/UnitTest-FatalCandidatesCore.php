<?php

//Los posibles 500 de fatal-candidates en el núcleo: cada guarda con el valor vacío provocado y con el dato válido dando lo de
//siempre. DataTablesHelper lee países de la base sin escribir; el exportador vuelca una tabla a memoria.
//@areas nucleo

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Export\Exporter;
use PiecesPHP\Core\Database\Export\Plugins\MemoryOutput;
use PiecesPHP\Core\Database\Export\Plugins\SqlFormat;
use PiecesPHP\Core\Database\ORM\ActiveRecord;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/fatal-candidates-core', function ($args) {

    echoTerminal("\e[33m[TEST:FatalCandidatesCore] Los posibles 500 del núcleo no revientan, y con datos válidos nada cambia\e[39m");
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

    //Lo que lanza la llamada: la clase y el mensaje. Un \Error (un método sobre null o sobre una cadena) es el 500 que se busca evitar.
    $lanza = function (callable $llamada): array {
        try {
            $llamada();
            return ['', ''];
        } catch (\Throwable $e) {
            return [get_class($e), $e->getMessage()];
        }
    };

    //──── a. DataTablesHelper: los dos callables fallan cerrado ────────────────────────────────
    echoTerminal('[a] DataTablesHelper::process(): on_set_model y config_result_model (484, 539-546, 730, 781)');

    $peticion = function (): RequestRoute {
        $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/datatables'), new Headers(['X-Requested-With' => 'XMLHttpRequest']), [], [], (new StreamFactory())->createStream(''));
        $conQuery = $request->withQueryParams(['draw' => 1, 'start' => 0, 'length' => 5, 'columns' => [['searchable' => 'true']], 'search' => ['value' => '', 'regex' => 'false']]);
        return $conQuery instanceof RequestRoute ? $conQuery : $request;
    };
    $listado = function (array $extra = []) use ($peticion): array {
        $valores = DataTablesHelper::process(['request' => $peticion(), 'mapper' => new CountryMapper(), 'columns_order' => ['name']] + $extra)->getValues();
        return [(int) ($valores['recordsTotal'] ?? -1), (int) ($valores['recordsFiltered'] ?? -1), json_encode($valores['data'] ?? null)];
    };
    $restringe = fn (ActiveRecord $modelo): ActiveRecord => $modelo->where(new WhereSegment([new WhereItem('name', WhereItem::EQUAL_OPERATOR, 'zz-pais-que-no-existe')]));

    $base = $listado();
    $check($base[0] > 0 && $base[2] !== '[]', 'a0 CANARIO: el listado de países sin callables trae filas', json_encode($base, JSON_THROW_ON_ERROR));

    foreach (['on_set_model', 'config_result_model'] as $parametro) {
        $llamar = $parametro === 'on_set_model'
            ? fn (callable $devuelve): array => $listado(['on_set_model' => fn ($modelo) => $devuelve($modelo)])
            : fn (callable $devuelve): array => $listado(['config_result_model' => fn ($campos, $orden, $where, $modelo) => $devuelve($modelo)]);

        $check($llamar(fn ($m) => null) === $base, "a1 {$parametro} que devuelve null: el listado es el de siempre");
        [$clase, $mensaje] = $lanza(fn () => $llamar(fn ($m) => CountryMapper::class));
        $check($clase === \UnexpectedValueException::class && str_contains($mensaje, $parametro),
            "a2 DISCRIMINANTE: {$parametro} que devuelve una cadena de clase: UnexpectedValueException que lo nombra, no un \\Error", "{$clase}: {$mensaje}");
        [$clase, $mensaje] = $lanza(fn () => $llamar(fn ($m) => ['no', 'es', 'un', 'modelo']));
        $check($clase === \UnexpectedValueException::class && str_contains($mensaje, 'array'), "a3 {$parametro} que devuelve un array: también lanza, y dice qué devolvió", "{$clase}: {$mensaje}");
        //El uso natural devuelve el MISMO objeto (where() devuelve static): da lo que da hoy modificarlo en sitio sin devolver nada.
        $natural = $llamar(fn (ActiveRecord $m) => $restringe($m));
        $enSitio = $llamar(function (ActiveRecord $m) use ($restringe) {
            $restringe($m);
            return null;
        });
        $check($natural === $enSitio && $natural[2] === '[]', "a4 {$parametro}: el callable natural, fn (\$m) => \$m->where(...), da lo mismo que hoy (el modelo restringido)", json_encode($natural, JSON_THROW_ON_ERROR));
        //El cambio declarado: un modelo DISTINTO del recibido, que hoy se ignoraba, ahora se usa.
        $restringido = $llamar(fn (ActiveRecord $m) => $restringe(clone $m));
        $check($restringido[2] === '[]', "a5 {$parametro} que devuelve OTRO modelo restringido: se usa (fallar cerrado no es ignorarlo)", json_encode($restringido, JSON_THROW_ON_ERROR));
    }
    echoTerminal(' ');

    //──── b. Exporter: sin plugins, una excepción con mensaje ──────────────────────────────────
    echoTerminal('[b] Exporter::export() sin plugin de formato o de salida (147, 196-227)');

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        $check(false, 'b0 hay conexión a la base');
        return $balance();
    }
    $check(true, 'b0 hay conexión a la base');
    $tabla = CountryMapper::model()->getTable();
    foreach (['sin ninguno' => [false, false], 'sin el de salida' => [true, false], 'sin el de formato' => [false, true]] as $caso => [$conFormato, $conSalida]) {
        $exportador = new Exporter($db, $db->getDatabaseName());
        if ($conFormato) {
            $exportador->setFormatPlugin(new SqlFormat());
        }
        if ($conSalida) {
            $exportador->setOutputPlugin(new MemoryOutput());
        }
        [$clase, $mensaje] = $lanza(fn () => $exportador->export(['tables' => [$tabla], 'include_views' => false]));
        $check($clase === \RuntimeException::class && str_contains($mensaje, 'plugins'), "b1 DISCRIMINANTE: {$caso}, RuntimeException con su mensaje y no un \\Error", "{$clase}: {$mensaje}");
    }
    $exportador = (new Exporter($db, $db->getDatabaseName()))->setFormatPlugin(new SqlFormat())->setOutputPlugin(new MemoryOutput());
    $volcado = $exportador->export(['tables' => [$tabla], 'routines' => false, 'triggers' => false, 'single_transaction' => false, 'include_data' => false]);
    $check(is_string($volcado) && str_contains($volcado, 'CREATE TABLE') && str_contains($volcado, $tabla), 'b2 CANARIO: con los dos plugins, el volcado de una tabla sale como siempre', is_string($volcado) ? mb_substr($volcado, 0, 80) : var_export($volcado, true));
    echoTerminal(' ');

    //──── c. Roles::hasPermissions() con una petición sin ruta ─────────────────────────────────
    echoTerminal('[c] Roles::hasPermissions() con un RequestRoute sin ruta resuelta (311)');

    //getRoute() da null cuando el atributo `route` está y vale null (o con silenceOnUnexistingRoute y sin ruta encontrada).
    $peticionVacia = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-sin-ruta'), new Headers(), [], [], (new StreamFactory())->createStream(''));
    $sinRuta = $peticionVacia->withAttribute('route', null);
    [$clase, $mensaje] = $lanza(fn () => Roles::hasPermissions($sinRuta, 0));
    $check($sinRuta instanceof RequestRoute && $sinRuta->getRoute() === null && $clase === '' && Roles::hasPermissions($sinRuta, 0) === false, 'c1 DISCRIMINANTE: sin ruta, false como una ruta que no existe, no un \\Error', "{$clase}: {$mensaje}");
    $rutaReal = get_config('slim_app')->getRouteCollector()->getNamedRoute('user-impersonation-start');
    $conRuta = $peticionVacia->withAttribute('route', $rutaReal);
    $check($conRuta instanceof RequestRoute && Roles::hasPermissions($conRuta, 0) === true && Roles::hasPermissions($conRuta, 2) === false,
        'c2 CANARIO: con ruta, el permiso de siempre (root sí, general no, en empezar a suplantar)');
    echoTerminal(' ');

    //──── d. Utilities: remove_directory y directory_to_zip ────────────────────────────────────
    echoTerminal('[d] remove_directory() (917) y directory_to_zip() (1045, 1058)');

    $temporal = sys_get_temp_dir() . '/zz-fatal-core-' . bin2hex(random_bytes(3));
    $preparado = mkdir("{$temporal}/origen/sub", 0700, true)
        && file_put_contents("{$temporal}/origen/a.txt", 'a') !== false
        && file_put_contents("{$temporal}/origen/sub/b.txt", 'b') !== false;
    $check($preparado, 'd0 el árbol temporal de la prueba se crea', $temporal);
    $zip = directory_to_zip("{$temporal}/origen", $temporal, 'zz.zip');
    $archivo = new \ZipArchive();
    $abierto = $zip !== '' && $archivo->open($zip) === true;
    $nombres = [];
    for ($i = 0; $abierto && $i < $archivo->numFiles; $i++) {
        $nombres[] = (string) $archivo->getNameIndex($i);
    }
    if ($abierto) {
        $archivo->close();
    }
    sort($nombres);
    $check($nombres === ['/a.txt', 'sub', 'sub/b.txt'] || in_array('sub/b.txt', $nombres, true) && in_array('/a.txt', $nombres, true) || in_array('a.txt', $nombres, true) && in_array('sub/b.txt', $nombres, true),
        'd1 CANARIO: directory_to_zip() sigue metiendo los archivos y el subdirectorio (la llamada recursiva con $zip)', json_encode($nombres, JSON_THROW_ON_ERROR));
    echoTerminal('   (el $zip que vuelve null de la llamada recursiva no se puede provocar: la función nunca lo anula; se prueba que el camino válido no cambia)');
    remove_directory($temporal);
    $check(!file_exists($temporal), 'd2 CANARIO: remove_directory() borra el árbol entero');
    [$clase] = $lanza(fn () => remove_directory("{$temporal}/no-existe"));
    $check($clase === '', 'd3 remove_directory() sobre una ruta que no existe no hace nada');
    echoTerminal('   (glob() en false no se provoca en proceso: con una ruta inexistente da [], no false; se trata como «nada que borrar»)');
    echoTerminal(' ');

    //──── e. SqlFormat::wrapRoutineParameters() ────────────────────────────────────────────────
    echoTerminal('[e] SqlFormat::wrapRoutineParameters() (359)');

    $envolver = new \ReflectionMethod(SqlFormat::class, 'wrapRoutineParameters');
    $rutina = "CREATE PROCEDURE p(IN a INT, OUT b VARCHAR(10), c INT)\nBEGIN\nEND";
    $check($envolver->invoke(new SqlFormat(), $rutina) === "CREATE PROCEDURE `p`(IN `a` INT, OUT `b` VARCHAR(10), `c` INT)\nBEGIN\nEND",
        'e1 CANARIO: los parámetros se envuelven en acentos graves como siempre');
    $check($envolver->invoke(new SqlFormat(), "CREATE FUNCTION f() RETURNS INT RETURN 1") === "CREATE FUNCTION `f`() RETURNS INT RETURN 1", 'e2 y una lista vacía sale intacta');
    echoTerminal('   (el preg_split() en false no se provoca: con pcre.backtrack_limit bajo falla antes el preg_replace_callback de fuera)');
    echoTerminal(' ');

    //──── f. El arranque: index.php ────────────────────────────────────────────────────────────
    echoTerminal('[f] index.php: el encargado de la organización (616) y $actionName (933)');

    $fuente = (string) file_get_contents(basepath('index.php'));
    $check(str_contains($fuente, '$administratorID = $administrator instanceof UsersModel ? $administrator->id : $administrator;')
        && str_contains($fuente, '$userIsOrganizationAdministrator = $administratorID == $user->id;'), 'f1 CANARIO: 616 compara por id, venga el encargado como UsersModel o como entero');
    $porId = function ($administrador, int $usuario): bool {
        $id = $administrador instanceof UsersModel ? $administrador->id : $administrador;
        return $id == $usuario;
    };
    $encargado = new UsersModel();
    $encargado->id = 7;
    $check($porId($encargado, 7) && $porId(7, 7) && !$porId($encargado, 8) && !$porId(8, 7), 'f2 la condición: un UsersModel y su id dan el mismo resultado');
    $salida = (string) shell_exec('cd ' . escapeshellarg(basepath('')) . ' && ' . escapeshellarg(PHP_BINARY) . ' index.php cli --local _list-actions 2>&1');
    $check(str_contains($salida, 'unit-tests:core/fatal-candidates-core'), 'f3 CANARIO: _list-actions (la rama de 933, la que lee bin/check-routes) sigue listando las acciones', mb_substr($salida, 0, 200));

    return $balance();

})->setDescription('Los posibles 500 del núcleo (fatal-candidates): DataTablesHelper falla cerrado con un callable que no devuelve un modelo, el exportador sin plugins lanza con mensaje, una petición sin ruta no tiene permisos, y glob, preg_split, el zip y el arranque no revientan; con datos válidos, lo de siempre.')->setEffects([CliActions::EFFECT_NONE])->register();
