<?php

/**
 * UserProblemsController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use PiecesPHP\MailCatalog\Definitions\OtherProblemsMail;
use PiecesPHP\MailCatalog\Definitions\VerificationCodeMail;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\RespondFirst;
use PiecesPHP\UserSystem\Authentication\TokenEvents;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;
use PiecesPHP\UserSystem\UserTypes;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
    const LANG_GROUP = 'usersProblems';

    //Frenan que se inunde el buzón de alguien y la tabla: un código por usuario cada minuto y no más de cinco por hora.
    const CODE_MIN_SECONDS_BETWEEN = 60;
    const CODE_MAX_PER_HOUR = 5;
    //Intentos de envío del formulario de otros problemas por IP y hora: cada uno es un correo o un ticket.
    const OTHER_PROBLEMS_MAX_PER_HOUR = 5;

    /**
     * @var UsersModel
     */
    protected $userMapper = null;

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
        $this->userMapper = new UsersModel();
        //Sus vistas viven en el módulo, no en la carpeta global: las diez que renderiza están en
        //`UserSystem/Views/`, y ninguna de ellas es el layout del panel.
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * Destinatarios del correo de «otros problemas», leídos de la configuración.
     *
     * Falla cerrado: si `other_problems_recipients` no es una lista no vacía de direcciones válidas, devuelve una lista vacía.
     *
     * @return string[]
     */
    public static function otherProblemsRecipients(): array
    {
        $configured = get_config('other_problems_recipients');
        if (!is_array($configured) || count($configured) === 0) {
            return [];
        }
        $recipients = [];
        foreach ($configured as $recipient) {
            $recipient = is_string($recipient) ? trim($recipient) : '';
            //filter_var y no Validator::isEmail(): ese consulta el DNS en cada llamada.
            if (filter_var($recipient, \FILTER_VALIDATE_EMAIL) === false) {
                return [];
            }
            $recipients[] = $recipient;
        }
        return $recipients;
    }

    /**
     * Vista del listado de problemas de usuario
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userProblemsList(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Solución a problemas de ingreso'));

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_fomantic();
        /* Librerías de la aplicación */
        import_app_libraries(['baseStyle']);

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('css/problems.css'),
        ], 'css');

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/user-problem-list.js'),
        ], 'js');

        $this->render('usuarios/problems/problems-list');

        return $response;
    }

    /**
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function userForgetForm(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'No recuerdo mi usuario'));

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_fomantic();
        /* Librerías de la aplicación */
        import_app_libraries(['baseStyle']);

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/user-forget.js'),
        ], 'js');

        $this->render('usuarios/problems/user_forget');

        return $response;
    }

    /**
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function userBlockedForm(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Desbloquear mi usuario'));

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_fomantic();
        /* Librerías de la aplicación */
        import_app_libraries(['baseStyle']);

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/user-blocked.js'),
        ], 'js');

        $this->render('usuarios/problems/user_blocked');

        return $response;
    }

    /**
     * No espera parámetros.
     *
     * @param Request $request Petición
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function otherProblemsForm(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Creación de solicitud de soporte'));

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_fomantic();
        /* Librerías de la aplicación */
        import_app_libraries(['baseStyle']);

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('css/problems-form.css'),
        ], 'css');

        set_custom_assets([
            UserSystemFeaturesRoutes::staticRoute('js/other-problems.js'),
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
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function generateCode(Request $request, Response $response)
    {

        //Parámetros
        $params = $request->getParsedBody();

        //Conjunto posible de datos para autenticación
        $requerido = [
            'username',
            'type',
        ];

        //Verificar que el grupo de datos para solicitados esté completo
        //Un campo que no es texto responde como uno que falta: si no, trim() o el envío posterior fallarían con un TypeError.
        $parametros_ok = is_array($params) && require_keys($requerido, $params) === true && count($requerido) === count($params) && self::allStrings($params, $requerido);

        //Cuerpo de la respuesta
        $json_response = [
            'send_mail' => false,
            'error' => self::NO_ERROR,
            'message' => '',
        ];

        //Si los parámetros son válidos en nombre y en cantidad se inicia el proceso de recuperación
        if ($parametros_ok) {

            $username = $params['username'];
            //Recortado UNA vez y guardado así: resolveProblem() compara el tipo recortado con el de la fila, y uno con
            //espacios crearía códigos inservibles que gastan el tope de códigos del correo.
            $type = trim($params['type']);

            //Se verifica que el tipo esté implementado
            if (in_array($type, [self::TYPE_USER_FORGET, self::TYPE_USER_BLOCKED], true)) {

                //Se responde ya el envío y todo lo que depende del usuario corre después (RespondFirst): ni el cuerpo ni el
                //tiempo dicen si existe, si está bloqueado o si su correo falló (eso queda en TicketsLogModel).
                $json_response['send_mail'] = true;
                $json_response['message'] = __(self::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.');
                return RespondFirst::json($response, $json_response, function () use ($username, $type): void {
                    $this->generateCodeAfterResponse($username, $type);
                });
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
     * Lo de generateCode() que depende del usuario, ya respondida la petición: la búsqueda, el usuario de sistema, el no
     * bloqueado, el límite de códigos, crear el código, enviarlo y anotar la petición con el resultado real del correo.
     *
     * @param mixed $username
     * @param mixed $type
     * @return void
     */
    private function generateCodeAfterResponse($username, $type): void
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
        //El usuario de sistema no pide códigos, y un eliminado tampoco (P380): para quien pide, no existen.
        if ($usuario !== null && (UsersModel::isSystemUser((int) $usuario->id) || (int) $usuario->status === UsersModel::STATUS_USER_DELETED)) {
            $usuario = null;
        }

        //A quien no está bloqueado no se le crea ni se le envía código (la respuesta ya fue la de un envío: no dice su
        //estado). Antes recibía un código que, con los intentos al máximo, lo dejaba activo.
        if ($usuario !== null && $type === self::TYPE_USER_BLOCKED && (int) $usuario->status !== UsersModel::STATUS_USER_ATTEMPTS_BLOCK) {
            return;
        }

        //Con el límite alcanzado no se crea ni se envía nada.
        if ($usuario !== null && self::codeLimitReached((string) $usuario->email)) {
            return;
        }

        //Verificación de existencia
        if ($usuario !== null) {

            //Datos de recuperación
            $problems = new UserProblemsModel();
            $problems->created = new \DateTime();
            //Sobre una copia: modify() cambia el objeto, y movía también la creación.
            $problems->expired = (clone $problems->created)->modify('+24 hour');
            $problems->email = $usuario->email;
            $problems->code = generate_code(6);
            $problems->type = $type;
            //Los anteriores vigentes de ese correo y tipo caducan, sin borrarse: el tope por correo los sigue contando.
            //RETORNO-IGNORADO: si no caducan, latestIDFor() en resolveProblem() ya los rechaza.
            UserProblemsModel::expireValidFor((string) $usuario->email, (string) $type, $problems->created);
            $problems->save();
            TokenEvents::log(LogsMapper::MSG_TOKEN_ISSUED, self::tokenKind((string) $type), (int) $usuario->id, (string) $usuario->username);

            //Envío de correo de recuperación
            $sent = $this->sendCode($problems->code, $usuario, $type);

            $logRequest = new TicketsLogModel();
            $logRequest->created = $problems->created;
            $logRequest->email = $problems->email;
            //El código NO se registra: el registro no puede servir para resolver el problema (como en recuperación).
            $logRequest->information = [
                'email_sended' => $sent,
                'ip' => \PiecesPHP\Core\VisitorIP::get(),
            ];
            if ($type == self::TYPE_USER_FORGET) {
                $logRequest->type = (string) __(self::LANG_GROUP, 'Solicitud por nombre de usuario olvidado.');
            } elseif ($type == self::TYPE_USER_BLOCKED) {
                $logRequest->type = (string) __(self::LANG_GROUP, 'Solicitud de desbloqueo de usuario.');
            }
            $logRequest->save();
        }
        //Quien no existe no crea ni envía nada; ya recibió la respuesta de un envío: no se delata (P201).
    }

    /**
     * Verifica el código y devuelve el usuario en caso de ser correcto
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function resolveProblem(Request $request, Response $response)
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
        $parametros_ok = is_array($args) && require_keys($requerido, $args) === true && count($requerido) === count($args) && self::allStrings($args, $requerido);

        if ($parametros_ok) {

            $code = trim($args['code']);
            $type = trim($args['type']);

            //EL LÍMITE VA ANTES DE MIRAR EL CÓDIGO: sin él, un código de 6 cifras se adivina.
            $secondsToUnlock = OTPRateLimiter::secondsToUnlock('', OTPRateLimiter::clientIP());
            if ($secondsToUnlock > 0) {
                return OTPRateLimiter::lockedResponse($response, OTPRateLimiter::VIA_USER_PROBLEM_CODE, '', $secondsToUnlock);
            }
            $resolvedUser = null;

            //Verificar si existe
            $exist = UserProblemsModel::exist($code);

            if ($exist) {

                $problems = UserProblemsModel::instanceByCode($code);

                $user = new UsersModel();
                $user = $problems !== null ? $user->getByEmail($problems->email) : null;
                //Una fila de la base (stdClass), y nunca el usuario de sistema ni un eliminado (P380): un código de antes
                //de la baja responde como el de un usuario que ya no existe.
                $user = $user instanceof \stdClass && !UsersModel::isSystemUser((int) $user->id) && (int) $user->status !== UsersModel::STATUS_USER_DELETED ? $user : null;

                //Verificar que el usuario existe
                if ($user !== null && $problems !== null) {

                    $resolvedUser = $user;

                    $now = new \DateTime();
                    $expired = $problems->expired <= $now;
                    //Solo vale el último código de ese correo y tipo: uno nuevo deja sin valor a los anteriores, sin borrarlos.
                    $superseded = (int) $problems->id !== UserProblemsModel::latestIDFor((string) $problems->email, (string) $problems->type);

                    if ($superseded) {
                        TokenEvents::log(LogsMapper::MSG_TOKEN_REJECTED, self::tokenKind((string) $problems->type), (int) $user->id, (string) $user->username, ['%reason%' => TokenEvents::REASON_EXPIRED]);
                        $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                        $json_response['message'] = $this->getMessage($json_response['error']);
                    } elseif (!$expired) {

                        if ($problems->type == self::TYPE_USER_FORGET && $type == self::TYPE_USER_FORGET) {

                            $json_response['success'] = true;
                            $json_response['username'] = $user->username;
                            $json_response['message'] = __(self::LANG_GROUP, 'Su nombre de usuario es') . ': ' . $user->username;
                            TokenEvents::log(LogsMapper::MSG_TOKEN_USED, TokenEvents::KIND_FORGOTTEN_USER, (int) $user->id, (string) $user->username);

                            $problems->getModel()->delete("id = '$problems->id'")->execute();
                        } elseif ($problems->type == self::TYPE_USER_BLOCKED && $type == self::TYPE_USER_BLOCKED) {

                            $is_block = $user->status == UsersModel::STATUS_USER_ATTEMPTS_BLOCK;
                            $blocked_by_attempts = $user->failedAttempts >= UsersController::MAX_ATTEMPTS;

                            //Solo el bloqueado: antes, con los intentos al máximo, dejaba activo a cualquiera (pendiente, rechazado,
                            //inactivo o borrado). Vuelve al estado que tenía al bloquearse.
                            if ($is_block && $blocked_by_attempts) {
                                $user = new UsersModel($user->id);
                                [$statusIfNoneSaved, $statusOverride] = self::statusesForUnblock($user);
                                $unblocked = $user->unblockFromAttempts((int) $user->id, $statusIfNoneSaved, $statusOverride);

                                if ($unblocked) {
                                    TokenEvents::log(LogsMapper::MSG_TOKEN_USED, TokenEvents::KIND_BLOCKED_USER, (int) $user->id, (string) $user->username);
                                    $json_response['success'] = true;
                                    $json_response['username'] = $user->username;
                                    $json_response['message'] = __(self::LANG_GROUP, 'Su usuario ha sido desbloqueado.');
                                } else {
                                    $json_response['message'] = __(self::LANG_GROUP, 'No se ha podido procesar la información, intente más tarde.');
                                }
                            } else {
                                if ($is_block) {
                                    $json_response['message'] = __(self::LANG_GROUP, 'El usuario no ha podido desbloquearse, contacte con el soporte.');
                                } else {
                                    $json_response['message'] = __(self::LANG_GROUP, 'El usuario no está bloqueado.');
                                }
                            }

                            $problems->getModel()->delete("id = '$problems->id'")->execute();
                        } else {
                            $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                            $json_response['message'] = $this->getMessage($json_response['error']);
                        }
                    } else {
                        TokenEvents::log(LogsMapper::MSG_TOKEN_REJECTED, self::tokenKind((string) $problems->type), (int) $user->id, (string) $user->username, ['%reason%' => TokenEvents::REASON_EXPIRED]);
                        $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                        $json_response['message'] = $this->getMessage($json_response['error']);
                    }
                } else {
                    //El usuario del código ya no existe: se responde como a un código que no vale, sin delatarlo.
                    $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                    $json_response['message'] = $this->getMessage($json_response['error']);
                }
            } else {

                //Sin la fila no se sabe de quién era el código.
                if (in_array($type, [self::TYPE_USER_FORGET, self::TYPE_USER_BLOCKED], true)) {
                    TokenEvents::log(LogsMapper::MSG_TOKEN_REJECTED, self::tokenKind($type), null, '', ['%reason%' => TokenEvents::REASON_NOT_FOUND]);
                }
                $json_response['error'] = self::EXPIRED_OR_NOT_EXIST_CODE;
                $json_response['message'] = $this->getMessage($json_response['error']);
            }

            //Un código bueno de alguien que no está bloqueado no es un fallo: solo cuenta el que no vale.
            $failedCode = $json_response['error'] === self::EXPIRED_OR_NOT_EXIST_CODE;
            //El fallo va sin nombre: con el del dueño gastaría su presupuesto por usuario en las demás vías.
            OTPRateLimiter::record(
                OTPRateLimiter::VIA_USER_PROBLEM_CODE,
                $resolvedUser !== null ? (int) $resolvedUser->id : null,
                $resolvedUser !== null && !$failedCode ? (string) $resolvedUser->username : '',
                !$failedCode,
                $failedCode ? $this->getMessage(self::EXPIRED_OR_NOT_EXIST_CODE) : ''
            );
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
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function sendMailOtherProblems(Request $request, Response $response)
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
        //Texto los obligatorios y el apellido si viene; `extra`, una lista si viene (sendMessageOtherProblems() la pide así).
        $parametros_ok = is_array($params) && require_keys($requerido, $params) === true && self::allStrings($params, $requerido)
            && (!isset($params['lastname']) || is_string($params['lastname'])) && (!isset($params['extra']) || is_array($params['extra']));

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
            $extra = $params['extra'] ?? null;

            //EL TOPE VA ANTES DE ENVIAR: sin él, cada petición es un correo o un ticket.
            if (OTPRateLimiter::ipSendLimitReached(OTPRateLimiter::VIA_OTHER_PROBLEMS_SEND, OTPRateLimiter::clientIP(), self::OTHER_PROBLEMS_MAX_PER_HOUR)) {
                return OTPRateLimiter::lockedResponse($response, OTPRateLimiter::VIA_OTHER_PROBLEMS_SEND, '', 3600);
            }
            //Cuenta el INTENTO, antes de enviar y como fallo: no es un acceso (los informes cuentan los éxitos como ingresos).
            OTPRateLimiter::record(OTPRateLimiter::VIA_OTHER_PROBLEMS_SEND, null, '', false, (string) __(self::LANG_GROUP, 'Intento de envío de otros problemas.'));

            //Envío de ticket
            /**
             * @var array<string,bool|OsTicketAPI>
             */
            $result = $this->sendMessageOtherProblems($email, $name, $message, $extra);

            /**
             * @var bool $success
             */
            $success = $result['success'];

            $json_response['send_mail'] = $success;

            $logRequest = new TicketsLogModel();
            $logRequest->created = new \DateTime();
            $logRequest->name = $name;
            $logRequest->email = $email;
            $logRequest->message = $message;
            $logRequest->information = [
                'email_sended' => $success,
                'ip' => \PiecesPHP\Core\VisitorIP::get(),
            ];
            $logRequest->type = __(self::LANG_GROUP, 'Otros inconvenientes (osTicket).');
            $logRequest->save();

            if ($success) {
                $json_response['message'] = __(self::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.');
            } else {
                $json_response['message'] = __(self::LANG_GROUP, 'No se ha podido enviar el mensaje, intente más tarde.');
            }
        } else {

            $json_response['error'] = self::MISSING_OR_UNEXPECTED_PARAMS;
            $json_response['message'] = $this->getMessage($json_response['error']);
        }

        return $response->withJson($json_response);
    }

    /**
     * La clase de código de Registros › Tokens y códigos que corresponde a un tipo de problema.
     *
     * @param string $type
     * @return string
     */
    public static function tokenKind(string $type): string
    {
        return $type === self::TYPE_USER_FORGET ? TokenEvents::KIND_FORGOTTEN_USER : TokenEvents::KIND_BLOCKED_USER;
    }

    /**
     * Con qué estado vuelve un bloqueado: [sin estado guardado, el que manda sobre lo guardado].
     *
     * La fila de aprobación del perfil manda: rechazada, vuelve rechazado aunque se guardara otro estado. Sin estado
     * guardado: aprobada o tipo que se aprueba solo, activo; si no, por aprobar. Con el módulo de aprobaciones apagado no
     * hay filas que consultar: activo.
     *
     * @param UsersModel $user
     * @return array{0:int,1:int|null}
     */
    private static function statusesForUnblock(UsersModel $user): array
    {
        if (!SystemApprovalsRoutes::ENABLE) {
            return [UsersModel::STATUS_USER_ACTIVE, null];
        }
        $approval = SystemApprovalsMapper::getByMultipleCriteries([
            [
                'column' => 'referenceTable',
                'value' => UsersModel::TABLE,
            ],
            [
                'column' => 'referenceValue',
                'value' => $user->id,
            ],
        ], [], false, true);
        $approvalStatus = $approval !== null ? (string) $approval->status : null;
        if ($approvalStatus === SystemApprovalsMapper::STATUS_REJECTED) {
            return [UsersModel::STATUS_USER_REJECTED, UsersModel::STATUS_USER_REJECTED];
        }
        $active = $approvalStatus === SystemApprovalsMapper::STATUS_APPROVED || UserTypes::isAutoApproval((int) $user->type);
        return [$active ? UsersModel::STATUS_USER_ACTIVE : UsersModel::STATUS_USER_APPROVED_PENDING, null];
    }

    /**
     * Envía un correo con el código
     *
     * @param string $code
     * @param \stdClass $usuario
     * @param string $type
     *
     * @return bool true si se envió, false si no
     */
    private function sendCode(string $code, \stdClass $usuario, string $type = 'TYPE_USER_FORGET')
    {
        $mail = new Mailer();
        $mailConfig = new MailConfig;

        $to = $usuario->email;

        $to_name = $usuario->username;

        if ($type !== self::TYPE_USER_FORGET && $type !== self::TYPE_USER_BLOCKED) {
            throw new \InvalidArgumentException("sendCode: tipo no soportado '{$type}'.");
        }
        //El asunto y el cuerpo, con su definición del catálogo de correos: la misma que pinta la vista previa.
        $definition = new VerificationCodeMail($type);
        $mailData = ['code' => $code, 'renderer' => $this];
        $subject = $definition->subject($mailData);
        $message = $definition->render($mailData);

        /**
         * @var string
         */
        $fromAddress = $mailConfig->user();
        /**
         * @var string
         */
        $nameAddress = $mailConfig->name();

        $mail->setFrom($fromAddress, $nameAddress);
        $mail->addAddress($to, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        if (!$mail->checkSettedSMTP()) {
            $mail->asSystemMailer();
        }

        return $mail->send();
    }

    /**
     * Envía un mensaje
     *
     * @param string $email
     * @param string $name
     * @param string $message
     * @param array $extra
     * @return array
     */
    private function sendMessageOtherProblems(string $email, string $name, string $message, ?array $extra = null): array
    {
        //El asunto y el cuerpo, con su definición del catálogo de correos: la misma que pinta la vista previa.
        $definition = new OtherProblemsMail();
        $mailData = ['email' => $email, 'name' => $name, 'message' => $message, 'extra' => $extra, 'renderer' => $this];
        $subject = $definition->subject($mailData);
        $message = $definition->render($mailData);

        $api = get_config('osTicketAPI');
        $key = get_config('osTicketAPIKey');

        $success = false;

        if (is_string($api) && mb_strlen(trim($api)) > 0 && is_string($key) && mb_strlen(trim($key)) > 0) {
            $osTicket = new OsTicketAPI($api, $key);
            $success = $osTicket->createTicket($name, $email, $subject, $message);
        }

        if (!$success) {

            $recipients = self::otherProblemsRecipients();

            if (count($recipients) === 0) {

                log_exception(new \RuntimeException(
                    "«Otros problemas» sin enviar por correo: osTicket no lo recibió y \$config['other_problems_recipients'] debe ser una lista no vacía de direcciones de correo válidas."
                ));

            } else {

                $mail = new Mailer();
                $mailConfig = new MailConfig;

                //user() y name() también escriben, y devuelven MailConfig: sin texto, el remitente queda vacío y el envío falla.
                $fromAddress = $mailConfig->user();
                $nameAddress = $mailConfig->name();

                $mail->setFrom(is_string($fromAddress) ? $fromAddress : '', is_string($nameAddress) ? $nameAddress : '');
                $mail->addReplyTo($email, $name);
                foreach ($recipients as $recipient) {
                    $mail->addAddress($recipient);
                }
                $mail->isHTML(true);
                $mail->Subject = (string) $subject;
                $mail->Body = $message;
                $mail->AltBody = strip_tags($message);

                if (!$mail->checkSettedSMTP()) {
                    $mail->asSystemMailer();
                }

                $success = $mail->send();

            }
        }

        return [
            'success' => $success,
            'instance' => $osTicket ?? null,
        ];
    }

    /**
     * Si ese correo ya pidió un código hace menos de CODE_MIN_SECONDS_BETWEEN o CODE_MAX_PER_HOUR en la última hora.
     *
     * @param string $email
     * @return bool
     */
    private static function codeLimitReached(string $email): bool
    {
        $now = new \DateTime();
        return UserProblemsModel::countSince($email, (clone $now)->modify('-' . self::CODE_MIN_SECONDS_BETWEEN . ' seconds')) > 0
            || UserProblemsModel::countSince($email, (clone $now)->modify('-1 hour')) >= self::CODE_MAX_PER_HOUR;
    }
}
