<?php

/**
 * BlackboardNewsController.php
 */

namespace App\Controller;

use App\Model\BlackboardNewsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * BlackboardNewsController.
 *
 * Controlador de tablón de noticias
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BlackboardNewsController extends AdminPanelController
{

    const UPLOAD_DIR = 'blackboard';
    const FORMAT_DATETIME = 'd-m-Y h:i A';

    /**
     * @var string
     */
    protected $uploadDir = '';
    /**
     * @var string
     */
    protected $uploadDirURL = '';

    const LANG_GROUP = 'news';

    /**
     * @return static
     */
    public function __construct()
    {
        parent::__construct();
        add_global_asset(BLACKBOARD_NEWS_PATH_JS . '/main.js', 'js');

        $this->uploadDir = append_to_url(get_config('upload_dir'), self::UPLOAD_DIR);
        $this->uploadDirURL = append_to_url(get_config('upload_dir_url'), self::UPLOAD_DIR);
    }

    /**
     * Vista de listado de noticias
     *
     * @param Request $req
     * @param Response $res
     * @return void
     */
    public function listView(Request $req, Response $res)
    {

        $this->render('panel/layout/header');
        $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/list');
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function writeForm(Request $request, Response $response)
    {

        $types = Roles::getRolesIdentifiers(true);

        import_default_rich_editor();

        $this->render(ADMIN_PATH_VIEWS . '/layout/header');
        $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/create', [
            'types' => $types,
        ]);
        $this->render(ADMIN_PATH_VIEWS . '/layout/footer');

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function editForm(Request $request, Response $response, array $args)
    {

        $id = isset($args['id']) ? $args['id'] : null;
        $id = ctype_digit($id) ? (int) $id : null;
        $types = Roles::getRolesIdentifiers(true);
        $new = new BlackboardNewsModel($id);

        if (!is_null($new->id)) {

            import_default_rich_editor();

            $this->render(ADMIN_PATH_VIEWS . '/layout/header');
            $this->render(BLACKBOARD_NEWS_PATH_VIEWS . '/edit', [
                'types' => $types,
                'new' => $new,
            ]);
            $this->render(ADMIN_PATH_VIEWS . '/layout/footer');

            return $response;
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function deleteNew(Request $request, Response $response, array $args)
    {

        $id = isset($args['id']) ? $args['id'] : null;
        $id = ctype_digit($id) ? (int) $id : null;
        $new = new BlackboardNewsModel($id);

        $result = new ResultOperations([
            'deleteNew' => new Operation('deleteNew'),
        ], __(self::LANG_GROUP, 'Eliminación de noticia'));

        if (!is_null($new->id)) {

            $deleted = $new->getModel()->delete([
                'id' => $id,
            ])->execute();

            if ($deleted) {
                $directory = append_to_url($this->uploadDir, (string) $id);
                remove_directory($directory);
            }

            $result
                ->setMessage($deleted ? __(self::LANG_GROUP, 'La noticia ha sido eliminada.') : __(self::LANG_GROUP, 'La noticia no pudo ser eliminada, intente luego.'))
                ->operation('deleteNew')
                ->setSuccess($deleted);

        } else {
            $result->setMessage(__(self::LANG_GROUP, 'La noticia no existe'));
        }
        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function registerNew(Request $request, Response $response)
    {

        $type = $request->getParsedBodyParam('type', null);
        $title = $request->getParsedBodyParam('title', null);
        $text = $request->getParsedBodyParam('text', null);
        $start_date = $request->getParsedBodyParam('start_date', null);
        $end_date = $request->getParsedBodyParam('end_date', null);

        $start_date = mb_strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = mb_strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $params_verify = !in_array(null, [
            $type,
            $title,
            $text,
        ]);

        $result = (new ResultOperations([
            'registerNew' => new Operation('registerNew'),
        ]))->setValue('redirect', '');

        if ($params_verify) {

            $entity = new BlackboardNewsModel();
            $entity->author = $this->user->id;
            $entity->type = $type;
            $entity->title = $title;
            $entity->start_date = $start_date;
            $entity->text = $text;
            $entity->end_date = $end_date;
            $saved = $entity->save();
            $id = $entity->getLastInsertID();
            $entity->id = $id;

            $redirect = '';
            if ($saved) {
                $redirect = get_route('blackboard-news-list');
            }

            $result
                ->setValue('redirect', $redirect)
                ->setMessage($saved ? __(self::LANG_GROUP, 'La noticia ha sido creada') : __(self::LANG_GROUP, 'No se ha podido crear la noticia, intente más tarde.'))
                ->operation('registerNew')
                ->setSuccess($saved);

        } else {
            $result
                ->setMessage(__(self::LANG_GROUP, 'Los parámetros recibidos no son correctos'))
                ->operation('registerNew');
        }

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function editNew(Request $request, Response $response)
    {

        $id = $request->getParsedBodyParam('id', null);
        $id = ctype_digit($id) ? (int) $id : null;
        $author = $request->getParsedBodyParam('author', null);
        $type = $request->getParsedBodyParam('type', null);
        $title = $request->getParsedBodyParam('title', null);
        $text = $request->getParsedBodyParam('text', null);
        $start_date = $request->getParsedBodyParam('start_date', null);
        $end_date = $request->getParsedBodyParam('end_date', null);

        $start_date = mb_strlen(trim($start_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $start_date) : null;
        $end_date = mb_strlen(trim($end_date)) > 0 ? date_create_from_format(self::FORMAT_DATETIME, $end_date) : null;

        $params_verify = !in_array(null, [
            $id,
            $author,
            $type,
            $title,
            $text,
        ]);

        $result = new ResultOperations([
            'editNew' => new Operation('editNew'),
        ]);

        if ($params_verify) {

            $entity = new BlackboardNewsModel($id);
            $entity->type = $type;
            $entity->title = $title;
            $entity->start_date = $start_date;
            $oldText = $entity->text;
            $entity->text = $text;
            $entity->end_date = $end_date;
            $updated = $entity->update();

            $result
                ->setMessage($updated ? __(self::LANG_GROUP, 'La noticia ha sido actualizada') : __(self::LANG_GROUP, 'No se ha podido actualizar la noticia, intente más tarde.'))
                ->operation('editNew')
                ->setSuccess($updated);

        } else {
            $result
                ->setMessage(__(self::LANG_GROUP, 'Los parámetros recibidos no son correctos'))
                ->operation('editNew');
        }

        return $response->withJson($result);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getNews(Request $request, Response $response)
    {
        $page = (int) $request->getQueryParam('page', 1);
        $perPage = (int) $request->getQueryParam('per_page', 10);
        $isList = $request->getQueryParam('is_list', null) === 'true';

        $model = (new BlackboardNewsModel())->getModel();

        $now = (new \DateTime())->format('Y-m-d h:s:i');

        if ($isList) {
            $where = trim(implode(' ', [
                " author = " . $this->user->id . ' OR ',
                " type = " . $this->user->type . ' AND ',
                " (start_date <= '$now' AND end_date > '$now') OR ",
                " (start_date IS NULL AND end_date IS NULL) ",
            ]));
        } else {
            $where = trim(implode(' ', [
                " type = " . $this->user->type . ' AND ',
                " (start_date <= '$now' AND end_date > '$now') OR ",
                " (start_date IS NULL AND end_date IS NULL) ",
            ]));
        }

        $query = $model->select()->where($where)->orderBy('start_date DESC, created_date DESC');

        $queryTotal = clone $query;

        $queryTotal->select()->execute();
        $query->execute(false, $page, $perPage);

        $result = $query->result();
        $result = is_array($result) ? $result : [];

        $total = count($queryTotal->result());

        $filtered = [];

        foreach ($result as $element) {

            $mapper = new BlackboardNewsModel($element->id);
            $element->author = [
                'id' => $mapper->author->id,
                'username' => $mapper->author->username,
                'firstname' => $mapper->author->firstname,
                'secondname' => $mapper->author->secondname,
                'first_lastname' => $mapper->author->first_lastname,
                'second_lastname' => $mapper->author->second_lastname,
                'email' => $mapper->author->email,
                'meta' => $mapper->author->meta,
            ];
            $element->type = [
                'code' => $element->type,
                'label' => UsersModel::getTypesUser()[$element->type],
            ];

            if (!is_null($element->start_date) && !is_null($element->end_date)) {
                $element->start_date = $mapper->start_date->format('d-m-Y h:i A');
                $element->end_date = $mapper->end_date->format('d-m-Y h:i A');
            }

            $element->title = $mapper->title;
            $element->text = $mapper->text;

            $filtered[] = $element;

        }

        return $response->withJson(
            (new ResultOperations([], 'Noticias', __(self::LANG_GROUP, 'Paginado de noticias')))
                ->setValue(self::LANG_GROUP, $filtered)
                ->setValue('page', $page)
                ->setValue('perPage', $perPage)
                ->setValue('total', $total)
                ->setValue('pages', ceil($total / $perPage))
        );
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = BlackboardNewsController::class;
        $routes = [];
        /**
         * @var array<string>
         */
        $all_roles = array_keys(UsersModel::TYPES_USERS);
        $edition_permissions = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
        ];

        //──── GET ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}create[/]",
            $classname . ':writeForm',
            'blackboard-news-create-form',
            'GET',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}edit/{id}[/]",
            $classname . ':editForm',
            'blackboard-news-edit-form',
            'GET',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}news[/]",
            $classname . ':getNews',
            'blackboard-news-get',
            'GET',
            true,
            null,
            $all_roles
        );
        $routes[] = new Route(
            "{$startRoute}list[/]",
            $classname . ':listView',
            'blackboard-news-list',
            'GET',
            true,
            null,
            $edition_permissions
        );

        //──── POST ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}create[/]",
            $classname . ':registerNew',
            'blackboard-news-create',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}edit[/]",
            $classname . ':editNew',
            'blackboard-news-edit',
            'POST',
            true,
            null,
            $edition_permissions
        );
        $routes[] = new Route(
            "{$startRoute}delete/{id}[/]",
            $classname . ':deleteNew',
            'blackboard-news-delete',
            'POST',
            true,
            null,
            $edition_permissions
        );

        $group->active(BLACKBOARD_NEWS_ENABLED);
        $group->register($routes);

        return $group;
    }
}
