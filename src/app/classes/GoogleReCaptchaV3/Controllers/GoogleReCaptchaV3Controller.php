<?php

/**
 * GoogleReCaptchaV3Controller.php
 */

namespace GoogleReCaptchaV3\Controllers;

use App\Controller\AdminPanelController;
use App\Model\AppConfigModel;
use GoogleReCaptchaV3\GoogleReCaptchaV3Lang;
use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * GoogleReCaptchaV3Controller.
 *
 * @package     GoogleReCaptchaV3\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class GoogleReCaptchaV3Controller extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'google-recaptcha-v3';
    /**
     * @var string
     */
    protected static $baseRouteName = 'google-recaptcha-v3';
    /**
     * @var string
     */
    public static $secretKey = '6Lc9cTgdAAAAAAwTQDa2u2mij2Utql1Ut0M7_Y_0';

    const LANG_GROUP = GoogleReCaptchaV3Lang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function action(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'token',
                null,
                function ($value) {
                    return is_string($value) && strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        $responseJSON = [
            'message' => '',
            'verify' => [],
        ];

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var string $token
             */
            $token = $expectedParameters->getValue('token');

            $now = new \DateTime();
            $nowLess1Hour = (clone $now)->modify('-1 hour');
            $requestHTTP = new HttpClient('https://www.google.com/recaptcha/api/');
            $requestHTTP->request('siteverify', 'POST', [
                'secret' => self::$secretKey,
                'response' => $token,
            ]);
            $defaultResult = (object) [
                'success' => false,
                'challenge_ts' => $now->format('c'),
                'hostname' => $_SERVER['HTTP_HOST'],
                'score' => 0.0,
                'action' => 'submit',
            ];
            $recaptchaResult = $requestHTTP->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
            $recaptchaResult = $recaptchaResult instanceof \stdClass ? $recaptchaResult : (is_array($recaptchaResult) ? (object) $recaptchaResult : null);
            $recaptchaResult = $recaptchaResult !== null ? $recaptchaResult : $defaultResult;
            if (array_key_exists('error-codes', (array) $recaptchaResult)) {
                $captchaErrorCodes = ((array) $recaptchaResult)['error-codes'];
                if (is_array($captchaErrorCodes) && !empty($captchaErrorCodes)) {
                    $recaptchaResult = $defaultResult;
                }
            }
            $recaptchaResult->token = $token;
            //Verificar validez
            $recaptchaResult->success = $recaptchaResult->score >= 0.5;

            $configElement = new AppConfigModel('GoogleReCaptchaV3Controller');
            if ($configElement->id === null) {
                $configElement->name = 'GoogleReCaptchaV3Controller';
                $configElement->value = [];
                $configElement->save();
                $configElement->id = $configElement->getLastInsertID();
            }
            $tokens = $configElement->value;

            if ($recaptchaResult->success) {
                $tokens[] = [
                    'date' => $now->format('Y-m-d H:i:s'),
                    'token' => $token,
                ];
            }

            foreach ($tokens as $indexToken => $valueToken) {
                $valueToken = (array) $valueToken;
                $dateAddedToken = new \DateTime($valueToken['date']);
                if ($dateAddedToken < $nowLess1Hour) {
                    unset($tokens[$indexToken]);
                }
            }

            $configElement->value = $tokens;
            $configElement->update();
            $responseJSON['verify'] = $recaptchaResult;

        } catch (MissingRequiredParamaterException $e) {

            $responseJSON['message'] = $e->getMessage();
            log_exception($e);

        } catch (ParsedValueException $e) {

            $responseJSON['message'] = $e->getMessage();
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $responseJSON['message'] = $e->getMessage();
            log_exception($e);

        } catch (\Exception $e) {

            $responseJSON['message'] = $e->getMessage();
            log_exception($e);

        }

        return $response->withJson($responseJSON);
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function verifyTokenCaptcha(string $token)
    {
        $configElement = new AppConfigModel('GoogleReCaptchaV3Controller');
        if ($configElement->id === null) {
            $configElement->name = 'GoogleReCaptchaV3Controller';
            $configElement->value = [];
            $configElement->save();
            $configElement->id = $configElement->getLastInsertID();
        }
        $tokens = $configElement->value;
        $existsToken = false;
        foreach ($tokens as $indexToken => $valueToken) {
            $valueToken = (array) $valueToken;
            if ($valueToken['token'] === $token) {
                $existsToken = true;
                unset($tokens[$indexToken]);
                break;
            }
        }
        if ($existsToken) {
            $configElement->value = $tokens;
            $configElement->update();
        }
        return $existsToken;
    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
    }

    /**
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

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            $routeResult = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            return is_string($routeResult) ? $routeResult : '';
        } else {
            return '';
        }
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

        $routes = [

            //──── POST ──────────────────────────────────────────────────────────────────────────────

            new Route( //Acción de verificar token
                "{$startRoute}/action/recaptcha-verify[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-verify',
                'POST'
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
