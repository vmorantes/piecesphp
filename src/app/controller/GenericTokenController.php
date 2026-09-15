<?php
/**
 * GenericTokenController.php
 */

namespace App\Controller;

use App\Model\TokenModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * GenericTokenController.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class GenericTokenController extends AdminPanelController
{

    const TYPES_HANDLER = [
        'commentary' => [
            'validate_session' => false,
            'roles' => [],
            'method' => 'commentary',
            'has_post_route' => true,
        ],
    ];

    const LANG_GROUP = 'genericTokenModule';

    /**
     * @var int
     */
    protected $tokenID = -1;

    /**
     * @var array
     */
    protected $tokenData = [];

    /**
     * @var string
     */
    protected $selector = '';

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function entryPoint(Request $req, Response $res, array $args)
    {
        $method_request = $req->getMethod();
        $is_post = $method_request == 'POST';

        $handler = $req->getAttribute('handler');
        $token = null;
        $selector = $req->getAttribute('token', '');

        if ($is_post) {
            $selector = $req->getParsedBodyParam('token', '');
        }

        //SOLO POR SELECTOR Y DE SU TIPO: un id que se pueda calcular ya no alcanza ninguna fila, y menos de otro tipo.
        $tokenElement = self::tokenBySelector(is_string($selector) ? $selector : '');

        if ($tokenElement !== null) {
            $token = $tokenElement->token;
            $this->tokenID = (int) $tokenElement->id;
            $this->selector = (string) $tokenElement->selector;
        }

        $handler = is_string($handler) ? $handler : '';
        $token = is_string($token) ? $token : '';

        $exists = array_key_exists($handler, self::TYPES_HANDLER);

        $tokenData = BaseToken::getData($token, self::jwtKey(), null, true);
        $tokenExpired = BaseToken::isExpire($token, self::jwtKey(), null);
        $this->tokenData = is_array($tokenData) || $tokenData instanceof \stdClass  ? (array) $tokenData : [];

        $tokenController = new TokenController();

        $exists_token = $tokenController->tokenExists($token);

        if ($exists && !$tokenExpired) {

            /**
             * @var array<string,mixed> $handler
             */
            $handler = self::TYPES_HANDLER[$handler];
            /**
             * @var bool $validate_session
             */
            $validate_session = $handler['validate_session'];
            /**
             * @var array|null $roles
             */
            $roles = $handler['roles'];
            /**
             * @var string $method
             */
            $method = $handler['method'];
            /**
             * @var bool $has_post_route
             */
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
                            new Operation(__(self::LANG_GROUP, 'Mensaje')),
                        ], __(self::LANG_GROUP, 'Mensaje'));
                        $result->setValue('reload', true);
                        $result
                            ->setMessage(__(self::LANG_GROUP, 'El recurso no existe o el enlace ha expirado'))
                            ->operation(__(self::LANG_GROUP, 'Mensaje'))
                            ->setSuccess(false);

                    } else {
                        $this->render('layout/header-for-token');
                        $this->render('panel/pages/generic_token/token_invalid_or_unexists');
                        $this->render('layout/footer-for-token');
                    }

                }

            } else {

                self::deleteOwnToken($tokenElement);

                return throw403($req, []);
            }

        } else {

            self::deleteOwnToken($tokenElement);
            throw new NotFoundException($req, $res);
        }

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function commentary(Request $req, Response $res)
    {
        $method = $req->getMethod();

        if ($method == 'GET') {

            $this->render('layout/header-for-token');
            $this->render('panel/pages/generic_token/commentary', [
                'action' => get_route('generic-token-action', ['handler' => 'commentary']),
                'method_action' => 'POST',
                'token' => $this->selector,
                'tokenData' => $this->tokenData,
            ]);
            $this->render('layout/footer-for-token');

        } elseif ($method == 'POST') {

            $operation_name = __(self::LANG_GROUP, 'Enviar comentario');

            $result = new ResultOperations([
                new Operation($operation_name),
            ], $operation_name);

            $message_sended = __(self::LANG_GROUP, 'Enviado.');
            $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');

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

            $token = new Parameter('token', null);

            $token->setValidator(function ($value) {

                return is_string($value) && self::isSelector($value);

            })->setParser(function ($value) {

                return (string) $value;

            });

            $parametersExcepted = new Parameters([
                $email,
                $subject,
                $message,
                $token,
            ]);

            $parametersExcepted->setInputValues($req->getParsedBody());

            try {

                $parametersExcepted->validate();
                $tokenElement = self::tokenBySelector((string) $token->getValue());

                if ($tokenElement !== null) {

                    $tokenData = BaseToken::getData($tokenElement->token, self::jwtKey(), null, true);
                    $this->tokenData = is_array($tokenData) || $tokenData instanceof \stdClass  ? (array) $tokenData : [];

                    //ACCIONES AL ENVIAR

                    $mailer = new Mailer();
                    $mailConfig = new MailConfig;

                    $mailer->setFrom($mailConfig->user(), $mailConfig->name());
                    $mailer->addAddress($mailConfig->user(), $mailConfig->name());
                    $mailer->isHTML(true);
                    $mailer->Subject = mb_convert_encoding((string) $subject->getValue(), 'UTF-8');

                    $data = [];

                    $data['text'] = mb_convert_encoding($message->getValue(), 'UTF-8');
                    $data['note'] = '';
                    $data['url'] = '';
                    $data['text_button'] = '';

                    $mailer->Body = $this->render('mailing/template_base', $data, false, false);

                    if (!$mailer->checkSettedSMTP()) {
                        $mailer->asGoDaddy();
                    }

                    $success = $mailer->send();

                    //FIN DE ACCIONES AL ENVIAR

                } else {
                    throw new \Exception(__(self::LANG_GROUP, 'El recurso al que intenta acceder ha expirado o ya ha sido utilizado.'));
                }

                if ($success) {

                    $result->setValue('reload', true);

                    $result
                        ->setMessage($message_sended)
                        ->operation($operation_name)
                        ->setSuccess(true);

                    self::deleteOwnToken($tokenElement);

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
     * Un selector nuevo: 16 bytes aleatorios en hexadecimal. Es lo único que viaja en la URL.
     * @return string
     */
    public static function newSelector(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param string $selector
     * @return bool
     */
    public static function isSelector(string $selector): bool
    {
        return preg_match('/^[0-9a-f]{32}$/', $selector) === 1;
    }

    /**
     * La fila de un token genérico por su selector, y solo si es de TOKEN_GENERIC_CONTROLLER.
     * @param string $selector
     * @return \stdClass|null
     */
    public static function tokenBySelector(string $selector): ?\stdClass
    {
        if (!self::isSelector($selector)) {
            return null;
        }
        $tokenModel = new TokenModel();
        $tokenModel->select()->where([
            'selector' => $selector,
            'type' => TokenController::TOKEN_GENERIC_CONTROLLER,
        ])->execute();
        $result = $tokenModel->result();
        return is_array($result) && isset($result[0]) && $result[0] instanceof \stdClass ? $result[0] : null;
    }

    /**
     * Borra un token genérico, y solo ese: la fila existe, es de su tipo y su selector es válido. Si no, no toca nada.
     * @param \stdClass|null $tokenElement La fila que devolvió tokenBySelector()
     * @return bool
     */
    public static function deleteOwnToken(?\stdClass $tokenElement): bool
    {
        if ($tokenElement === null || ($tokenElement->type ?? null) !== TokenController::TOKEN_GENERIC_CONTROLLER || !self::isSelector((string) ($tokenElement->selector ?? ''))) {
            return false;
        }
        $tokenModel = new TokenModel();
        return (bool) $tokenModel->delete([
            'id' => (int) $tokenElement->id,
            'selector' => (string) $tokenElement->selector,
            'type' => TokenController::TOKEN_GENERIC_CONTROLLER,
        ])->execute();
    }

    /**
     * La clave de los JWT de los tokens genéricos: derivada de app_key para este uso, sin literales.
     * @param string|null $appKey Null: la de la app
     * @return string
     */
    public static function jwtKey(?string $appKey = null): string
    {
        return Config::app_key_derived('generic-token-controller-jwt', $appKey);
    }

    /**
     * @param string $handlerName
     * @param array $data
     * @param int $duration Minutos
     * @return string
     */
    public static function createTokenURL(string $handlerName, array $data, int $duration = 60)
    {
        $token = self::createToken($data, $duration);

        //LA URL LLEVA UN SELECTOR OPACO, no el id: el id iba cifrado con una clave pública y se podía calcular.
        $selector = self::newSelector();
        $tokenModel = new TokenModel();

        $tokenModel->insert([
            'token' => $token,
            'type' => TokenController::TOKEN_GENERIC_CONTROLLER,
            'selector' => $selector,
        ])->execute();

        return get_route('generic-token-view', [
            'handler' => $handlerName,
            'token' => $selector,
        ]);
    }

    /**
     * @param array $data
     * @param int $duration Minutos
     * @return string
     */
    public static function createToken(array $data, int $duration = 60)
    {
        $time = time();
        $duration = $duration * 60 + $time;
        $token = BaseToken::setToken($data, self::jwtKey(), $time, $duration);
        return $token;
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
