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

/**
 * ¿Este archivo decide la operación a partir del CUERPO de la petición?
 *
 * SE TOKENIZA. La versión anterior buscaba el literal `$isEdit = $id !== -1;` y por eso no
 * vio cuatro controladores que escriben `$is_edit`, con guion bajo, desde T120. Un literal
 * comprueba una propiedad del TEXTO, no del comportamiento. Ver LEY 24 y T141.
 *
 * Lo que busca es el flujo: una variable que sale de `getParsedBodyParam('id', …)` y acaba
 * decidiendo otra variable comparándose contra `-1`. El nombre da igual.
 *
 * @param string $codigo Código PHP completo.
 * @return bool
 */
function derivaLaOperacionDelCuerpo(string $codigo): bool
{
    $tokens = @token_get_all($codigo);
    if (!is_array($tokens)) {
        return false;
    }

    $planos = [];
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }
            $planos[] = ['tipo' => $token[0], 'texto' => $token[1]];
        } else {
            $planos[] = ['tipo' => null, 'texto' => $token];
        }
    }

    //Paso 1: qué variables salen del `id` del CUERPO.
    $delCuerpo = [];
    $total = count($planos);
    for ($i = 0; $i < $total - 6; $i++) {
        if ($planos[$i]['tipo'] !== \T_VARIABLE || $planos[$i + 1]['texto'] !== '=') {
            continue;
        }
        for ($j = $i + 2; $j < min($i + 10, $total - 2); $j++) {
            if ($planos[$j]['tipo'] !== \T_STRING || $planos[$j]['texto'] !== 'getParsedBodyParam') {
                continue;
            }
            $argumento = $planos[$j + 2]['texto'] ?? '';
            if (trim($argumento, "'\"") === 'id') {
                $delCuerpo[$planos[$i]['texto']] = true;
            }
            break;
        }
    }

    if (count($delCuerpo) === 0) {
        return false;
    }

    //Paso 2: alguna de ellas decide un booleano comparándose contra -1.
    for ($i = 0; $i < $total - 5; $i++) {
        if ($planos[$i]['tipo'] !== \T_VARIABLE || $planos[$i + 1]['texto'] !== '=') {
            continue;
        }
        if ($planos[$i + 2]['tipo'] !== \T_VARIABLE || !isset($delCuerpo[$planos[$i + 2]['texto']])) {
            continue;
        }
        $comparadores = [\T_IS_NOT_IDENTICAL, \T_IS_IDENTICAL, \T_IS_NOT_EQUAL, \T_IS_EQUAL];
        if (!in_array($planos[$i + 3]['tipo'], $comparadores, true)) {
            continue;
        }
        if ($planos[$i + 4]['texto'] === '-' && ($planos[$i + 5]['texto'] ?? '') === '1') {
            return true;
        }
    }

    return false;
}

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

    //─── El contrato, sobre una población DERIVADA DEL ÁRBOL ───────────────────────────
    echoTerminal(' ');
    echoTerminal('[extra] Todo controlador que comparte manejador toma la operación de la ruta');

    $raiz = rtrim(str_replace('\\', '/', basepath('')), '/') . '/app/classes';
    $usanAyudante = [];
    $compartenManejador = [];
    $derivanDelCuerpo = [];
    $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        if (!$archivo->isFile() || strtolower($archivo->getExtension()) !== 'php') {
            continue;
        }
        $ruta = str_replace($raiz . '/', '', (string) $archivo->getPathname());
        $contenido = (string) @file_get_contents((string) $archivo->getPathname());
        if (mb_strpos($contenido, 'self::isEditRoute($request)') !== false) {
            $usanAyudante[] = $ruta;
        }
        //LA PREGUNTA NO ES SI EXISTE UN LITERAL. Buscar `$isEdit = $id !== -1;` dejó CUATRO
        //controladores sin ver desde T120: escriben `$is_edit`, con guion bajo. Ver T141.
        if (derivaLaOperacionDelCuerpo($contenido)) {
            $derivanDelCuerpo[] = $ruta;
        }

        //LA POBLACIÓN, por un método INDEPENDIENTE del anterior: el MISMO manejador registrado
        //para `-actions-add` y para `-actions-edit`. Quien comparte metodo necesita el ayudante.
        $manejadores = [];
        if (preg_match_all('/new\s+Route\s*\((.*?)\)\s*,/s', $contenido, $bloques) > 0) {
            foreach ($bloques[1] as $argumentos) {
                foreach (['-actions-add', '-actions-edit'] as $sufijo) {
                    if (mb_strpos($argumentos, $sufijo) === false) {
                        continue;
                    }
                    $partes = explode(',', $argumentos);
                    if (isset($partes[1])) {
                        $manejadores[$sufijo][] = trim($partes[1]);
                    }
                }
            }
        }
        //Solo si el archivo DEFINE el método: `Locations.php` registra rutas para OTRAS cuatro
        //clases con un manejador en variable, y esas cuatro NO pasan por aquí. Ver T140.
        $comparte = isset($manejadores['-actions-add'], $manejadores['-actions-edit'])
            && count(array_intersect($manejadores['-actions-add'], $manejadores['-actions-edit'])) > 0;
        if ($comparte && mb_strpos($contenido, 'public function action(') !== false) {
            $compartenManejador[] = $ruta;
        }
    }
    $conAyudante = count($usanAyudante);
    //NO una cifra escrita: la poblacion sale del arbol. Un `13` aqui se pudre en el primer
    //borrado de E3 —paso con `prefer-slug` en YC y con este mismo en el lote 2—. Ver T140.
    $sinAyudante = array_values(array_diff($compartenManejador, $usanAyudante));
    $check(
        count($sinAyudante) === 0,
        'todos los que comparten manejador usan el ayudante',
        count($compartenManejador) . ' comparten, ' . $conAyudante . ' usan el ayudante; sin él: ' . implode(', ', $sinAyudante)
    );
    //LOS CUATRO DECLARADOS, con su razón y su puntero. Si aparece un quinto, esto se pone
    //rojo: una excepción declarada no es una excepción abierta. Ver T141 y LEY 2.
    $exceptionesDeclaradas = [
        'App/Locations/Controllers/City.php',
        'App/Locations/Controllers/Country.php',
        'App/Locations/Controllers/Point.php',
        'App/Locations/Controllers/State.php',
    ];
    sort($derivanDelCuerpo);
    $sinDeclarar = array_values(array_diff($derivanDelCuerpo, $exceptionesDeclaradas));
    $declaradasQueYaNo = array_values(array_diff($exceptionesDeclaradas, $derivanDelCuerpo));

    $check(
        count($sinDeclarar) === 0,
        'ninguno deriva la operación del cuerpo, salvo los declarados',
        count($derivanDelCuerpo) . ' derivan; sin declarar: ' . implode(', ', $sinDeclarar)
    );
    //LA LISTA SOLO PUEDE ENCOGER, y encoger incluye vaciarse: si uno se arregla, hay que
    //quitarlo de aquí, o la declaración empieza a cubrir algo que ya no existe.
    $check(
        count($declaradasQueYaNo) === 0,
        'y la lista de excepciones no cubre nada que ya esté arreglado',
        'sobran: ' . implode(', ', $declaradasQueYaNo)
    );

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La operación de alta/edición sale del nombre de la ruta, y el desajuste con el cuerpo se rechaza.')->setEffects([CliActions::EFFECT_DATABASE])->register();
