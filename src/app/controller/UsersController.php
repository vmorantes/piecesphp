<?php

/**
 * UsersController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use App\Model\LoginAttemptsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\HTML\HtmlElement;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

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
     * $token_controller
     *
     * Controlador de Tokens
     *
     * @var TokenController
     */
    protected $token_controller = null;

    /**
     * $model
     *
     * Modelo
     *
     * @var \PiecesPHP\Core\BaseModel
     */
    protected $model = null;

    /**
     * $model
     *
     * Modelo
     *
     * @var UsersModel
     */
    protected $mapper = null;

    /**
     * $url_recovery
     *
     * URL para recuperación de contraseña
     *
     * @var string
     */
    public $url_recovery = 'users/recovery/';

    /**
     * $requested_uri
     *
     * URL para recuperación de contraseña
     *
     * @var string
     */
    public $requested_uri = '';

    //Constantes de errores
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
    const EXPIRED_OR_NOT_EXIST_CODE = 'EXPIRED_OR_NOT_EXIST_CODE';
    const NOT_MATCH_PASSWORDS = 'NOT_MATCH_PASSWORDS';

    //Constante de intentos máximos permitidos
    const MAX_ATTEMPTS = 4;

    /** @ignore */
    public function __construct()
    {
        parent::__construct();

        $this->mapper = new UsersModel();
        $this->model = $this->mapper->getModel();

        $flash = get_flash_messages();

        $global_variables = $this->getGlobalVariables();
        $global_variables['requested_uri'] = isset($flash['requested_uri']) ? $flash['requested_uri'] : '';
        $this->setVariables($global_variables);

        $this->token_controller = new TokenController();
    }

    /**
     * usersList
     *
     * Vista del listado de todos los usuarios
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function usersList(Request $req, Response $res, array $args)
    {
        set_custom_assets([
            base_url(ADMIN_AREA_PATH_JS . '/users-forms.js'),
        ], 'js');

        $this->render('panel/layout/header');
        $this->render('panel/pages/list-usuarios', [
            'process_table' => get_route('users-datatables'),
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * dataTablesRequestUsers
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function dataTablesRequestUsers(Request $req, Response $res, array $args)
    {

        $where = "id != {$this->user->id}";

        $on_set_data = function ($element) {

            $edit_button = new HtmlElement('a', '<i class="icon edit"></i>' . 'Editar');
            $edit_button->setAttribute('class', 'ui green button');
            $edit_button->setAttribute('href', get_route('users-form-edit', ['id' => $element->id]));

            return [
                $element->id,
                $element->firstname . ' ' . $element->secondname,
                $element->first_lastname . ' ' . $element->second_lastname,
                $element->email,
                $element->username,
                $element->status == UsersModel::STATUS_USER_ACTIVE ? 'Sí' : 'No',
                UsersModel::TYPES_USERS[$element->type],
                '' . $edit_button,
            ];
        };

        $columns = [
            'id',
            ['firstname', 'secondname'],
            ['first_lastname', 'second_lastname'],
            'email',
            'username',
            'status',
            'type',
        ];

        $options = [
            'request' => $req,
            'mapper' => new UsersModel(),
            'columns_order' => $columns,
            'on_set_data' => $on_set_data,
            'where_string' => $where,
        ];

        return $res->withJson(DataTablesHelper::process($options)->getValues());
    }

    /**
     * loginForm
     *
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function loginForm(Request $request, Response $response, array $args)
    {
        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* NProgress */
        import_nprogress();
        /* AlertifyJS */
        import_alertifyjs();
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries([
            'urls',
        ]);

        set_custom_assets([
            baseurl('statics/login-and-recovery/css/login.css'),
        ], 'css');

        set_custom_assets([
            baseurl('statics/login-and-recovery/js/login.js'),
        ], 'js');

        $this->render('usuarios/login');

        return $response;
    }

    /**
     * formUserView
     *
     * Vista de creación/edición de usuario
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function formUserView(Request $req, Response $res, array $args)
    {
        set_custom_assets([
            base_url('statics/features/avatars/js/canvg.min.js'),
            base_url('statics/features/avatars/js/avatar.js'),
            base_url(ADMIN_AREA_PATH_JS . '/users-forms.js'),
        ], 'js');

        set_custom_assets([
            base_url('statics/features/avatars/css/style.css'),
        ], 'css');

        $route = $req->getAttribute('route');

        $name_route = $route->getName();

        $is_creation_view = $name_route == 'users-form-create';

        if ($is_creation_view) {
            //Si es la vista de creación

            return $this->form($req, $res, $args, $this->user, null, true);

        } else if (isset($args['id'])) {
            //Si es la vista de edición

            $this->model->resetAll();

            $user = $this->model->select()->where(['id' => $args['id']])->row();

            return $this->form($req, $res, $args, $this->user, $user);
        } else {
            //Si es la vista de perfil
            return $this->form($req, $res, $args, $this->user, $this->user);
        }
    }

    /**
     * form
     *
     * Vista de creación/edición de usuario
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param stdClass $user_login
     * @param stdClass $edit_user
     * @param bool $create
     * @return void
     */
    public function form(Request $request, Response $response, array $args, \stdClass $user_login, \stdClass $edit_user = null, bool $create = false)
    {
        $is_same = $user_login == $edit_user;

        if (!$create) {
            $edit_user->avatar = AvatarModel::getAvatar($edit_user->id);
            $edit_user->hasAvatar = !is_null($edit_user->avatar);
        }

        $data['create'] = $create;
        $data['user'] = $user_login;
        $data['edit_user'] = $edit_user;
        $data['is_same'] = $is_same;

        $type_disabled = $edit_user !== null && ($is_same || $user_login->type == UsersModel::TYPE_USER_GENERAL);
        $type_disabled = $type_disabled ? 'disabled' : '';
        $type_options = Roles::getRolesIdentifiers();

        $status_disabled = $is_same;
        $status_disabled = $status_disabled ? 'disabled' : '';
        $status_options = [
            __('general', 'active') => UsersModel::STATUS_USER_ACTIVE,
            __('general', 'inactive') => UsersModel::STATUS_USER_INACTIVE,
        ];

        $data['type_disabled'] = $type_disabled;
        $data['type_options'] = $type_options;
        $data['status_disabled'] = $status_disabled;
        $data['status_options'] = $status_options;

        $this->setVariables($data);

        $form = '';

        if ($is_same) {

            set_title('Perfil de usuario - ' . get_title());
            $form = $this->render('usuarios/inc/profile-form', [], false);

        } elseif ($create) {

            set_title('Creación de usuario - ' . get_title());
            $form = $this->render('usuarios/inc/create-form', [], false);

        } else {

            set_title('Edición de usuario - ' . get_title());
            $form = $this->render('usuarios/inc/edit-form', [], false);

        }

        $this->render('panel/layout/header');
        $this->render('usuarios/form', [
            'form' => $form,
        ]);
        $this->render('panel/layout/footer');

        return $response;
    }

    /**
     * Inicia sesión.
     *
     * Este método espera recibir por POST: [username,password]
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function login(Request $request, Response $response, array $args)
    {

        $usernameParameter = new Parameter(
            'username',
            null,
            function ($value) {
                return is_string($value);
            },
            false,
            function ($value) {
                return trim(strtolower($value));
            }
        );

        $passwordParameter = new Parameter(
            'password',
            null,
            function ($value) {
                return is_string($value);
            }
        );

        $expectedParameters = new Parameters([
            $usernameParameter,
            $passwordParameter,
        ]);

        $inputData = $request->getParsedBody();
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        $resultOperation = new ResultOperations([], 'Incio de sesión', '');
        $resultOperation->setSingleOperation(true);
        $resultOperation->setValues([
            'auth' => false,
            'isAuth' => false,
            'token' => '',
            'error' => self::NO_ERROR,
            'user' => '',
            'message' => '',
            'extras' => [],
        ]);

        //Se verifica si ya existe una sesión activa
        $JWT = SessionToken::getJWTReceived();

        if (!SessionToken::isActiveSession($JWT)) {

            try {

                $expectedParameters->validate();

                //Se selecciona un elemento que concuerde con el usuario
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

                    //Verificar status
                    if ($user->status !== UsersModel::STATUS_USER_INACTIVE) {

                        if (password_verify($password, $user->password)) {

                            $resultOperation->setValue('auth', true);

                            //Se genera un token de sesión
                            $resultOperation->setValue('token', SessionToken::generateToken([
                                'id' => $user->id,
                                'type' => $user->type,
                            ]));

                            $this->mapper->resetAttempts($user->id);

                            LoginAttemptsModel::addLogin(
                                (int) $user->id,
                                $user->username,
                                true
                            );

                        } else {

                            $resultOperation->setValue('error', self::INCORRECT_PASSWORD);
                            $resultOperation->setValue('message', $this->getMessage(self::INCORRECT_PASSWORD));
                            LoginAttemptsModel::addLogin(
                                (int) $user->id,
                                $user->username,
                                false,
                                $resultOperation->getMessage()
                            );

                            if ($user->failed_attempts >= self::MAX_ATTEMPTS) {

                                $this->mapper->changeStatus(UsersModel::STATUS_USER_INACTIVE, $user->id);
                                $resultOperation->setValue('error', self::BLOCKED_FOR_ATTEMPTS);
                                $resultOperation->setValue('message', vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]));

                            } else {

                                $attempts = $this->mapper->updateAttempts($user->id);

                                if ($attempts >= self::MAX_ATTEMPTS) {

                                    $this->mapper->changeStatus(UsersModel::STATUS_USER_INACTIVE, $user->id);
                                    $resultOperation->setValue('error', self::BLOCKED_FOR_ATTEMPTS);
                                    $resultOperation->setValue('message', vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]));

                                }
                            }

                        }

                    } else {

                        $resultOperation->setValue('error', self::BLOCKED_FOR_ATTEMPTS);
                        $resultOperation->setValue('message', vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$user->username]));
                        LoginAttemptsModel::addLogin(
                            null,
                            $username,
                            false,
                            $resultOperation->getMessage()
                        );

                    }

                } else {

                    $resultOperation->setValue('error', self::USER_NO_EXISTS);
                    $resultOperation->setValue('message', vsprintf($this->getMessage(self::USER_NO_EXISTS), [$username]));
                    LoginAttemptsModel::addLogin(
                        null,
                        $username,
                        false,
                        $resultOperation->getMessage()
                    );

                }

            } catch (MissingRequiredParamaterException $e) {

                $resultOperation->setValue('error', self::MISSING_OR_UNEXPECTED_PARAMS);
                $resultOperation->setValue('message', $e->getMessage());

            } catch (ParsedValueException $e) {

                $resultOperation->setValue('error', self::GENERIC_ERROR);
                $resultOperation->setValue('message', 'Ha ocurrido un error desconocido con los valores ingresados.');
                $resultOperation->setValue('extras', [
                    'exception' => $e->getMessage(),
                ]);

            } catch (InvalidParameterValueException $e) {

                $resultOperation->setValue('error', self::GENERIC_ERROR);
                $resultOperation->setValue('message', $e->getMessage());

            }

        } else {
            $resultOperation->setValue('auth', false);
            $resultOperation->setValue('isAuth', true);
            $resultOperation->setValue('error', self::ACTIVE_SESSION);
            $resultOperation->setValue('message', $this->getMessage(self::ACTIVE_SESSION));
        }

        return $response->withJson($resultOperation->getValues());
    }

    /**
     * verifySession
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function verifySession(Request $request, Response $response, array $args)
    {
        $resultOperation = new ResultOperations([], 'Varificar sesión', '');
        $resultOperation->setSingleOperation(true);
        $resultOperation->setValues([
            'auth' => false,
            'isAuth' => false,
        ]);

        //Se verifica si ya existe una sesión activa
        $JWT = SessionToken::getJWTReceived();
        $resultOperation->setValue('isAuth', SessionToken::isActiveSession($JWT));
        return $response->withJson($resultOperation->getValues());
    }

    /**
     * Registra un usuario nuevo.
     *
     * Este método espera recibir por POST:  [id, username, password, firstname, lastname, email, type, status]
     *
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function register(Request $request, Response $response, array $args)
    {
        $requerido = [
            'username',
            'password',
            'firstname',
            'secondname',
            'first_lastname',
            'second_lastname',
            'email',
            'type',
            'status',
        ];

        $params = $request->getParsedBody();

        $json_response = [
            'success' => false,
            'duplicate' => false,
            'errors' => null,
            'data' => null,
        ];

        $params_ok = require_keys($requerido, $params) === true && count($params) == count($requerido);

        if ($params_ok) {

            $username = strtolower($request->getParsedBodyParam('username'));
            $email = strtolower($request->getParsedBodyParam('email'));

            $password = $request->getParsedBodyParam('password');

            $firstname = ucwords($request->getParsedBodyParam('firstname'));
            $secondname = ucwords($request->getParsedBodyParam('secondname', ''));
            $first_lastname = ucwords($request->getParsedBodyParam('first_lastname'));
            $second_lastname = ucwords($request->getParsedBodyParam('second_lastname', ''));

            $type = $request->getParsedBodyParam('type');
            $status = $request->getParsedBodyParam('status');

            $duplicado = $this->isDuplicate($username, $email);

            if ($duplicado['duplicate']) {

                $json_response['duplicate'] = $duplicado['duplicate'];
                $json_response['errors'] = $duplicado['errors'];

            } else {

                $data['username'] = $username;
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
                $data['firstname'] = $firstname;
                $data['secondname'] = $secondname;
                $data['first_lastname'] = $first_lastname;
                $data['second_lastname'] = $second_lastname;
                $data['email'] = $email;
                $data['type'] = $type;
                $data['status'] = $status;
                $this->model->insert($data);
                $json_response['success'] = $this->model->execute();

            }

            $response = $response->withJson($json_response);

        } else {
            $json_response['errors'] = [
                [
                    'code' => self::MISSING_OR_UNEXPECTED_PARAMS,
                    'message' => $this->getMessage(self::MISSING_OR_UNEXPECTED_PARAMS),
                ],
            ];
            $json_response['data'] = $params;
            $response = $response->withJson($json_response);
        }

        return $response;
    }

    /**
     * Edita un usuario.
     *
     * Este método espera recibir por POST:
     *
     * [id, username, password, firstname, lastname, email, type, status]
     *
     * o
     *
     * [id, username, firstname, lastname, email, type, status]
     *
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function edit(Request $request, Response $response, array $args)
    {
        $requerido_perfil = [
            'id',
            'username',
            'current-password',
            'password',
            'firstname',
            'secondname',
            'first_lastname',
            'second_lastname',
            'email',
            'type',
            'status',
        ];

        $requerido_perfil_edicion = [
            'id',
            'username',
            'password',
            'firstname',
            'secondname',
            'first_lastname',
            'second_lastname',
            'email',
            'type',
            'status',
        ];

        $requerido_edicion_alt = [
            'id',
            'username',
            'firstname',
            'secondname',
            'first_lastname',
            'second_lastname',
            'email',
            'type',
            'status',
        ];

        $params = $request->getParsedBody();

        $json_response = [
            'success' => false,
            'duplicate' => false,
            'errors' => null,
            'data' => null,
        ];

        $params_ok = require_keys($requerido_perfil, $params) === true && count($params) == count($requerido_perfil);
        $params_ok = $params_ok || require_keys($requerido_perfil_edicion, $params) === true && count($params) == count($requerido_perfil_edicion);
        $params_ok = $params_ok || require_keys($requerido_edicion_alt, $params) === true && count($params) == count($requerido_edicion_alt);

        if ($params_ok) {

            $id = strtolower($request->getParsedBodyParam('id'));

            $username = strtolower($request->getParsedBodyParam('username'));
            $email = strtolower($request->getParsedBodyParam('email'));

            $password = $request->getParsedBodyParam('password');
            $currentPassword = $request->getParsedBodyParam('current-password');

            $firstname = ucwords($request->getParsedBodyParam('firstname'));
            $secondname = ucwords($request->getParsedBodyParam('secondname', ''));
            $first_lastname = ucwords($request->getParsedBodyParam('first_lastname'));
            $second_lastname = ucwords($request->getParsedBodyParam('second_lastname', ''));

            $type = $request->getParsedBodyParam('type');
            $status = $request->getParsedBodyParam('status');

            $duplicado = $this->isDuplicate($username, $email, (int) $id);

            if ($duplicado['duplicate']) {

                $json_response['duplicate'] = $duplicado['duplicate'];
                $json_response['errors'] = $duplicado['errors'];

            } else {

                $data['username'] = $username;
                $data['firstname'] = $firstname;
                $data['secondname'] = $secondname;
                $data['first_lastname'] = $first_lastname;
                $data['second_lastname'] = $second_lastname;
                $data['email'] = $email;
                $data['type'] = $type;
                $data['status'] = $status;

                if (is_null($password)) {

                    $this->model->update($data)->where(['id' => $id]);
                    $json_response['success'] = $this->model->execute();

                } else {

                    $userEditing = new UsersModel((int) $id);
                    $data['password'] = password_hash($password, PASSWORD_DEFAULT);

                    if (password_verify($currentPassword, $userEditing->password) || is_null($currentPassword)) {
                        $this->model->update($data)->where(['id' => $id]);
                        $json_response['success'] = $this->model->execute();
                    } else {
                        $json_response['errors'] = [
                            [
                                'code' => self::INCORRECT_PASSWORD,
                                'message' => $this->getMessage(self::INCORRECT_PASSWORD),
                            ],
                        ];
                    }

                }

            }

            $response = $response->withJson($json_response);

        } else {
            $json_response['errors'] = [
                [
                    'code' => self::MISSING_OR_UNEXPECTED_PARAMS,
                    'message' => $this->getMessage(self::MISSING_OR_UNEXPECTED_PARAMS),
                ],
            ];
            $json_response['data'] = $params;
            $response = $response->withJson($json_response);
        }

        return $response;
    }

    /**
     * Comprueba que el usuario no esté duplicado.
     *
     * @param string $user Usuario
     * @param string $email Email
     * @param int $exclude_id ID
     * @return array Array asociativo con la estructura ['duplicate'=>boolean,'errors'=>array()|null]
     */
    private function isDuplicate(string $user, string $email, int $exclude_id = null)
    {
        $query = $this->model->select();
        if (!is_null($exclude_id)) {
            $query->where("id != $exclude_id");
        }
        $query->execute();
        $usuarios = $this->model->result();

        $contador = 0;
        $duplicado = false;
        $errors = [];

        if ($this->model->rowCount() > 0) {
            foreach ($usuarios as $usuario) {
                if ($usuario->username == $user) {
                    $tmp = [];
                    $tmp['code'] = self::DUPLICATE_USER;
                    $tmp['message'] = vsprintf($this->getMessage(self::DUPLICATE_USER), [$user]);
                    $errors[] = $tmp;
                    $duplicado = true;
                }
                if ($usuario->email == $email) {
                    $tmp = [];
                    $tmp['code'] = self::DUPLICATE_EMAIL;
                    $tmp['message'] = vsprintf($this->getMessage(self::DUPLICATE_EMAIL), [$email]);
                    $errors[] = $tmp;
                    $duplicado = true;
                }
                if ($duplicado) {
                    break;
                }
            }
            return [
                "duplicate" => $duplicado,
                'errors' => $errors,
            ];
        } else {
            return [
                "duplicate" => $duplicado,
                'errors' => null,
            ];
        }
    }

    /**
     * encodeURL
     *
     * @param mixed $data
     * @return string
     */
    public function encodeURL($data)
    {
        return BaseHashEncryption::encrypt(StringManipulate::jsonEncode($data));
    }

    /**
     * decodeURL
     *
     * @param string $url
     * @return mixed
     */
    public function decodeURL(string $url)
    {
        return StringManipulate::jsonDecode(BaseHashEncryption::decrypt($url));
    }

    /**
     * getMessage
     *
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
     * routes
     *
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

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Usuarios
        $group->register([
            //Listado de usuarios
            new Route(
                "{$startRoute}usuarios/list[/]",
                $users . ':usersList',
                'users-list',
                'GET',
                true
            ),
            //Vista de creación de usuario
            new Route(
                "{$startRoute}usuarios/crear[/]",
                $users . ':formUserView',
                'users-form-create',
                'GET',
                true
            ),
            //Vista de edición de usuario
            new Route(
                "{$startRoute}usuarios/editar/{id}[/]",
                $users . ':formUserView',
                'users-form-edit',
                'GET',
                true
            ),
            //Vista de perfil de usuario
            new Route(
                "{$startRoute}perfil[/]",
                $users . ':formUserView',
                'users-form-profile',
                'GET',
                true
            ),

            //Datatables
            new Route(
                "{$startRoute}usuarios/datatbles[/]",
                $users . ':dataTablesRequestUsers',
                'users-datatables',
                'GET'
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
                'recovery-form'
            ),
            new Route(
                "{$startRoute}recovery/{url_token}[/]",
                $recovery . ':newPasswordCreate',
                'new-password-create'
            ),
            new Route(
                "{$startRoute}user-forget[/]",
                $users_problems . ':userForgetForm',
                'user-forget-form'
            ),
            new Route(
                "{$startRoute}user-blocked[/]",
                $users_problems . ':userBlockedForm',
                'user-blocked-form'
            ),
            new Route(
                "{$startRoute}other-problems[/]",
                $users_problems . ':otherProblemsForm',
                'other-problems-form'
            ),
            new Route(
                "{$startRoute}problems[/]",
                $users_problems . ':userProblemsList',
                'user-problems-list'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Inicio, cierre, registro y edición
        $group->register([
            new Route(
                "{$startRoute}login[/]",
                $users . ':login',
                'login-request',
                'POST'
            ),
            new Route(
                "{$startRoute}verify[/]",
                $users . ':verifySession',
                'verify-login-request',
                'POST'
            ),
            new Route(
                "{$startRoute}register[/]",
                $users . ':register',
                'register-request',
                'POST'
            ),
            new Route(
                "{$startRoute}edit[/]",
                $users . ':edit',
                'user-edit-request',
                'POST'
            ),
        ]);

        //Problemas
        $group->register([
            new Route(
                "{$startRoute}recovery[/]",
                $recovery . ':recoveryPasswordRequest',
                'recovery-password-request',
                'POST'
            ),
            new Route(
                "{$startRoute}recovery-code[/]",
                $recovery . ':recoveryPasswordRequestCode',
                'recovery-password-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}create-password-code[/]",
                $recovery . ':newPasswordCreateCode',
                'new-password-create-code',
                'POST'
            ),
            new Route(
                "{$startRoute}verify-create-password-code[/]",
                $recovery . ':verifyCode',
                'new-password-verify-code',
                'POST'
            ),
            new Route(
                "{$startRoute}user-forget-code[/]",
                $users_problems . ':generateCode',
                'user-forget-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}user-blocked-code[/]",
                $users_problems . ':generateCode',
                'user-blocked-request-code',
                'POST'
            ),
            new Route(
                "{$startRoute}get-username[/]",
                $users_problems . ':resolveProblem',
                'user-forget-get',
                'POST'
            ),
            new Route(
                "{$startRoute}unblock-user[/]",
                $users_problems . ':resolveProblem',
                'user-blocked-resolve',
                'POST'
            ),
            new Route(
                "{$startRoute}other-problems[/]",
                $users_problems . ':sendMailOtherProblems',
                'other-problems-send',
                'POST'
            ),
        ]);

        return $group;
    }
}
