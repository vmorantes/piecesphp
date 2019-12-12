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
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Exception\NotFoundException;
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
     * $password
     *
     * @var string
     */
    private $password = "NDG3iIk43xMlo5OKpCZ6Buyu0pC99v9qef9du5tncHoCbgZnHY";

    /**
     * $ofuscate
     *
     * @var string
     */
    private $ofuscate = "NDG3iIk43xMlo5OKpCZ6Buyu0pC99v9qef9du5tncHoCbgZnHY";

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

        $currentUser = new UsersModel($this->user->id);
        $disallowedTypes = $currentUser->getHigherPriorityTypes();

        $where = [
            "id != {$this->user->id}",
        ];

        if (is_array($disallowedTypes) && count($disallowedTypes) > 0) {
            $where[] = " AND type != " . implode(' AND type != ', $disallowedTypes);
        }

        $where = trim(implode(' ', $where));

        $on_set_data = function ($element) {

            $edit_button = new HtmlElement('a', '<i class="icon edit"></i>' . __('usersModule', 'Editar'));
            $edit_button->setAttribute('class', 'ui green button');
            $edit_button->setAttribute('href', get_route('users-form-edit', ['id' => $element->id]));

            return [
                $element->id,
                stripslashes($element->firstname . ' ' . $element->secondname),
                stripslashes($element->first_lastname . ' ' . $element->second_lastname),
                $element->email,
                stripslashes($element->username),
                $element->status == UsersModel::STATUS_USER_ACTIVE ? __('usersModule', 'Sí') : __('usersModule', 'No'),
                UsersModel::getTypesUser()[$element->type],
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
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries();

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
     * selectionTypeToCreate
     *
     * Vista de selección de tipo de usuario para crear
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function selectionTypeToCreate(Request $req, Response $res, array $args)
    {
        $types = UsersModel::getTypesUser();
        $currentUser = new UsersModel($this->user->id);

        foreach ($types as $key => $display) {

            $type_encrypt = strrev($this->ofuscate) . ":$key:" . $this->ofuscate;
            $type_encrypt = BaseHashEncryption::encrypt($type_encrypt, $this->password);

            $hasAuthority = $currentUser->hasAuthorityOver($key);

            if ($hasAuthority) {

                $types[$key] = [
                    'link' => get_route('users-form-create', ['type' => $type_encrypt]),
                    'text' => $display,
                ];

            } else {

                unset($types[$key]);

            }

        }

        $this->render('panel/layout/header');
        $this->render('usuarios/select-type-user', [
            'types' => $types,
        ]);
        $this->render('panel/layout/footer');
    }

    /**
     * formCreateByType
     *
     * Vista de creación de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function formCreateByType(Request $req, Response $res, array $args)
    {
        set_custom_assets([
            base_url('statics/features/avatars/js/canvg.min.js'),
            base_url('statics/features/avatars/js/avatar.js'),
            base_url(ADMIN_AREA_PATH_JS . '/users-forms.js'),
        ], 'js');

        set_custom_assets([
            base_url('statics/features/avatars/css/style.css'),
        ], 'css');

        $type = $req->getAttribute('type', null);
        $type = BaseHashEncryption::decrypt($type, $this->password);
        $type = str_replace([
            strrev($this->ofuscate) . ':',
            ':' . $this->ofuscate,
        ], '', $type);

        $currentUser = new UsersModel($this->user->id);
        $hasAuthority = $currentUser->hasAuthorityOver($type);

        if (!$hasAuthority) {
            $controller = new \PiecesPHP\Core\BaseController(false);
            $controller->render('pages/403', [
                'url' => get_route('users-selection-create'),
            ]);
            return $res->withStatus(403);
        }

        if (isset(UsersModel::getTypesUser()[$type])) {

            $status_options = [
                __('usersModule', 'active') => UsersModel::STATUS_USER_ACTIVE,
                __('usersModule', 'inactive') => UsersModel::STATUS_USER_INACTIVE,
            ];

            $data_form = [];
            $data_form['status_options'] = $status_options;

            $form = '';

            if ($type == UsersModel::TYPE_USER_ROOT) {

                $form = $this->render('usuarios/form-by-type/create/root/form', $data_form, false);

            } elseif ($type == UsersModel::TYPE_USER_ADMIN) {

                $form = $this->render('usuarios/form-by-type/create/admin/form', $data_form, false);

            } elseif ($type == UsersModel::TYPE_USER_GENERAL) {

                $form = $this->render('usuarios/form-by-type/create/general/form', $data_form, false);

            }

            $data = [];
            $data['form'] = $form;
            $data['create'] = true;

            $this->render('panel/layout/header');
            $this->render('usuarios/form', $data);
            $this->render('panel/layout/footer');

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * formEditByType
     *
     * Vista de edición de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function formEditByType(Request $req, Response $res, array $args)
    {

        import_cropper();

        set_custom_assets([
            base_url('statics/features/avatars/js/canvg.min.js'),
            base_url('statics/features/avatars/js/avatar.js'),
            base_url(ADMIN_AREA_PATH_JS . '/users-forms.js'),
        ], 'js');

        set_custom_assets([
            base_url('statics/features/avatars/css/style.css'),
        ], 'css');

        $id = $req->getAttribute('id', null);
        $id = !is_null($id) && ctype_digit($id) ? (int) $id : null;

        $user = new UsersModel($id);

        $currentUser = new UsersModel($this->user->id);
        $hasAuthority = $currentUser->hasAuthorityOver($user->type);

        if (!is_null($user->id)) {

            if (!$hasAuthority) {
                $controller = new \PiecesPHP\Core\BaseController(false);
                $controller->render('pages/403', [
                    'url' => get_route('users-list'),
                ]);
                return $res->withStatus(403);
            }

            $is_same = $user->id == $this->user->id;

            if (!$is_same) {

                $type = $user->type;

                $status_options = [
                    __('usersModule', 'active') => UsersModel::STATUS_USER_ACTIVE,
                    __('usersModule', 'inactive') => UsersModel::STATUS_USER_INACTIVE,
                ];

                $data_form = [];
                $data_form['status_options'] = $status_options;
                $data_form['edit_user'] = $user;

                $form = '';

                if ($type == UsersModel::TYPE_USER_ROOT) {

                    $form = $this->render('usuarios/form-by-type/edit/root/form', $data_form, false);

                } elseif ($type == UsersModel::TYPE_USER_ADMIN) {

                    $form = $this->render('usuarios/form-by-type/edit/admin/form', $data_form, false);

                } elseif ($type == UsersModel::TYPE_USER_GENERAL) {

                    $form = $this->render('usuarios/form-by-type/edit/general/form', $data_form, false);

                }

                $data = [];
                $data['form'] = $form;
                $data['edit_user'] = $user;
                $data['avatar'] = AvatarModel::getAvatar($user->id);
                $data['hasAvatar'] = !is_null($data['avatar']);
                $data['create'] = false;

                $this->render('panel/layout/header');
                $this->render('usuarios/form', $data);
                $this->render('panel/layout/footer');

            } else {

                throw new NotFoundException($req, $res);

            }

        } else {
            throw new NotFoundException($req, $res);
        }
    }

    /**
     * formProfileByType
     *
     * Vista de perfil de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function formProfileByType(Request $req, Response $res, array $args)
    {

        import_cropper();

        set_custom_assets([
            base_url('statics/features/avatars/js/canvg.min.js'),
            base_url('statics/features/avatars/js/avatar.js'),
            base_url(ADMIN_AREA_PATH_JS . '/users-forms.js'),
        ], 'js');

        set_custom_assets([
            base_url('statics/features/avatars/css/style.css'),
        ], 'css');

        $user = new UsersModel($this->user->id);

        if (!is_null($user->id)) {

            $type = $user->type;

            $data_form = [];
            $data_form['edit_user'] = $user;

            $form = '';

            if ($type == UsersModel::TYPE_USER_ROOT) {

                $form = $this->render('usuarios/form-by-type/profile/root/form', $data_form, false);

            } elseif ($type == UsersModel::TYPE_USER_ADMIN) {

                $form = $this->render('usuarios/form-by-type/profile/admin/form', $data_form, false);

            } elseif ($type == UsersModel::TYPE_USER_GENERAL) {

                $form = $this->render('usuarios/form-by-type/profile/general/form', $data_form, false);

            }

            $data = [];
            $data['form'] = $form;
            $data['edit_user'] = $user;
            $data['avatar'] = AvatarModel::getAvatar($user->id);
            $data['hasAvatar'] = !is_null($data['avatar']);
            $data['create'] = false;

            $this->render('panel/layout/header');
            $this->render('usuarios/form', $data);
            $this->render('panel/layout/footer');

        } else {
            throw new NotFoundException($req, $res);
        }
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

        $resolutionWidth = $request->getQueryParam('vp-w', null);
        $resolutionHeight = $request->getQueryParam('vp-h', null);
        $userAgent = $request->getQueryParam('user-agent', null);
        $extraDataLog = [
            'dimensions' => [
                'w' => !is_null($resolutionWidth) ? (int) $resolutionWidth : null,
                'h' => !is_null($resolutionHeight) ? (int) $resolutionHeight : null,
            ],
            'userAgent' => !is_null($userAgent) ? base64_decode($userAgent) : (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null),
        ];

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
                $username = addslashes($usernameParameter->getValue());
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
                                true,
                                '',
                                $extraDataLog
                            );

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

            } catch (MissingRequiredParamaterException $e) {

                $resultOperation->setValue('error', self::MISSING_OR_UNEXPECTED_PARAMS);
                $resultOperation->setValue('message', $e->getMessage());
                log_exception($e);

            } catch (ParsedValueException $e) {

                $resultOperation->setValue('error', self::GENERIC_ERROR);
                $resultOperation->setValue('message', __('usersModule', 'Ha ocurrido un error desconocido con los valores ingresados.'));
                $resultOperation->setValue('extras', [
                    'exception' => $e->getMessage(),
                ]);
                log_exception($e);

            } catch (InvalidParameterValueException $e) {

                $resultOperation->setValue('error', self::GENERIC_ERROR);
                $resultOperation->setValue('message', $e->getMessage());
                log_exception($e);

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
        $resultOperation = new ResultOperations([], __('usersModule', 'Verificar sesión'), '');
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
     * Registra un usuario nuevo
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return Response
     */
    public function register(Request $request, Response $response, array $args)
    {

        $operation_name = __('usersModule', 'Creación de usuario');

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
                    return strtolower($value);
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
                    return strtolower($value);
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
                    return is_string($value) && ctype_digit($value);
                }
            ),
            new Parameter(
                'status',
                null,
                function ($value) {
                    return is_string($value) && ctype_digit($value);
                }
            ),
        ]);

        $parametersExcepted->setInputValues($request->getParsedBody());

        $message_create = __('usersModule', 'Usuario creado.');
        $message_duplicate_email = __('usersModule', 'Ya existe un usuario con ese email.');
        $message_duplicate_user = __('usersModule', 'Ya existe un usuario con ese nombre de usuario.');
        $message_duplicate_all = __('usersModule', 'Ya existe un usuario con ese email y nombre de usuario.');
        $message_password_unmatch = __('usersModule', 'Las contraseñas no coinciden.');
        $message_unknow_error = __('usersModule', 'Ha ocurrido un error inesperado.');

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
            $status = $parametersExcepted->getValue('status');

            $username_duplicate = UsersModel::isDuplicateUsername($username);
            $email_duplicate = UsersModel::isDuplicateEmail($email);

            $password_match = $password == $password2;

            if (!$username_duplicate && !$email_duplicate && $password_match) {

                $userMapper = new UsersModel();

                $userMapper->username = $username;
                $userMapper->email = $email;
                $userMapper->password = password_hash($password, \PASSWORD_DEFAULT);
                $userMapper->firstname = $firstname;
                $userMapper->secondname = $secondname;
                $userMapper->first_lastname = $first_lastname;
                $userMapper->second_lastname = $second_lastname;
                $userMapper->type = $type;
                $userMapper->status = $status;
                $userMapper->failed_attempts = 0;
                $userMapper->created_at = new \DateTime();
                $userMapper->modified_at = $userMapper->created_at;

                $success = $userMapper->save();

                if ($success) {

                    $result->setValue('reload', true);

                    $result
                        ->setMessage($message_create)
                        ->operation($operation_name)
                        ->setSuccess(true);

                } else {

                    $result
                        ->setMessage($message_unknow_error)
                        ->operation($operation_name);

                }

            } else {

                if (!$password_match) {

                    $result
                        ->setMessage($message_password_unmatch)
                        ->operation($operation_name);

                } elseif ($username_duplicate && $email_duplicate) {

                    $result
                        ->setMessage($message_duplicate_all)
                        ->operation($operation_name);

                } elseif ($username_duplicate) {

                    $result
                        ->setMessage($message_duplicate_user)
                        ->operation($operation_name);

                } elseif ($email_duplicate) {

                    $result
                        ->setMessage($message_duplicate_email)
                        ->operation($operation_name);

                }

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        }

        return $response->withJson($result);

    }

    /**
     * Edita un usuario
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return Response
     */
    public function edit(Request $request, Response $response, array $args)
    {

        $operation_name = __('usersModule', 'Edición de usuario');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $parametersExcepted = new Parameters([
            new Parameter(
                'id',
                null,
                function ($value) {
                    return ctype_digit($value);
                }
            ),
            new Parameter(
                'username',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return strtolower($value);
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
                    return strtolower($value);
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
                'status',
                null,
                function ($value) {
                    return is_string($value) && ctype_digit($value);
                }
            ),
        ]);

        $parametersExcepted->setInputValues($request->getParsedBody());

        $message_edit = __('usersModule', 'Usuario editado.');
        $message_duplicate_email = __('usersModule', 'Ya existe un usuario con ese email.');
        $message_duplicate_user = __('usersModule', 'Ya existe un usuario con ese nombre de usuario.');
        $message_duplicate_all = __('usersModule', 'Ya existe un usuario con ese email y nombre de usuario.');
        $message_password_unmatch = __('usersModule', 'Las contraseñas no coinciden.');
        $message_password_wrong = __('usersModule', 'La contraseña es errónea.');
        $message_unknow_error = __('usersModule', 'Ha ocurrido un error inesperado.');

        try {

            $parametersExcepted->validate();

            $id = $parametersExcepted->getValue('id');
            $username = $parametersExcepted->getValue('username');
            $email = $parametersExcepted->getValue('email');
            $isProfile = $parametersExcepted->getValue('is_profile');
            $currentPassword = $parametersExcepted->getValue('current-password');
            $password = $parametersExcepted->getValue('password');
            $password2 = $parametersExcepted->getValue('password2');
            $firstname = $parametersExcepted->getValue('firstname');
            $secondname = $parametersExcepted->getValue('secondname');
            $first_lastname = $parametersExcepted->getValue('first_lastname');
            $second_lastname = $parametersExcepted->getValue('second_lastname');
            $status = $parametersExcepted->getValue('status');

            $username_duplicate = UsersModel::isDuplicateUsername($username, $id);
            $email_duplicate = UsersModel::isDuplicateEmail($email, $id);

            $change_password = !is_null($password);
            $password_match = $change_password ? $password == $password2 : true;
            $password_ok = true;

            $userMapper = new UsersModel($id);

            if ($userMapper->id !== null) {

                if ($isProfile && $change_password) {
                    $password_ok = password_verify($currentPassword, $userMapper->password);
                }

                if (!$username_duplicate && !$email_duplicate && $password_match && $password_ok) {

                    $userMapper->username = $username;
                    $userMapper->email = $email;
                    $userMapper->firstname = $firstname;
                    $userMapper->secondname = $secondname;
                    $userMapper->first_lastname = $first_lastname;
                    $userMapper->second_lastname = $second_lastname;
                    $userMapper->status = $status;
                    $userMapper->modified_at = new \DateTime();

                    if ($change_password) {
                        $userMapper->password = password_hash($password, \PASSWORD_DEFAULT);
                    }

                    $success = $userMapper->update();

                    if ($success) {

                        $result->setValue('reload', true);

                        $result
                            ->setMessage($message_edit)
                            ->operation($operation_name)
                            ->setSuccess(true);

                    } else {

                        $result
                            ->setMessage($message_unknow_error)
                            ->operation($operation_name);

                    }

                } else {

                    if (!$password_ok) {

                        $result
                            ->setMessage($message_password_wrong)
                            ->operation($operation_name);

                    } elseif (!$password_match) {

                        $result
                            ->setMessage($message_password_unmatch)
                            ->operation($operation_name);

                    } elseif ($username_duplicate && $email_duplicate) {

                        $result
                            ->setMessage($message_duplicate_all)
                            ->operation($operation_name);

                    } elseif ($username_duplicate) {

                        $result
                            ->setMessage($message_duplicate_user)
                            ->operation($operation_name);

                    } elseif ($email_duplicate) {

                        $result
                            ->setMessage($message_duplicate_email)
                            ->operation($operation_name);

                    }

                }

            } else {

                $result
                    ->setMessage(__('usersModule', 'No existe el usuario que intenta modificar.'))
                    ->operation($operation_name);

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        }

        return $response->withJson($result);

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
