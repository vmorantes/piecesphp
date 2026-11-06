<?php

/**
 * MailCatalogController.php
 */

namespace PiecesPHP\MailCatalog\Controllers;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailCatalogRoutes;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * MailCatalogController - «Correos del sitio»: cada correo del catálogo con su vista previa, y «Enviar prueba» solo con la
 * entrega retenida.
 *
 * @package     PiecesPHP\MailCatalog\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailCatalogController extends AdminPanelController
{
    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'mail-catalog';

    /**
     * @var string
     */
    protected static $baseRouteName = 'mail-catalog';

    /**
     * @var HelperController
     */
    protected $helpController;

    const LANG_GROUP = MailCatalogLang::LANG_GROUP;

    /** Lo que lleva delante el asunto de una prueba. */
    const TEST_SUBJECT_PREFIX = '[PRUEBA] ';

    const ALLOWED = [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL];

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * La pantalla.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listView(Request $request, Response $response)
    {
        set_custom_assets([MailCatalogRoutes::staticRoute('js/mail-catalog.js')], 'js');
        set_custom_assets([MailCatalogRoutes::staticRoute('css/mail-catalog.css')], 'css');

        $title = __(self::LANG_GROUP, 'Correos del sitio');
        set_title($title);

        //Por módulo, el núcleo primero: solo lo registrado, así que un módulo apagado no sale.
        $groups = [];
        foreach (MailCatalog::byModule() as $module => $definitions) {
            $mails = [];
            foreach ($definitions as $key => $definition) {
                $mails[] = [
                    'key' => (string) $key,
                    'name' => $definition->name(),
                    'trigger' => $definition->trigger(),
                    'recipients' => $definition->recipients(),
                ] + MailCatalog::preview($definition);
            }
            $groups[] = [
                'module' => $module === '' ? __(self::LANG_GROUP, 'Núcleo del sistema') : __(self::LANG_GROUP, $module),
                'mails' => $mails,
            ];
        }
        $user = getLoggedFrameworkUser();

        $header = SettingsController::sectionHeaderData($title, __(self::LANG_GROUP, 'Todos los correos que envía el sitio, con datos de muestra. Abrir esta pantalla no envía nada.'));
        unset($header['breadcrumbs']);

        $this->helpController->render('panel/layout/header');
        $this->render('list', [
            'langGroup' => self::LANG_GROUP,
            'groups' => $groups,
            'canSendTest' => MailDelivery::goesToSink(),
            'cannotSendReason' => self::cannotSendReason(),
            'sendTestURL' => self::routeName('send-test'),
            'currentEmail' => $user !== null ? (string) $user->email : '',
            'helpController' => $this->helpController,
        ] + $header);
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Envía un correo del catálogo con sus datos de muestra, solo si la entrega es retenida.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function sendTest(Request $request, Response $response)
    {
        //El botón no sale sin sumidero, pero la petición se puede hacer a mano: el servidor lo comprueba otra vez.
        if (!MailDelivery::goesToSink()) {
            return $response->withStatus(409)->withJson(['success' => false, 'error' => self::cannotSendReason()]);
        }
        $body = $request->getParsedBody();
        $key = is_array($body) && is_string($body['key'] ?? null) ? $body['key'] : '';
        $to = is_array($body) && is_string($body['to'] ?? null) ? trim($body['to']) : '';
        //Un módulo apagado no registra sus correos: su clave, como una inventada, no está.
        $definition = MailCatalog::get($key);
        if ($definition === null) {
            return $response->withStatus(404)->withJson(['success' => false, 'error' => __(self::LANG_GROUP, 'Ese correo no está en el catálogo.')]);
        }
        //filter_var y no Validator::isEmail(): ese consulta el DNS en cada llamada.
        if (filter_var($to, \FILTER_VALIDATE_EMAIL) === false) {
            return $response->withStatus(400)->withJson(['success' => false, 'error' => __(self::LANG_GROUP, 'Escribe un destinatario válido para el correo de prueba.')]);
        }

        try {
            $data = $definition->sampleData();
            $mailer = new Mailer();
            $mailConfig = new MailConfig;
            //Los getters de MailConfig devuelven el valor o el propio objeto: se estrecha por tipo.
            $from = $mailConfig->user();
            $fromName = $mailConfig->name();
            $mailer->setFrom(is_string($from) ? $from : '', is_string($fromName) ? $fromName : '');
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = self::TEST_SUBJECT_PREFIX . $definition->subject($data);
            $mailer->Body = $definition->render($data);
            $sent = $mailer->send();
        } catch (\Throwable $e) {
            return $response->withStatus(500)->withJson(['success' => false, 'error' => sprintf(__(self::LANG_GROUP, 'No se pudo enviar (referencia %s).'), log_exception($e))]);
        }

        if (!$sent) {
            return $response->withStatus(500)->withJson(['success' => false, 'error' => __(self::LANG_GROUP, 'No se pudo enviar: el registro de correos dice por qué.')]);
        }
        if (LogsRoutes::ENABLE) {
            $user = getLoggedFrameworkUser();
            LogsMapper::addLog(LogsMapper::MSG_MAIL_TEST_SENT, [
                '%username%' => $user !== null ? (string) $user->username : '?',
                '%key%' => $definition->key(),
                '%to%' => $to,
            ], 'key', $definition->key(), 'mail-catalog');
        }
        return $response->withJson([
            'success' => true,
            'message' => sprintf(__(self::LANG_GROUP, 'Enviado a %s por el servidor de pruebas, no por la entrega real.'), $to),
        ]);
    }

    /**
     * Por qué no se puede enviar una prueba, de la misma condición que lo decide (MailDelivery); vacío si se puede.
     *
     * @return string
     */
    public static function cannotSendReason(): string
    {
        if (MailDelivery::goesToSink()) {
            return '';
        }
        return match (MailDelivery::reason()) {
            'declarado' => __(self::LANG_GROUP, 'La «Entrega del correo» está en «Real»: una prueba saldría de esta máquina. Para enviar pruebas, ponla en «Retenido» en Configuración › Sistema › Correo.'),
            default => __(self::LANG_GROUP, 'La «Entrega del correo» está en «Según el entorno» y esta instalación no es de desarrollo, así que el correo sale de verdad. Para enviar pruebas, ponla en «Retenido» en Configuración › Sistema › Correo.'),
        };
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $group->register([
            new Route("{$startRoute}[/]", self::class . ':listView', self::$baseRouteName . '-list', 'GET', true, null, self::ALLOWED),
            new Route("{$startRoute}/send-test[/]", self::class . ':sendTest', self::$baseRouteName . '-send-test', 'POST', true, null, self::ALLOWED),
        ]);
        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });
        return $group;
    }
}
