<?php
/**
 * CategoryController.php
 */

namespace PiecesPHP\BuiltIn\Article\Category\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryContentMapper;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
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
    protected static $prefixRootParentEntity = 'built-in';
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
        self::$title = __('articlesBackend', self::$title);
        self::$pluralTitle = __('articlesBackend', self::$pluralTitle);
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        $this->model = (new CategoryContentMapper())->getModel();
        set_title(self::$title);
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

        set_custom_assets([
            'statics/js/built-in/article/category/backend/forms.js',
        ], 'js');

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

        set_custom_assets([
            'statics/js/built-in/article/category/backend/forms.js',
        ], 'js');

        $id = $request->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $element = new CategoryMapper($id);

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

            //Opciones recibidas
            $paginate = $request->getQueryParam('paginate', null);
            $page = $request->getQueryParam('page', 1);
            $perPage = $request->getQueryParam('per_page', 5);
            $onlyLang = $request->getQueryParam('lang', null);

            //Tratamiento de las opciones
            $allowedLangs = get_config('allowed_langs');

            $onlyLang = is_string($onlyLang) && strlen(trim($onlyLang)) > 0 ? trim($onlyLang) : null;
            $onlyLang = !is_null($onlyLang) && in_array($onlyLang, $allowedLangs) ? $onlyLang : get_config('app_lang');

            $page = is_integer($page) || ctype_digit($page) ? (int) $page : 1;
            $perPage = is_integer($perPage) || ctype_digit($perPage) ? (int) $perPage : 5;

            //Prepación de la consulta
            $model = CategoryContentMapper::model();
            $query = $model->select();

            $where = [
                "lang = '$onlyLang'",
            ];

            $where = implode(' ', $where);

            $query->where($where);

            if ($paginate === 'yes') {

                $response_data = [];

                $response_data['sql'] = $query->getCompiledSQL();
                $query->execute(false, $page, $perPage);

                $data = $query->result();

                $query->resetAll();

                $query->select('COUNT(id) AS total');
                $query->where($where);
                $query->execute();
                $total = $query->result();
                $total = count($total) > 0 ? (int) $total[0]->total : 0;
                $pages = ceil($total / $perPage);

                $data = array_map(function ($e) {
					$e->link = ArticleControllerPublic::routeName('list-by-category', ['category' => $e->friendly_url]);
					$e->name = htmlentities(stripslashes($e->name));
					$e->description = htmlentities(stripslashes($e->description));
                    return $e;
                }, $data);

                $response_data['page'] = $page;
                $response_data['perPage'] = $perPage;
                $response_data['pages'] = $pages;
                $response_data['total'] = $total;
                $response_data['data'] = $data;

                return $response->withJson($response_data);

            } else {

                $query->execute();

                return $response->withJson($query->result());

            }

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
                'content_of',
                'name',
                'description',
            ];

            $ids = CategoryContentMapper::getPreferedsIDs();

            $where_string = count($ids) > 0 ? "id = '" . implode("' OR id = '", $ids) . "'" : null;

            $result = DataTablesHelper::process([
                'where_string' => $where_string,
                'columns_order' => $columns_order,
                'mapper' => new CategoryContentMapper(),
                'request' => $request,
                'on_set_data' => function ($e) {

                    $buttonEdit = new HtmlElement('a', __('articlesBackend', 'Editar'));
                    $buttonEdit->setAttribute('class', "ui button green");
                    $buttonEdit->setAttribute('href', self::routeName('forms-edit', [
                        'id' => $e->content_of,
                    ]));

                    if ($buttonEdit->getAttributes(false)->offsetExists('href')) {
                        $href = $buttonEdit->getAttributes(false)->offsetGet('href');
                        if (strlen(trim($href->getValue())) < 1) {
                            $buttonEdit = '';
                        }
                    }

                    $mapper = new CategoryContentMapper($e->id);

                    return [
                        $mapper->content_of->id,
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
        $properties = $request->getParsedBodyParam('properties', []);
        $properties = is_array($properties) ? $properties : [];
        $isEdit = $id !== -1;

        if (!$isEdit) {
            $result = $this->addAction($properties);
        } else {
            $result = $this->editAction($id, $properties);
        }

        return $response->withJson($result);
    }

    /**
     * addAction
     *
     * Creación
     *
     * @param array $properties
     * @return ResultOperations
     */
    protected function addAction(array $properties)
    {

        //Configuraciones iniciales de la respuesta
        $result = new ResultOperations([], __('articlesBackend', 'Crear categoría'), '', true);
        $result->setValue('redirect', false);

        //URLs para redirección
        $urlRedirect = self::routeName('list');
        $urlEdit = '';

        //Mensajes
        $createdWithErrorsMessage = __('articlesBackend', 'Se ha creado la categoría, excepto: %s');
        $multipleErrorsMessages = __('articlesBackend', 'Errores: %s');
        $noInformationMessage = __('articlesBackend', 'No se ha recibido información.');
        $unknowErrorMessage = __('articlesBackend', 'Ha ocurrido un error desconocido.');
        $isDuplicateMessage = __('articlesBackend', 'Ya existe una categoría con ese nombre.');
        $successCreatedMessage = __('articlesBackend', 'Categoría creada.');

        try {

            $hasInformation = false; //Verifica si fue recibida información
            $allOK = true; //Si todos los idiomas fueron agregados
            $oneOK = false; //Si al menos un idioma fue agregado
            $errors = []; //Errores
            $createdNewCategory = false; //Verifica si se creó la nueva categoría

            $mainCategory = new CategoryMapper();
            $createdNewCategory = $mainCategory->save();
            $mainCategoryID = $mainCategory->getLastInsertID();

            $urlEdit = self::routeName('forms-edit', ['id' => $mainCategoryID]);

            if ($createdNewCategory) {

                foreach ($properties as $lang => $structure) {

                    //Validación de entrada
                    $name = isset($structure['name']) ? $structure['name'] : null;
                    $description = isset($structure['description']) ? $structure['description'] : null;

                    //Limpieza de entrada
                    $name = is_string($name) ? clean_string($name) : '';
                    $description = is_string($description) ? clean_string($description) : '';

                    //Validación final de entrada
                    $validations = strlen($name) > 0;

                    if ($validations) {

                        $hasInformation = true;
                        $friendly_url = CategoryContentMapper::generateFriendlyURL($name, -1);
                        $isDuplicate = CategoryContentMapper::isDuplicate(
                            $name,
                            $friendly_url,
                            -1,
                            -1
                        );

                        if (!$isDuplicate) {

                            $newLangCategory = new CategoryContentMapper();
                            $newLangCategory->content_of = $mainCategoryID;
                            $newLangCategory->name = $name;
                            $newLangCategory->friendly_url = $friendly_url;
                            $newLangCategory->description = $description;
                            $newLangCategory->lang = $lang;

                            $saved = $newLangCategory->save();
                            $result->setSuccessOnSingleOperation($saved);

                            if (!$saved) {
                                $allOK = false;
                                $errors[] = $name;
                            } else {
                                $oneOK = true;
                            }

                        } else {

                            $allOK = false;
                            $errors[] = __('lang', $lang) . ' - ' . $name . ' - ' . $isDuplicateMessage;

                        }

                    }

                }

            }

            //Configuraciones finales de la respuesta
            if ($createdNewCategory) {

                if ($hasInformation) {

                    if ($allOK) {

                        $result->setMessage($successCreatedMessage);

                    } else {

                        $urlRedirect = $urlEdit;

                        $result->setSuccessOnSingleOperation(false);

                        if ($oneOK) {
                            $result->setMessage(sprintf($createdWithErrorsMessage, implode(', ', $errors)));
                        } else {
                            $result->setMessage(sprintf($multipleErrorsMessages, implode('<br>', $errors)));
                        }

                    }

                    if ($oneOK) {

                        $result->setSuccessOnSingleOperation(true);
                        $result->setValue('redirect', true);
                        $result->setValue('redirect_to', $urlRedirect);

                    } else {

                        if ($createdNewCategory) {

                            $mainCategory->getModel()->delete([
                                'id' => $mainCategoryID,
                            ])->execute();

                        }

                    }

                } else {

                    $result->setMessage(sprintf($noInformationMessage, __('lang', $lang)));

                }

            } else {
                $result->setMessage($unknowErrorMessage);
            }

        } catch (\PDOException $e) {

            $result->setMessage($unknowErrorMessage);
            $result->setValues([
                'exception' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'trace' => $e->getTrace(),
                ],
            ]);
            log_exception($e);

        }

        return $result;

    }

    /**
     * editAction
     *
     * Edición
     *
     * @param int $categoryID
     * @param array $properties
     * @return ResultOperations
     */
    protected function editAction(int $categoryID, array $properties)
    {

        //Configuraciones iniciales de la respuesta
        $result = new ResultOperations([], __('articlesBackend', 'Modificar categoría'), '', true);
        $result->setValue('reload', false);

        //Mensajes
        $editedWithErrorsMessage = __('articlesBackend', 'Se ha guardado la categoría, excepto: %s');
        $multipleErrorsMessages = __('articlesBackend', 'Errores: %s');
        $noInformationMessage = __('articlesBackend', 'No se ha recibido información.');
        $unknowErrorMessage = __('articlesBackend', 'Ha ocurrido un error desconocido.');
        $isDuplicateMessage = __('articlesBackend', 'Ya existe una categoría con ese nombre.');
        $successEditMessage = __('articlesBackend', 'Datos guardados.');
        $notExistsMessage = __('articlesBackend', 'La categoría que intenta modificar no existe');

        try {

            $hasInformation = false; //Verifica si fue recibida información
            $allOK = true; //Si todos los idiomas fueron agregados
            $oneOK = false; //Si al menos un idioma fue agregado
            $errors = []; //Errores

            $mainCategory = new CategoryMapper($categoryID);

            if (!is_null($mainCategory->id)) {

                foreach ($properties as $lang => $structure) {

                    //Validación de entrada
                    $idLang = isset($structure['id']) ? (int) $structure['id'] : -1;
                    $name = isset($structure['name']) ? $structure['name'] : null;
                    $description = isset($structure['description']) ? $structure['description'] : null;

                    //Limpieza de entrada
                    $name = is_string($name) ? clean_string($name) : '';
                    $description = is_string($description) ? clean_string($description) : '';

                    //Validación final de entrada
                    $validations = strlen($name) > 0;

                    if ($validations) {

                        //Duplicidad y control de errores
                        $hasInformation = true;
                        $friendly_url = CategoryContentMapper::generateFriendlyURL($name, $idLang);
                        $isDuplicate = CategoryContentMapper::isDuplicate(
                            $name,
                            $friendly_url,
                            $categoryID,
                            $idLang
                        );

                        if (!$isDuplicate) {

                            if ($idLang != -1) {
                                $langCategory = new CategoryContentMapper($idLang);
                            }

                            if ($langCategory->id == null) {
                                $langCategory = $mainCategory->getContentByLang($lang);
                            }

                            $langCategory = !is_null($langCategory) ? $langCategory : new CategoryContentMapper();
                            $langCategory->content_of = $mainCategory->id;
                            $langCategory->name = $name;
                            $langCategory->friendly_url = $friendly_url;
                            $langCategory->description = $description;
                            $langCategory->lang = $lang;

                            if ($langCategory->id !== null) {
                                $saved = $langCategory->update();
                            } else {
                                $saved = $langCategory->save();
                            }

                            $result->setSuccessOnSingleOperation($saved);

                            if (!$saved) {
                                $allOK = false;
                                $errors[] = $name;
                            } else {
                                $oneOK = true;
                            }

                        } else {

                            $allOK = false;
                            $errors[] = __('lang', $lang) . ' - ' . $name . ' - ' . $isDuplicateMessage;

                        }

                    }

                }

            }

            //Configuraciones finales de la respuesta
            if (!is_null($mainCategory->id)) {

                if ($hasInformation) {

                    if ($allOK) {

                        $result->setMessage($successEditMessage);

                    } else {

                        $result->setSuccessOnSingleOperation(false);

                        if ($oneOK) {

                            $result->setMessage(sprintf($editedWithErrorsMessage, implode(', ', $errors)));

                        } else {

                            $result->setMessage(sprintf($multipleErrorsMessages, implode('<br>', $errors)));

                        }

                    }

                    if ($oneOK) {

                        $result->setSuccessOnSingleOperation(true);
                        $result->setValue('reload', true);

                    }

                } else {

                    $result->setMessage(sprintf($noInformationMessage, __('lang', $lang)));

                }

            } else {

                $result->setMessage($notExistsMessage);

            }

        } catch (\PDOException $e) {

            $result->setMessage($unknowErrorMessage);
            $result->setValues([
                'exception' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'trace' => $e->getTrace(),
                ],
            ]);
            log_exception($e);

        }

        return $result;
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
