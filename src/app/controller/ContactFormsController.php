<?php

/**
 * ContactFormsController.php
 */

namespace App\Controller;

use GoogleReCaptchaV3\Controllers\GoogleReCaptchaV3Controller;
use GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use Newsletter\Mappers\NewsletterSuscriberMapper;
use Newsletter\NewsletterRoutes;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * ContactFormsController.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class ContactFormsController extends PublicAreaController
{

    /**
     * @var string
     */
    private static $prefixNameRoutes = 'contact-forms';

    /**
     * @var string
     */
    private static $startSegmentRoutes = 'contact';

    private $recipientsMessages = [
        'sir.vamb@gmail.com',
    ];

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function contactMessage(Request $req, Response $res)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'from',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'name',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'email',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'subject',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'message',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'updates',
                false,
                function ($value) {
                    return (is_string($value) && $value === 'yes') || is_bool($value);
                },
                true,
                function ($value) {
                    return $value === true ? true : ($value === 'yes');
                }
            ),
            new Parameter(
                'tokenCaptcha',
                '',
                function ($value) {
                    return is_null($value) || (is_string($value) && mb_strlen(trim($value)) > 0);
                },
                true,
                function ($value) {
                    return !is_null($value) ? clean_string($value) : '';
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $req->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(LANG_GROUP, 'Mensaje'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $successMessage = __(LANG_GROUP, 'El mensaje ha sido enviado.');
        $unknowErrorMessage = __(LANG_GROUP, 'Ha ocurrido un error desconocido, intente más tarde.');
        $unknowErrorWithValuesMessage = __(LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');
        $captchaFailErrorMessage = __(LANG_GROUP, 'CAPTCHA_FAIL');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var string $from
             * @var string $name
             * @var string $email
             * @var string $subject
             * @var string $message
             * @var bool $updates
             * @var string $tokenCaptcha
             */
            $from = $expectedParameters->getValue('from');
            $name = $expectedParameters->getValue('name');
            $email = $expectedParameters->getValue('email');
            $subject = $expectedParameters->getValue('subject');
            $message = $expectedParameters->getValue('message');
            $updates = $expectedParameters->getValue('updates');
            $tokenCaptcha = $expectedParameters->getValue('tokenCaptcha');

            try {

                //Verificar token si GoogleReCaptchaV3Controller está activo
                $captchaSuccess = true;
                if (GoogleReCaptchaV3Routes::ENABLE) {
                    $captchaSuccess = GoogleReCaptchaV3Controller::verifyTokenCaptcha($tokenCaptcha);
                }

                if ($captchaSuccess) {

                    $title = get_config('title_app');
                    $title = vsprintf(__(LANG_GROUP, "Fue contactado desde: <a href='%s'>%s</a>"), [
                        baseurl(),
                        $title,
                    ]);

                    $subject = mb_convert_encoding((string) __(LANG_GROUP, 'Contacto') . ': ' . $subject, 'UTF-8') . ' - ' . get_title();

                    $bodyMessage = $this->render('mailing/generic-contact-form', [
                        'title' => $title,
                        'name' => $name,
                        'email' => $email,
                        'subject' => $subject,
                        'message' => $message,
                        'updates' => $updates,
                    ], false);
                    $bodyMessage = mb_convert_encoding($bodyMessage, 'UTF-8');
                    $mailer = new Mailer();
                    $mailConfig = new MailConfig;
                    $mailer->SMTPDebug = 2;
                    $mailer->isHTML(true);
                    $mailer->setFrom($mailConfig->user());
                    $mailer->addReplyTo($email, $name);
                    foreach ($this->recipientsMessages as $recipient) {
                        $mailer->addAddress($recipient);
                    }

                    $mailer->Subject = mb_convert_encoding($subject, 'UTF-8');
                    $mailer->Body = $bodyMessage;
                    if (!$mailer->checkSettedSMTP() && !is_local()) {
                        $mailer->asGoDaddy(true);
                    }

                    $success = $mailer->send();

                    if ($success) {
                        $resultOperation->setMessage($successMessage);
                        $resultOperation->setSuccessOnSingleOperation($success);
                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }
                } else {
                    $resultOperation->setMessage($captchaFailErrorMessage);
                }

                if (NewsletterRoutes::ENABLE) {
                    //Agregar a suscriptores
                    $suscriber = new NewsletterSuscriberMapper();
                    $suscriber->name = $name;
                    $suscriber->email = $email;
                    $suscriber->acceptUpdates = $updates ? NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : NewsletterSuscriberMapper::ACCEPT_UPDATES_NO;
                    $suscriber->save(true);
                }

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                $resultOperation->setValue('logMailer', $mailer->log());
                log_exception($e);

            }

        } catch (MissingRequiredParamaterException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $res->withJson($resultOperation);

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
            $name = mb_strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixNameRoutes . $name : self::$prefixNameRoutes;

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

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        //Otras rutas
        $namePrefix = self::$prefixNameRoutes;

        if (mb_strlen(self::$startSegmentRoutes) > 0) {
            $startRoute .= self::$startSegmentRoutes;
        } else {
            $startRoute = '';
        }

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                "{$startRoute}/general[/]",
                self::class . ":contactMessage",
                "{$namePrefix}-general",
                'POST'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

}
