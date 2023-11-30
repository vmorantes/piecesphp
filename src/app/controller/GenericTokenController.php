<?php
/**
 * GenericTokenController.php
 */

namespace App\Controller;

use App\Model\TokenModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseToken;
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
use \PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use \PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;

/**
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

    const LANG_GROUP = 'genericTokenModule';

    /**
     * @var int
     */
    protected $tokenID = -1;

    /**
     * @var array
     */
    protected $tokenData = [];

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
        $tokenID = $req->getAttribute('token', '');

        if ($is_post) {
            $tokenID = $req->getParsedBodyParam('token', '');
        }

        $tokenID = BaseHashEncryption::decrypt($tokenID, self::class);
        $tokenID = ctype_digit($tokenID) || is_int($tokenID) ? (int) $tokenID : null;

        if ($tokenID !== null) {

            $tokenModel = new TokenModel();
            $tokenModel->select()->where([
                'id' => $tokenID,
            ]);
            $tokenModel->execute();
            $tokenElement = $tokenModel->result();
            $tokenElement = !empty($tokenElement) ? $tokenElement[0] : null;

            if ($tokenElement !== null) {
                $token = $tokenElement->token;
                $this->tokenID = (int) $tokenElement->id;
            }

        }

        $handler = is_string($handler) ? $handler : '';
        $token = is_string($token) ? $token : '';

        $exists = array_key_exists($handler, self::TYPES_HANDLER);

        $tokenData = BaseToken::getData($token, self::KEY_JWT, null, true);
        $this->tokenData = is_array($tokenData) || $tokenData instanceof \stdClass ? (array) $tokenData : [];

        $tokenController = new TokenController();

        $exists_token = $tokenController->tokenExists($token);

        if ($exists) {

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
                        $this->render('layout/header');
                        $this->render('panel/pages/generic_token/token_invalid_or_unexists');
                        $this->render('layout/footer');
                    }

                }

            } else {

                $tokenController->deleteTokenByID($this->tokenID);

                return throw403($req, []);
            }

        } else {

            $tokenController->deleteTokenByID($this->tokenID);
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

            $this->render('layout/header');
            $this->render('panel/pages/generic_token/commentary', [
                'action' => get_route('generic-token-action', ['handler' => 'commentary']),
                'method_action' => 'POST',
                'token' => $this->tokenID,
                'tokenData' => $this->tokenData,
            ]);
            $this->render('layout/footer');

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

                return (is_string($value) && ctype_digit($value)) || is_int($value);

            })->setParser(function ($value) {

                return (int) $value;

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
                $this->tokenID = $token->getValue();

                $tokenModel = new TokenModel();
                $tokenModel->select()->where([
                    'id' => $this->tokenID,
                ]);
                $tokenModel->execute();
                $tokenElement = $tokenModel->result();
                $tokenElement = !empty($tokenElement) ? $tokenElement[0] : null;

                if ($tokenElement !== null) {

                    $tokenData = BaseToken::getData($tokenElement->token, self::KEY_JWT, null, true);
                    $this->tokenData = is_array($tokenData) || $tokenData instanceof \stdClass ? (array) $tokenData : [];

                    //ACCIONES AL ENVIAR

                    $mailer = new Mailer();
                    $mailConfig = new MailConfig;

                    $mailer->setFrom($mailConfig->user(), $mailConfig->name());
                    $mailer->addAddress($mailConfig->user(), $mailConfig->name());
                    $mailer->isHTML(true);
                    $mailer->Subject = mb_convert_encoding((string) $subject->getValue(), 'UTF-8');

                    $data = [];

                    $data['text'] = mb_convert_encoding($message->getValue(), 'UTF-8');
                    $data['with_link'] = false;
                    $data['note'] = '';
                    $data['url'] = '';
                    $data['text_button'] = '';

                    $mailer->Body = $this->render('panel/pages/generic_token/mail_template', $data, false, false);

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

                    $tokenController = new TokenController();
                    $tokenController->deleteTokenByID($this->tokenID);

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

        $tokenID = $tokenModel->lastInsertId();
        $tokenID = BaseHashEncryption::encrypt($tokenID, self::class);

        return get_route('generic-token-view', [
            'handler' => $handlerName,
            'token' => $tokenID,
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
        $token = BaseToken::setToken($data, self::KEY_JWT, $time, $duration);
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
