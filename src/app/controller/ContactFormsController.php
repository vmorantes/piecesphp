<?php

/**
 * ContactFormsController.php
 */

namespace App\Controller;

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
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

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
     * @param array $args
     * @return Response
     */
    public function contactMessage(Request $req, Response $res, array $args)
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
             */;
            $from = $expectedParameters->getValue('from');
            $name = $expectedParameters->getValue('name');
            $email = $expectedParameters->getValue('email');
            $subject = $expectedParameters->getValue('subject');
            $message = $expectedParameters->getValue('message');
            $updates = $expectedParameters->getValue('updates');

            try {

                $title = get_config('title_app');
                $title = vsprintf(__(LANG_GROUP, "Fue contactado desde: <a href='%s'>%s</a>"), [
                    baseurl(),
                    $title,
                ]);
                $logo = baseurl(get_config('logo'));

                $bodyMessage = $this->render('mail-templates/generic-contact-form', [
                    'from' => $from,
                    'title' => $title,
                    'logo' => $logo,
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'updates' => $updates,
                ], false);
                $bodyMessage = utf8_decode($bodyMessage);

                $mailer = new Mailer();
                $mailConfig = new MailConfig;

                $mailer->SMTPDebug = 2;
                $mailer->isHTML(true);

                $mailer->setFrom($mailConfig->user());
                $mailer->addReplyTo($email, $name);

                foreach ($this->recipientsMessages as $recipient) {
                    $mailer->addAddress($recipient);
                }

                $mailer->Subject = utf8_decode(__(LANG_GROUP, 'Contacto') . ': ' . $subject);
                $mailer->Body = $bodyMessage;

                if (!$mailer->checkSettedSMTP()) {
                    $mailer->asGoDaddy();
                }

                $success = $mailer->send();

                if ($success) {
                    $resultOperation->setMessage($successMessage);
                    $resultOperation->setSuccessOnSingleOperation($success);
                } else {
                    $resultOperation->setMessage($unknowErrorMessage);
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
     * routeName
     *
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
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
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
