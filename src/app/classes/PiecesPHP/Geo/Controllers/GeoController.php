<?php

/**
 * GeoController.php
 */

namespace PiecesPHP\Geo\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\GeoJson\Feature;
use PiecesPHP\GeoJson\GeoJson;
use PiecesPHP\GeoJson\Geometry\Point;
use PiecesPHP\Geo\GeoFilter;
use PiecesPHP\Geo\GeoLang;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoQueryException;
use PiecesPHP\Geo\GeoRoutes;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceRegistry;
use PiecesPHP\Geo\GeoThumbnail;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * GeoController - El geovisor: la pantalla, los puntos de las fuentes visibles y el detalle de uno. Los puntos no
 * llevan HTML: los textos viajan tal cual en el JSON y el cliente los escapa.
 *
 * @package     PiecesPHP\Geo\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GeoController extends AdminPanelController
{

    use ControllerRoutingTrait;

    const LANG_GROUP = GeoLang::LANG_GROUP;

    /**
     * Las marcas de la URL del detalle que el cliente sustituye por la fuente y el id del punto.
     */
    const SOURCE_PLACEHOLDER = 'zz-source';
    const ID_PLACEHOLDER = 'zz-id';

    /**
     * Cuántas opciones devuelve, como mucho, la búsqueda de un filtro de opciones remotas.
     */
    const FILTER_OPTIONS_LIMIT = 20;

    /**
     * @var string
     */
    protected static $URLDirectory = 'geo';

    /**
     * @var string
     */
    protected static $baseRouteName = 'geo-admin';

    /**
     * La pantalla: el mapa a pantalla completa, con los filtros, la leyenda y las plantillas de las fuentes visibles.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function viewer(Request $request, Response $response)
    {
        //El panel importa mapbox con su adaptador para Ubicaciones: el geovisor trae su propia clase y solo quiere mapbox-gl.
        remove_imported_asset('locations');
        remove_imported_asset('mapbox');
        import_front_library('mapbox', []);
        set_title(__(self::LANG_GROUP, 'Mapa'));
        set_custom_assets([GeoRoutes::staticRoute('css/geo-viewer.css')], 'css');
        set_custom_assets([GeoRoutes::staticRoute('js/geo-viewer.js')], 'js');

        $data = self::viewData(GeoSourceRegistry::visibleFor((int) $this->user->type));

        $this->render('panel/layout/header', [
            'bodyClasses' => ['geo-viewer'],
        ]);
        (new HelperController($this->user, $this->getGlobalVariables()))->setInstanceViewDir(__DIR__ . '/../Views/')->render('viewer', $data);
        $this->render('panel/layout/footer');

        return $response;
    }

    /**
     * Los puntos de las fuentes pedidas (`sources[]`) o, sin ellas, de todas las visibles para el rol.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function points(Request $request, Response $response)
    {
        $sources = self::resolveSources($request->getQueryParam('sources', null), (int) $this->user->type);
        if (is_int($sources)) {
            if ($sources === 403) {
                return throw403($request);
            }
            return $sources === 404
                ? self::notFound($response, __(self::LANG_GROUP, 'No existe esa fuente de puntos.'))
                : self::badRequest($response, __(self::LANG_GROUP, 'Las fuentes no tienen una forma válida.'));
        }

        try {
            $query = GeoQuery::fromRequest($request);
            $summary = [];
            $collection = new GeoJson();
            foreach ($sources as $key => $source) {
                $result = $source->points($query);
                $summary[$key] = [
                    'total' => $result->total(),
                    'returned' => count($result->points()),
                    'truncated' => $result->truncated(),
                ];
                foreach ($result->points() as $point) {
                    //Sin modo: la librería saca [lng, lat], el orden de GeoJSON (RFC 7946).
                    $collection->addFeature(new Feature([$point->lng, $point->lat], Point::TYPE, [
                        'source' => $key,
                        'id' => $point->id,
                        'title' => $point->title,
                        'thumbnail' => $point->thumbnail,
                    ]));
                }
            }
        } catch (GeoQueryException $e) {
            return self::badRequest($response, $e->getMessage());
        }

        return $response->withJson(['sources' => (object) $summary] + $collection->jsonSerialize());
    }

    /**
     * El detalle de un punto: el que la fuente no devolvería en sus puntos (oculto, inactivo, sin coordenadas) no existe.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function point(Request $request, Response $response, array $args)
    {
        $key = is_string($args['source'] ?? null) ? $args['source'] : '';
        $source = self::resolveSource($key, (int) $this->user->type);
        if (is_int($source)) {
            return $source === 403 ? throw403($request) : self::notFound($response, __(self::LANG_GROUP, 'No existe esa fuente de puntos.'));
        }
        $id = $args['id'] ?? null;
        $detail = is_string($id) && preg_match('/^\d+$/', $id) === 1 ? $source->pointDetail((int) $id) : null;
        if ($detail === null) {
            return self::notFound($response, __(self::LANG_GROUP, 'No existe ese punto.'));
        }
        return $response->withJson([
            'source' => $key,
            'id' => (int) $id,
            'detail' => $detail,
        ]);
    }

    /**
     * Las opciones de un filtro GeoFilter::TYPE_REMOTE_OPTIONS que casan con `search`, de la primera fuente visible para el
     * rol que lo declara (la misma que lo pinta, GeoController::filtersOf()). 404 si ninguna visible lo declara así. El
     * texto va escapado: Fomantic lo pinta como HTML.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function filterOptions(Request $request, Response $response, array $args)
    {
        $key = is_string($args['key'] ?? null) ? $args['key'] : '';
        $search = $request->getQueryParam('search', '');
        $search = is_string($search) ? trim($search) : '';
        $source = self::remoteFilterSource($key, GeoSourceRegistry::visibleFor((int) $this->user->type));
        if ($source === null) {
            return self::notFound($response, __(self::LANG_GROUP, 'No existe ese filtro.'));
        }
        $results = [];
        foreach ($source->filterOptions($key, $search, self::FILTER_OPTIONS_LIMIT) as $value => $name) {
            $results[] = [
                'value' => (string) $value,
                'name' => escape_html($name),
            ];
        }
        return $response->withJson([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * La primera de las fuentes que declara el filtro como de opciones remotas, o null.
     *
     * @param string $key
     * @param array<string,GeoSource> $sources
     * @return GeoSource|null
     */
    public static function remoteFilterSource(string $key, array $sources): ?GeoSource
    {
        foreach ($sources as $source) {
            foreach ($source->filters() as $filter) {
                if ($filter->key() === $key) {
                    return $filter->type() === GeoFilter::TYPE_REMOTE_OPTIONS ? $source : null;
                }
            }
        }
        return null;
    }

    /**
     * La miniatura de la imagen de un punto, con el permiso de su fuente: 403 si el rol no la ve, 404 si la fuente o el
     * punto no existen, si no tiene imagen o si el tamaño no es uno de GeoThumbnail::SIZES. La URL lleva su sello de
     * versión, así que el navegador la guarda sin volver a preguntar.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function thumbnail(Request $request, Response $response, array $args)
    {
        $key = is_string($args['source'] ?? null) ? $args['source'] : '';
        $file = self::resolveThumbnail($key, (int) $this->user->type, $args['id'] ?? null, $args['size'] ?? null);
        if ($file === 403) {
            return throw403($request);
        }
        $data = is_string($file) ? file_get_contents($file) : false;
        if ($data === false) {
            return self::notFound($response, __(self::LANG_GROUP, 'No existe esa imagen.'));
        }
        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->write($data);
    }

    /**
     * El archivo de la miniatura que pide un rol: 403 si la fuente existe y el rol no la ve (lo mismo que points), 404 si
     * no existe la fuente, el punto o su imagen, o si el id o el tamaño no tienen forma.
     *
     * @param string $key
     * @param int $userType
     * @param mixed $id
     * @param mixed $size
     * @return string|int
     */
    public static function resolveThumbnail(string $key, int $userType, $id, $size)
    {
        $source = self::resolveSource($key, $userType);
        if (is_int($source)) {
            return $source;
        }
        $valid = is_string($id) && preg_match('/^\d+$/', $id) === 1 && is_string($size) && preg_match('/^\d+$/', $size) === 1;
        $file = $valid ? GeoThumbnail::file($source, (int) $id, (int) $size) : null;
        return $file ?? 404;
    }

    /**
     * Las fuentes de una petición de puntos: sin `sources[]`, todas las visibles para el rol. Si no, 400 (mal
     * formadas), 404 (una no existe) o 403 (una existe y el rol no la ve).
     *
     * @param mixed $requested
     * @param int $userType
     * @return array<string,GeoSource>|int
     */
    public static function resolveSources($requested, int $userType)
    {
        $visible = GeoSourceRegistry::visibleFor($userType);
        if ($requested === null) {
            return $visible;
        }
        if (!is_array($requested) || !array_is_list($requested) || count(array_filter($requested, 'is_string')) !== count($requested)) {
            return 400;
        }
        $sources = [];
        foreach (array_unique($requested) as $key) {
            if (GeoSourceRegistry::get($key) === null) {
                return 404;
            }
            if (!array_key_exists($key, $visible)) {
                return 403;
            }
            $sources[$key] = $visible[$key];
        }
        return $sources;
    }

    /**
     * La fuente del detalle de un punto: 404 si no existe, 403 si el rol no la ve.
     *
     * @param string $key
     * @param int $userType
     * @return GeoSource|int
     */
    public static function resolveSource(string $key, int $userType)
    {
        if (GeoSourceRegistry::get($key) === null) {
            return 404;
        }
        return GeoSourceRegistry::visibleFor($userType)[$key] ?? 403;
    }

    /**
     * Lo que pinta la vista de la pantalla con unas fuentes: un filtro por clave, sus plantillas y lo que lee el JS.
     *
     * @param array<string,GeoSource> $sources
     * @return array{langGroup: string, sources: array<string,GeoSource>, filters: array<string,GeoFilter>, client: array<string,mixed>}
     */
    public static function viewData(array $sources): array
    {
        $client = [
            'pointsURL' => self::routeName('points', [], true),
            'pointURL' => self::routeName('point', ['source' => self::SOURCE_PLACEHOLDER, 'id' => self::ID_PLACEHOLDER], true),
            'sourcePlaceholder' => self::SOURCE_PLACEHOLDER,
            'idPlaceholder' => self::ID_PLACEHOLDER,
            'sources' => [],
        ];
        foreach ($sources as $key => $source) {
            $client['sources'][] = [
                'key' => $key,
                'title' => $source->title(),
                'marker' => $source->marker(),
                'colorVariable' => $source->colorVariable(),
            ];
        }
        return [
            'langGroup' => self::LANG_GROUP,
            'sources' => $sources,
            'filters' => self::filtersOf($sources),
            'client' => $client,
        ];
    }

    /**
     * Un filtro por clave: si dos fuentes declaran la misma, manda la primera en el orden de registro.
     *
     * @param array<string,GeoSource> $sources
     * @return array<string,GeoFilter>
     */
    public static function filtersOf(array $sources): array
    {
        $filters = [];
        foreach ($sources as $source) {
            foreach ($source->filters() as $filter) {
                if (!array_key_exists($filter->key(), $filters)) {
                    $filters[$filter->key()] = $filter;
                }
            }
        }
        return $filters;
    }

    /**
     * @param Response $response
     * @param string $message
     * @return Response
     */
    protected static function badRequest(Response $response, string $message): Response
    {
        return $response->withStatus(400)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Geovisor'),
            'message' => $message,
        ]);
    }

    /**
     * @param Response $response
     * @param string $message
     * @return Response
     */
    protected static function notFound(Response $response, string $message): Response
    {
        return $response->withStatus(404)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Geovisor'),
            'message' => $message,
        ]);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;

        //Los cinco roles del panel que ven personas y organizaciones; lo que ve cada uno lo decide
        //el permiso de cada fuente.
        $roles = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];

        $group->register([
            new Route("{$startRoute}[/]", self::class . ':viewer', self::$baseRouteName . '-viewer', 'GET', true, null, $roles),
            new Route("{$startRoute}/points[/]", self::class . ':points', self::$baseRouteName . '-points', 'GET', true, null, $roles),
            new Route("{$startRoute}/points/{source}/{id}[/]", self::class . ':point', self::$baseRouteName . '-point', 'GET', true, null, $roles),
            new Route("{$startRoute}/thumbnail/{source}/{id}/{size}[/]", self::class . ':thumbnail', self::$baseRouteName . '-thumbnail', 'GET', true, null, $roles),
            new Route("{$startRoute}/filters/{key}/options[/]", self::class . ':filterOptions', self::$baseRouteName . '-filter-options', 'GET', true, null, $roles),
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
