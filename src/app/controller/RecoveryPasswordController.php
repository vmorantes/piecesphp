<?php

/**
 * RecoveryPasswordController.php
 */

namespace App\Controller;

use App\Model\RecoveryPasswordModel;
use App\Model\TicketsLogModel;
use App\Model\UsersModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;
use \stdClass;

/**
 * RecoveryPasswordController.
 *
 * Controlador de recuperacón de contraseña
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class RecoveryPasswordController extends UsersController
{
    const LANG_GROUP = 'revoveryPasswordModule';

    /**
     * @var UsersModel
     */
    public $userMapper = null;

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
        $this->userMapper = new UsersModel();
    }

    /**
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function recoveryPasswordForm(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Recuperación de contraseña'));

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
            baseurl('statics/login-and-recovery/js/recovery-password.js'),
        ], 'js');

        $this->render('usuarios/problems/password');

        return $response;
    }

    /**
     * Envía un correo para recuperar la contraseña. Responde igual exista o no el usuario (ADR 0018).
     *
     * Este método espera recibir por POST: [username|email]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function recoveryPasswordRequest(Request $request, Response $response)
    {
        return $this->recoveryCodeRequestResponse($request, $response);
    }

    /**
     * Envía un correo para recuperar la contraseña. Responde igual exista o no el usuario (ADR 0018).
     *
     * Este método espera recibir por POST: [username]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @return Response
     */
    public function recoveryPasswordRequestCode(Request $request, Response $response)
    {
        return $this->recoveryCodeRequestResponse($request, $response);
    }

    /**
     * El enlace antiguo ya no cambia la contraseña: lleva al formulario de recuperación.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta. Espera $args['url_token]
     * @return Response
     */
    public function newPasswordCreate(Request $request, Response $response, array $args)
    {
        if ($request->isXhr()) {
            return $response->withJson([
                'success' => false,
                'error' => self::EXPIRED_OR_NOT_EXIST_CODE,
                'message' => $this->getMessage(self::EXPIRED_OR_NOT_EXIST_CODE),
            ]);
        }
        return $response->withRedirect(get_route('recovery-form'));
    }

    /**
     * Cambia la contraseña con un código.
     *
     * Este método espera recibir por POST: [username, code, password, repassword]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @param array $args
     * @return Response
     */
    public function newPasswordCreateCode(Request $request, Response $response, array $args)
    {
        //Cuerpo de respuesta
        $json_response = [
            'success' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Conjunto posible de datos para autenticación
        $requerido = [
            'username',
            'code',
            'password',
            'repassword',
        ];

        $args = $request->getParsedBody();

        //Verificar que el grupo de datos para autenticación sea válido
        $parametros_ok = require_keys($requerido, $args) === true && count($requerido) === count($args);

        if ($parametros_ok) {

            $username = trim((string) $args['username']);
            $code = trim((string) $args['code']);
            $password = trim((string) $args['password']);
            $repassword = trim((string) $args['repassword']);

            //Verificar que las contraseñas coincidan
            if ($password == $repassword) {

                //EL LÍMITE VA ANTES DE MIRAR EL CÓDIGO: sin él, un código de 6 cifras se adivina.
                $secondsToUnlock = OTPRateLimiter::secondsToUnlock($username, OTPRateLimiter::clientIP());
                if ($secondsToUnlock > 0) {
                    return OTPRateLimiter::lockedResponse($response, OTPRateLimiter::VIA_RECOVERY_CODE, $username, $secondsToUnlock);
                }

                $user = $this->resolveUser($username);
                $recoveryPassword = $user !== null ? RecoveryPasswordModel::findValid((string) $user->email, $code) : null;
                OTPRateLimiter::record(
                    OTPRateLimiter::VIA_RECOVERY_CODE,
                    $user !== null ? (int) $user->id : null,
                    $username,
                    $recoveryPassword !== null,
                    $recoveryPassword !== null ? '' : $this->getMessage(self::EXPIRED_OR_NOT_EXIST_CODE)
                );

                if ($user !== null && $recoveryPassword !== null) {

                    //Actualizar contraseña
                    $updated = $this->userMapper->changePassword($user->email, password_hash($password, \PASSWORD_DEFAULT));

                    $json_response['updated'] = $updated;

                    //Verificar si la contraseña fue actualizada
                    if ($updated) {
                        $json_response['success'] = true;
                        $json_response['message'] = __(self::LANG_GROUP, 'Contraseña cambiada.');
                        RecoveryPasswordModel::deleteByEmail((string) $user->email);
                    }
                } else {
                    $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                    $json_response['message'] = $this->getMessage($json_response['error']);
                }
            } else {
                $json_response['error'] = self::NOT_MATCH_PASSWORDS;
                $json_response['message'] = $this->getMessage($json_response['error']);
            }
        } else {
            $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
            $json_response['message'] = $this->getMessage($json_response['error']);
        }

        return $response->withJson($json_response);
    }

    /**
     * Verifica que el código sea vigente para ese usuario.
     *
     * Este método espera recibir por POST: [username, code]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta
     * @param array $args
     * @return Response
     */
    public function verifyCode(Request $request, Response $response, array $args)
    {
        //Cuerpo de respuesta
        $json_response = [
            'success' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Conjunto posible de dato
        $requerido = [
            'username',
            'code',
        ];

        $args = $request->getParsedBody();

        //Verificar que el grupo de datos sea válido
        $parametros_ok = require_keys($requerido, $args) === true && count($requerido) === count($args);

        if ($parametros_ok) {

            $username = trim((string) $args['username']);
            $code = trim((string) $args['code']);

            //EL LÍMITE VA ANTES DE MIRAR EL CÓDIGO: sin él, un código de 6 cifras se adivina.
            $secondsToUnlock = OTPRateLimiter::secondsToUnlock($username, OTPRateLimiter::clientIP());
            if ($secondsToUnlock > 0) {
                return OTPRateLimiter::lockedResponse($response, OTPRateLimiter::VIA_RECOVERY_CODE, $username, $secondsToUnlock);
            }

            $user = $this->resolveUser($username);
            $recoveryPassword = $user !== null ? RecoveryPasswordModel::findValid((string) $user->email, $code) : null;
            OTPRateLimiter::record(
                OTPRateLimiter::VIA_RECOVERY_CODE,
                $user !== null ? (int) $user->id : null,
                $username,
                $recoveryPassword !== null,
                $recoveryPassword !== null ? '' : $this->getMessage(self::EXPIRED_OR_NOT_EXIST_CODE)
            );

            if ($recoveryPassword !== null) {
                $json_response['success'] = true;
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
     * El usuario por nombre de usuario o por correo.
     *
     * @param string $username
     * @return stdClass|null
     */
    public function resolveUser(string $username): ?stdClass
    {
        $usuario = $this->userMapper->getWhere([
            'username' => [
                '=' => $username,
                'and_or' => 'OR',
            ],
            'email' => [
                '=' => $username,
            ],
        ]);
        return $usuario instanceof stdClass ? $usuario : null;
    }

    /**
     * Crea el código de recuperación y lo envía, si el usuario existe.
     *
     * @param string $username Nombre de usuario o correo
     * @param bool $onlyCode
     * @return stdClass|null El usuario, o null si no existe. Quien responde por HTTP no lo distingue (ADR 0018).
     */
    public function requestRecoveryCode(string $username, bool $onlyCode = false): ?stdClass
    {
        $usuario = $this->resolveUser($username);
        if ($usuario === null) {
            return null;
        }

        $recoveryPassword = new RecoveryPasswordModel();
        $recoveryPassword->created = new \DateTime();
        $recoveryPassword->expired = (clone $recoveryPassword->created)->modify('+24 hour');
        $recoveryPassword->email = $usuario->email;
        $recoveryPassword->code = generate_code(6);
        $recoveryPassword->save();

        try {
            $sent = $this->mailRecoveryPasswordCode($recoveryPassword->code, $usuario, $onlyCode);
        } catch (\Throwable $exception) {
            $sent = false;
        }
        if (!$sent) {
            log_exception(new \RuntimeException('Falló el envío del correo de recuperación de contraseña para el usuario ' . $usuario->id . '.'));
        }

        //El código NO se registra: el log no puede servir para cambiar la contraseña.
        $logRequest = new TicketsLogModel();
        $logRequest->created = $recoveryPassword->created;
        $logRequest->email = $recoveryPassword->email;
        $logRequest->information = [
            'email_sended' => $sent,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ];
        $logRequest->type = (string) __(self::LANG_GROUP, 'Solicitud de restablecimiento de contraseña.');
        $logRequest->save();

        return $usuario;
    }

    /**
     * La respuesta de las dos rutas de petición: la misma exista o no el usuario.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    private function recoveryCodeRequestResponse(Request $request, Response $response)
    {
        $params = $request->getParsedBody();

        $requerido = [
            'username',
        ];

        $parametros_ok = require_keys($requerido, $params) === true && count($requerido) === count($params);

        if (!$parametros_ok) {
            return $response->withJson([
                'send_mail' => false,
                'error' => self::MISSING_OR_UNEXPECTED_PARAMS,
                'message' => $this->getMessage(self::MISSING_OR_UNEXPECTED_PARAMS),
            ]);
        }

        $this->requestRecoveryCode(trim((string) $params['username']));

        return $response->withJson([
            'send_mail' => true,
            'error' => self::NO_ERROR,
            'message' => OTPRateLimiter::uniformOTPMessage(),
        ]);
    }

    /**
     * Envía un correo de recuperación de contraseña.
     *
     * @param string $code
     * @param stdClass $usuario
     * @param bool $onlyCode
     *
     * @return bool true si se envió, false si no
     */
    public function mailRecoveryPasswordCode(string $code, stdClass $usuario, bool $onlyCode = false)
    {
        $mail = new Mailer();
        $mailConfig = new MailConfig;

        $to = $usuario->email;

        $to_name = $usuario->username;

        $subject = __(self::LANG_GROUP, 'Recuperación de contraseña');

        if (!$onlyCode) {
            $message = $this->render('usuarios/mail/recovery_password_code', [
                'code' => $code,
                'url' => get_route('recovery-form') . '?code=' . rawurlencode($code) . '&email=' . rawurlencode($usuario->email),
            ], false);
        } else {
            $message = $this->render('usuarios/mail/recovery_password_code_only', [
                'code' => $code,
                'text' => __(MAIL_TEMPLATES_LANG_GROUP, 'Recuperación de contraseña') . ':',
                'text_button' => __(MAIL_TEMPLATES_LANG_GROUP, 'Ingresar el código'),
                'note' => __(MAIL_TEMPLATES_LANG_GROUP, 'MENSAJE_DE_VALIDEZ'),
            ], false);
        }

        $mail->setFrom($mailConfig->user(), $mailConfig->name());
        $mail->addAddress($to, $to_name);
        $mail->isHTML(true);
        $mail->Subject = (string) $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        if (!$mail->checkSettedSMTP()) {
            $mail->asGoDaddy();
        }

        return $mail->send();
    }
}
