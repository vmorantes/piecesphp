<?php

/**
 * UserSystemFeaturesController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use App\Controller\AdminPanelController;
use App\Controller\UsersController;
use App\Model\LoginAttemptsModel;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Exceptions\SafeException;

/**
 * UserSystemFeaturesController.
 *
 * @package     PiecesPHP\UserSystem\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class UserSystemFeaturesController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'user-system-features';
    /**
     * @var string
     */
    protected static $baseRouteName = 'user-system-features';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function generateOTP(Request $request, Response $response)
    {
        $username = $request->getQueryParam('username', null);
        $username = is_string($username) && mb_strlen($username) > 0 ? $username : '';

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Generación de OTP'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('error', "");
        $resultOperation->setValue('error', "");
        $resultOperation->setValue('user', $username);
        $resultOperation->setSuccessOnSingleOperation(false);

        try {
            OTPHandler::generateOTP($username);
            $resultOperation->setSuccessOnSingleOperation(true);
            $resultOperation->setMessage(__(self::LANG_GROUP, 'Revise su correo electrónico para obtener el cógido de un uso.'));
        } catch (SafeException $exception) {
            if ($exception->getCode() == SafeException::USER_NOT_EXISTS) {
                $usersController = new UsersController();
                $resultOperation->setValue('error', UsersController::USER_NO_EXISTS);
                $resultOperation->setValue('message', vsprintf($usersController->getMessage(UsersController::USER_NO_EXISTS), [$username]));
                LoginAttemptsModel::addLogin(
                    null,
                    $username,
                    false,
                    $resultOperation->getValue('message'),
                    []
                );
            } else {
                $resultOperation->setMessage($exception->getMessage());
            }
        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function markQRDataAsViewed(Request $request, Response $response)
    {

        $currentUser = getLoggedFrameworkUser();

        if ($currentUser !== null) {

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'QR de 2FA usado'));

            $resultOperation->setSingleOperation(true); //Se define que es de una única operación
            $resultOperation->setSuccessOnSingleOperation(false);
            $resultOperation->setValue('redirect', false);
            $resultOperation->setValue('redirect_to', null);
            $resultOperation->setValue('reload', false);
            $resultOperation->setSuccessOnSingleOperation(true);

            $currentUser->TOTPData->twoAuthFactorQRViewed = 1;
            $currentUser->TOTPData->update();
            $resultOperation->setSuccessOnSingleOperation(true);

            $response = $response->withJson($resultOperation);

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function getTOTPDataQR(Request $request, Response $response)
    {

        $currentUser = getLoggedFrameworkUser();

        if ($currentUser !== null) {

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'QR de 2FA'));

            $resultOperation->setSingleOperation(true); //Se define que es de una única operación
            $resultOperation->setSuccessOnSingleOperation(false);
            $resultOperation->setValue('redirect', false);
            $resultOperation->setValue('redirect_to', null);
            $resultOperation->setValue('reload', false);
            $resultOperation->setValue('QRDataURL', '');

            if (!OTPHandler::wasViewedCurrentUserQRData()) {
                $resultOperation->setValue('QRDataURL', OTPHandler::getCurrentUserQRData());
                $resultOperation->setSuccessOnSingleOperation(true);
            } else {
                $resultOperation->setMessage(__(self::LANG_GROUP, 'El QR ya caducó.'));
            }

            $response = $response->withJson($resultOperation);

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function getCurrentTOTP(Request $request, Response $response)
    {
        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Generación de TOTP'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('code', OTPHandler::getCurrentUserTOTP());
        $resultOperation->setSuccessOnSingleOperation(true);
        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function checkTOTP(Request $request, Response $response)
    {
        $username = $request->getParsedBodyParam('username', null);
        $username = is_string($username) && mb_strlen($username) > 0 ? $username : '';
        $totp = $request->getParsedBodyParam('totp', null);
        $totp = is_string($totp) && mb_strlen($totp) > 0 ? $totp : '';
        $valid = OTPHandler::checkValidityTOTP($totp, $username);
        $okMessage = __(self::LANG_GROUP, 'Código aceptado.');
        $badMessage = __(self::LANG_GROUP, 'Código inválido.');
        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Verificación de TOTP'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setSuccessOnSingleOperation($valid);
        $resultOperation->setMessage($valid ? $okMessage : $badMessage);

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function configureTOTP(Request $request, Response $response)
    {

        $currentUser = getLoggedFrameworkUser();

        if ($currentUser !== null) {

            $enable = $request->getParsedBodyParam('enable', null) === 'yes';
            $totp = $request->getParsedBodyParam('totp', null);
            $totp = is_string($totp) && mb_strlen(trim($totp)) > 0 ? $totp : null;
            $issuerName = $request->getParsedBodyParam('issuerName', null);
            $issuerName = is_string($issuerName) && mb_strlen(trim($issuerName)) > 0 ? $issuerName : get_config('owner');
            $password = $request->getParsedBodyParam('password', null);
            $password = is_string($password) && mb_strlen(trim($password)) > 0 ? $password : '';

            $isCurrentlyEnabled = OTPHandler::isEnabled2FA();
            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, '2AF'));

            $resultOperation->setSingleOperation(true); //Se define que es de una única operación
            $resultOperation->setSuccessOnSingleOperation(false);
            $resultOperation->setValue('redirect', false);
            $resultOperation->setValue('redirect_to', null);
            $resultOperation->setValue('reload', false);
            $resultOperation->setValue('securityCode', '');
            $resultOperation->setValue('enable', $isCurrentlyEnabled);

            if (password_verify($password, $currentUser->password)) {

                if ($enable) {

                    if (!$isCurrentlyEnabled) {
                        $securityCode = sha1(uniqid() . generate_pass(50)['password']);
                        OTPHandler::toggleCurrentUser2AF($enable, $securityCode, $issuerName);
                        $resultOperation->setValue('securityCode', $securityCode);
                    }

                    $resultOperation->setValue('enable', true);
                    $resultOperation->setMessage(__(self::LANG_GROUP, 'Activado.'));
                    $resultOperation->setSuccessOnSingleOperation(true);

                } else {

                    $disabled = false;
                    if ($isCurrentlyEnabled) {

                        if (OTPHandler::checkValidityTOTP($totp, $currentUser->username)) {
                            OTPHandler::toggleCurrentUser2AF($enable, '');
                            $disabled = true;
                        } else {
                            $resultOperation->setMessage(__(self::LANG_GROUP, 'El código de la aplicación es inválido.'));
                        }

                    } else {
                        $disabled = true;
                    }

                    $resultOperation->setSuccessOnSingleOperation($disabled);
                    if ($disabled) {
                        $resultOperation->setValue('enable', false);
                        $resultOperation->setMessage(__(self::LANG_GROUP, 'Desactivado.'));
                    }
                }

            } else {
                $resultOperation->setMessage(__(self::LANG_GROUP, 'Contraseña errónea.'));
            }

            $response = $response->withJson($resultOperation);

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new UserSystemFeaturesController)->render(trim($name, '/'), $data, $mode, $format);
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

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            new Route(
                "{$startRoute}/generate-otp[/]",
                $classname . ':generateOTP',
                self::$baseRouteName . '-generate-otp',
                'GET',
                false
            ),
            new Route(
                "{$startRoute}/get-current-totp[/]",
                $classname . ':getCurrentTOTP',
                self::$baseRouteName . '-get-current-totp',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}/get-current-totp-qr-data[/]",
                $classname . ':getTOTPDataQR',
                self::$baseRouteName . '-get-current-totp-qr-data',
                'GET',
                true,
                null,
                $allRoles
            ),

            //──── POST ───────────────────────────────────────────────────────────────────────────────
            new Route(
                "{$startRoute}/mark-current-totp-qr-as-viewed[/]",
                $classname . ':markQRDataAsViewed',
                self::$baseRouteName . '-mark-current-totp-qr-as-viewed',
                'POST',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}/check-totp[/]",
                $classname . ':checkTOTP',
                self::$baseRouteName . '-check-totp',
                'POST',
                false
            ),
            new Route(
                "{$startRoute}/configure-totp[/]",
                $classname . ':configureTOTP',
                self::$baseRouteName . '-configure-totp',
                'POST',
                true,
                null,
                $allRoles
            ),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
