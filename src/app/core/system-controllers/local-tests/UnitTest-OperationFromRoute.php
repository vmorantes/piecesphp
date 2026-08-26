<?php

//La operación la decide la RUTA, no el `id` del cuerpo. Ver T120.

use News\Controllers\NewsCategoryController;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/operation-from-route', function ($args) {

    echoTerminal("\e[33m[TEST:OperationFromRoute] La ruta decide la operación, no el cuerpo\e[39m");
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

    //Un doble basta: el ayudante solo pregunta el nombre, que es lo que concede el permiso.
    $peticion = function (string $nombreRuta, array $cuerpo): RequestRoute {
        $ruta = new class ($nombreRuta) {
            public function __construct(public string $nombre)
            {
            }
            public function getName()
            {
                return $this->nombre;
            }
            public function getArguments()
            {
                return [];
            }
        };
        $request = new RequestRoute(
            'POST',
            (new UriFactory())->createUri('http://localhost/prueba'),
            new Headers(),
            [],
            [],
            (new StreamFactory())->createStream('')
        );
        $conRuta = $request->withAttribute('route', $ruta)->withParsedBody($cuerpo);
        return $conRuta instanceof RequestRoute ? $conRuta : $request;
    };

    //─── 1/4 · El ayudante, por sí solo ────────────────────────────────────────────────
    echoTerminal('[1/4] `isEditRoute()` lee el nombre de la ruta');

    $check(BaseController::isEditRoute($peticion('news-category-admin-actions-add', [])) === false,
        'una ruta que acaba en -actions-add NO es edición');
    $check(BaseController::isEditRoute($peticion('news-category-admin-actions-edit', [])) === true,
        'una ruta que acaba en -actions-edit SÍ lo es');

    //NO ADIVINA: una ruta que no declara su operación es un error de registro, no un defecto.
    $lanzo = false;
    try {
        BaseController::isEditRoute($peticion('news-category-admin-actions-delete', []));
    } catch (\UnexpectedValueException $exception) {
        $lanzo = true;
    }
    $check($lanzo, 'y una que no declara ninguna de las dos ABORTA en vez de elegir');
    echoTerminal(' ');

    //─── 2/4 · Un `id` en la ruta de ALTA se rechaza ───────────────────────────────────
    echoTerminal('[2/4] POST con id≠-1 a `-actions-add`');

    $controlador = new NewsCategoryController();
    $cuerpo = ['id' => 999999, 'name' => 'Prueba', 'color' => '#ffffff', 'lang' => 'es', 'baseLang' => 'es'];

    $alta = $controlador->action($peticion('news-category-admin-actions-add', $cuerpo), new ResponseRoute());
    $cuerpoAlta = json_decode((string) $alta->getBody(), true);
    $check($alta->getStatusCode() === 400, 'responde 400', 'estado=' . $alta->getStatusCode());
    $check(is_array($cuerpoAlta) && ($cuerpoAlta['success'] ?? null) === false, 'y success es false');
    echoTerminal(' ');

    //─── 3/4 · Y la ausencia de `id` en la ruta de EDICIÓN, también ────────────────────
    echoTerminal('[3/4] POST sin id a `-actions-edit`');

    $sinId = ['name' => 'Prueba', 'color' => '#ffffff', 'lang' => 'es', 'baseLang' => 'es'];
    $edicion = $controlador->action($peticion('news-category-admin-actions-edit', $sinId), new ResponseRoute());
    $check($edicion->getStatusCode() === 400, 'responde 400', 'estado=' . $edicion->getStatusCode());
    echoTerminal(' ');

    //─── 4/4 · Cuando ruta y cuerpo COINCIDEN, la guarda no se mete ────────────────────
    echoTerminal('[4/4] La guarda NO dispara cuando coinciden');

    //Sin esta comprobación, una guarda que rechazara SIEMPRE pasaría las dos anteriores.
    $coherente = $controlador->action($peticion('news-category-admin-actions-edit', $cuerpo), new ResponseRoute());
    $cuerpoCoherente = json_decode((string) $coherente->getBody(), true);
    $mensaje = is_array($cuerpoCoherente) ? (string) ($cuerpoCoherente['message'] ?? '') : '';
    $check($coherente->getStatusCode() !== 400, 'edición con id: no es 400', 'estado=' . $coherente->getStatusCode());
    $check(mb_strpos($mensaje, 'no corresponde con la ruta') === false,
        'y el mensaje NO es el del desajuste', $mensaje);

    //─── El contrato en los 13 sitios ──────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[extra] Los 13 controladores usan el ayudante y ninguno deriva del cuerpo');

    $raiz = rtrim(str_replace('\\', '/', basepath('')), '/') . '/app/classes';
    $conAyudante = 0;
    $conElViejo = 0;
    $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        if (!$archivo->isFile() || strtolower($archivo->getExtension()) !== 'php') {
            continue;
        }
        $contenido = (string) @file_get_contents((string) $archivo->getPathname());
        if (mb_strpos($contenido, 'self::isEditRoute($request)') !== false) {
            $conAyudante++;
        }
        if (mb_strpos($contenido, '$isEdit = $id !== -1;') !== false) {
            $conElViejo++;
        }
    }
    $check($conAyudante === 13, 'los 13 lo usan', "encontrados={$conAyudante}");
    $check($conElViejo === 0, 'y ninguno deriva ya la operación del cuerpo', "quedan={$conElViejo}");

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La operación de alta/edición sale del nombre de la ruta, y el desajuste con el cuerpo se rechaza.')->setEffects([CliActions::EFFECT_DATABASE])->register();
