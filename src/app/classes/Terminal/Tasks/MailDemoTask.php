<?php

/**
 * MailDemoTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Controllers\SystemApprovalsController;

/**
 * MailDemoTask — manda un correo de cada plantilla del catálogo a la bandeja de pruebas, para verlos.
 *
 * Las suites limpian lo que mandan (ADR 0047), así que el registro de correos de una instalación de
 * desarrollo queda vacío. Esto deja correos para mirar: cada plantilla, pintada con datos de ejemplo y
 * enviada por el `Mailer` real, que la anota en el registro con su cuerpo. **No ejecuta ningún flujo**:
 * no crea códigos, tokens ni aprobaciones; solo pinta y envía. Marca `demo-`, no `zz-`: no cuenta como
 * resto de prueba.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailDemoTask extends TerminalTaskAbstract
{

    /**
     * A quién van todos. `localhost.test` no resuelve fuera de esta máquina.
     *
     * @var string
     */
    const RECIPIENT = 'demo-correos@localhost.test';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Manda un correo de cada plantilla del catálogo a la bandeja de pruebas, para poder verlos.\r\n",
            "\tSOLO en una instalación `local` y SOLO si la entrega efectiva del correo es «Retenido»:\r\n",
            "\tsi no, se niega antes de mandar nada. Van a " . self::RECIPIENT . ", con datos de ejemplo,\r\n",
            "\tpor el Mailer real: quedan en Mailpit (o en el buzón en disco) y en el registro de correos,\r\n",
            "\tcon su cuerpo. No borra nada: cada vez, otra tanda.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A",
        ]);
        $this->route = "{$startRoute}/mail-demo[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'mail-demo';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * Las dos guardas, en orden. **Las dos fallan cerrado**: sin `environment.php` no es `local`, y si la
     * entrega efectiva es la real, se niega aunque el entorno sea `local` (`mail-real-en-local` existe
     * porque eso pasa).
     *
     * @return array<int,array{ok:bool,line:string}>
     */
    public static function guards(): array
    {
        $local = AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::LOCAL;
        $sink = MailDelivery::goesToSink();
        return [
            [
                'ok' => $local,
                'line' => $local
                    ? 'entorno: local.'
                    : "\e[31mentorno: NO local\e[39m — solo se manda en una instalación de desarrollo. Declare `return 'local';` en src/" . AppEnvironment::FILE_RELATIVE_PATH . '.',
            ],
            [
                'ok' => $sink,
                'line' => $sink
                    ? 'entrega: retenida; nada sale de esta máquina.'
                    : "\e[31mentrega: REAL\e[39m — estos correos saldrían de esta máquina. Ponga «Entrega del correo» en «Retenido» en Integraciones → Correo, o en «Según el entorno».",
            ],
        ];
    }

    /**
     * El catálogo: un correo por cada plantilla que el framework manda, pintado con las mismas variables
     * que su envío real y datos de ejemplo. **No envía nada**.
     *
     * @return array<int,array{template:string,subject:string,body:string}>
     */
    public static function catalog(): array
    {
        $app = Config::app_title();
        $persona = 'Laura Gómez';
        $correoPersona = 'demo-laura@localhost.test';
        $codigo = 'DEMO42';
        $plantillaBase = new \PiecesPHP\Tokens\Controllers\HelperController(null, []);
        $plantillaSinEstilos = new \SystemApprovals\Controllers\HelperController(null, []);
        $recuperacion = new RecoveryPasswordController();
        $problemas = new UserProblemsController();
        $aprobaciones = SystemApprovalsController::LANG_GROUP;
        $nombre = htmlspecialchars($persona, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return [
            [
                'template' => 'usuarios/mail/recovery_password_code',
                'subject' => (string) __(RecoveryPasswordController::LANG_GROUP, 'Recuperación de contraseña'),
                'body' => (string) $recuperacion->render('usuarios/mail/recovery_password_code', [
                    'code' => $codigo,
                    'url' => RecoveryPasswordController::routeName('recovery-form') . '?code=' . rawurlencode($codigo) . '&email=' . rawurlencode($correoPersona),
                ], false),
            ],
            [
                'template' => 'usuarios/mail/recovery_password_code_only',
                'subject' => (string) __(RecoveryPasswordController::LANG_GROUP, 'Recuperación de contraseña'),
                'body' => (string) $recuperacion->render('usuarios/mail/recovery_password_code_only', [
                    'code' => $codigo,
                    'text' => __(MAIL_TEMPLATES_LANG_GROUP, 'Recuperación de contraseña') . ':',
                    'text_button' => __(MAIL_TEMPLATES_LANG_GROUP, 'Ingresar el código'),
                    'note' => __(MAIL_TEMPLATES_LANG_GROUP, 'MENSAJE_DE_VALIDEZ'),
                ], false),
            ],
            [
                'template' => 'usuarios/mail/user_forget_code',
                'subject' => (string) __(UserProblemsController::LANG_GROUP, 'Código de verificación'),
                'body' => (string) $problemas->render('usuarios/mail/user_forget_code', [
                    'code' => $codigo,
                    'url' => UserProblemsController::routeName('forget-form') . '?code=' . $codigo,
                ], false),
            ],
            [
                'template' => 'usuarios/mail/user_blocked_code',
                'subject' => (string) __(UserProblemsController::LANG_GROUP, 'Código de verificación'),
                'body' => (string) $problemas->render('usuarios/mail/user_blocked_code', [
                    'code' => $codigo,
                    'url' => UserProblemsController::routeName('blocked-form') . '?code=' . $codigo,
                ], false),
            ],
            [
                'template' => 'usuarios/mail/other-problems',
                'subject' => (string) __(UserProblemsController::LANG_GROUP, 'Otros problemas') . ' - ' . $app,
                'body' => (string) $problemas->render('usuarios/mail/other-problems', [
                    'originURL' => baseurl(),
                    'subject' => __(UserProblemsController::LANG_GROUP, 'Otros problemas') . ' - ' . $app,
                    'mail' => $correoPersona,
                    'name' => $persona,
                    'message' => 'No consigo entrar desde el móvil: la página se queda en blanco después de escribir la contraseña.',
                    'extra' => [['display' => 'Navegador', 'text' => 'Firefox 140 en Android']],
                ], false),
            ],
            [
                'template' => 'mails/otp-code',
                'subject' => (string) __(OTPHandler::LANG_GROUP, 'Contraseña de un uso') . ' - ' . $app,
                'body' => (string) (new UserSystemFeaturesController())->render('mails/otp-code', [
                    'text' => __(OTPHandler::LANG_GROUP, 'Contraseña de un solo uso'),
                    'note' => vsprintf(__(OTPHandler::LANG_GROUP, 'Tiene una validez de %s minutos'), [10]),
                    'code' => '481516',
                ], false),
            ],
            [
                'template' => 'mailing/template_base',
                'subject' => 'Comentario sobre el enlace compartido',
                'body' => (string) $plantillaBase->render('mailing/template_base', [
                    'text' => htmlspecialchars('El documento del enlace ya no está actualizado; ¿pueden enviar la versión nueva?', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    'note' => '',
                    'url' => '',
                    'text_button' => '',
                ], false, false),
            ],
            [
                'template' => 'mailing/template_base_no_style (alta por la API)',
                'subject' => (string) __(\API\Controllers\APIController::LANG_GROUP, 'Aprobaciones') . ' - ' . $app,
                'body' => (string) $plantillaSinEstilos->render('mailing/template_base_no_style', [
                    'text' => strReplaceTemplate(__(\API\Controllers\APIController::LANG_GROUP, 'Sr(a). {NAME}, le informamos que su usuario ha sido creado y está a la espera de aprobación. De momento puede iniciar sesión y completar su perfil para agilizar el proceso de aprobación.'), ['{NAME}' => $nombre]),
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                    'text_button' => __(\API\Controllers\APIController::LANG_GROUP, 'Iniciar sesión'),
                ], false, false),
            ],
            [
                'template' => 'mailing/template_base_no_style (aprobado)',
                'subject' => (string) __($aprobaciones, 'Aprobaciones') . ' - ' . $app,
                'body' => (string) $plantillaSinEstilos->render('mailing/template_base_no_style', [
                    'text' => strReplaceTemplate(__($aprobaciones, 'Sr(a). {NAME}, le informamos que su contenido "{CONTENT_NAME}" ha sido aprobado'), ['{NAME}' => $nombre, '{CONTENT_NAME}' => 'Informe anual 2026']),
                    'reason' => '',
                ], false, false),
            ],
            [
                'template' => 'mailing/template_base_no_style (rechazado)',
                'subject' => (string) __($aprobaciones, 'Aprobaciones') . ' - ' . $app,
                'body' => (string) $plantillaSinEstilos->render('mailing/template_base_no_style', [
                    'text' => strReplaceTemplate(__($aprobaciones, 'Sr(a). {NAME}, le informamos que su contenido "{CONTENT_NAME}" ha sido rechazado'), ['{NAME}' => $nombre, '{CONTENT_NAME}' => 'Informe anual 2026']),
                    'reason' => htmlspecialchars('Falta la fuente de las cifras del segundo apartado.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                ], false, false),
            ],
            [
                'template' => 'mailing/generic-contact-form',
                'subject' => (string) __(LANG_GROUP, 'Contacto') . ': Información sobre los cursos - ' . $app,
                'body' => (string) (new \App\Controller\ContactFormsController())->render('mailing/generic-contact-form', [
                    'title' => vsprintf(__(LANG_GROUP, "Fue contactado desde: <a href='%s'>%s</a>"), [baseurl(), $app]),
                    'name' => $persona,
                    'email' => $correoPersona,
                    'subject' => 'Información sobre los cursos',
                    'message' => '¿Hay plazas para el curso de otoño? Me interesa el horario de tarde.',
                    'updates' => true,
                ], false),
            ],
        ];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Correos de muestra';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        //LO PRIMERO, antes de pintar nada: si una guarda no deja, no se manda ni uno.
        $permitido = true;
        foreach (self::guards() as $guard) {
            echoTerminal('   ' . $guard['line']);
            $permitido = $permitido && $guard['ok'];
        }
        if (!$permitido) {
            echoTerminal("\e[31m*** {$titleTask}, NO SE MANDA NINGUNO ***\e[39m");
            exit(2);
        }

        $mailConfig = new MailConfig();
        $remitente = $mailConfig->user();
        $remitente = is_string($remitente) && filter_var($remitente, \FILTER_VALIDATE_EMAIL) !== false ? $remitente : 'demo-remitente@localhost.test';
        $nombreRemitente = $mailConfig->name();
        $nombreRemitente = is_string($nombreRemitente) ? $nombreRemitente : '';
        $porResultado = [];
        $catalogo = self::catalog();
        foreach ($catalogo as $correo) {
            try {
                $mailer = new Mailer();
                $mailer->setFrom($remitente, $nombreRemitente);
                $mailer->addAddress(self::RECIPIENT, 'Demo');
                $mailer->isHTML(true);
                $mailer->Subject = mb_convert_encoding($correo['subject'], 'UTF-8');
                $mailer->Body = $correo['body'];
                $mailer->AltBody = strip_tags($correo['body']);
                //RETORNO-IGNORADO: a dónde fue lo dice la fila del registro, que se lee justo después.
                $mailer->send();
            } catch (\Throwable $throwable) {
                echoTerminal("   \e[31m{$correo['template']}: no se pudo mandar\e[39m — " . get_class($throwable) . ': ' . mb_substr($throwable->getMessage(), 0, 200));
            }
            $fila = MailLogMapper::latest(1)[0] ?? null;
            $resultado = $fila !== null && str_contains((string) $fila->recipients, self::RECIPIENT) ? (string) $fila->result : 'sin fila en el registro';
            $porResultado[$resultado] = ($porResultado[$resultado] ?? 0) + 1;
            $destino = match ($resultado) {
                MailLogMapper::RESULT_DELIVERED => 'a Mailpit',
                MailLogMapper::RESULT_OUTBOX => 'al buzón en disco (' . MailDelivery::outboxDirectory() . ')',
                default => "\e[31m{$resultado}\e[39m",
            };
            echoTerminal("   {$correo['template']} → {$destino}");
        }

        echoTerminal('');
        $resumen = [];
        foreach ($porResultado as $resultado => $cuantos) {
            $resumen[] = "{$cuantos} {$resultado}";
        }
        echoTerminal('   ' . count($catalogo) . ' correo(s) a ' . self::RECIPIENT . ': ' . implode(', ', $resumen) . '.');
        echoTerminal('   Ábralos en Sistema → Registro de correos, o en Mailpit (http://127.0.0.1:8025).');
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new MailDemoTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }
}
