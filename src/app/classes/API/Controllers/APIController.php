<?php

/**
 * APIController.php
 */

namespace API\Controllers;

use API\Adapters\APIExternalAdapterExample;
use API\APILang;
use App\Controller\AdminPanelController;
use App\Controller\AvatarController;
use App\Controller\RecoveryPasswordController;
use App\Controller\UserProblemsController;
use App\Controller\UsersController;
use App\Model\AvatarModel;
use App\Model\RecoveryPasswordModel;
use App\Model\TicketsLogModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * APIController.
 *
 * @package     API\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APIController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'api';
    /**
     * @var string
     */
    protected static $baseRouteName = 'api-admin';
    /**
     * @var string
     */
    protected static $title = 'API';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const LANG_GROUP = APILang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);

        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function publications(Request $request, Response $response)
    {

        $context = $request->getAttribute('context');
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        if ($context == 'publications') {

            if ($actionType == 'list') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

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
                        'perPage',
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
                        'category',
                        null,
                        function ($value) {
                            return (ctype_digit($value) || is_int($value)) || $value == PublicationCategoryMapper::UNCATEGORIZED_ID;
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                    new Parameter(
                        'title',
                        null,
                        function ($value) {
                            return is_scalar($value) && mb_strlen((string) $value) > 0;
                        },
                        true,
                        function ($value) {
                            return (string) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 * @var int $perPage
                 * @var int $category
                 * @var string $title
                 */
                $page = $expectedParameters->getValue('page');
                $perPage = $expectedParameters->getValue('perPage');
                $category = $expectedParameters->getValue('category');
                $title = $expectedParameters->getValue('title');

                $controller = new PublicationsController();
                $all = $controller->_all($page, $perPage, $category, null, null, $title);

                $response = $response->withJson($all);

            } elseif ($actionType == 'detail') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $expectedParameters = new Parameters([
                    new Parameter(
                        'id',
                        null,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        false,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 */
                $id = $expectedParameters->getValue('id');

                $elementData = [];

                $publicationMapper = new PublicationMapper($id);

                if ($publicationMapper->id !== null) {

                    $elementData = $publicationMapper->humanReadable();
                    $elementData['mainImage'] = baseurl($publicationMapper->mainImage);
                    $elementData['thumbImage'] = baseurl($publicationMapper->thumbImage);
                    $elementData['createdBy'] = $publicationMapper->createdBy->id;
                    $elementData['modifiedBy'] = $publicationMapper->modifiedBy !== null ? $publicationMapper->modifiedBy->id : null;
                    unset($elementData['category']['meta']);
                    unset($elementData['category']['META:langData']);
                    unset($elementData['meta']);
                    unset($elementData['META:langData']);
                } else {
                    $elementData = null;
                }

                $response = $response->withJson([
                    'publicationData' => $elementData,
                ]);
            } else {
                throw new NotFoundException($request, $response);
            }

        } else if ($context == 'categories') {

            if ($actionType == 'list') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

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
                        'perPage',
                        10,
                        function ($value) {
                            return ctype_digit($value) || is_int($value);
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 * @var int $perPage
                 */
                $page = $expectedParameters->getValue('page');
                $perPage = $expectedParameters->getValue('perPage');

                $controller = new PublicationsCategoryController();
                $all = $controller->_all($page, $perPage);

                $response = $response->withJson($all);

                return $response;
            } else {
                throw new NotFoundException($request, $response);
            }
        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function usersActions(Request $request, Response $response)
    {

        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        if ($actionType == 'register') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            //username - ESPERADOS
            //email - ESPERADOS
            //password - ESPERADOS
            //password2 - ESPERADOS
            //firstname - ESPERADOS
            //first_lastname - ESPERADOS
            //type
            //status
            $parsedBody = $request->getParsedBody();
            $parsedBody['type'] = (string) UsersModel::TYPE_USER_GENERAL;
            $parsedBody['status'] = (string) UsersModel::STATUS_USER_INACTIVE;

            $request = $request->withParsedBody($parsedBody);

            $controller = new UsersController();
            $response = $controller->register($request, $response);

        } elseif ($actionType == 'edit') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                //username
                //email
                //is_profile
                //current-password
                //password
                //password2
                //firstname
                //first_lastname
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getParsedBodyParam('id', null);

                $availableParams = [
                    'username',
                    'email',
                    'firstname',
                    'first_lastname',
                ];

                $isSameUser = $userID == $currentUser->id;

                if ($isSameUser) {

                    if (Validator::isInteger($userID)) {

                        $userMapper = new UsersModel($userID);
                        $parsedBody['is_profile'] = true;

                        if ($userMapper->id !== null) {

                            $availableFields = array_merge(array_keys($userMapper->getFields()), array_keys($userMapper->getMetaProperties()));

                            foreach ($availableParams as $paramName) {
                                if (!array_key_exists($paramName, $parsedBody) && in_array($paramName, $availableFields) && $paramName !== 'meta') {
                                    $specialBehaviour = [
                                        'TEST' => function ($mapper) {
                                            return '';
                                        },
                                    ];
                                    $parsedBody[$paramName] = array_key_exists($paramName, $specialBehaviour) ? ($specialBehaviour[$paramName])($userMapper) : $userMapper->$paramName;
                                }
                            }
                        }
                    } else {
                        return throw403($request, $response);
                    }

                    $request = $request->withParsedBody($parsedBody);

                    $controller = new UsersController();
                    $response = $controller->edit($request, $response);

                } else {
                    return throw403($request, $response);
                }

            } else {
                return throw403($request, $response);
            }
        } elseif ($actionType == 'profile-image') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getParsedBodyParam('id', null);

                $isSameUser = $userID == $currentUser->id;

                if ($isSameUser) {

                    if (Validator::isInteger($userID)) {

                        $userMapper = new UsersModel($userID);

                        if ($userMapper->id !== null) {

                            $parsedBody['user_id'] = $userID;
                        } else {
                            return throw403($request, $response);
                        }
                    } else {
                        return throw403($request, $response);
                    }

                    $request = $request->withParsedBody($parsedBody);

                    $controller = new AvatarController();
                    $response = $controller->register($request, $response);
                } else {
                    return throw403($request, $response);
                }
            } else {
                return throw403($request, $response);
            }
        } elseif ($actionType == 'get-data-user') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getQueryParam('id', null);

                $isSameUser = $userID == $currentUser->id;

                if ($isSameUser) {

                    $userMapper = new UsersModel($userID);

                    if ($userMapper->id !== null) {

                        $userLoginData = $userMapper->humanReadable();

                        $avatar = AvatarModel::getAvatar($userMapper->id);
                        $userLoginData['misc'] = [
                            'avatar' => $avatar,
                        ];
                        unset($userLoginData['password']);
                        unset($userLoginData['meta']);

                        foreach ($userLoginData as $k => $i) {
                            if (strpos($k, 'META:') !== false) {
                                unset($userLoginData[$k]);
                                $userLoginData['misc'][str_replace('META:', '', $k)] = $i;
                            }
                        }

                        $response = $response->withJson([
                            'userData' => $userLoginData,
                        ]);
                    } else {
                        return throw403($request, $response);
                    }
                } else {
                    return throw403($request, $response);
                }
            } else {
                return throw403($request, $response);
            }
        } elseif ($actionType == 'recovery-password') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $controller = new RecoveryPasswordController();

            //Parámetros
            $params = $request->getParsedBody();

            //Conjunto posible de datos para autenticación
            $requerido = [
                'username',
            ];

            //Verificar que el grupo de datos para solicitados esté completo
            $parametros_ok = require_keys($requerido, $params) === true && count($requerido) === count($params);

            //Cuerpo de la respuesta
            $json_response = [
                'send_mail' => false,
                'error' => UserProblemsController::NO_ERROR,
                'message' => '',
            ];

            //Si los parámetros son válidos en nombre y en cantidad se inicia el proceso de recuperación
            if ($parametros_ok) {

                //Se selecciona un elemento que concuerde con el usuario
                $usuario = null;

                $username = $params['username'];

                $usuario = $controller->userMapper->getWhere([
                    'username' => [
                        '=' => $username,
                        'and_or' => 'OR',
                    ],
                    'email' => [
                        '=' => $username,
                    ],
                ]);

                //Verificación de existencia
                if ($usuario !== null) {

                    //Datos de recuperación
                    $recoveryPassword = new RecoveryPasswordModel();
                    $recoveryPassword->created = new \DateTime();
                    $recoveryPassword->expired = $recoveryPassword->created->modify('+24 hour');
                    $recoveryPassword->email = $usuario->email;
                    $recoveryPassword->code = generate_code(6);
                    $recoveryPassword->save();

                    //Envío de correo de recuperación
                    $json_response['send_mail'] = $controller->mailRecoveryPasswordCode($recoveryPassword->code, $usuario, true);
                    $json_response['message'] = __(RecoveryPasswordController::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.');

                    $logRequest = new TicketsLogModel();
                    $logRequest->created = $recoveryPassword->created;
                    $logRequest->email = $recoveryPassword->email;
                    $logRequest->information = [
                        'code' => $recoveryPassword->code,
                        'email_sended' => $json_response['send_mail'],
                        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
                    ];
                    $logRequest->type = (string) __(self::LANG_GROUP, 'Solicitud de restablecimiento de contraseña.');
                    $logRequest->save();
                } else {

                    $json_response['error'] = RecoveryPasswordController::USER_NO_EXISTS;
                    $json_response['message'] = vsprintf($controller->getMessage($json_response['error']), [$username]);
                }
            } else {

                $json_response['error'] = RecoveryPasswordController::MISSING_OR_UNEXPECTED_PARAMS;
                $json_response['message'] = $controller->getMessage($json_response['error']);
            }

            $response = $response->withJson($json_response);
        } elseif ($actionType == 'change-password-code') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            //code
            //password
            //repassword
            $parsedBody = $request->getParsedBody();
            $request = $request->withParsedBody($parsedBody);

            $controller = new RecoveryPasswordController();
            $response = $controller->newPasswordCreateCode($request, $response, []);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function externalActions(Request $request, Response $response)
    {

        $context = $request->getAttribute('context');
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        $apiHandler = new APIExternalAdapterExample('user@domain.tld', '123456');
        $hasValidToken = $apiHandler->verifyCurrentAuthToken();
        if (!$hasValidToken) {
            $apiHandler->login();
        }

        if ($context == 'context') {

            if ($actionType == 'action') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $responseJSON = [];
                $response = $response->withJson($responseJSON);

            } else {
                throw new NotFoundException($request, $response);
            }

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;
            }
        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos

        $publications = $allRoles;

        $usersActions = $allRoles;

        $externalActions = $allRoles;

        $other = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];

        $routes = [

            //──── GET|POST ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //Rutas de publicaciones
                "{$startRoute}/publications/{context}/{actionType}[/]",
                $classname . ':publications',
                self::$baseRouteName . '-publications-actions',
                'GET',
                true,
                null,
                $publications,
            ),
            new Route( //Acciones sobre el módulo de usuarios
                "{$startRoute}/users/{actionType}[/]",
                $classname . ':usersActions',
                self::$baseRouteName . '-users-actions',
                'GET|POST',
                false,
                null,
                $usersActions,
            ),
            //new Route( //Acciones sobre API externa (muestra)
            //    "{$startRoute}/external/{context}/{actionType}[/]",
            //    $classname . ':externalActions',
            //    self::$baseRouteName . '-external-actions',
            //    'GET|POST',
            //    false,
            //    null,
            //    $externalActions,
            //),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

            $route = $request->getAttribute('route');
            $routeName = $route->getName();
            $routeArguments = $route->getArguments();
            $routeArguments = is_array($routeArguments) ? $routeArguments : [];
            $basenameRoute = self::$baseRouteName . '-';

            if (strpos($routeName, $basenameRoute) !== false) {

                $simpleName = str_replace($basenameRoute, '', $routeName);
                $routeURL = self::routeName($simpleName, $routeArguments);
                $allowed = mb_strlen($routeURL) > 0;

                if (!$allowed) {
                    return throw403($request, $response);
                }
            }
            return $next($request, $response);
        });

        return $group;
    }
}
