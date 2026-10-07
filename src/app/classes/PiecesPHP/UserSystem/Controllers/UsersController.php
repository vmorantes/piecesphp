<?php

/**
 * UsersController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Tokens\Controllers\TokenController;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Pagination\PaginationResult;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;

/**
 * UsersController.
 *
 * Controlador de usuarios
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class UsersController extends AdminPanelController
{

    /**
     * El layout del panel vive en la carpeta global de vistas, no en la del módulo: lo pide una
     * instancia aparte, que es el patrón de SystemStatus y de Publications.
     *
     * @var HelperController
     */
    protected $helpController = null;

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'users';

    /**
     * @var string
     */
    private $password = "NDG3iIk43xMlo5OKpCZ6Buyu0pC99v9qef9du5tncHoCbgZnHY";

    /**
     * @var string
     */
    private $ofuscate = "NDG3iIk43xMlo5OKpCZ6Buyu0pC99v9qef9du5tncHoCbgZnHY";

    /**
     * Controlador de Tokens
     *
     * @var TokenController
     */
    protected $token_controller = null;

    /**
     * @var ActiveRecordModel
     */
    protected $model = null;

    /**
     * @var UsersModel
     */
    protected $mapper = null;

    /**
     * URL para recuperación de contraseña
     *
     * @var string
     */
    public $url_recovery = 'users/recovery/';

    /**
     * URL para recuperación de contraseña
     *
     * @var string
     */
    public $requested_uri = '';

    //Constantes de errores
    /**
     * Campos del usuario que el login manda al navegador. LISTA BLANCA: lo que no esté aquí NO viaja, aunque la tabla
     * lo tenga. Antes se quitaban a mano dos campos, así que una columna nueva viajaba sola. Ver files/dev/login-user-data-declared.json.
     */
    const LOGIN_USER_DATA_FIELDS = [
        'id',
        'username',
        'email',
        'firstname',
        'secondname',
        'firstLastname',
        'secondLastname',
        'type',
        'organization',
    ];

    const NO_ERROR = 'NO_ERROR';
    const GENERIC_ERROR = 'GENERIC_ERROR';
    const DUPLICATE_USER = 'DUPLICATE_USER';
    const DUPLICATE_EMAIL = 'DUPLICATE_EMAIL';
    const INCORRECT_PASSWORD = 'INCORRECT_PASSWORD';
    const USER_NO_EXISTS = 'USER_NO_EXISTS';
    const MISSING_OR_UNEXPECTED_PARAMS = 'MISSING_OR_UNEXPECTED_PARAMS';
    const TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    const UNEXPECTED_ACTION = 'UNEXPECTED_ACTION';
    const ACTIVE_SESSION = 'ACTIVE_SESSION';
    const BLOCKED_FOR_ATTEMPTS = 'BLOCKED_FOR_ATTEMPTS';
    const APPROVED_PENDING = 'APPROVED_PENDING';
    const INACTIVE_USER = 'INACTIVE_USER';
    const ORGANIZATION_IS_NOT_ACTIVE = 'ORGANIZATION_IS_NOT_ACTIVE';
    const EXPIRED_OR_NOT_EXIST_CODE = 'EXPIRED_OR_NOT_EXIST_CODE';
    const NOT_MATCH_PASSWORDS = 'NOT_MATCH_PASSWORDS';
    const NO_EXTERNAL_LOGIN_AVAILABLE = 'NO_EXTERNAL_LOGIN_AVAILABLE';
    const INVALID_TWO_FACTOR_CODE = 'INVALID_TWO_FACTOR_CODE';

    //Constante de intentos máximos permitidos
    const MAX_ATTEMPTS = 4;

    /**
     * Quién puede cerrar las sesiones de otro usuario. La ruta lo declara y el controlador lo vuelve a comprobar.
     */
    const CAN_REVOKE_USER_SESSIONS = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];

    const LANG_GROUP = UserDataPackage::LANG_GROUP;

    /** @ignore */
    public function __construct()
    {

        $this->token_controller = new TokenController();

        parent::__construct();

        $this->mapper = new UsersModel();
        $this->model = $this->mapper->getModel();

        $flash = get_flash_messages();

        $global_variables = $this->getGlobalVariables();
        $global_variables['requested_uri'] = $flash['requested_uri'] ?? '';
        $this->setVariables($global_variables);

        //Al final y no tras el parent, como en SystemStatus: aquí las variables globales se amplían
        //más abajo, y el ayudante tiene que recibirlas completas.
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        //Sus veintitrés vistas viven en el módulo. Lo del armazón —el layout y el recortador— lo
        //pide el ayudante, que no lleva este directorio.
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * Vista del listado de todos los usuarios
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function usersList(Request $req, Response $res)
    {
        $langGroup = UsersController::LANG_GROUP;
        $title = __($langGroup, 'Usuarios');

        set_title($title);

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('css/users-list.css'),
        ], 'css');
        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/users-forms.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        $this->render('panel/pages/list-usuarios', [
            'langGroup' => $langGroup,
            'title' => $title,
            'process_table' => self::routeName('datatables'),
        ]);
        $this->helpController->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTablesRequestUsers(Request $request, Response $response)
    {

        $filterStatus = $request->getQueryParam('with-status', null);
        $filterStatus = Validator::isInteger($filterStatus) ? (int) $filterStatus : null;
        $currentUser = new UsersModel($this->user->id);
        $disallowedTypes = $currentUser->getHigherPriorityTypes();
        $canModify = OrganizationMapper::canModifyAnyOrganization($currentUser->type);

        //POR MARCADOR, con `where_segment`. El `AND` del último criterio lo descarta
        //`WhereSegment::toString()`, que usa `toString(false)` para el que cierra. Ver T157.
        $whereItems = [
            new WhereItem('id', WhereItem::NOT_EQUAL_OPERATOR, $this->user->id, WhereItem::AND_OPERATOR),
            //No mostrar usuarios marcados eliminados
            new WhereItem('status', WhereItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED, WhereItem::AND_OPERATOR),
        ];

        if (is_array($disallowedTypes) && !empty($disallowedTypes)) {
            foreach ($disallowedTypes as $disallowedType) {
                $whereItems[] = new WhereItem('type', WhereItem::NOT_EQUAL_OPERATOR, $disallowedType, WhereItem::AND_OPERATOR);
            }
        }

        if (!$canModify) {
            $whereItems[] = new WhereItem('organization', WhereItem::EQUAL_OPERATOR, $currentUser->organization, WhereItem::AND_OPERATOR);
        }

        if ($filterStatus !== null) {
            $whereItems[] = new WhereItem('status', WhereItem::EQUAL_OPERATOR, $filterStatus, WhereItem::AND_OPERATOR);
        }

        $whereSegment = new WhereSegment($whereItems);

        $selectFields = UsersModel::fieldsToSelect();

        $columnsOrder = [
            UsersModel::TABLE . '.id',
            'names',
            'lastNames',
            'email',
            'username',
            'statusText',
            'typeName',
        ];
        $customOrder = [
            UsersModel::TABLE . '.id' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($element) {

                $buttons = [];

                $editLink = self::routeName('form-edit', ['id' => $element->id]);
                $editLink = is_string($editLink) ? $editLink : '';

                if (mb_strlen($editLink) > 0) {
                    $editText = '<i class="icon edit"></i>' . __(self::LANG_GROUP, 'Editar');
                    $editButton = "<a class='ui button green' href='{$editLink}'>{$editText}</a>";
                    $buttons[] = $editButton;
                }

                $columns = [];

                if (!Roles::roleExists($element->type)) {
                    //Si el rol no existe, se marca para eliminar
                    $userMapper = new UsersModel($element->id);
                    $userMapper->status = UsersModel::STATUS_USER_DELETED;
                    $userMapper->update();
                }

                $columns[] = $element->idPadding;
                $columns[] = $element->names;
                $columns[] = $element->lastNames;
                $columns[] = $element->email;
                $columns[] = $element->username;
                $columns[] = $element->statusText;
                $columns[] = $element->typeName;
                $columns[] = implode(' ', $buttons);

                return $columns;
            },
        ]);

        $rawData = DataTablesHelper::rawRows($result);

        foreach ($rawData as $index => $element) {

            $mapper = new UsersModel($element->id);

            $rawData[$index] = $this->render(
                'usuarios/utils/user-card',
                [
                    'mapper' => $mapper,
                    'data' => $result->getValue('data')[$index],
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );
        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function searchDropdown(Request $request, Response $response)
    {

        $RESULT_FULLNAME = 'RESULT_FULLNAME';
        $RESULT_FULLNAME_USERNAME = 'RESULT_FULLNAME_USERNAME';
        $RESULT_USERNAME = 'RESULT_USERNAME';

        $typeResult = $request->getQueryParam('typeResult', null);
        $typeResult = is_string($typeResult) && mb_strlen(trim($typeResult)) > 0 ? trim($typeResult) : $RESULT_FULLNAME;

        $search = $request->getQueryParam('search', null);
        $search = is_string($search) && mb_strlen(trim($search)) > 0 ? trim($search) : null;

        $ignoreTypes = $request->getQueryParam('ignoreTypes', null);
        $ignoreTypes = is_string($ignoreTypes) && mb_strlen(trim($ignoreTypes)) > 0 ? trim($ignoreTypes) : null;
        $ignoreTypes = is_string($ignoreTypes) ? explode(',', $ignoreTypes) : [];
        //`is_numeric` ANTES de `intval`: `intval('abc')` da 0, y 0 es `TYPE_USER_ROOT`.
        //`TYPES_USER_PRIORITY` es el único que enumera los siete tipos. Ver T153.
        $tiposConocidos = array_keys(UsersModel::TYPES_USER_PRIORITY);
        $ignoreTypes = array_values(array_filter(
            array_map('intval', array_filter($ignoreTypes, 'is_numeric')),
            static fn (int $tipo): bool => in_array($tipo, $tiposConocidos, true)
        ));

        $results = new \stdClass;
        $results->success = true;
        $results->results = [];

        $model = UsersModel::model();
        $model->select(UsersModel::fieldsToSelect());
        $model->orderBy('fullname ASC, username ASC, id DESC');

        //UN SOLO `where()`: `where()` SUSTITUYE el segmento, no acumula — y por eso el `having`
        //de la búsqueda borraba el de `status`. Ver T153.
        $criteriosWhere = [];
        if (!empty($ignoreTypes)) {
            $criteriosWhere[] = new WhereItem(
                'type',
                WhereItem::NOT_IN_OPERATOR,
                '(' . implode(', ', $ignoreTypes) . ')',
                WhereItem::AND_OPERATOR
            );
        }
        $criteriosWhere[] = new WhereItem('status', WhereItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED);
        $model->where(new WhereSegment($criteriosWhere));

        if ($search !== null) {

            //`having(string)` CONCATENA igual que `where(string)`. Por marcador. `fullname` es
            //un alias calculado en `fieldsToSelect()`, y por eso este criterio sigue en HAVING.
            $patron = mb_strtolower($search) . '%';
            $criteriosHaving = [];
            foreach (['fullname', 'username', 'firstname', 'secondname', 'secondLastname', 'firstLastname'] as $columna) {
                $criteriosHaving[] = new HavingItem(
                    "LOWER({$columna})",
                    HavingItem::LIKE_OPERATOR,
                    $patron,
                    HavingItem::OR_OPERATOR,
                    'LOWER(' . HavingItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
                );
            }

            $model->having(new HavingSegment($criteriosHaving));

            $model->execute();

        } else {
            $model->execute(false, 1, 15);
        }

        $resultsQuery = $model->result();
        $resultsQuery = is_array($resultsQuery) ? $resultsQuery : [];

        foreach ($resultsQuery as $element) {

            $elementResult = [
                'value' => $element->id,
                'name' => $element->fullname,
            ];

            if ($typeResult == $RESULT_FULLNAME) {

                $elementResult = [
                    'value' => $element->id,
                    'name' => $element->fullname,
                ];

            } elseif ($typeResult == $RESULT_FULLNAME_USERNAME) {

                $elementResult = [
                    'value' => $element->id,
                    'name' => "{$element->fullname} ({$element->username})",
                ];

            } elseif ($typeResult == $RESULT_USERNAME) {

                $elementResult = [
                    'value' => $element->id,
                    'name' => $element->username,
                ];

            }

            $results->results[] = $elementResult;
        }

        return $response->withJson($results);
    }

    /**
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function loginForm(Request $request, Response $response)
    {

        set_title(str_replace('.', '', __('general', 'loging')));

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* NProgress */
        import_nprogress();
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries();

        set_custom_assets([
            baseurl('statics/login-and-recovery/css/login.css'),
        ], 'css');

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/login.js'),
        ], 'js');

        $this->render('usuarios/login');

        return $response;
    }

    /**
     * Vista de selección de tipo de usuario para crear
     *
     * @return void
     */
    public function selectionTypeToCreate()
    {
        $types = UsersModel::getTypesUser();
        $currentUser = new UsersModel($this->user->id);

        set_custom_assets([
            baseurl('statics/login-and-recovery/css/select-type-user.css'),
        ], 'css');

        set_title(__(self::LANG_GROUP, 'Agregar usuario'));

        foreach ($types as $key => $display) {

            $type_encrypt = strrev($this->ofuscate) . ":$key:" . $this->ofuscate;
            $type_encrypt = BaseHashEncryption::encrypt($type_encrypt, $this->password);

            $hasAuthority = $currentUser->hasAuthorityOver($key);

            if ($hasAuthority) {

                $types[$key] = [
                    'link' => self::routeName('form-create', ['type' => $type_encrypt]),
                    'text' => $display,
                ];

            } else {

                unset($types[$key]);

            }

        }

        $this->helpController->render('panel/layout/header');
        $this->render('usuarios/select-type-user', [
            'types' => $types,
        ]);
        $this->helpController->render('panel/layout/footer');
    }

    /**
     * Vista de creación de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function formCreateByType(Request $req, Response $res)
    {

        set_title(__(self::LANG_GROUP, 'Agregar usuario'));

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/users-forms.js'),
        ], 'js');

        $type = $req->getAttribute('type', null);
        $type = BaseHashEncryption::decrypt($type, $this->password);
        $type = str_replace([
            strrev($this->ofuscate) . ':',
            ':' . $this->ofuscate,
        ], '', $type);

        //El {type} de la URL viene cifrado: si no descifra a un entero, no es un tipo y la ruta no existe.
        if (!Validator::isInteger($type)) {
            throw new NotFoundException($req, $res);
        }
        $type = (int) $type;

        $currentUser = new UsersModel($this->user->id);
        $hasAuthority = $currentUser->hasAuthorityOver($type);

        if (!$hasAuthority) {
            return throw403($req, [
                'url' => self::routeName('selection-create'),
            ]);
        }

        if (isset(UsersModel::getTypesUser()[$type])) {

            $status_options = UsersModel::statuses();
            unsetKeys(UsersModel::STATUSES_HIDDEN_ON_CREATION, $status_options);
            $status_options = array_flip($status_options);

            $data_form = [];
            $data_form['status_options'] = $status_options;

            $form = '';

            $formsByType = [
                UsersModel::TYPE_USER_ROOT => [
                    'view' => 'usuarios/form-by-type/create/root/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_ADMIN_GRAL => [
                    'view' => 'usuarios/form-by-type/create/admin-general/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_ADMIN_ORG => [
                    'view' => 'usuarios/form-by-type/create/admin-organization/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_GENERAL => [
                    'view' => 'usuarios/form-by-type/create/general/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_INSTITUCIONAL => [
                    'view' => 'usuarios/form-by-type/create/institucional/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_COMUNICACIONES => [
                    'view' => 'usuarios/form-by-type/create/comunicaciones/form',
                    'data' => array_merge($data_form, []),
                ],
            ];

            foreach ($formsByType as $formType => $formTypeData) {
                if ($type == $formType) {
                    $form = $this->render($formTypeData['view'], $formTypeData['data'], false);
                    break;
                }
            }

            $data = [];
            $data['form'] = $form;
            $data['typeName'] = UsersModel::getTypeUserName($type);
            $data['create'] = true;

            $this->helpController->render('panel/layout/header');
            $this->render('usuarios/form', $data);
            $this->helpController->render('panel/layout/footer');

            return $res;

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * Vista de edición de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function formEditByType(Request $req, Response $res)
    {
        set_title(__(self::LANG_GROUP, 'Editar usuario'));

        import_cropper();

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/users-forms.js'),
        ], 'js');

        $id = $req->getAttribute('id', null);
        $id = Validator::isInteger($id) ? (int) $id : null;

        $user = new UsersModel($id);

        $currentUser = new UsersModel($this->user->id);
        $hasAuthority = $currentUser->hasAuthorityOver($user->type);

        if (!is_null($user->id) && Roles::roleExists($user->type)) {

            //Su POST no deja tocar a un usuario de otra organización: el GET tampoco lo enseña.
            if (!$hasAuthority || !self::canManageUser($currentUser, $user)) {
                return throw403($req, [
                    'url' => self::routeName('list'),
                ]);
            }

            $is_same = $user->id == $this->user->id;

            if (!$is_same) {

                $type = $user->type;

                $status_options = UsersModel::statuses();
                unsetKeys(UsersModel::STATUSES_HIDDEN_ON_EDIT, $status_options);
                $status_options = array_flip($status_options);

                $data_form = [];
                $data_form['status_options'] = $status_options;
                $data_form['edit_user'] = $user;

                $form = '';

                $formsByType = [
                    UsersModel::TYPE_USER_ROOT => [
                        'view' => 'usuarios/form-by-type/edit/root/form',
                        'data' => array_merge($data_form, []),
                    ],
                    UsersModel::TYPE_USER_ADMIN_GRAL => [
                        'view' => 'usuarios/form-by-type/edit/admin-general/form',
                        'data' => array_merge($data_form, []),
                    ],
                    UsersModel::TYPE_USER_ADMIN_ORG => [
                        'view' => 'usuarios/form-by-type/edit/admin-organization/form',
                        'data' => array_merge($data_form, []),
                    ],
                    UsersModel::TYPE_USER_GENERAL => [
                        'view' => 'usuarios/form-by-type/edit/general/form',
                        'data' => array_merge($data_form, []),
                    ],
                    UsersModel::TYPE_USER_INSTITUCIONAL => [
                        'view' => 'usuarios/form-by-type/edit/institucional/form',
                        'data' => array_merge($data_form, []),
                    ],
                    UsersModel::TYPE_USER_COMUNICACIONES => [
                        'view' => 'usuarios/form-by-type/edit/comunicaciones/form',
                        'data' => array_merge($data_form, []),
                    ],
                ];

                foreach ($formsByType as $formType => $formTypeData) {
                    if ($type == $formType) {
                        $form = $this->render($formTypeData['view'], $formTypeData['data'], false);
                        break;
                    }
                }

                $data = [];
                $data['form'] = $form;
                $data['typeName'] = UsersModel::getTypeUserName($type);
                $data['edit_user'] = $user;
                $data['avatar'] = AvatarModel::getAvatar($user->id);
                $data['hasAvatar'] = !is_null($data['avatar']);
                $data['create'] = false;

                $this->helpController->render('panel/layout/header');
                $this->render('usuarios/form', $data);
                $this->helpController->render('panel/layout/footer');

                return $res;

            } else {

                throw new NotFoundException($req, $res);

            }

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * Vista de perfil de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function formProfileByType(Request $req, Response $res)
    {

        $onlyProfile = $req->getQueryParam('onlyProfile', 'no') === 'yes';
        $onlyImage = $req->getQueryParam('onlyImage', 'no') === 'yes';

        import_cropper();

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/users-forms.js'),
        ], 'js');

        $user = new UsersModel($this->user->id);

        if (!is_null($user->id)) {

            $type = $user->type;

            $data_form = [];
            $data_form['edit_user'] = $user;

            $form = '';

            $formsByType = [
                UsersModel::TYPE_USER_ROOT => [
                    'view' => 'usuarios/form-by-type/profile/root/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_ADMIN_GRAL => [
                    'view' => 'usuarios/form-by-type/profile/admin-general/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_ADMIN_ORG => [
                    'view' => 'usuarios/form-by-type/profile/admin-organization/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_GENERAL => [
                    'view' => 'usuarios/form-by-type/profile/general/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_INSTITUCIONAL => [
                    'view' => 'usuarios/form-by-type/profile/institucional/form',
                    'data' => array_merge($data_form, []),
                ],
                UsersModel::TYPE_USER_COMUNICACIONES => [
                    'view' => 'usuarios/form-by-type/profile/comunicaciones/form',
                    'data' => array_merge($data_form, []),
                ],
            ];

            foreach ($formsByType as $formType => $formTypeData) {
                if ($type == $formType) {
                    $form = $this->render($formTypeData['view'], $formTypeData['data'], false);
                    break;
                }
            }

            $data = [];
            $data['form'] = $form;
            $data['typeName'] = UsersModel::getTypeUserName($type);
            $data['edit_user'] = $user;
            $data['avatar'] = AvatarModel::getAvatar($user->id);
            $data['hasAvatar'] = !is_null($data['avatar']);
            $data['create'] = false;
            $data['onlyProfile'] = $onlyProfile;
            $data['onlyImage'] = $onlyImage;

            $this->helpController->render('panel/layout/header');
            $this->render('usuarios/form', $data);
            $this->helpController->render('panel/layout/footer');

            return $res;

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * Este método espera recibir por POST: [username,password]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function login(Request $request, Response $response)
    {

        $usernameParameter = new Parameter(
            'username',
            null,
            function ($value) {
                return is_string($value);
            },
            false,
            function ($value) {
                return trim(mb_strtolower($value));
            }
        );

        $passwordParameter = new Parameter(
            'password',
            null,
            function ($value) {
                return is_string($value);
            }
        );

        $twoFactorCode = new Parameter(
            'twoFactor',
            null,
            function ($value) {
                return is_string($value) || null;
            },
            true,
            function ($value) {
                return is_string($value) ? $value : '';
            }
        );

        $resolutionWidth = $request->getQueryParam('vp-w', null);
        $resolutionHeight = $request->getQueryParam('vp-h', null);
        $userAgent = $request->getQueryParam('user-agent', null);
        $extraDataLog = [
            'dimensions' => [
                'w' => !is_null($resolutionWidth) ? (int) $resolutionWidth : null,
                'h' => !is_null($resolutionHeight) ? (int) $resolutionHeight : null,
            ],
            'userAgent' => !is_null($userAgent) ? base64_decode($userAgent) : ($_SERVER['HTTP_USER_AGENT'] ?? null),
        ];

        $isExternalLogin = $request->getHeaderLine('isExternalLogin') === 'yes';

        $expectedParameters = new Parameters([
            $usernameParameter,
            $passwordParameter,
            $twoFactorCode,
            new Parameter(
                'overwriteSession',
                false,
                function ($value) {
                    return is_bool($value) || $value === 'yes';
                },
                true,
                function ($value) {
                    return $value === true || $value === 'yes';
                }
            ),
        ]);

        $inputData = $request->getParsedBody();
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        $resultOperation = new ResultOperations([], 'Login', '');
        $resultOperation->setSingleOperation(true);
        $resultOperation->setValues([
            'auth' => false,
            'isAuth' => false,
            'token' => '',
            'error' => self::NO_ERROR,
            'user' => '',
            'message' => '',
            'userData' => [],
        ]);

        //Se verifica si ya existe una sesión activa
        $JWT = SessionToken::getJWTReceived();

        try {

            $expectedParameters->validate();

            $overwriteSession = $expectedParameters->getValue('overwriteSession');

            if (!SessionToken::isActiveSession($JWT) || $overwriteSession) {

                //Se selecciona un elemento que concuerde con el usuario
                //Sin escapeString(): where() con array ya liga el valor, y escapar lo alteraba (ADR 0009).
                $username = $usernameParameter->getValue();
                $password = $passwordParameter->getValue();

                $user = $this->model->select()->where([
                    'username' => [
                        '=' => $username,
                        'and_or' => 'OR',
                    ],
                    'email' => [
                        '=' => $username,
                    ],
                ])->row();
                $resultOperation->setValue('user', $username);

                //Verificación de existencia
                if ($user instanceof \stdClass) {

                    $user->status = (int) $user->status;
                    $userMapper = new UsersModel($user->id);
                    $requireAprobation = UsersModel::REQUIRE_APPROBATION_FOR_LOGIN;
                    $statusOk = $requireAprobation ? in_array($user->status, UsersModel::STATUSES_OK_FOR_LOGIN_ON_REQUIRE_APPROBATION) : in_array($user->status, UsersModel::STATUSES_OK_FOR_LOGIN_ON_NO_REQUIRE_APPROBATION);

                    //Verificar status
                    if ($statusOk) {

                        //Verificar status de la organización si aplica
                        $organizationID = $user->organization;
                        $organizationMapper = $organizationID !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($organizationID, 'id')) : null;
                        if ($organizationMapper == null || in_array($organizationMapper->status, OrganizationMapper::STATUSES_FOR_LOGIN)) {

                            if ($isExternalLogin) {
                                if (!in_array($user->type, UsersModel::TYPES_WITH_EXTERNAL_LOGIN)) {
                                    $resultOperation->setValue('error', self::NO_EXTERNAL_LOGIN_AVAILABLE);
                                    $resultOperation->setValue('message', __(self::LANG_GROUP, 'El usuario no está habilitado para usar la este método de inicio de sesión'));
                                    return $response->withJson($resultOperation->getValues());
                                }
                            }

                            $otpIsValid = OTPHandler::checkValidityOTP($password, $username);
                            if (password_verify($password, $user->password) || $otpIsValid) {

                                $require2FA = OTPHandler::isEnabled2FA($userMapper->id) && OTPHandler::wasViewedCurrentUserQRData($userMapper->id);
                                //LÍMITE DEL SEGUNDO FACTOR (otp_security): con el usuario o la IP bloqueados, 429 antes de mirar el código.
                                $secondsToUnlock = $require2FA ? OTPRateLimiter::secondsToUnlock($user->username, OTPRateLimiter::clientIP()) : 0;
                                if ($secondsToUnlock > 0) {
                                    return OTPRateLimiter::lockedResponse($response, OTPRateLimiter::VIA_LOGIN_TOTP, $user->username, $secondsToUnlock);
                                }
                                $twoFactorCodeValue = $twoFactorCode->getValue();
                                $twoFactorCodeValue = is_string($twoFactorCodeValue) ? $twoFactorCodeValue : '';
                                $twoFactorIsValid = OTPHandler::checkValidityTOTP($twoFactorCodeValue, $username);

                                if (!$require2FA || $twoFactorIsValid) {

                                    OTPHandler::toExpireOTP($username);

                                    $resultOperation->setValue('auth', true);

                                    //EL TOKEN DICE QUIÉN, NUNCA QUÉ ES: tipo, estado y organización salen de la fila
                                    //en cada petición; dentro del token se quedarían viejos y nadie lo sabría.
                                    $resultOperation->setValue('token', SessionToken::generateToken([
                                        'id' => $user->id,
                                    ], null, null, get_config('check_aud_on_auth')));

                                    //Valores de usuario devueltos, FILTRADOS POR LISTA BLANCA: se toma lo declarado,
                                    //no se quita lo que sobra. Así una columna nueva no viaja sola al navegador.
                                    $userReadable = $userMapper->humanReadable();
                                    $userLoginData = [];
                                    foreach (self::LOGIN_USER_DATA_FIELDS as $campo) {
                                        if (array_key_exists($campo, $userReadable)) {
                                            $userLoginData[$campo] = $userReadable[$campo];
                                        }
                                    }
                                    $userLoginData['misc'] = [
                                        'avatar' => AvatarModel::getAvatar($userMapper->id),
                                    ];

                                    foreach ($userReadable as $k => $i) {
                                        if (str_contains($k, 'META:')) {
                                            $userLoginData['misc'][str_replace('META:', '', $k)] = $i;
                                        }
                                    }

                                    $resultOperation->setValue('userData', $userLoginData);

                                    $this->mapper->resetAttempts($user->id);

                                    LoginAttemptsModel::addLogin(
                                        (int) $user->id,
                                        $user->username,
                                        true,
                                        '',
                                        $extraDataLog
                                    );

                                } else {

                                    $resultOperation->setValue('error', self::INVALID_TWO_FACTOR_CODE);
                                    $resultOperation->setValue('message', $this->getMessage(self::INVALID_TWO_FACTOR_CODE));
                                    //El fallo del segundo factor no suma failedAttempts: lo cuenta el límite de OTPRateLimiter, por su vía.
                                    LoginAttemptsModel::addLogin(
                                        (int) $user->id,
                                        $user->username,
                                        false,
                                        $resultOperation->getValue('message'),
                                        $extraDataLog + [OTPRateLimiter::EXTRA_DATA_VIA => OTPRateLimiter::VIA_LOGIN_TOTP]
                                    );

                                }

                            } else {

                                $resultOperation->setValue('error', self::INCORRECT_PASSWORD);
                                $resultOperation->setValue('message', $this->getMessage(self::INCORRECT_PASSWORD));
                                LoginAttemptsModel::addLogin(
                                    (int) $user->id,
                                    $user->username,
                                    false,
                                    $resultOperation->getValue('message'),
                                    $extraDataLog
                                );

                                if ($user->failedAttempts >= self::MAX_ATTEMPTS) {

                                    $this->mapper->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $user->id);
                                    $resultOperation->setValue('error', self::BLOCKED_FOR_ATTEMPTS);
                                    $resultOperation->setValue('message', vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]));

                                } else {

                                    $attempts = $this->mapper->updateAttempts($user->id);

                                    if ($attempts >= self::MAX_ATTEMPTS) {

                                        $this->mapper->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $user->id);
                                        $resultOperation->setValue('error', self::BLOCKED_FOR_ATTEMPTS);
                                        $resultOperation->setValue('message', vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]));

                                    }
                                }

                            }

                        } else {

                            $errorType = self::ORGANIZATION_IS_NOT_ACTIVE;
                            $errorTypeMessage = vsprintf($this->getMessage(self::ORGANIZATION_IS_NOT_ACTIVE), [$organizationMapper->name]);

                            $resultOperation->setValue('error', $errorType);
                            $resultOperation->setValue('message', $errorTypeMessage);

                            LoginAttemptsModel::addLogin(
                                $user->id,
                                $username,
                                false,
                                $resultOperation->getValue('message'),
                                $extraDataLog
                            );

                        }

                    } else {

                        if ($user->status == UsersModel::STATUS_USER_ATTEMPTS_BLOCK) {

                            $errorType = self::BLOCKED_FOR_ATTEMPTS;
                            $errorTypeMessage = vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]);

                        } elseif ($user->status == UsersModel::STATUS_USER_APPROVED_PENDING && $requireAprobation) {

                            $errorType = self::APPROVED_PENDING;
                            $errorTypeMessage = vsprintf($this->getMessage(self::APPROVED_PENDING), [$user->username]);

                        } else {

                            $errorType = self::INACTIVE_USER;
                            $errorTypeMessage = vsprintf($this->getMessage(self::INACTIVE_USER), [$user->username]);

                        }

                        $resultOperation->setValue('error', $errorType);
                        $resultOperation->setValue('message', $errorTypeMessage);

                        LoginAttemptsModel::addLogin(
                            null,
                            $username,
                            false,
                            $resultOperation->getValue('message'),
                            $extraDataLog
                        );

                    }

                } else {

                    $resultOperation->setValue('error', self::USER_NO_EXISTS);
                    $resultOperation->setValue('message', vsprintf($this->getMessage(self::USER_NO_EXISTS), [$username]));
                    LoginAttemptsModel::addLogin(
                        null,
                        $username,
                        false,
                        $resultOperation->getValue('message'),
                        $extraDataLog
                    );

                }
            } else {
                $resultOperation->setValue('auth', false);
                $resultOperation->setValue('isAuth', true);
                $resultOperation->setValue('error', self::ACTIVE_SESSION);
                $resultOperation->setValue('message', $this->getMessage(self::ACTIVE_SESSION));
            }

        } catch (MissingRequiredParameterException $e) {

            $resultOperation->setValue('error', self::MISSING_OR_UNEXPECTED_PARAMS);
            $resultOperation->setValue('message', $e->getMessage());
            log_exception($e);
        } catch (ParsedValueException $e) {

            $resultOperation->setValue('error', self::GENERIC_ERROR);
            $resultOperation->setValue('message', __(self::LANG_GROUP, 'Ha ocurrido un error desconocido con los valores ingresados.'));
            log_exception($e);
        } catch (InvalidParameterValueException $e) {

            $resultOperation->setValue('error', self::GENERIC_ERROR);
            $resultOperation->setValue('message', $e->getMessage());
            log_exception($e);
        }

        return $response->withJson($resultOperation->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function verifySession(Request $request, Response $response)
    {
        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Verificar sesión'), '');
        $resultOperation->setSingleOperation(true);
        $resultOperation->setValues([
            'auth' => false,
            'isAuth' => false,
        ]);

        //Se verifica si ya existe una sesión activa
        $JWT = SessionToken::getJWTReceived();
        $isActiveSession = SessionToken::isActiveSession($JWT, null);
        $resultOperation->setValue('isAuth', $isActiveSession);
        //Verificar status de la organización si aplica
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null) {
            $organizationID = $currentUser->organization;
            $organizationMapper = $organizationID !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($organizationID, 'id')) : null;
            if ($organizationMapper != null && !in_array($organizationMapper->status, OrganizationMapper::STATUSES_FOR_LOGIN)) {
                $resultOperation->setValue('isAuth', false);
            }
        }
        return $response->withJson($resultOperation->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteAccount(Request $request, Response $response)
    {
        $currentUser = getLoggedFrameworkUser();
        $result = [
            'success' => false,
        ];
        if ($currentUser !== null) {
            $mapper = $currentUser->getMapper();
            $mapper->status = UsersModel::STATUS_USER_DELETED;
            $result['success'] = $mapper->update();
        }
        return $response->withJSON($result);
    }

    /**
     * Un administrador cierra todas las sesiones de un usuario (ADR 0026). No emite token.
     *
     * LISTA BLANCA, no negra: quien pide tiene que ser root o administrador general, comprobado AQUÍ además de en la
     * ruta; y si el destino es root, quien pide también. Un rol nuevo no hereda el poder de echar a root.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function revokeUserSessions(Request $request, Response $response)
    {
        $name = __(self::LANG_GROUP, 'Cerrar sesiones');
        $fail = function (int $status, string $message) use ($response, $name): Response {
            return $response->withStatus($status)->withJson([
                'success' => false,
                'name' => $name,
                'message' => $message,
            ]);
        };

        $currentUser = getLoggedFrameworkUserOrFail();
        $currentType = (int) $currentUser->type;
        if (!in_array($currentType, self::CAN_REVOKE_USER_SESSIONS, true)) {
            return $fail(403, __(self::LANG_GROUP, 'No tiene permisos para cerrar las sesiones de otro usuario.'));
        }

        $body = $request->getParsedBody();
        $id = is_array($body) && array_key_exists('id', $body) ? $body['id'] : null;
        if (!Validator::isInteger($id) || (int) $id < 1) {
            return $fail(400, __(self::LANG_GROUP, 'El usuario indicado no es válido.'));
        }

        $target = new UsersModel((int) $id);
        if ($target->id === null) {
            return $fail(404, __(self::LANG_GROUP, 'El usuario no existe.'));
        }

        if ((int) $target->type === UsersModel::TYPE_USER_ROOT && $currentType !== UsersModel::TYPE_USER_ROOT) {
            return $fail(403, __(self::LANG_GROUP, 'Solo un superadministrador puede cerrar las sesiones de otro superadministrador.'));
        }

        if (!(new UsersModel())->revokeSessions((int) $target->id)) {
            return $fail(500, __(self::LANG_GROUP, 'No se pudieron cerrar las sesiones.'));
        }

        LogsMapper::addLog(LogsMapper::MSG_REVOKE_USER_SESSIONS, [
            '%username%' => $currentUser->username,
            '%target%' => $target->username,
        ], 'id', (string) $target->id, UsersModel::TABLE);

        return $response->withJson([
            'success' => true,
            'name' => $name,
            'message' => strReplaceTemplate(__(self::LANG_GROUP, 'Se cerraron todas las sesiones de %s.'), ['%s' => $target->username]),
        ]);
    }

    /**
     * Si se puede ofrecer el cierre de sesiones de este usuario: la misma regla de root que aplica la ruta.
     *
     * @param int $targetType
     * @return bool
     */
    public static function canRevokeSessionsOf(int $targetType): bool
    {
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser === null || !self::allowedRoute('revoke-sessions-request')) {
            return false;
        }
        $currentType = (int) $currentUser->type;
        return in_array($currentType, self::CAN_REVOKE_USER_SESSIONS, true)
            && ($targetType !== UsersModel::TYPE_USER_ROOT || $currentType === UsersModel::TYPE_USER_ROOT);
    }

    /**
     * Registra un usuario nuevo desde el panel: el borde de users-register-request. Aplica la política del formulario
     * de alta (canCreateUser) y delega en el núcleo.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function register(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $type = $body['type'] ?? null;
        $status = $body['status'] ?? null;
        $organization = $body['organization'] ?? null;
        //Sin tipo o estado enteros no hay alta: la validación del núcleo la rechaza con su mensaje.
        if (Validator::isInteger($type) && Validator::isInteger($status)) {
            $organization = Validator::isInteger($organization) ? (int) $organization : null;
            if (!self::canCreateUser(new UsersModel(getLoggedFrameworkUserOrFail()->id), (int) $type, $organization, (int) $status)) {
                return throw403($request);
            }
        }
        return $this->createUserFromRequest($request, $response);
    }

    /**
     * El núcleo del alta: valida, comprueba duplicados, guarda y responde.
     *
     * ATENCIÓN: no comprueba quién da el alta. Cada borde HTTP aplica su política (register() la del panel,
     * APIController la del alta pública); ninguna ruta debe apuntar aquí.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function createUserFromRequest(Request $request, Response $response): Response
    {

        $operation_name = __(self::LANG_GROUP, 'Creación de usuario');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $parametersExcepted = new Parameters([
            new Parameter(
                'username',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return mb_strtolower($value);
                }
            ),
            new Parameter(
                'email',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return mb_strtolower($value);
                }
            ),
            new Parameter(
                'password',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
            new Parameter(
                'password2',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
            new Parameter(
                'firstname',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'secondname',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'first_lastname',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'second_lastname',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'type',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                }
            ),
            new Parameter(
                'status',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                }
            ),
            new Parameter(
                'organization',
                null,
                function ($value) {
                    return Validator::isInteger($value) || is_null($value);
                },
                true,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : $value;
                }
            ),
        ]);

        $parametersExcepted->setInputValues($request->getParsedBody());

        $message_create = __(self::LANG_GROUP, 'Usuario creado.');
        $message_duplicate_email = __(self::LANG_GROUP, 'Ya existe un usuario con ese email.');
        $message_duplicate_user = __(self::LANG_GROUP, 'Ya existe un usuario con ese nombre de usuario.');
        $message_duplicate_all = __(self::LANG_GROUP, 'Ya existe un usuario con ese email y nombre de usuario.');
        $message_password_unmatch = __(self::LANG_GROUP, 'Las contraseñas no coinciden.');
        $message_organization_required = __(self::LANG_GROUP, 'Debe seleccionar una organización.');
        $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');

        try {

            $parametersExcepted->validate();

            $username = $parametersExcepted->getValue('username');
            $email = $parametersExcepted->getValue('email');
            $password = $parametersExcepted->getValue('password');
            $password2 = $parametersExcepted->getValue('password2');
            $firstname = $parametersExcepted->getValue('firstname');
            $secondname = $parametersExcepted->getValue('secondname');
            $first_lastname = $parametersExcepted->getValue('first_lastname');
            $second_lastname = $parametersExcepted->getValue('second_lastname');
            $type = $parametersExcepted->getValue('type');
            $organization = $parametersExcepted->getValue('organization');
            $status = $parametersExcepted->getValue('status');

            $username_duplicate = UsersModel::isDuplicateUsername($username);
            $email_duplicate = UsersModel::isDuplicateEmail($email);

            $password_match = $password == $password2;

            //Validar que la organización sea obligatoria si no está en UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION
            if (!in_array($type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)) {
                if (!Validator::isInteger($organization)) {
                    $result
                        ->setMessage($message_organization_required);
                    return $response->withJson($result);
                }
            }

            if (!$username_duplicate && !$email_duplicate && $password_match) {

                $userMapper = new UsersModel();

                $userMapper->username = $username;
                $userMapper->email = $email;
                $userMapper->password = password_hash($password, \PASSWORD_DEFAULT);
                $userMapper->firstname = $firstname;
                $userMapper->secondname = $secondname;
                $userMapper->firstLastname = $first_lastname;
                $userMapper->secondLastname = $second_lastname;
                $userMapper->type = $type;
                $userMapper->status = $status;
                $userMapper->failedAttempts = 0;
                $userMapper->createdAt = new \DateTime();
                $userMapper->modifiedAt = $userMapper->createdAt;

                //Asignar la organización si no está en UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION
                if (!in_array($type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)) {
                    $userMapper->organization = $organization;
                }

                $success = $userMapper->save();

                if ($success) {

                    //El perfil se crea aquí, no al leerlo: la creación vive en los caminos de escritura.
                    if ($userMapper->id !== null) {
                        UserProfileMapper::createProfile((int) $userMapper->id);
                    }

                    $result->setValue('reload', true);

                    $result
                        ->setMessage($message_create);
                    //La operación se creó con este nombre en el constructor: si faltara, es un fallo de verdad.
                    ($result->operation($operation_name) ?? throw new \LogicException('La operación del resultado no existe.'))->setSuccess(true);

                } else {

                    $result
                        ->setMessage($message_unknow_error);

                }

            } else {

                if (!$password_match) {

                    $result
                        ->setMessage($message_password_unmatch);

                } elseif ($username_duplicate && $email_duplicate) {

                    $result
                        ->setMessage($message_duplicate_all);

                } elseif ($username_duplicate) {

                    $result
                        ->setMessage($message_duplicate_user);

                } elseif ($email_duplicate) {

                    $result
                        ->setMessage($message_duplicate_email);

                }

            }

        } catch (\PDOException $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        } catch (MissingRequiredParameterException | InvalidParameterValueException | ParsedValueException $e) {

            $result->setMessage($e->getMessage());
            log_exception($e);

        } catch (\Exception $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        }

        return $response->withJson($result);

    }

    /**
     * Un POST no concede más que su GET (pendientes.md 380): lo que el formulario de edición no deja hacer, la
     * petición tampoco. El permiso del formulario, la autoridad sobre el tipo, sus estados y, sin poder sobre todas
     * las organizaciones, solo usuarios de la propia y sin moverlos.
     *
     * @param UsersModel $actor
     * @param UsersModel $target
     * @param int|null $organization La que trae la petición
     * @param int|null $status El que trae la petición
     * @return bool
     */
    private static function canEditOtherUser(UsersModel $actor, UsersModel $target, ?int $organization, ?int $status): bool
    {
        if (!Roles::hasPermissions('users-form-edit', (int) $actor->type, true) || !Roles::roleExists((int) $target->type) || !$actor->hasAuthorityOver((int) $target->type)) {
            return false;
        }
        //Lista blanca: los estados que ofrece el formulario de edición, no «todo menos los ocultos».
        if ($status !== null && !in_array($status, array_diff(array_keys(UsersModel::STATUSES), UsersModel::STATUSES_HIDDEN_ON_EDIT), true)) {
            return false;
        }
        if (!OrganizationMapper::canModifyAnyOrganization((int) $actor->type)) {
            $own = $actor->organization;
            if ($own === null || $target->organization != $own || ($organization !== null && $organization != $own)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Si el actor puede gestionar al destino: es él mismo, o podría abrir su formulario de edición (la regla de
     * canEditOtherUser sin estado ni organización pedidos). La usan el GET de edición y el avatar (pendientes.md 382).
     *
     * @param UsersModel $actor
     * @param UsersModel $target
     * @return bool
     */
    public static function canManageUser(UsersModel $actor, UsersModel $target): bool
    {
        if ($actor->id !== null && $actor->id == $target->id) {
            return true;
        }
        return $target->id !== null && self::canEditOtherUser($actor, $target, null, null);
    }

    /**
     * Lo mismo para el alta: lo que ofrece el formulario de creación de ese tipo.
     *
     * @param UsersModel $actor
     * @param int $type
     * @param int|null $organization
     * @param int $status
     * @return bool
     */
    private static function canCreateUser(UsersModel $actor, int $type, ?int $organization, int $status): bool
    {
        if (!Roles::hasPermissions('users-form-create', (int) $actor->type, true) || !isset(UsersModel::getTypesUser()[$type]) || !$actor->hasAuthorityOver($type)) {
            return false;
        }
        //Lista blanca: los estados que ofrece el formulario de alta.
        if (!in_array($status, array_diff(array_keys(UsersModel::STATUSES), UsersModel::STATUSES_HIDDEN_ON_CREATION), true)) {
            return false;
        }
        if (!in_array($type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION) && !OrganizationMapper::canModifyAnyOrganization((int) $actor->type)) {
            $own = $actor->organization;
            if ($own === null || $organization != $own) {
                return false;
            }
        }
        return true;
    }

    /**
     * Edita un usuario
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function edit(Request $request, Response $response)
    {

        $operation_name = __(self::LANG_GROUP, 'Edición de usuario');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $parametersExcepted = new Parameters([
            new Parameter(
                'id',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                }
            ),
            new Parameter(
                'username',
                null,
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return mb_strtolower($value);
                }
            ),
            new Parameter(
                'email',
                null,
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return mb_strtolower($value);
                }
            ),
            new Parameter(
                'is_profile',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? $value == 'yes' : $value === true;
                }
            ),
            new Parameter(
                'current-password',
                null,
                function ($value) {
                    return is_string($value) || is_null($value);
                },
                true
            ),
            new Parameter(
                'password',
                null,
                function ($value) {
                    return is_string($value) || is_null($value);
                },
                true
            ),
            new Parameter(
                'password2',
                null,
                function ($value) {
                    return is_string($value) || is_null($value);
                },
                true
            ),
            new Parameter(
                'firstname',
                null,
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'secondname',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'first_lastname',
                null,
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'second_lastname',
                '',
                function ($value) {
                    return is_string($value);
                },
                true,
                function ($value) {
                    return ucwords($value);
                }
            ),
            new Parameter(
                'status',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true
            ),
            new Parameter(
                'organization',
                null,
                function ($value) {
                    return Validator::isInteger($value) || is_null($value);
                },
                true,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : $value;
                }
            ),
        ]);

        $parametersExcepted->setInputValues($request->getParsedBody());

        $message_edit = __(self::LANG_GROUP, 'Usuario editado.');
        $message_duplicate_email = __(self::LANG_GROUP, 'Ya existe un usuario con ese email.');
        $message_duplicate_user = __(self::LANG_GROUP, 'Ya existe un usuario con ese nombre de usuario.');
        $message_duplicate_all = __(self::LANG_GROUP, 'Ya existe un usuario con ese email y nombre de usuario.');
        $message_password_unmatch = __(self::LANG_GROUP, 'Las contraseñas no coinciden.');
        $message_password_wrong = __(self::LANG_GROUP, 'La contraseña es errónea.');
        $message_organization_required = __(self::LANG_GROUP, 'Debe seleccionar una organización.');
        $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');

        try {

            $parametersExcepted->validate();

            $isProfile = $parametersExcepted->getValue('is_profile');

            $parametersExcepted->getParameter('username')->setOptional($isProfile);
            $parametersExcepted->getParameter('email')->setOptional($isProfile);
            $parametersExcepted->getParameter('firstname')->setOptional($isProfile);
            $parametersExcepted->getParameter('first_lastname')->setOptional($isProfile);
            $parametersExcepted->getParameter('status')->setOptional($isProfile);

            $parametersExcepted->validate();

            $id = $parametersExcepted->getValue('id');
            $username = $parametersExcepted->getValue('username');
            $email = $parametersExcepted->getValue('email');
            $currentPassword = $parametersExcepted->getValue('current-password');
            $password = $parametersExcepted->getValue('password');
            $password2 = $parametersExcepted->getValue('password2');
            $firstname = $parametersExcepted->getValue('firstname');
            $secondname = $parametersExcepted->getValue('secondname');
            $first_lastname = $parametersExcepted->getValue('first_lastname');
            $second_lastname = $parametersExcepted->getValue('second_lastname');
            $status = $parametersExcepted->getValue('status');
            $organization = $parametersExcepted->getValue('organization');

            $userMapper = new UsersModel($id);

            if ($userMapper->id !== null) {
                $actor = new UsersModel(getLoggedFrameworkUserOrFail()->id);
                if ($userMapper->id == $actor->id) {
                    //Uno mismo, por la vía que sea, es su perfil: el estado no cambia y la contraseña pide la actual.
                    $isProfile = true;
                    $status = null;
                } elseif ($isProfile || !self::canEditOtherUser($actor, $userMapper, $organization, $status)) {
                    return throw403($request);
                }
            }
            if ($username === null) {
                $username = $userMapper->username;
            }
            if ($email === null) {
                $email = $userMapper->email;
            }
            $username_duplicate = UsersModel::isDuplicateUsername($username, $id);
            $email_duplicate = UsersModel::isDuplicateEmail($email, $id);

            $change_password = !is_null($password);
            $password_match = $change_password ? $password == $password2 : true;
            $password_ok = true;

            //Validar que la organización sea obligatoria si no está en UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION
            if (!in_array($userMapper->type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)) {
                if (!Validator::isInteger($organization) && !$isProfile) {
                    $result
                        ->setMessage($message_organization_required);
                    return $response->withJson($result);
                }
            }

            if ($userMapper->id !== null) {

                if ($isProfile && $change_password) {
                    $password_ok = password_verify($currentPassword, $userMapper->password);
                }

                if (!$username_duplicate && !$email_duplicate && $password_match && $password_ok) {

                    $userMapper->username = $username;
                    $userMapper->email = $email;

                    if ($firstname !== null) {
                        $userMapper->firstname = $firstname;
                    }
                    if ($secondname !== null) {
                        $userMapper->secondname = $secondname;
                    }
                    if ($first_lastname !== null) {
                        $userMapper->firstLastname = $first_lastname;
                    }
                    if ($second_lastname !== null) {
                        $userMapper->secondLastname = $second_lastname;
                    }
                    if ($status !== null) {
                        $userMapper->status = $status;
                    }
                    if ($organization !== null && (!in_array($userMapper->type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)) && !$isProfile) {
                        $userMapper->organization = $organization;
                    }
                    $userMapper->modifiedAt = new \DateTime();

                    if ($change_password) {
                        $userMapper->password = password_hash($password, \PASSWORD_DEFAULT);
                    }

                    $success = $userMapper->update();

                    if ($success) {

                        $result->setValue('reload', true);

                        $result
                            ->setMessage($message_edit);
                        //La operación se creó con este nombre en el constructor: si faltara, es un fallo de verdad.
                        ($result->operation($operation_name) ?? throw new \LogicException('La operación del resultado no existe.'))->setSuccess(true);

                    } else {

                        $result
                            ->setMessage($message_unknow_error);

                    }

                } else {

                    if (!$password_ok) {

                        $result
                            ->setMessage($message_password_wrong);

                    } elseif (!$password_match) {

                        $result
                            ->setMessage($message_password_unmatch);

                    } elseif ($username_duplicate && $email_duplicate) {

                        $result
                            ->setMessage($message_duplicate_all);

                    } elseif ($username_duplicate) {

                        $result
                            ->setMessage($message_duplicate_user);

                    } elseif ($email_duplicate) {

                        $result
                            ->setMessage($message_duplicate_email);

                    }

                }

            } else {

                $result
                    ->setMessage(__(self::LANG_GROUP, 'No existe el usuario que intenta modificar.'));

            }

        } catch (\PDOException $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        } catch (MissingRequiredParameterException | InvalidParameterValueException | ParsedValueException $e) {

            $result->setMessage($e->getMessage());
            log_exception($e);

        } catch (\Exception $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        }

        return $response->withJson($result);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function all(Request $request, Response $response)
    {

        $expectedParameters = new Parameters([
            new Parameter(
                'page',
                1,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'per_page',
                10,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'type',
                null,
                function ($value) {
                    return Validator::isInteger($value) || $value == 'ANY';
                },
                true,
                function ($value) {
                    return $value != 'ANY' ? (int) $value : $value;
                }
            ),
            new Parameter(
                'ignore',
                [],
                function ($value) {
                    return is_array($value) || Validator::isInteger($value);
                },
                true,
                function ($value) {

                    if (is_scalar($value)) {
                        $value = [$value];
                    }

                    $value = is_array($value) ? $value : [];

                    $value = array_filter($value, function ($i) {

                        return Validator::isInteger($i);

                    });
                    $value = array_map(function ($i) {

                        return (int) $i;

                    }, $value);

                    return $value;

                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $page
         * @var int $perPage
         * @var int $type
         * @var int[] $ignore
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $type = $expectedParameters->getValue('type');
        $type = $type === 'ANY' ? null : $type;
        $ignore = $expectedParameters->getValue('ignore');

        $result = self::_all($page, $perPage, $type, $ignore);

        return $response->withJson($result);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param int $type
     * @param int[] $ignore
     * @return PaginationResult
     */
    public static function _all(int $page = 1, int $perPage = 10, ?int $type = null, array $ignore = [])
    {
        $table = 'pcsphp_users';
        //LISTA BLANCA, la misma del login: una columna nueva de la tabla NO viaja sola. Antes se
        //quitaba solo `password` y salían estado, intentos fallidos, fechas y la marca de revocación.
        $columns = self::LOGIN_USER_DATA_FIELDS;
        $fields = array_map(fn ($f) => "{$table}.{$f}", $columns);

        $whereString = null;
        $where = [
            $type !== null ? "{$table}.type = {$type}" : '',
        ];

        $where = array_filter($where, function ($i) {
            return mb_strlen($i) > 0;
        });

        if (!empty($ignore)) {
            $ignore = implode(', ', array_map(fn ($i) => (int) $i, $ignore));
            $where[] = (!empty($where) ? ' AND ' : '') . "{$table}.id NOT IN ($ignore)";
        }

        if (!empty($where)) {
            $whereString = implode('', $where);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";
        $sqlCount = "SELECT COUNT({$table}.id) AS total FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
            $sqlCount .= " WHERE {$whereString}";
        }

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $pagination = $pageQuery->getPagination();

        return $pagination;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function encodeURL($data)
    {
        return BaseHashEncryption::encrypt(StringManipulate::jsonEncode($data));
    }

    /**
     * @param string $url
     * @return mixed
     */
    public function decodeURL(string $url)
    {
        return StringManipulate::jsonDecode(BaseHashEncryption::decrypt($url));
    }

    /**
     * Devuelve el mensaje asociado del grupo 'users' configurado en el archivo lang/*.php
     *
     * @param mixed $message
     * @return string
     */
    public function getMessage($message)
    {
        return __('users', $message);
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
        $users = self::class;
        $recovery = RecoveryPasswordController::class;
        $users_problems = UserProblemsController::class;
        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        //EL LISTADO DE USUARIOS NO LO VE `general` (PO, 2026-09-25). Sus dos fuentes de datos van con la pantalla
        //`users-list`, que solo abren root y administrador general por las listas de `roles.php`.
        $listadoDeUsuarios = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_INSTITUCIONAL,
            UsersModel::TYPE_USER_COMUNICACIONES,
        ];

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Usuarios
        $group->register([
            //Listado de usuarios
            new Route(
                "{$startRoute}list[/]",
                $users . ':usersList',
                'users-list',
                'GET',
                true
            ),
            //Vista de selección de tipo para creación
            new Route(
                "{$startRoute}select-type/add[/]",
                $users . ':selectionTypeToCreate',
                'users-selection-create',
                'GET',
                true
            ),
            //Vista de formulario para creación por tipo
            new Route(
                "{$startRoute}add/type/{type}[/]",
                $users . ':formCreateByType',
                'users-form-create',
                'GET',
                true
            ),
            //Vista de formulario para edición por tipo
            new Route(
                "{$startRoute}edit/{id}[/]",
                $users . ':formEditByType',
                'users-form-edit',
                'GET',
                true
            ),
            //Vista de perfil de usuario
            new Route(
                "{$startRoute}profile[/]",
                $users . ':formProfileByType',
                'users-form-profile',
                'GET',
                true
            ),

            //Datatables
            new Route(
                "{$startRoute}datatables[/]",
                $users . ':dataTablesRequestUsers',
                'users-datatables',
                'GET',
                true,
                null,
                $listadoDeUsuarios
            ),
            //Search Dropdown
            new Route(
                "{$startRoute}search-dropdown[/]",
                $users . ':searchDropdown',
                'users-search-dropdown',
                'GET',
                true,
                null,
                $allRoles
            ),

            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $users . ':all',
                'users-ajax-all',
                'GET',
                true,
                null,
                $listadoDeUsuarios
            ),
        ]);

        //Inicio, cierre, registro y edición
        $group->register([
            new Route(
                "{$startRoute}login[/]",
                $users . ':loginForm',
                'users-form-login'
            ),
        ]);

        //Problemas
        $group->register([
            new Route(
                "{$startRoute}recovery[/]",
                $recovery . ':recoveryPasswordForm',
                'users-recovery-form'
            ),
            new Route(
                "{$startRoute}recovery/{url_token}[/]",
                $recovery . ':newPasswordCreate',
                'users-new-password-create'
            ),
            new Route(
                "{$startRoute}user-forget[/]",
                $users_problems . ':userForgetForm',
                'users-forget-form'
            ),
            new Route(
                "{$startRoute}user-blocked[/]",
                $users_problems . ':userBlockedForm',
                'users-blocked-form'
            ),
            new Route(
                "{$startRoute}other-problems[/]",
                $users_problems . ':otherProblemsForm',
                'users-other-problems-form'
            ),
            new Route(
                "{$startRoute}problems[/]",
                $users_problems . ':userProblemsList',
                'users-problems-list'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Inicio, cierre, registro y edición
        $group->register([
            new Route(
                "{$startRoute}login[/]",
                $users . ':login',
                'users-login-request',
                'POST'
            ),
            new Route(
                "{$startRoute}verify[/]",
                $users . ':verifySession',
                'users-verify-login-request',
                'POST'
            ),
            new Route(
                "{$startRoute}delete-account[/]",
                $users . ':deleteAccount',
                'users-delete-account-request',
                'POST', true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}register[/]",
                $users . ':register',
                'users-register-request',
                'POST',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}edit[/]",
                $users . ':edit',
                'users-edit-request',
                'POST',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}revoke-sessions[/]",
                $users . ':revokeUserSessions',
                'users-revoke-sessions-request',
                'POST',
                true,
                null,
                self::CAN_REVOKE_USER_SESSIONS
            ),
        ]);

        //Problemas
        $group->register([
            new Route(
                "{$startRoute}recovery[/]",
                $recovery . ':recoveryPasswordRequest',
                'users-recovery-password-request',
                'POST'
            ),
            new Route(
                "{$startRoute}recovery-code[/]",
                $recovery . ':recoveryPasswordRequestCode',
                'users-recovery-password-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}create-password-code[/]",
                $recovery . ':newPasswordCreateCode',
                'users-new-password-create-code',
                'POST'
            ),
            new Route(
                "{$startRoute}verify-create-password-code[/]",
                $recovery . ':verifyCode',
                'users-new-password-verify-code',
                'POST'
            ),
            new Route(
                "{$startRoute}user-forget-code[/]",
                $users_problems . ':generateCode',
                'users-forget-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}user-blocked-code[/]",
                $users_problems . ':generateCode',
                'users-blocked-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}get-username[/]",
                $users_problems . ':resolveProblem',
                'users-forget-get',
                'POST'
            ),
            new Route(
                "{$startRoute}unblock-user[/]",
                $users_problems . ':resolveProblem',
                'users-blocked-resolve',
                'POST'
            ),
            new Route(
                "{$startRoute}other-problems[/]",
                $users_problems . ':sendMailOtherProblems',
                'users-other-problems-send',
                'POST'
            ),
        ]);

        return $group;
    }
}
