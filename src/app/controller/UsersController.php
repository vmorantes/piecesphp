<?php

/**
 * UsersController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use App\Model\LoginAttemptsModel;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
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
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
    const INACTIVE_USER = 'INACTIVE_USER';
    const ORGANIZATION_IS_NOT_ACTIVE = 'ORGANIZATION_IS_NOT_ACTIVE';
    const EXPIRED_OR_NOT_EXIST_CODE = 'EXPIRED_OR_NOT_EXIST_CODE';
    const NOT_MATCH_PASSWORDS = 'NOT_MATCH_PASSWORDS';
    const NO_EXTERNAL_LOGIN_AVAILABLE = 'NO_EXTERNAL_LOGIN_AVAILABLE';
    const INVALID_TWO_FACTOR_CODE = 'INVALID_TWO_FACTOR_CODE';

    //Constante de intentos máximos permitidos
    const MAX_ATTEMPTS = 4;

    const LANG_GROUP = 'usersModule';

    /** @ignore */
    public function __construct()
    {

        $this->token_controller = new TokenController();

        parent::__construct();

        $this->mapper = new UsersModel();
        $this->model = $this->mapper->getModel();

        $flash = get_flash_messages();

        $global_variables = $this->getGlobalVariables();
        $global_variables['requested_uri'] = isset($flash['requested_uri']) ? $flash['requested_uri'] : '';
        $this->setVariables($global_variables);
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
        set_custom_assets([
            base_url('statics/admin-area/css/users-list.css'),
        ], 'css');
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
        $canAssign = OrganizationMapper::canAssignAnyOrganization($currentUser->type);

        $where = [
            "id != {$this->user->id}",
        ];

        if (is_array($disallowedTypes) && !empty($disallowedTypes)) {
            $where[] = " AND type != " . implode(' AND type != ', $disallowedTypes);
        }

        if (!$canAssign) {
            $where[] = " AND organization = " . $currentUser->organization;
        }

        if ($filterStatus !== null) {
            $where[] = " AND status = {$filterStatus}";
        }

        $whereString = trim(implode(' ', $where));

        $selectFields = UsersModel::fieldsToSelect();

        $columnsOrder = [
            'idPadding',
            'names',
            'lastNames',
            'email',
            'username',
            'statusText',
            'typeName',
        ];
        $customOrder = [
            'idPadding' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($element) {

                $buttons = [];

                $editLink = get_route('users-form-edit', ['id' => $element->id]);
                $editLink = is_string($editLink) ? $editLink : '';

                if (mb_strlen($editLink) > 0) {
                    $editText = '<i class="icon edit"></i>' . __(self::LANG_GROUP, 'Editar');
                    $editButton = "<a class='ui button green' href='{$editLink}'>{$editText}</a>";
                    $buttons[] = $editButton;
                }

                $columns = [];

                $columns[] = $element->idPadding;
                $columns[] = stripslashes($element->names);
                $columns[] = stripslashes($element->lastNames);
                $columns[] = $element->email;
                $columns[] = stripslashes($element->username);
                $columns[] = $element->statusText;
                $columns[] = $element->typeName;
                $columns[] = implode(' ', $buttons);

                return $columns;
            },
        ]);

        $rawData = $result->getValue('rawData');

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

        $results = new \stdClass;
        $results->success = true;
        $results->results = [];

        $model = UsersModel::model();
        $model->select(UsersModel::fieldsToSelect());
        $model->orderBy('fullname ASC, username ASC, id DESC');

        if (!empty($ignoreTypes)) {
            $ignoreTypes = implode(', ', $ignoreTypes);
            $model->where("type NOT IN ({$ignoreTypes})");
        }

        if ($search !== null) {

            $search = mb_strtolower($search);
            $having = [
                "LOWER(fullname) LIKE '{$search}%'",
                "OR LOWER(username) LIKE '{$search}%'",
                "OR LOWER(firstname) LIKE '{$search}%'",
                "OR LOWER(secondname) LIKE '{$search}%'",
                "OR LOWER(second_lastname) LIKE '{$search}%'",
                "OR LOWER(first_lastname) LIKE '{$search}%'",
            ];
            $having = trim(implode(' ', $having));

            $model->having($having);

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
            baseurl('statics/login-and-recovery/js/login.js'),
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
     * Vista de creación de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function formCreateByType(Request $req, Response $res)
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
            return throw403($req, [
                'url' => get_route('users-selection-create'),
            ]);
        }

        if (isset(UsersModel::getTypesUser()[$type])) {

            $status_options = [
                __(self::LANG_GROUP, 'active') => UsersModel::STATUS_USER_ACTIVE,
                __(self::LANG_GROUP, 'inactive') => UsersModel::STATUS_USER_INACTIVE,
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
     * Vista de edición de usuario por tipo
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function formEditByType(Request $req, Response $res)
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
                return throw403($req, [
                    'url' => get_route('users-list'),
                ]);
            }

            $is_same = $user->id == $this->user->id;

            if (!$is_same) {

                $type = $user->type;

                $status_options = [
                    __(self::LANG_GROUP, 'active') => UsersModel::STATUS_USER_ACTIVE,
                    __(self::LANG_GROUP, 'inactive') => UsersModel::STATUS_USER_INACTIVE,
                ];

                if ($user->status == UsersModel::STATUS_USER_ATTEMPTS_BLOCK) {
                    $status_options[__(self::LANG_GROUP, 'Bloqueado por intentos fallidos')] = UsersModel::STATUS_USER_ATTEMPTS_BLOCK;
                }

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
            $data['onlyProfile'] = $onlyProfile;
            $data['onlyImage'] = $onlyImage;

            $this->render('panel/layout/header');
            $this->render('usuarios/form', $data);
            $this->render('panel/layout/footer');

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
            'userAgent' => !is_null($userAgent) ? base64_decode($userAgent) : (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null),
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
            'extras' => [],
            'userData' => [],
        ]);

        //Se verifica si ya existe una sesión activa
        $JWT = SessionToken::getJWTReceived();

        try {

            $expectedParameters->validate();

            $overwriteSession = $expectedParameters->getValue('overwriteSession');

            if (!SessionToken::isActiveSession($JWT) || $overwriteSession) {

                //Se selecciona un elemento que concuerde con el usuario
                $username = escapeString($usernameParameter->getValue());
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

                    //Verificar status
                    if ($user->status == UsersModel::STATUS_USER_ACTIVE) {

                        //Verificar status de la organización si aplica
                        $organizationID = $user->organization;
                        $organizationMapper = $organizationID !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($organizationID, 'id')) : null;
                        if ($organizationMapper == null || $organizationMapper->status == OrganizationMapper::ACTIVE) {

                            if ($isExternalLogin) {
                                if ($user->type != UsersModel::TYPE_USER_GENERAL) {
                                    $resultOperation->setValue('error', self::NO_EXTERNAL_LOGIN_AVAILABLE);
                                    $resultOperation->setValue('message', __(self::LANG_GROUP, 'El usuario no está habilitado para usar la este método de inicio de sesión'));
                                    return $response->withJson($resultOperation->getValues());
                                }
                            }

                            $otpIsValid = OTPHandler::checkValidityOTP($password, $username);
                            if (password_verify($password, $user->password) || $otpIsValid) {

                                $require2FA = OTPHandler::isEnabled2FA($userMapper->id) && OTPHandler::wasViewedCurrentUserQRData($userMapper->id);
                                $twoFactorCodeValue = $twoFactorCode->getValue();
                                $twoFactorCodeValue = is_string($twoFactorCodeValue) ? $twoFactorCodeValue : '';
                                $twoFactorIsValid = OTPHandler::checkValidityTOTP($twoFactorCodeValue, $username);

                                if (!$require2FA || $twoFactorIsValid) {

                                    OTPHandler::toExpireOTP($username);

                                    $resultOperation->setValue('auth', true);

                                    //Se genera un token de sesión
                                    $resultOperation->setValue('token', SessionToken::generateToken([
                                        'id' => $user->id,
                                        'type' => $user->type,
                                    ], null, null, get_config('check_aud_on_auth')));

                                    //Valores de usuario devueltos
                                    $userLoginData = $userMapper->humanReadable();
                                    $userLoginData['misc'] = [
                                        'avatar' => AvatarModel::getAvatar($userMapper->id),
                                    ];
                                    unset($userLoginData['password']);
                                    unset($userLoginData['meta']);

                                    foreach ($userLoginData as $k => $i) {
                                        if (strpos($k, 'META:') !== false) {
                                            unset($userLoginData[$k]);
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
                                    LoginAttemptsModel::addLogin(
                                        (int) $user->id,
                                        $user->username,
                                        false,
                                        $resultOperation->getValue('message'),
                                        $extraDataLog
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

                                if ($user->failed_attempts >= self::MAX_ATTEMPTS) {

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

        } catch (MissingRequiredParamaterException $e) {

            $resultOperation->setValue('error', self::MISSING_OR_UNEXPECTED_PARAMS);
            $resultOperation->setValue('message', $e->getMessage());
            log_exception($e);
        } catch (ParsedValueException $e) {

            $resultOperation->setValue('error', self::GENERIC_ERROR);
            $resultOperation->setValue('message', __(self::LANG_GROUP, 'Ha ocurrido un error desconocido con los valores ingresados.'));
            $resultOperation->setValue('extras', [
                'exception' => $e->getMessage(),
            ]);
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
        $resultOperation->setValue('isAuth', SessionToken::isActiveSession($JWT));
        //Verificar status de la organización si aplica
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null) {
            $organizationID = $currentUser->organization;
            $organizationMapper = $organizationID !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($organizationID, 'id')) : null;
            if ($organizationMapper != null && $organizationMapper->status != OrganizationMapper::ACTIVE) {
                $resultOperation->setValue('isAuth', false);
            }
        }
        return $response->withJson($resultOperation->getValues());
    }

    /**
     * Registra un usuario nuevo
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function register(Request $request, Response $response)
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

            //Validar que la organización sea obligatoria si no es usuario ROOT
            if ($type != UsersModel::TYPE_USER_ROOT) {
                if (!Validator::isInteger($organization)) {
                    $result
                        ->setMessage($message_organization_required)
                        ->operation($operation_name);
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
                $userMapper->first_lastname = $first_lastname;
                $userMapper->second_lastname = $second_lastname;
                $userMapper->type = $type;
                $userMapper->status = $status;
                $userMapper->failed_attempts = 0;
                $userMapper->created_at = new \DateTime();
                $userMapper->modified_at = $userMapper->created_at;

                //Asignar la organización si no es usuario ROOT
                if ($type != UsersModel::TYPE_USER_ROOT) {
                    $userMapper->organization = $organization;
                }

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
                    return ctype_digit($value);
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
                    return is_string($value) && ctype_digit($value);
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

            //Validar que la organización sea obligatoria si no es usuario ROOT
            if ($userMapper->type != UsersModel::TYPE_USER_ROOT) {
                if (!Validator::isInteger($organization) && !$isProfile) {
                    $result
                        ->setMessage($message_organization_required)
                        ->operation($operation_name);
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
                        $userMapper->first_lastname = $first_lastname;
                    }
                    if ($second_lastname !== null) {
                        $userMapper->second_lastname = $second_lastname;
                    }
                    if ($status !== null) {
                        $userMapper->status = $status;
                    }
                    if ($organization !== null && $userMapper->type != UsersModel::TYPE_USER_ROOT && !$isProfile) {
                        $userMapper->organization = $organization;
                    }
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
                    ->setMessage(__(self::LANG_GROUP, 'No existe el usuario que intenta modificar.'))
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
                    return ctype_digit($value) || is_int($value);
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
                    return ctype_digit($value) || is_int($value);
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
                    return ctype_digit($value) || is_int($value) || $value == -1 || $value == 'ANY';
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
                    return is_array($value) || ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {

                    if (is_scalar($value)) {
                        $value = [$value];
                    }

                    $value = is_array($value) ? $value : [];

                    $value = array_filter($value, function ($i) {

                        return (is_string($i) && ctype_digit($i)) || is_int($i);

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
    public static function _all(int $page = 1, int $perPage = 10, int $type = null, array $ignore = [])
    {
        $table = 'pcsphp_users';
        $fields = [
            "{$table}.*",
        ];

        $whereString = null;
        $where = [
            $type !== null ? "{$table}.type = {$type}" : '',
        ];

        $where = array_filter($where, function ($i) {
            return mb_strlen($i) > 0;
        });

        if (!empty($ignore)) {
            $ignore = implode(', ', $ignore);
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
            //Search Dropdown
            new Route(
                "{$startRoute}search-dropdown[/]",
                $users . ':searchDropdown',
                'users-search-dropdown',
                'GET'
            ),

            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $users . ':all',
                'users-ajax-all',
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
