<?php

/**
 * NotificationsController.php
 */

namespace PiecesPHP\Notifications\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationsLang;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * NotificationsController - La lista y las marcas de leída, en JSON (ADR 0073). Cada uno ve y toca solo lo suyo: lo
 * ajeno responde 404, igual que lo que no existe.
 *
 * @package     PiecesPHP\Notifications\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NotificationsController extends AdminPanelController
{
    use ControllerRoutingTrait;

    const LANG_GROUP = NotificationsLang::LANG_GROUP;

    /**
     * Por omisión, cuántas por página.
     */
    const DEFAULT_PER_PAGE = 10;

    /**
     * @var string
     */
    protected static $URLDirectory = 'notifications';

    /**
     * @var string
     */
    protected static $baseRouteName = 'notifications';

    /**
     * @var HelperController
     */
    protected $helpController;

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * La vista central «Todas las notificaciones»: la misma lista a página entera.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function view(Request $request, Response $response): Response
    {
        $title = __(self::LANG_GROUP, 'Todas las notificaciones');
        set_title($title);
        $this->helpController->render('panel/layout/header');
        $this->render('view', [
            'title' => $title,
            'description' => __(self::LANG_GROUP, 'Lo que te concierne: los avisos del sistema y las noticias internas.'),
            'markAllText' => __(self::LANG_GROUP, 'Marcar todas como leídas'),
            'helpController' => $this->helpController,
            'list' => self::listMarkup('notifications-view-list', false),
        ]);
        $this->helpController->render('panel/layout/footer');
        return $response;
    }

    /**
     * El marcado de la lista (notifications-list.php), para la campana y para la vista.
     *
     * @param string $id
     * @param bool $inPopup
     * @return string
     */
    public static function listMarkup(string $id, bool $inPopup): string
    {
        //HelperController (BaseController vacía los estáticos), con lock_assets repuesto: si no, el próximo controlador de
        //la página vacía lo importado antes de la cabecera (H261).
        $lockAssets = get_config('lock_assets');
        $helper = new HelperController(null, []);
        set_config('lock_assets', $lockAssets);
        return (string) $helper->render('panel/built-in/content/notifications-list', [
            'id' => $id,
            'inPopup' => $inPopup,
            'filters' => Notifications::filters(),
            'filterTitles' => [
                Notifications::FILTER_ALL => __(self::LANG_GROUP, 'Todas'),
                Notifications::FILTER_NEWS => __(self::LANG_GROUP, 'Noticias internas'),
                Notifications::FILTER_UNREAD => __(self::LANG_GROUP, 'No leídas'),
                Notifications::FILTER_READ => __(self::LANG_GROUP, 'Leídas'),
            ],
            'texts' => [
                'unread' => __(self::LANG_GROUP, 'Sin leer'),
                'error' => __(self::LANG_GROUP, 'No se pudieron cargar las notificaciones.'),
            ],
            'listURL' => self::routeName('list'),
            'markURL' => self::routeName('mark-read'),
            'markAllURL' => self::routeName('mark-all-read'),
            'perPage' => self::DEFAULT_PER_PAGE,
            'emptyTitle' => __(self::LANG_GROUP, 'Estás al día'),
            'emptyText' => __(self::LANG_GROUP, 'No tienes notificaciones nuevas. Te avisaremos cuando ocurra algo importante.'),
            'moreText' => __(self::LANG_GROUP, 'Cargar más'),
            'filtersLabel' => __(self::LANG_GROUP, 'Filtrar notificaciones'),
            'title' => __(self::LANG_GROUP, 'Notificaciones'),
            'markAllText' => __(self::LANG_GROUP, 'Marcar todas como leídas'),
            'viewURL' => self::routeName('view'),
            'viewAllText' => __(self::LANG_GROUP, 'Ver todas'),
        ], false);
    }

    /**
     * GET: filter (all|news|unread|read), page y perPage.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function list(Request $request, Response $response): Response
    {
        $user = getLoggedFrameworkUserOrFail();
        $query = $request->getQueryParams();
        $filter = is_string($query['filter'] ?? null) ? $query['filter'] : Notifications::FILTER_ALL;
        if (!in_array($filter, Notifications::filters(), true)) {
            return $response->withStatus(400)->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Notificaciones'),
                'message' => __(self::LANG_GROUP, 'Filtro de notificaciones desconocido.'),
            ]);
        }
        $page = self::positiveInt($query['page'] ?? null, 1);
        $perPage = min(Notifications::MAX_PER_PAGE, self::positiveInt($query['perPage'] ?? null, self::DEFAULT_PER_PAGE));
        return $response->withJson(['success' => true] + Notifications::page((int) $user->id, $filter, $page, $perPage));
    }

    /**
     * POST: source e id.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function markRead(Request $request, Response $response): Response
    {
        $user = getLoggedFrameworkUserOrFail();
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $sourceKey = is_string($body['source'] ?? null) ? $body['source'] : '';
        $id = is_scalar($body['id'] ?? null) ? (string) $body['id'] : '';
        $source = Notifications::source($sourceKey);
        if ($source === null || $id === '' || !$source->markRead((int) $user->id, $id)) {
            return $response->withStatus(404)->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Notificaciones'),
                'message' => __(self::LANG_GROUP, 'No existe esa notificación.'),
            ]);
        }
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Notificaciones'),
            'message' => __(self::LANG_GROUP, 'Notificación marcada como leída.'),
            'counts' => Notifications::counts((int) $user->id),
        ]);
    }

    /**
     * POST: las de todas las fuentes del usuario.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function markAllRead(Request $request, Response $response): Response
    {
        $user = getLoggedFrameworkUserOrFail();
        $marked = 0;
        foreach (Notifications::sources() as $source) {
            $marked += $source->markAllRead((int) $user->id);
        }
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Notificaciones'),
            'message' => __(self::LANG_GROUP, 'Todas las notificaciones quedaron como leídas.'),
            'marked' => $marked,
            'counts' => Notifications::counts((int) $user->id),
        ]);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        $group->register([
            new Route("{$startRoute}/view[/]", self::class . ':view', self::$baseRouteName . '-view', 'GET', true, null, $allRoles),
            new Route("{$startRoute}/list[/]", self::class . ':list', self::$baseRouteName . '-list', 'GET', true, null, $allRoles),
            new Route("{$startRoute}/mark-read[/]", self::class . ':markRead', self::$baseRouteName . '-mark-read', 'POST', true, null, $allRoles),
            new Route("{$startRoute}/mark-all-read[/]", self::class . ':markAllRead', self::$baseRouteName . '-mark-all-read', 'POST', true, null, $allRoles),
        ]);
        return $group;
    }

    /**
     * @param mixed $value
     * @param int $default
     * @return int
     */
    private static function positiveInt(mixed $value, int $default): int
    {
        return is_scalar($value) && preg_match('/^[1-9][0-9]{0,5}$/', (string) $value) === 1 ? (int) $value : $default;
    }
}
