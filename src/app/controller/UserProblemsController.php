<?php

/**
 * UserProblemsController.php
 */

namespace App\Controller;

use App\Model\TicketsLogModel;
use App\Model\UserProblemsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * UserProblemsController.
 *
 * Controlador de problemas con el usuario
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class UserProblemsController extends UsersController
{
    const TYPE_USER_FORGET = 'TYPE_USER_FORGET';
    const TYPE_USER_BLOCKED = 'TYPE_USER_BLOCKED';

    /**
     * $userMapper
     *
     * @var UsersModel
     */
    protected $userMapper = null;

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
        $this->userMapper = new UsersModel();
    }

    /**
     * userProblemsList
     *
     * Vista del listado de problemas de usuario
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function userProblemsList(Request $request, Response $response, array $args)
    {

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* Librerías de la aplicación */
        import_app_libraries();

        set_custom_assets([
            base_url('statics/login-and-recovery/css/problems.css'),
        ], 'css');
        $this->render('usuarios/problems/problems-list');

        return $response;
    }

    /**
     * userForgetForm
     *
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function userForgetForm(Request $request, Response $response, array $args)
    {
        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries();

        set_custom_assets([
            base_url('statics/login-and-recovery/css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            baseurl('statics/login-and-recovery/js/user-forget.js'),
        ], 'js');

        $this->render('usuarios/problems/user_forget');

        return $response;
    }

    /**
     * userBlockedForm
     *
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function userBlockedForm(Request $request, Response $response, array $args)
    {
        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries();

        set_custom_assets([
            base_url('statics/login-and-recovery/css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            baseurl('statics/login-and-recovery/js/user-blocked.js'),
        ], 'js');

        $this->render('usuarios/problems/user_blocked');

        return $response;
    }

    /**
     * otherProblemsForm
     *
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function otherProblemsForm(Request $request, Response $response, array $args)
    {
        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* izitoast */
        import_izitoast();
        /* Librerías de la aplicación */
        import_app_libraries();

        set_custom_assets([
            base_url('statics/login-and-recovery/css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            baseurl('statics/login-and-recovery/js/other-problems.js'),
        ], 'js');

        $this->render('usuarios/problems/other-problems');

        return $response;
    }

    /**
     * Genera y envía el código
     *
     * Este método espera recibir por POST: [username,type]
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function generateCode(Request $request, Response $response, array $args)
    {

        //Parámetros
        $params = $request->getParsedBody();

        //Conjunto posible de datos para autenticación
        $requerido = [
            'username',
            'type',
        ];

        //Verificar que el grupo de datos para solicitados esté completo
        $parametros_ok = require_keys($requerido, $params) === true && count($requerido) === count($params);

        //Cuerpo de la respuesta
        $json_response = [
            'send_mail' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Si los parámetros son válidos en nombre y en cantidad se inicia el proceso de recuperación
        if ($parametros_ok) {

            $username = $params['username'];
            $type = $params['type'];

            //Se verifica que el tipo esté implementado
            if (in_array(trim($type), [self::TYPE_USER_FORGET, self::TYPE_USER_BLOCKED])) {

                //Se selecciona un elemento que concuerde con el usuario
                $usuario = null;

                $usuario = $this->userMapper->getWhere([
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

                    //Datos de recuperación
                    $problems = new UserProblemsModel();
                    $problems->created = date('Y-m-d h:i:s');
                    $problems->expired = $problems->created->modify('+24 hour');
                    $problems->email = $usuario->email;
                    $problems->code = generate_code(6);
                    $problems->type = $type;
                    $problems->save();

                    //Envío de correo de recuperación
                    $json_response['send_mail'] = $this->sendCode($problems->code, $usuario, $type);
                    $json_response['message'] = __('usersProblems', 'Se ha enviado un mensaje al correo proporcionado.');

                    $logRequest = new TicketsLogModel();
                    $logRequest->created = $problems->created;
                    $logRequest->email = $problems->email;
                    $logRequest->information = [
                        'code' => $problems->code,
                        'email_sended' => $json_response['send_mail'],
                        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
                    ];
                    if ($type == self::TYPE_USER_FORGET) {
                        $logRequest->type = __('usersProblems', 'Solicitud por nombre de usuario olvidado.');
                    } elseif ($type == self::TYPE_USER_BLOCKED) {
                        $logRequest->type = __('usersProblems', 'Solicitud de desbloqueo de usuario.');
                    }
                    $logRequest->save();
                } else {

                    $json_response['error'] = self::USER_NO_EXISTS;
                    $json_response['message'] = vsprintf($this->getMessage($json_response['error']), [$username]);
                }
            } else {
                $json_response['error'] = self::UNEXPECTED_ACTION;
                $json_response['message'] = $this->getMessage($json_response['error']);
            }
        } else {

            $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
            $json_response['message'] = $this->getMessage($json_response['error']);
        }

        return $response->withJson($json_response);
    }

    /**
     * resolveProblem
     *
     * Verifica el código y devuelve el usuario en caso de ser correcto
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function resolveProblem(Request $request, Response $response, array $args)
    {
        //Cuerpo de respuesta
        $json_response = [
            'success' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Conjunto posible de datos para autenticación
        $requerido = [
            'code',
            'type',
        ];

        $args = $request->getParsedBody();

        //Verificar que el grupo de datos para autenticación sea válido
        $parametros_ok = require_keys($requerido, $args) === true && count($requerido) === count($args);

        if ($parametros_ok) {

            $code = trim($args['code']);
            $type = trim($args['type']);

            //Verificar si existe
            $exist = UserProblemsModel::exist($code);

            if ($exist) {

                $problems = UserProblemsModel::instanceByCode($code);

                $user = new UsersModel();
                $user = $user->getByEmail($problems->email);

                $exist_user = $user !== -1;

                //Verificar que el usuario existe
                if ($exist_user) {

                    $now = new \DateTime();
                    $expired = $problems->expired <= $now;

                    //Verificar expiración
                    if (!$expired) {

                        if ($problems->type == self::TYPE_USER_FORGET && $type == self::TYPE_USER_FORGET) {

                            $json_response['success'] = true;
                            $json_response['username'] = $user->username;
                            $json_response['message'] = __('usersProblems', 'Su nombre de usuario es') . ': ' . $user->username;

                            $problems->getModel()->delete("id = '$problems->id'")->execute();
                        } elseif ($problems->type == self::TYPE_USER_BLOCKED && $type == self::TYPE_USER_BLOCKED) {

                            $is_block = $user->status == UsersModel::STATUS_USER_INACTIVE;
                            $blocked_by_attempts = $user->failed_attempts >= UsersController::MAX_ATTEMPTS;

                            if ($blocked_by_attempts) {
                                $user = new UsersModel($user->id);
                                $unblocked = $user->resetAttempts($user->id) && $user->changeStatus(UsersModel::STATUS_USER_ACTIVE, $user->id);

                                if ($unblocked) {
                                    $json_response['success'] = true;
                                    $json_response['username'] = $user->username;
                                    $json_response['message'] = __('usersProblems', 'Su usuario ha sido desbloqueado.');
                                } else {
                                    $json_response['message'] = __('usersProblems', 'No se ha podido procesar la información, intente más tarde.');
                                }
                            } else {
                                if ($is_block) {
                                    $json_response['message'] = __('usersProblems', 'El usuario no ha podido desbloquearse, contacte con el soporte.');
                                } else {
                                    $json_response['message'] = __('usersProblems', 'El usuario no está bloqueado.');
                                }
                            }

                            $problems->getModel()->delete("id = '$problems->id'")->execute();
                        } else {
                            $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                            $json_response['message'] = $this->getMessage($json_response['error']);
                        }
                    } else {
                        $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                        $json_response['message'] = $this->getMessage($json_response['error']);
                    }
                } else {
                    $json_response['error'] = self::USER_NO_EXISTS;
                    $json_response['message'] = $this->getMessage($json_response['error']);
                }
            } else {

                $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                $json_response['message'] = $this->getMessage($json_response['error']);
            }
        } else {
            $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
            $json_response['message'] = $this->getMessage($json_response['error']);
        }

        return $response->withJson($json_response);
    }

    /**
     * Envía un mensaje
     *
     * Este método espera recibir por POST: [name,email,message]
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function sendMailOtherProblems(Request $request, Response $response, array $args)
    {

        //Parámetros
        $params = $request->getParsedBody();

        //Conjunto posible de datos para autenticación
        $requerido = [
            'name',
            'email',
            'message',
        ];

        //Verificar que el grupo de datos para solicitados esté completo
        $parametros_ok = require_keys($requerido, $params) === true;

        //Cuerpo de la respuesta
        $json_response = [
            'send_mail' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Si los parámetros son válidos en nombre y en cantidad se inicia el proceso de recuperación
        if ($parametros_ok) {

            $name = $params['name'] . (isset($params['lastname']) ? ' ' . $params['lastname'] : '');
            $email = $params['email'];
            $message = $params['message'];
            $extra = isset($params['extra']) ? $params['extra'] : null;

            //Envío de ticket
            /**
             * @var array $result
             * @var bool $result['success']
             * @var OsTicketAPI $result['instance']
             */
            $result = $this->sendMessageOtherProblems($email, $name, $message, $extra);

            /**
             * @var bool $success
             */
            $success = $result['success'];

            /**
             * @var OsTicketAPI $instance
             */
            $instance = $result['instance'];

            $json_response['send_mail'] = $success;

            $logRequest = new TicketsLogModel();
            $logRequest->created = new \DateTime();
            $logRequest->name = $name;
            $logRequest->email = $email;
            $logRequest->message = $message;
            $logRequest->information = [
                'email_sended' => $success,
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
            ];
            $logRequest->type = __('usersProblems', 'Otros inconvenientes (osTicket).');
            $logRequest->save();

            if ($success) {
                $json_response['message'] = __('usersProblems', 'Se ha enviado un mensaje al correo proporcionado.');
            } else {
                $json_response['message'] = __('usersProblems', 'No se ha podido enviar el mensaje, intente más tarde.');
                $json_response['extra'] = $instance->getHttpClient()->getResponseHeaders();
            }
        } else {

            $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
            $json_response['message'] = $this->getMessage($json_response['error']);
        }

        return $response->withJson($json_response);
    }

    /**
     * Envía un correo con el código
     *
     * @param string $code
     * @param stdClass $usuario
     *
     * @return bool true si se envió, false si no
     */
    private function sendCode(string $code, \stdClass $usuario, $type = 'TYPE_USER_FORGET')
    {
        $mail = new Mailer();

        $from = get_config('mail')['user'];

        $from_name = get_title();

        $to = $usuario->email;

        $to_name = $usuario->username;

        $subject = __('usersProblems', 'Código de verificación');

        if ($type == self::TYPE_USER_FORGET) {
            $url = get_route('user-forget-form') . '?code=' . $code;
            $message = $this->render('usuarios/mail/user_forget_code', [
                'code' => $code,
                'url' => $url,

            ], false);
        } elseif ($type == self::TYPE_USER_BLOCKED) {
            $url = get_route('user-blocked-form') . '?code=' . $code;
            $message = $this->render('usuarios/mail/user_blocked_code', [
                'code' => $code,
                'url' => $url,

            ], false);
        }

        $mail->setMessageInformation($from, $from_name, $to, $to_name, $subject, $message);

        return $mail->send();
    }

    /**
     * sendMessageOtherProblems
     *
     * Envía un mensaje
     *
     * @param string $email
     * @param string $name
     * @param string $message
     * @param array $extra
     * @return array
     */
    private function sendMessageOtherProblems(string $email, string $name, string $message, array $extra = null)
    {
        $subject = __('usersProblems', 'Ticket genérico') . ' - ' . get_title();

        $message = $this->render('usuarios/mail/other-problems', [
            'subject' => $subject,
            'mail' => $email,
            'name' => $name,
            'message' => $message,
            'extra' => $extra,
        ], false);

        $api = get_config('osTicketAPI');
        $key = get_config('osTicketAPIKey');

        $osTicket = new OsTicketAPI($api, $key);

        $success = $osTicket->createTicket($name, $email, $subject, $message);

        return [
            'success' => $success,
            'instance' => $osTicket,
        ];
    }
}
