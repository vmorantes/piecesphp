<?php
/**
 * CategoryController.php
 */

namespace PiecesPHP\BuiltIn\Article\Category\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper as MainMapper;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * CategoryController.
 *
 * @package     PiecesPHP\BuiltIn\Article\Category\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class CategoryController extends AdminPanelController
{

    /**
     * $prefixRootParentEntity
     *
     * @var string
     */
    protected static $prefixRootParentEntity = 'piecesphp-built-in';
    /**
     * $prefixParentEntity
     *
     * @var string
     */
    protected static $prefixParentEntity = 'articles';
    /**
     * $prefixSingularParentEntity
     *
     * @var string
     */
    protected static $prefixSingularParentEntity = 'article';
    /**
     * $prefixEntity
     *
     * @var string
     */
    protected static $prefixEntity = 'categories';
    /**
     * $prefixSingularEntity
     *
     * @var string
     */
    protected static $prefixSingularEntity = 'category';
    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Categoría';
    /**
     * $pluralTitle
     *
     * @var string
     */
    protected static $pluralTitle = 'Categorías';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        $this->model = (new MainMapper)->getModel();
        set_title(self::$title . ' - ' . get_title());
    }

    /**
     * addForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function addForm(Request $request, Response $response, array $args)
    {

        $action = self::routeName('actions-add');
        $back_link = self::routeName('list');

        $data = [];
        $data['action'] = $action;
        $data['back_link'] = $back_link;
        $data['title'] = self::$title;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixRootParentEntity . '/' . self::$prefixSingularParentEntity . '/' . self::$prefixSingularEntity . '/add-form', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * editForm
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function editForm(Request $request, Response $response, array $args)
    {

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $element = new MainMapper($id);

        if (!is_null($element->id)) {

            $action = self::routeName('actions-edit');
            $back_link = self::routeName('list');

            $data = [];
            $data['action'] = $action;
            $data['element'] = $element;
            $data['back_link'] = $back_link;
            $data['title'] = self::$title;

            $this->render('panel/layout/header');
            $this->render('panel/' . self::$prefixRootParentEntity . '/' . self::$prefixSingularParentEntity . '/' . self::$prefixSingularEntity . '/edit-form', $data);
            $this->render('panel/layout/footer');
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * listView
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function listView(Request $request, Response $response, array $args)
    {

        $process_table = self::routeName('datatables');
        //$back_link = self::routeName();
        $back_link = get_route('admin');
        $add_link = self::routeName('forms-add');

        $data = [];
        $data['process_table'] = $process_table;
        $data['back_link'] = $back_link;
        $data['add_link'] = $add_link;
        $data['has_permissions_add'] = strlen($add_link) > 0;
        $data['title'] = self::$pluralTitle;

        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixRootParentEntity . '/' . self::$prefixSingularParentEntity . '/' . self::$prefixSingularEntity . '/list', $data);
        $this->render('panel/layout/footer');
    }

    /**
     * all
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function all(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $query = $this->model->select();

            $query->execute();

            return $response->withJson($query->result());
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * dataTables
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function dataTables(Request $request, Response $response, array $args)
    {

        if ($request->isXhr()) {

            $columns_order = [
                'id',
                'name',
                'friendly_url',
            ];

            $result = DataTablesHelper::process([
                'columns_order' => $columns_order,
                'mapper' => new MainMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $buttonEdit = new HtmlElement('a', 'Editar');
                    $buttonEdit->setAttribute('class', "ui button green");
                    $buttonEdit->setAttribute('href', self::routeName('forms-edit', [
                        'id' => $e->id,
                    ]));

                    if ($buttonEdit->getAttributes(false)->offsetExists('href')) {
                        $href = $buttonEdit->getAttributes(false)->offsetGet('href');
                        if (strlen(trim($href->getValue())) < 1) {
                            $buttonEdit = '';
                        }
                    }

                    $mapper = new MainMapper($e->id);

                    return [
                        $mapper->id,
                        strlen($mapper->name) > 50 ? trim(mb_substr($mapper->name, 0, 50)) . '...' : $mapper->name,
                        strlen($mapper->description) > 50 ? trim(mb_substr($mapper->description, 0, 50)) . '...' : $mapper->description,
                        (string) $buttonEdit,
                    ];
                },
            ]);

            return $response->withJson($result->getValues());
        } else {
            throw new NotFoundException($request, $response);
        }
    }

    /**
     * action
     *
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function action(Request $request, Response $response, array $args)
    {

        $id = $request->getParsedBodyParam('id', -1);
        $name = $request->getParsedBodyParam('name', null);
        $description = $request->getParsedBodyParam('description', '');

        $is_edit = $id !== -1;

        $valid_params = !in_array(null, [
            $name,
        ]);

        $operation_name = $is_edit ? 'Modificar categoría' : 'Crear categoría';

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $result->setValue('redirect', false);

        $error_parameters_message = 'Los parámetros recibidos son erróneos.';
        $not_exists_message = 'La categoría que intenta modificar no existe';
        $success_create_message = 'Categoría creada.';
        $success_edit_message = 'Datos guardados.';
        $unknow_error_message = 'Ha ocurrido un error desconocido.';
        $is_duplicate_message = 'Ya existe una categoría con ese nombre.';

        $redirect_url_on_create = self::routeName('list');

        if ($valid_params) {

            $name = clean_string($name);
            $friendly_url = MainMapper::generateFriendlyURL($name, $id);
            $description = clean_string($description);

            $is_duplicate = MainMapper::isDuplicate($name, $friendly_url, $id);

            if (!$is_duplicate) {

                if (!$is_edit) {

                    $mapper = new MainMapper();

                    try {

                        $mapper->name = $name;
                        $mapper->friendly_url = $friendly_url;
                        $mapper->description = $description;
                        $saved = $mapper->save();

                        if ($saved) {

                            $result->setMessage($success_create_message)
                                ->operation($operation_name)
                                ->setSuccess(true);

                            $result->setValue('redirect', true);
                            $result->setValue('redirect_to', $redirect_url_on_create);
                        } else {
                            $result->setMessage($unknow_error_message);
                        }
                    } catch (\Exception $e) {
                        $result->setMessage($e->getMessage());
                    }
                } else {

                    $mapper = new MainMapper((int) $id);
                    $exists = !is_null($mapper->id);

                    if ($exists) {

                        try {

                            $mapper->name = $name;
                            $mapper->friendly_url = $friendly_url;
                            $mapper->description = $description;
                            $updated = $mapper->update();

                            if ($updated) {

                                $result->setValue('reload', true);

                                $result->setMessage($success_edit_message)
                                    ->operation($operation_name)
                                    ->setSuccess(true);
                            } else {
                                $result->setMessage($unknow_error_message);
                            }
                        } catch (\Exception $e) {
                            $result->setMessage($e->getMessage());
                        }
                    } else {
                        $result->setMessage($not_exists_message);
                    }
                }
            } else {

                $result->setMessage($is_duplicate_message);
            }
        } else {
            $result->setMessage($error_parameters_message);
        }

        return $response->withJson($result);
    }

    /**
     * routeName
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixRootParentEntity . '-' . self::$prefixParentEntity . '-' . self::$prefixEntity . $name : self::$prefixRootParentEntity;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (PIECES_PHP_BLOG_ENABLED) {

            $routes = [];

            $groupSegmentURL = $group->getGroupSegment();

            $lastIsBar = last_char($groupSegmentURL) == '/';
            $startRoute = $lastIsBar ? '' : '/';

            $roles_manage_permission = [
                UsersModel::TYPE_USER_ROOT,
                UsersModel::TYPE_USER_ADMIN,
            ];

            $group->active(PIECES_PHP_BLOG_ENABLED);
            $group->register($routes);

            //Rutas
            $group->register(
                self::genericManageRoutes($startRoute, self::$prefixRootParentEntity . '-' . self::$prefixParentEntity, self::class, self::$prefixEntity, $roles_manage_permission, true)
            );
        }

        return $group;
    }

    /**
     * genericManageRoutes
     *
     * @param string $startRoute
     * @param string $namePrefix
     * @param string $handler
     * @param string $uriPrefix
     * @param array $rolesAllowed
     * @return Route[]
     */
    protected static function genericManageRoutes(string $startRoute, string $namePrefix, string $handler, string $uriPrefix, array $rolesAllowed = [], bool $withQuillHandler = false)
    {
        $namePrefix .= '-' . $uriPrefix;
        $startRoute .= $uriPrefix;
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        $routes = [
            new Route(
                "{$startRoute}",
                "{$handler}:all",
                "{$namePrefix}-ajax-all",
                'GET'
            ),
            new Route(
                "{$startRoute}/datatables[/]",
                "{$handler}:dataTables",
                "{$namePrefix}-datatables",
                'GET'
            ),
            new Route(
                "{$startRoute}/list[/]",
                "{$handler}:listView",
                "{$namePrefix}-list",
                'GET',
                true,
                null,
                $all_roles
            ),
            new Route(
                "{$startRoute}/forms/add[/]",
                "{$handler}:addForm",
                "{$namePrefix}-forms-add",
                'GET',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/action/add[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-add",
                'POST',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/forms/edit/{id}[/]",
                "{$handler}:editForm",
                "{$namePrefix}-forms-edit",
                'GET',
                true,
                null,
                $rolesAllowed
            ),
            new Route(
                "{$startRoute}/action/edit[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-edit",
                'POST',
                true,
                null,
                $rolesAllowed
            ),
        ];

        if ($withQuillHandler) {
            $routes[] = new Route(
                "{$startRoute}/quill-image-upload[/]",
                "{$handler}:quillImageHandler",
                "{$namePrefix}-image-handler",
                'POST',
                true,
                null,
                $rolesAllowed
            );
        }

        return $routes;
    }
}
