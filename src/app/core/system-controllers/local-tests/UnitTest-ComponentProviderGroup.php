<?php

//El {group} de provide() acaba en un require. Se llama al controlador directamente, sin Apache ni Slim delante: lo que
//se mide es la defensa del código, no la del servidor (pendientes.md 446).
//@areas src/app/classes/Publications/

use PiecesPHP\Components\Controllers\ComponentProvider;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/component-provider-group', function ($args) {

    echoTerminal("\e[33m[TEST:ComponentProviderGroup] Solo se sirve un grupo que exista en Views/ o registrado, y lo demás da 404 sin llegar al require; el bloque de publicaciones escapa y tiene tope\e[39m");
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

    //Devuelve 'NOT_FOUND', o el cuerpo si provide() respondió. Cualquier otra excepción sale con su clase.
    $pedir = function (string $group, ?ComponentProvider $proveedor = null, ?string &$tipo = null): string {
        $peticion = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/components-provider/provide/zz/'), new Headers(), [], [], (new StreamFactory())->createStream('')))
        //Sin Slim delante no hay RouteContext: con 'route' presente, NotFoundException no lo pide.
            ->withAttribute('route', null)
            ->withAttribute('group', $group);
        try {
            $respuesta = ($proveedor ?? new ComponentProvider())->provide($peticion, new ResponseRoute());
            $tipo = $respuesta->getHeaderLine('Content-Type');
            return (string) $respuesta->getBody();
        } catch (NotFoundException $e) {
            return 'NOT_FOUND';
        } catch (\Throwable $e) {
            return 'EXCEPCION ' . get_class($e) . ': ' . $e->getMessage();
        }
    };

    echoTerminal('[canario] El universo de la puerta');
    //Sin los registrados: un clon que registre los suyos al arrancar no tumba el canario.
    $registradosAlEmpezar = array_keys((new \ReflectionProperty(ComponentProvider::class, 'registeredGroups'))->getValue());
    $grupos = array_values(array_diff((new ComponentProvider())->availableGroups(), $registradosAlEmpezar));
    sort($grupos);
    $check($grupos === ['publications', 'sample'], 'c1 los grupos de Views/ servibles son los directorios con components.php', implode(', ', $grupos) ?: 'ninguno');
    echoTerminal(' ');

    echoTerminal('[1] Un grupo que existe sigue sirviendo su plantilla');
    foreach (['publications', 'sample'] as $grupo) {
        $cuerpo = $pedir($grupo);
        $check(str_contains($cuerpo, '<components name='), "1 «{$grupo}» responde con su plantilla", mb_substr($cuerpo, 0, 80));
    }
    echoTerminal(' ');

    echoTerminal('[2] Lo demás da 404 antes del require');
    //Si alguno llegara al require, el archivo no existiría y saldría el aviso de PHP, no la NotFoundException.
    foreach (['..', '../x', '../../sample', 'a/b', '....//', 'sample/../publications', 'zz-no-existe', '', 'SAMPLE', 'sample.php'] as $grupo) {
        $resultado = $pedir($grupo);
        $check($resultado === 'NOT_FOUND', "2 «{$grupo}» da 404", mb_substr($resultado, 0, 80));
    }
    echoTerminal(' ');

    echoTerminal('[3] La respuesta lleva su tipo, y un 404 de la vista no se vuelve un 200');
    $tipo = '';
    $pedir('sample', null, $tipo);
    $check($tipo === 'text/html; charset=utf-8', '3a el grupo responde con Content-Type: text/html; charset=utf-8', $tipo);
    //No es lo que pasa con una plantilla real (render() captura lo suyo y responde 500): vigila que provide() no vuelva a
    //envolver el render en un try que se trague lo que salga de él.
    $conVistaQueFalla = new class () extends ComponentProvider {
        public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
        {
            $peticion = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withAttribute('route', null);
            throw new NotFoundException($peticion, new ResponseRoute());
        }
    };
    $resultado = $pedir('sample', $conVistaQueFalla);
    $check($resultado === 'NOT_FOUND', '3b provide() no envuelve el render en un try: lo que lance el render sale de provide()', mb_substr($resultado, 0, 80));
    echoTerminal(' ');

    echoTerminal('[4] El bloque público de publicaciones: un tope de slugs y lo que pinta, escapado');
    $database = (new BaseModel())->getDatabase();
    $consulta = $database->prepare('SELECT id FROM `' . UsersModel::TABLE . '` WHERE type = ? ORDER BY id LIMIT 1');
    $consulta->execute([UsersModel::TYPE_USER_ROOT]);
    $rootID = (int) $consulta->fetchColumn();
    $idPublicacion = null;
    $getPrevio = $_GET;
    try {
        $lang = (string) get_config('default_lang');
        $publicacion = new PublicationMapper();
        $publicacion->baseLang = $lang;
        foreach (['title' => 'zz-prueba-componentes', 'content' => '<p>zz-prueba-componentes</p>', 'seoDescription' => '', 'publicDate' => new \DateTime(), 'startDate' => null, 'endDate' => null, 'category' => (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0), 'visits' => 0, 'author' => $rootID, 'folder' => str_replace('.', '', uniqid()), 'featured' => PublicationMapper::UNFEATURED, 'mainImage' => 'statics/images/zz-prueba.jpg', 'thumbImage' => 'statics/images/zz-prueba.jpg', 'ogImage' => ''] as $campo => $valor) {
            $publicacion->setLangData($lang, $campo, $valor);
        }
        $publicacion->status = PublicationMapper::DRAFT;
        //`save()` exige un usuario en sesión, y el terminal no tiene: se le presta root y se devuelve el que había.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $rootID]);
            set_config('pcsphp_current_user_stored', null);
            $publicacion->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $idPublicacion = (int) $publicacion->id;
        //Un preferSlug con comillas y etiquetas: la vista lo pinta en data-slug y lo lleva al href.
        $database->prepare('UPDATE `' . PublicationMapper::TABLE . '` SET preferSlug = ? WHERE id = ?')->execute(['zz-hj"><b>zz-roto</b>', $idPublicacion]);

        //Doce slugs válidos de la misma publicación: cada uno es una consulta, y la vista solo atiende los diez primeros.
        $_GET['slugs'] = array_map(fn (): string => PublicationMapper::getEncryptIDForSlug($idPublicacion), range(1, 12));
        $cuerpo = $pedir('publications');
        $piezas = substr_count($cuerpo, 'name="special-main-item"');
        $check($piezas === 10, '4a con 12 slugs, la vista pinta 10 piezas: el tope', "piezas: {$piezas}; " . mb_substr($cuerpo, 0, 80));
        $check(str_contains($cuerpo, 'data-slug="zz-hj&quot;&gt;&lt;b&gt;zz-roto&lt;/b&gt;"') && !str_contains($cuerpo, '<b>zz-roto</b>'), '4b el preferSlug con comillas sale escapado en data-slug y en el href', mb_substr($cuerpo, 0, 200));
    } finally {
        $_GET = $getPrevio;
        if ($idPublicacion !== null) {
            $database->prepare('DELETE FROM `' . PublicationMapper::TABLE . '` WHERE id = ?')->execute([$idPublicacion]);
        }
        $quedan = $database->prepare('SELECT COUNT(*) FROM `' . PublicationMapper::TABLE . '` WHERE id = ?');
        $quedan->execute([(int) $idPublicacion]);
        $check($idPublicacion !== null && (int) $quedan->fetchColumn() === 0, 'z1 no queda la publicación de la prueba');
    }
    echoTerminal(' ');

    echoTerminal('[5] Un grupo registrado por el proyecto (H222): se sirve, se lista, y no pisa a nadie');
    //El registro vive en el proceso y gates corre las suites en el mismo: se vacía al terminar, pase lo que pase.
    $registro = new \ReflectionProperty(ComponentProvider::class, 'registeredGroups');
    $registroPrevio = $registro->getValue();
    $gruposPrevios = (new ComponentProvider())->availableGroups();
    try {
        $registro->setValue(null, []);
        ComponentProvider::registerGroup('zz-prueba-grupo', function (): void {
            echo '<components name="zz-prueba-grupo"><b>zz-ok</b></components>';
        });
        $check(in_array('zz-prueba-grupo', (new ComponentProvider())->availableGroups(), true), '5a el registrado aparece en availableGroups()');
        $tipo = '';
        $cuerpo = $pedir('zz-prueba-grupo', null, $tipo);
        $check($cuerpo === '<components name="zz-prueba-grupo"><b>zz-ok</b></components>', '5b provide() sirve su HTML', mb_substr($cuerpo, 0, 80));
        $check($tipo === 'text/html; charset=utf-8', '5c con el mismo Content-Type que un grupo de Views/', $tipo);
        //DISCRIMINANTE: un repetido que pisara al anterior cambiaría en silencio lo que se sirve.
        $lanza = function (callable $accion): string {
            try {
                $accion();
                return 'no lanzó';
            } catch (\Throwable $e) {
                return get_class($e) . ': ' . $e->getMessage();
            }
        };
        $nada = function (): void {
        };
        $conVistas = $lanza(fn () => ComponentProvider::registerGroup('sample', $nada));
        $check(str_starts_with($conVistas, 'LogicException') && str_contains($conVistas, '«sample»'), '5d un nombre de Views/ lanza, nombrando el grupo', $conVistas);
        $check(str_contains($pedir('sample'), '<components name='), '5d y «sample» sigue sirviendo su vista');
        $repetido = $lanza(fn () => ComponentProvider::registerGroup('zz-prueba-grupo', $nada));
        $check(str_starts_with($repetido, 'LogicException') && str_contains($repetido, '«zz-prueba-grupo»'), '5e un nombre ya registrado lanza', $repetido);
        $check($pedir('zz-prueba-grupo') === '<components name="zz-prueba-grupo"><b>zz-ok</b></components>', '5e y el primero sigue siendo el que se sirve');
        foreach (['../x', 'a b', '', 'zz/grupo'] as $nombre) {
            $invalido = $lanza(fn () => ComponentProvider::registerGroup($nombre, $nada));
            $check(str_starts_with($invalido, 'InvalidArgumentException'), "5f el nombre «{$nombre}» lanza", $invalido);
        }
        $check($pedir('zz-no-registrado') === 'NOT_FOUND', '5g un grupo no registrado sigue dando 404');
        ComponentProvider::registerGroup('zz-prueba-falla', function (): void {
            echo 'zz-a-medias';
            throw new \RuntimeException('zz-falla');
        });
        $nivel = ob_get_level();
        ob_start();
        $fallo = $pedir('zz-prueba-falla');
        $escapado = (string) ob_get_clean();
        $check(str_contains($fallo, 'RuntimeException') && str_contains($fallo, 'zz-falla'), '5h lo que lanza un registrado sale de provide(), no se vuelve un 200', $fallo);
        $check(ob_get_level() === $nivel, '5h y no deja búferes abiertos');
        //DISCRIMINANTE: con ob_end_flush() en vez de ob_end_clean(), lo pintado a medias saldría a la respuesta.
        $check(!str_contains($escapado, 'zz-a-medias'), '5h y lo pintado a medias se descarta', $escapado);
        ComponentProvider::registerGroup('zz-prueba-bufer', function (): void {
            echo 'zz-fuera-';
            ob_start();
            echo 'zz-dentro';
        });
        $check($pedir('zz-prueba-bufer') === 'zz-fuera-zz-dentro', '5i un búfer que $render deja abierto se recoge entero, no solo el interno');
        ComponentProvider::registerGroup('zz-prueba-marca', function (): void {
            echo '<img src="statics/images/404.png">';
        });
        $marca = get_config('cache_stamp_render_files') === true ? static_file_version('statics/images/404.png') : 'none';
        $servido = $pedir('zz-prueba-marca');
        //DISCRIMINANTE: sin addStaticVersions() en la rama registrada, el src sale sin marca aunque la opción esté encendida.
        $check($marca === 'none' ? $servido === '<img src="statics/images/404.png">' : $servido === '<img src="statics/images/404.png?cacheStamp=' . $marca . '">', '5j los estáticos de un registrado llevan la marca de caché, como los de Views/', $servido);
    } finally {
        $registro->setValue(null, $registroPrevio);
    }
    $check((new ComponentProvider())->availableGroups() === $gruposPrevios, 'z2 al terminar, los grupos son los de antes de la prueba', implode(', ', (new ComponentProvider())->availableGroups()));
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El {group} de components-provider-provide se valida contra los grupos que existen y con forma de nombre, sin depender del servidor; la respuesta lleva su tipo, un 404 de la vista no se traga, y el bloque de publicaciones tiene tope y escapa. Escribe una publicación zz- y la retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
