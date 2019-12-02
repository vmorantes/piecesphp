<?php

/**
 * GenericTokenController.php
 */

namespace App\Controller;

use App\Model\TokenModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * GenericTokenController.
 *
 * GenericTokenController.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class GenericTokenController extends AdminPanelController
{
    const KEY_JWT = 'GenericTokenController';

    const TYPES_HANDLER = [
        'commentary' => [
            'validate_session' => false,
            'roles' => [],
            'method' => 'commentary',
            'has_post_route' => true,
        ],
    ];

    /**
     * $tokenString
     *
     * @var string
     */
    protected $tokenString = '';

    /**
     * $tokenData
     *
     * @var array
     */
    protected $tokenData = [];

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * entryPoint
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function entryPoint(Request $req, Response $res, array $args)
    {
        $method_request = $req->getMethod();
        $is_post = $method_request == 'POST';

        $handler = $req->getAttribute('handler');
        $token = $req->getAttribute('token');

        if ($is_post) {
            $token = $req->getParsedBodyParam('token', '');
        }

        $handler = is_string($handler) ? $handler : '';
        $token = is_string($token) ? $token : '';

        $exists = array_key_exists($handler, self::TYPES_HANDLER);

        $this->tokenString = $token;
        $token = BaseToken::getData($token, self::KEY_JWT, null, true);
        $this->tokenData = is_array($token) ? $token : [];

        $tokenController = new TokenController();
        $exists_token = $tokenController->tokenExists($this->tokenString);

        if ($exists) {

            $handler = self::TYPES_HANDLER[$handler];
            $validate_session = $handler['validate_session'];
            $roles = $handler['roles'];
            $method = $handler['method'];
            $has_post_route = $handler['has_post_route'];

            $valid_user = false;

            if ($validate_session) {

                $JWT = SessionToken::getJWTReceived();
                $isActiveSession = SessionToken::isActiveSession($JWT);

                if ($isActiveSession) {

                    if (is_array($roles)) {

                        if (in_array($this->user->type, $roles)) {

                            $valid_user = true;

                        }

                    } else {

                        $valid_user = true;

                    }

                }

            } else {

                $valid_user = true;

            }

            if ($valid_user) {

                if ($exists_token || ($has_post_route && $is_post)) {

                    return $this->$method($req, $res, $args);

                } else {

                    if ($is_post) {

                        $result = new ResultOperations([
                            new Operation(__('genericTokenModule', 'Mensaje')),
                        ], __('genericTokenModule', 'Mensaje'));
                        $result->setValue('reload', true);
                        $result
                            ->setMessage(__('genericTokenModule', 'El recurso no existe o el enlace ha expirado'))
                            ->operation(__('genericTokenModule', 'Mensaje'))
                            ->setSuccess(false);

                    } else {
                        $this->render('layout/header');
                        $this->render('panel/pages/generic_token/token_invalid_or_unexists');
                        $this->render('layout/footer');
                    }

                }

            } else {

                $tokenController->deleteToken($this->tokenString);

                $controller = new \PiecesPHP\Core\BaseController(false);
                $controller->render('pages/403');
                return $res->withStatus(403);
            }

        } else {

            $tokenController->deleteToken($this->tokenString);
            throw new NotFoundException($req, $res);
        }

        return $res;
    }

    /**
     * commentary
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function commentary(Request $req, Response $res, array $args)
    {
        $method = $req->getMethod();

        if ($method == 'GET') {

            $this->render('layout/header');
            $this->render('panel/pages/generic_token/commentary', [
                'action' => get_route('generic-token-action', ['handler' => 'commentary']),
                'method_action' => 'POST',
                'token' => $this->tokenString,
            ]);
            $this->render('layout/footer');

        } elseif ($method == 'POST') {

            $operation_name = __('genericTokenModule', 'Enviar comentario');

            $result = new ResultOperations([
                new Operation($operation_name),
            ], $operation_name);

            $message_sended = __('genericTokenModule', 'Enviado.');
            $message_unknow_error = __('genericTokenModule', 'Ha ocurrido un error inesperado.');

            $email = new Parameter('email', null);

            $email->setValidator(function ($value) {

                return is_string($value);

            })->setParser(function ($value) {

                return trim($value);

            });

            $subject = new Parameter('subject', null);

            $subject->setValidator(function ($value) {

                return is_string($value);

            })->setParser(function ($value) {

                return trim($value);

            });

            $message = new Parameter('message', null);

            $message->setValidator(function ($value) {

                return is_string($value);

            })->setParser(function ($value) {

                return trim($value);

            });

            $resquired_response = new Parameter('response', false, null, true);

            $resquired_response->setValidator(function ($value) {

                return is_string($value) || is_bool($value);

            })->setParser(function ($value) {

                return trim($value) === 'yes';

            });

            $parametersExcepted = new Parameters([
                $email,
                $subject,
                $message,
                $resquired_response,
            ]);

            $parametersExcepted->setInputValues($req->getParsedBody());

            try {

                $parametersExcepted->validate();

                $resquired_response = $resquired_response->getValue();

                $mailer = new Mailer();

                $mailer->setFrom($mailer->Username, $mailer->Username);
                $mailer->addAddress($email->getValue(), $email->getValue());
                $mailer->isHTML(true);
                $mailer->Subject = utf8_decode($subject->getValue());

                $data = [];

                $data['text'] = utf8_decode($message->getValue());
                $data['with_link'] = false;
                $data['note'] = '';
                $data['url'] = '';
                $data['text_button'] = '';

                if ($resquired_response) {

                    $data['with_link'] = true;
                    $data['note'] = utf8_decode(__('genericTokenModule', 'El enlace tendrá validez de una hora.'));
                    $data['url'] = self::createTokenURL('HANDLER', [
                        //INFORMACIÓN
                    ], 60);
                    $data['text_button'] = __('genericTokenModule', 'Responder');

                }

                $mailer->Body = $this->render('panel/pages/generic_token/mail_template', $data, false, false);

                $success = $mailer->send();

                if ($success) {

                    $result->setValue('reload', true);

                    $result
                        ->setMessage($message_sended)
                        ->operation($operation_name)
                        ->setSuccess(true);

                    $tokenController = new TokenController();
                    $tokenController->deleteToken($this->tokenString);

                } else {

                    $result
                        ->setMessage($message_unknow_error)
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

            return $res->withJson($result);

        } else {

            throw new NotFoundException($req, $res);

        }

        return $res;
    }

    /**
     * createTokenURL
     *
     * @param string $handlerName
     * @param array $data
     * @param int $duration Minutos
     * @return string
     */
    public static function createTokenURL(string $handlerName, array $data, int $duration = 60)
    {
        $token = self::createToken($data, $duration);

        $tokenModel = new TokenModel();

        $tokenModel->insert([
            'token' => $token,
            'type' => TokenController::TOKEN_GENERIC_CONTROLLER,
        ])->execute();

        return get_route('generic-token-view', [
            'handler' => $handlerName,
            'token' => $token,
        ]);
    }

    /**
     * createToken
     *
     * @param array $data
     * @param int $duration Minutos
     * @return string
     */
    public static function createToken(array $data, int $duration = 60)
    {
        $time = time();
        $duration = $duration * 60 + $time;
        return BaseToken::setToken($data, self::KEY_JWT, $time, $duration);
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
        $classname = GenericTokenController::class;
        $routes = [];

        //──── GET ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}{handler}/{token}[/]",
            $classname . ':entryPoint',
            'generic-token-view',
            'GET'
        );

        //──── POST ────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}{handler}[/]",
            $classname . ':entryPoint',
            'generic-token-action',
            'POST'
        );

        $group->register($routes);

        return $group;
    }

}
