<?php

/**
 * UsersController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use App\Model\LoginAttemptsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\HTML\Form;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\StringManipulate;
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
class UsersController extends \PiecesPHP\Core\BaseController
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

    //Constantes de errores
    const NO_ERROR = 'NO_ERROR';
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

        $this->mapper = $this->model;
        $this->model = $this->mapper->getModel();

        $flash = get_flash_messages();

        $this->setVariables([
            'session_errors' => isset($flash['session_errors']) ? $flash['session_errors'] : [],
            'requested_uri' => isset($flash['requested_uri']) ? $flash['requested_uri'] : '',
        ]);

        $this->token_controller = new TokenController();
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
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param stdClass $user_login
     * @param stdClass $edit_user
     * @param bool $create
     * @return void
     */
    public function formUserView(Request $request, Response $response, array $args, \stdClass $user_login, \stdClass $edit_user = null, bool $create = false)
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
        //Parámetros POST
        $params = $request->getParsedBody();

        //Cuerpo de la respuesta
        $json_response = [
            'auth' => false,
            'is_auth' => false,
            'token' => '',
            'error' => self::NO_ERROR,
            'message' => '',
        ];
        //Se verifica si ya existe una sesión activa

        if (!SessionToken::isActiveSession()) {

            //Conjunto posible de datos para autenticación
            $cantidad_parametros = count($params) == 2;

            $requerido_1 = [
                'username',
                'password',
            ];

            //Verificar que el grupo de datos para autenticación sea válido
            $parametros_ok = (require_keys($requerido_1, $params) === true);

            //Si los parámetros son válidos en nombre y en cantidad se inicia verificación de autenticación
            if ($parametros_ok && $cantidad_parametros) {

                //Se selecciona un elemento que concuerde con el usuario

                $username = strtolower($params['username']);
                $password = $params['password'];

                $usuario = $this->mapper->getWhere([
                    'username' => [
                        '=' => $username,
                        'and_or' => 'OR',
                    ],
                    'email' => [
                        '=' => $username,
                    ],
                ]);

                //Verificación de existencia
                if ($usuario !== -1) {

                    $usuario->status = (int) $usuario->status;

                    //Verificar status
                    if ($usuario->status !== UsersModel::STATUS_USER_INACTIVE) {

                        if (password_verify($password, $usuario->password)) {

                            $json_response['auth'] = true;

                            //Se crea la sesión con el uso de un token y de COOKIES
                            $json_response['token'] = SessionToken::initSession($usuario);

                            $this->mapper->resetAttempts($usuario->id);
                            LoginAttemptsModel::addLogin((int) $usuario->id, $usuario->username, true);

                        } else {

                            $json_response['error'] = self::INCORRECT_PASSWORD;
                            $json_response['message'] = $this->getMessage(self::INCORRECT_PASSWORD);

                            LoginAttemptsModel::addLogin((int) $usuario->id, $usuario->username, false, $json_response['message']);

                            if ($usuario->failed_attempts >= self::MAX_ATTEMPTS) {

                                $this->mapper->changeStatus(UsersModel::STATUS_USER_INACTIVE, $usuario->id);

                                $json_response['error'] = self::BLOCKED_FOR_ATTEMPTS;
                                $json_response['message'] = vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$usuario->username]);

                            } else {

                                $attempts = $this->mapper->updateAttempts($usuario->id);

                                if ($attempts >= self::MAX_ATTEMPTS) {

                                    $this->mapper->changeStatus(UsersModel::STATUS_USER_INACTIVE, $usuario->id);

                                    $json_response['error'] = self::BLOCKED_FOR_ATTEMPTS;
                                    $json_response['message'] = vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$usuario->username]);

                                }
                            }

                        }

                    } else {

                        $json_response['error'] = self::BLOCKED_FOR_ATTEMPTS;
                        $json_response['message'] = vsprintf($this->getMessage(self::BLOCKED_FOR_ATTEMPTS), [$usuario->username]);
                        LoginAttemptsModel::addLogin(null, $username, false, $json_response['message']);

                    }
                } else {
                    $json_response['error'] = self::USER_NO_EXISTS;
                    $json_response['message'] = $this->getMessage(self::USER_NO_EXISTS);
                    $json_response['message'] = vsprintf($this->getMessage(self::USER_NO_EXISTS), [$username]);
                    LoginAttemptsModel::addLogin(null, $username, false, $json_response['message']);
                }

            } else {
                $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
                $json_response['message'] = $this->getMessage(self::MISSING_OR_UNEXPECTED_PARAMS);
            }
        } else {
            $json_response['auth'] = false;
            $json_response['is_auth'] = true;
            $json_response['token'] = SessionToken::getSession();
            $json_response['error'] = self::ACTIVE_SESSION;
            $json_response['message'] = $this->getMessage(self::ACTIVE_SESSION);
        }

        return $response->withJson($json_response);
    }

    /**
     * Cierra la sesión.
     * No espera parámetros.
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function logout(Request $request, Response $response, array $args)
    {
        $logout = SessionToken::finishSession();
        if ($request->isXhr()) {
            return $response->withJson(['logout' => $logout]);
        } else {
            return $response->withRedirect(get_route('login-form'));
        }
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
}
