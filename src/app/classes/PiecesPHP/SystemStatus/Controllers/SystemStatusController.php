<?php

/**
 * SystemStatusController.php
 */

namespace PiecesPHP\SystemStatus\Controllers;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\CleanTargetRegistry;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemHealth;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CronJobTask;
use PiecesPHP\Terminal\TaskRunMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SystemStatusController - Las páginas «Avisos del sistema» y «Estado y cachés».
 *
 * @package     PiecesPHP\SystemStatus\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SystemStatusController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'system-status';

    /**
     * @var string
     */
    protected static $baseRouteName = 'system-status';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const LANG_GROUP = 'system-status';

    /**
     * La key de su pestaña en «Registros».
     */
    const MAIL_LOG_TAB = 'mails';

    /**
     * Las entregas con que se anota un correo: la resuelta (MailDelivery::resolved()), nunca «auto».
     */
    const MAIL_LOG_DELIVERIES = [MailDelivery::SINK, MailDelivery::REAL];

    /**
     * La key de la pestaña «Tareas programadas» en «Registros».
     */
    const TASK_RUNS_TAB = 'tasks';

    /**
     * Rutas rotas que la página de mantenimiento enumera.
     */
    const BROKEN_LINKS_SHOWN = 50;

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function alerts(Request $request, Response $response)
    {
        $user = getLoggedFrameworkUser();
        $userType = $user !== null ? (int) $user->type : -1;

        set_custom_assets([
            SystemStatusRoutes::staticRoute('js/system-status.js'),
        ], 'js');
        //El armazón de configuración, y con él el relleno a cero: una hoja para las tres pantallas del módulo.
        set_custom_assets([
            SystemStatusRoutes::staticRoute('css/system-status.css'),
        ], 'css');

        $title = __(self::LANG_GROUP, 'Avisos del sistema');
        set_title($title);

        $this->helpController->render('panel/layout/header');
        $this->render('alerts', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'rows' => self::alertRows($userType),
            'isRoot' => $userType === UsersModel::TYPE_USER_ROOT,
            'toggleURL' => self::routeName('alerts-toggle'),
        ] + self::systemHeader($title, __(self::LANG_GROUP, 'Lo que el sistema detecta y conviene revisar, y qué avisos se muestran.'), $this->helpController));
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * La cabecera de una pantalla del grupo Sistema de Configuración: la misma que las de SettingsController
     * (SettingsController::sectionHeaderData()), sin migas (son vistas principales) y con el HelperController que pinta
     * las piezas.
     *
     * @param string $title Ya traducido
     * @param string $description Ya traducida
     * @param HelperController $helpController
     * @return array{title:string,description:string,indexLink:string,helpController:HelperController}
     */
    protected static function systemHeader(string $title, string $description, HelperController $helpController): array
    {
        $header = SettingsController::sectionHeaderData($title, $description);
        unset($header['breadcrumbs']);
        return $header + [
            'helpController' => $helpController,
        ];
    }

    /**
     * Las filas de la página: los avisos activos de ese tipo de usuario, ocultos incluidos (con su estado).
     *
     * @param int $userType
     * @return array<int,array{key:string,severity:string,message:string,hidden:bool,dismissible:bool,fixURL:string,fixLabel:string}>
     */
    public static function alertRows(int $userType): array
    {
        $rows = [];
        foreach (SystemAlertRegistry::all() as $key => $alert) {
            if (!in_array($userType, $alert->audience(), true) || !SystemAlertRegistry::isActive($alert)) {
                continue;
            }
            $fixURL = '';
            if ($alert->fixRoute() !== null) {
                //Solo si quien mira puede entrar en la ruta de arreglo.
                $fixURL = self::fixURL($alert);
            }
            $rows[] = [
                'key' => $key,
                'severity' => $alert->severity(),
                'message' => $alert->message(),
                'hidden' => SystemAlertRegistry::isHidden($key),
                'dismissible' => $alert->isDismissible(),
                'fixURL' => $fixURL,
                'fixLabel' => $alert->fixLabel() ?? __(self::LANG_GROUP, 'Cómo arreglarlo'),
            ];
        }
        return $rows;
    }

    /**
     * Oculta o muestra un aviso ocultable (solo root, por la ruta). Cuerpo: key, hidden=yes|no.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function alertsToggle(Request $request, Response $response)
    {
        $body = (array) $request->getParsedBody();
        $key = $body['key'] ?? null;
        $hidden = $body['hidden'] ?? null;
        $alert = is_string($key) ? SystemAlertRegistry::get($key) : null;
        if ($alert === null || !$alert->isDismissible() || !in_array($hidden, ['yes', 'no'], true)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Petición no válida: key de un aviso ocultable y hidden=yes|no.')], 400);
        }
        $done = $hidden === 'yes' ? SystemAlertRegistry::hide((string) $key) : SystemAlertRegistry::show((string) $key);
        if (!$done) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No se pudo guardar el cambio.')], 500);
        }
        self::logAction(LogsMapper::MSG_SYSTEM_ALERT_TOGGLED, [
            '%username%' => self::currentUsername(),
            '%action%' => $hidden === 'yes' ? __(self::LANG_GROUP, 'ocultó') : __(self::LANG_GROUP, 'mostró'),
            '%alert%' => (string) $key,
        ]);
        return $response->withJson(['key' => $key, 'hidden' => $hidden === 'yes']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function maintenance(Request $request, Response $response)
    {
        set_custom_assets([
            SystemStatusRoutes::staticRoute('js/system-status.js'),
        ], 'js');
        //El armazón de configuración, y con él el relleno a cero: una hoja para las tres pantallas del módulo.
        set_custom_assets([
            SystemStatusRoutes::staticRoute('css/system-status.css'),
        ], 'css');

        $title = __(self::LANG_GROUP, 'Estado y cachés');
        set_title($title);

        $this->helpController->render('panel/layout/header');
        $usuario = getLoggedFrameworkUser();
        $status = self::maintenanceStatus();
        $this->render('maintenance', array_merge($status, [
            'maintenanceStatus' => $status,
            'cleanTargets' => array_filter(CleanTargetRegistry::all(), fn ($target): bool => $usuario !== null && in_array((int) $usuario->type, $target->allowedTypes(), true)),
            'cleanRows' => CleanTargetRegistry::withRows(),
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'brokenLinksURL' => self::routeName('maintenance-broken-links'),
            'cleanURL' => self::routeName('maintenance-clean'),
            'cacheCleanURL' => SettingsController::routeName('system-cache-clean'),
        ], self::systemHeader($title, __(self::LANG_GROUP, 'Cómo está la instalación y las cachés que se pueden vaciar.'), $this->helpController)));
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * La Salud de la instalación (P179): versiones, entorno, base de datos, tareas y carpetas, cada fila con su origen.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function health(Request $request, Response $response)
    {
        set_custom_assets([
            SystemStatusRoutes::staticRoute('css/system-status.css'),
        ], 'css');

        $title = __(self::LANG_GROUP, 'Salud de la instalación');
        set_title($title);

        $this->helpController->render('panel/layout/header');
        $this->render('health', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'rows' => SystemHealth::rows(),
            'groupTitles' => SystemHealth::groupTitles(),
        ] + self::systemHeader($title, __(self::LANG_GROUP, 'Cómo está esta instalación: versiones, entorno, base de datos, tareas y carpetas.'), $this->helpController));
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Lo que enseña la página de mantenimiento: las claves de estado de cada limpieza registrada con fila, en el orden de
     * las filas (hoy linksTotal, linksBroken, brokenShown, webpCacheBytes, publicationsCacheBytes y staticsStamp).
     *
     * @return array<string,mixed>
     */
    public static function maintenanceStatus(): array
    {
        $status = [];
        foreach (CleanTargetRegistry::withRows() as $target) {
            $status = array_merge($status, $target->status());
        }
        return $status;
    }

    /**
     * Lo que da la fila de los accesos directos: el total, los rotos y los primeros rotos para la lista.
     *
     * @return array{linksTotal:int,linksBroken:int,brokenShown:string[]}
     */
    public static function linksStatus(): array
    {
        $links = ServerDelegatedLinks::scan();
        return [
            'linksTotal' => $links['total'],
            'linksBroken' => count($links['broken']),
            'brokenShown' => array_slice($links['broken'], 0, self::BROKEN_LINKS_SHOWN),
        ];
    }

    /**
     * Renueva la marca de versión de los estáticos (la limpieza statics-stamp).
     *
     * @return array<string,mixed>
     */
    public static function cleanStaticsStamp(): array
    {
        $before = (string) static_files_cache_stamp();
        static_files_cache_stamp(true);
        $after = (string) static_files_cache_stamp();
        return [
            'before' => $before,
            'after' => $after,
            //El coste va en el acuse: quien lo pulsa tiene que verlo, no deducirlo.
            'message' => __(self::LANG_GROUP, 'Hecho. Todos los visitantes volverán a descargar los estilos, los scripts y las imágenes de las páginas.'),
            'freed' => 0,
            'log' => [LogsMapper::MSG_MAINTENANCE_CACHE_CLEANED, ['%freed%' => '0']],
        ];
    }

    /**
     * Borra todos los accesos directos de server-delegated (la limpieza server-delegated-links).
     *
     * @return array<string,mixed>
     */
    public static function cleanServerDelegatedLinks(): array
    {
        //En bytes no se mide: un enlace no ocupa, así que dar bytes aquí sería una cifra que no
        //puede cambiar. La unidad de este destino es el NÚMERO de enlaces.
        $before = ServerDelegatedLinks::scan()['total'];
        $result = ServerDelegatedLinks::deleteAll();
        $after = ServerDelegatedLinks::scan()['total'];
        //Lo que no se pudo borrar se dice: un recuento que lo calla miente.
        $message = $result['failed'] > 0
            ? sprintf(
            __(self::LANG_GROUP, 'Borrados %d accesos directos y %d carpetas vacías; %d no se pudieron borrar por falta de permiso. Se vuelven a crear al pedirse cada archivo.'),
            count($result['deleted']),
            count($result['emptiedDirectories']),
            $result['failed']
        )
            : sprintf(
            __(self::LANG_GROUP, 'Borrados %d accesos directos y %d carpetas vacías. Se vuelven a crear al pedirse cada archivo.'),
            count($result['deleted']),
            count($result['emptiedDirectories'])
        );
        return [
            'before' => $before,
            'after' => $after,
            'message' => $message,
            'freed' => 0,
            'extra' => $result,
            'log' => [LogsMapper::MSG_MAINTENANCE_LINKS_CLEANED, [
                '%deleted%' => (string) count($result['deleted']),
                '%failed%' => (string) (int) $result['failed'],
            ]],
        ];
    }

    /**
     * Vacía una carpeta de caché, menos su .htaccess y su .gitignore, y dice cuántos bytes liberó. La usan las limpiezas
     * que son carpetas: la del núcleo (imágenes optimizadas) y la de un módulo (los listados de Publications).
     *
     * @param string $directory Ruta absoluta
     * @param string $notWritable El error si el sistema no puede escribir en ella, ya traducido
     * @param string $done El acuse, ya traducido
     * @return array<string,mixed>
     */
    public static function cleanCacheDirectory(string $directory, string $notWritable, string $done): array
    {
        //Sin permiso de escritura (la creó otro usuario con 0755) NO se intenta: delete() haría un
        //unlink que falla con un aviso, y aquí un aviso aborta la operación entera.
        if (is_dir($directory) && !is_writable($directory)) {
            //La ruta del servidor es para quien depura: va al log, no a la pantalla.
            log_exception(new \RuntimeException("Mantenimiento: sin permiso de escritura sobre {$directory}; la creó otro usuario del sistema."));
            return ['error' => $notWritable, 'httpStatus' => 409];
        }

        $before = ServerDelegatedLinks::directorySize($directory);
        if (is_dir($directory)) {
            $carpeta = new DirectoryObject($directory);
            $carpeta->process(new FilesIgnore([
                '.htaccess',
                '.gitignore',
            ]));
            $carpeta->delete();
        }
        $after = ServerDelegatedLinks::directorySize($directory);
        //`freed` es de BYTES: solo tiene sentido en los destinos que son carpetas.
        $freed = max(0, $before - $after);
        return [
            'before' => $before,
            'after' => $after,
            'message' => $done,
            'freed' => $freed,
            'log' => [LogsMapper::MSG_MAINTENANCE_CACHE_CLEANED, ['%freed%' => (string) $freed]],
        ];
    }

    /**
     * Borra los enlaces rotos de server-delegated y las carpetas que quedan vacías (solo root, por la ruta).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function maintenanceBrokenLinks(Request $request, Response $response)
    {
        $result = ServerDelegatedLinks::deleteBroken();
        self::logAction(LogsMapper::MSG_BROKEN_LINKS_DELETED, [
            '%username%' => self::currentUsername(),
            '%deleted%' => (string) count($result['deleted']),
            '%directories%' => (string) count($result['emptiedDirectories']),
            '%failed%' => (string) $result['failed'],
            '%links%' => count($result['deleted']) > 0 ? implode(', ', $result['deleted']) : '—',
        ]);
        return $response->withJson($result);
    }

    /**
     * El enlace «Cómo arreglarlo», o '' si quien mira no puede entrar en la ruta de arreglo.
     *
     * @param SystemAlert $alert
     * @return string
     */
    public static function fixURL(SystemAlert $alert): string
    {
        //La ruta de arreglo puede ser de cualquier módulo: se pregunta por su nombre completo, con permiso.
        $name = (string) $alert->fixRoute();
        $user = getLoggedFrameworkUser();
        if ($user !== null && !\PiecesPHP\Core\Roles::hasPermissions($name, (int) $user->type)) {
            return '';
        }
        $url = get_route($name, [], true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return $alert->fixQuery() !== [] ? $url . '?' . http_build_query($alert->fixQuery()) : $url;
    }

    /**
     * @return string
     */
    private static function currentUsername(): string
    {
        $user = getLoggedFrameworkUser();
        return $user !== null ? (string) $user->username : '?';
    }

    /**
     * En el registro de acciones del panel (EventsLog), con su tipo: quién y cuándo los pone el mapper.
     *
     * @param string $type Uno de LogsMapper::MSG_*
     * @param array<string,string> $variables
     * @param string|null $mailLogID La fila del registro de correos, si la acción es sobre una
     * @return void
     */
    private static function logAction(string $type, array $variables, ?string $mailLogID = null): void
    {
        if (LogsRoutes::ENABLE) {
            LogsMapper::addLog($type, $variables, $mailLogID !== null ? 'id' : null, $mailLogID, $mailLogID !== null ? MailLogMapper::TABLE : null);
        }
    }

    /**
     * Comparación ESTRICTA y sensible a mayúsculas: un destino que no esté registrado no se toca (CleanTargetRegistry).
     *
     * @param mixed $target
     * @return bool
     */
    public static function isCleanTarget(mixed $target): bool
    {
        return CleanTargetRegistry::get($target) !== null;
    }

    /**
     * Borra UN destino de caché, no los cuatro (solo root, por la ruta).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function maintenanceClean(Request $request, Response $response)
    {
        $body = (array) $request->getParsedBody();
        $target = $body['target'] ?? null;

        $cleanTarget = CleanTargetRegistry::get($target);
        if ($cleanTarget === null) {
            //La lista de opciones válidas es para quien depura: va al log, no a la pantalla.
            log_exception(new \InvalidArgumentException('Mantenimiento: target no válido; tiene que ser uno de ' . implode(', ', array_keys(CleanTargetRegistry::all())) . '.'));
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Petición no válida: esa opción no existe.')], 400);
        }
        //La ruta ya exige sesión de root; esto respeta, además, a quién deja pulsar cada destino (sin usuario, una suite).
        $usuario = getLoggedFrameworkUser();
        if ($usuario !== null && !in_array((int) $usuario->type, $cleanTarget->allowedTypes(), true)) {
            return throw403($request);
        }

        $target = $cleanTarget->key();
        $result = $cleanTarget->clean();
        if (isset($result['error'])) {
            return $response->withJson(['error' => (string) $result['error']], (int) ($result['httpStatus'] ?? 409));
        }
        $extra = is_array($result['extra'] ?? null) ? $result['extra'] : [];

        [$logMessage, $logParams] = is_array($result['log'] ?? null) ? $result['log'] : [LogsMapper::MSG_MAINTENANCE_CACHE_CLEANED, ['%freed%' => (string) (int) ($result['freed'] ?? 0)]];
        self::logAction($logMessage, array_merge([
            '%username%' => self::currentUsername(),
            '%target%' => $target,
        ], $logParams));

        //`status` recalculado, y es obligatorio: sin él la tabla sigue diciendo la cifra vieja al lado
        //de un mensaje que dice que se borró.
        return $response->withJson(array_merge($extra, [
            'success' => true,
            'target' => $target,
            'message' => (string) ($result['message'] ?? ''),
            'before' => $result['before'] ?? 0,
            'after' => $result['after'] ?? 0,
            'freed' => (int) ($result['freed'] ?? 0),
            'status' => self::maintenanceStatus(),
        ]));
    }

    /**
     * La pantalla del modo mantenimiento del sitio.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function siteMaintenance(Request $request, Response $response)
    {
        set_custom_assets([
            SystemStatusRoutes::staticRoute('js/site-maintenance.js'),
        ], 'js');
        //El armazón de configuración, y con él el relleno a cero: una hoja para las tres pantallas del módulo.
        set_custom_assets([
            SystemStatusRoutes::staticRoute('css/system-status.css'),
        ], 'css');

        $title = __(self::LANG_GROUP, 'Sitio en mantenimiento');
        set_title($title);

        $usuario = getLoggedFrameworkUser();

        $this->helpController->render('panel/layout/header');
        $this->render('site-maintenance', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'saveURL' => self::routeName('site-maintenance-save'),
            'enabled' => MaintenanceMode::isEnabled(),
            'allowedRoles' => MaintenanceMode::allowedRoles(),
            'retryAfter' => MaintenanceMode::retryAfter(),
            'retryAfterMax' => MaintenanceMode::RETRY_AFTER_MAX,
            'rootCode' => UsersModel::TYPE_USER_ROOT,
            'currentRole' => $usuario !== null ? (int) $usuario->type : null,
        ] + self::systemHeader($title, __(self::LANG_GROUP, 'Cerrar el sitio público por mantenimiento y quién puede seguir entrando.'), $this->helpController));
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Guarda los tres ajustes del modo. Solo root, por la ruta.
     *
     * VALIDA ANTES DE GUARDAR, al contrario que la acción genérica de configuraciones: aquella
     * guarda cualquier cosa y contesta «Guardado», y es la clase la que descarta el valor al leerlo.
     * Aquí lo que no vale no entra, y se dice cuál.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function siteMaintenanceSave(Request $request, Response $response)
    {
        $body = (array) $request->getParsedBody();

        $enabled = ($body['enabled'] ?? '0') === '1';
        $retryAfter = $body['retry_after'] ?? null;
        $roles = $body['allowed_roles'] ?? '[]';
        $roles = is_string($roles) ? json_decode($roles, true) : $roles;

        if (!MaintenanceMode::retryAfterIsValid($retryAfter)) {
            return $response->withJson([
                'error' => sprintf(
                    __(self::LANG_GROUP, 'Los segundos de reintento tienen que ser un entero de 1 a %s.'),
                    (string) MaintenanceMode::RETRY_AFTER_MAX
                ),
            ], 400);
        }

        if (!MaintenanceMode::allowedRolesAreValid($roles)) {
            return $response->withJson([
                'error' => __(self::LANG_GROUP, 'La lista de roles tiene que traer solo códigos de rol existentes.'),
            ], 400);
        }

        $guardado = SettingsModel::setConfigValue(MaintenanceMode::ENABLED_CONFIG, $enabled)
            && SettingsModel::setConfigValue(MaintenanceMode::ALLOWED_ROLES_CONFIG, array_values(array_map('intval', (array) $roles)))
            && SettingsModel::setConfigValue(MaintenanceMode::RETRY_AFTER_CONFIG, (int) $retryAfter);

        if (!$guardado) {
            return $response->withJson([
                'error' => __(self::LANG_GROUP, 'No se pudo guardar el cambio.'),
            ], 500);
        }

        self::logAction(LogsMapper::MSG_SITE_MAINTENANCE_TOGGLED, [
            '%username%' => self::currentUsername(),
            '%action%' => $enabled ? __(self::LANG_GROUP, 'encendió') : __(self::LANG_GROUP, 'apagó'),
        ]);

        return $response->withJson([
            'success' => true,
            'message' => $enabled
                ? __(self::LANG_GROUP, 'El sitio está en mantenimiento.')
                : __(self::LANG_GROUP, 'El sitio vuelve a estar disponible.'),
            'enabled' => $enabled,
        ]);
    }

    /**
     * La pantalla vieja del registro de correos: ahora es una pestaña de «Registros».
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function mailLog(Request $request, Response $response)
    {
        if (!LogsController::allowedRoute('index')) {
            return throw403($request);
        }
        return $response->withRedirect(LogsController::routeName('index') . '?' . http_build_query(['tab' => self::MAIL_LOG_TAB]));
    }

    /**
     * Una fecha del filtro (DD-MM-AAAA, la del calendario del panel) como AAAA-MM-DD; null si no lo es.
     *
     * @param mixed $value
     * @return string|null
     */
    protected static function mailLogDate($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $date = \DateTime::createFromFormat('!d-m-Y', trim($value));
        return $date !== false && $date->format('d-m-Y') === trim($value) ? $date->format('Y-m-d') : null;
    }

    /**
     * La pestaña «Correos enviados» de «Registros»: qué se mandó, a dónde y si llegó.
     *
     * **Solo el usuario principal**, y el motivo no es la costumbre: la tabla guarda los
     * DESTINATARIOS de cada correo, que son datos personales de quien no pidió salir en ninguna
     * lista. Lo mismo que el registro de actividad.
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function mailLogTab(Request $request, string $tabURL): string
    {
        add_global_asset(SystemStatusRoutes::staticRoute('js/mail-log.js'), 'js');

        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('mail-log', [
            'pieces' => new HelperController(),
            'langGroup' => self::LANG_GROUP,
            'results' => [
                MailLogMapper::RESULT_DELIVERED => __(self::LANG_GROUP, 'Entregado'),
                MailLogMapper::RESULT_OUTBOX => __(self::LANG_GROUP, 'En el buzón'),
                MailLogMapper::RESULT_FAILED => __(self::LANG_GROUP, 'No llegó'),
            ],
            'deliveries' => self::MAIL_LOG_DELIVERIES,
            'processTableLink' => self::routeName('mail-log-datatables'),
            //Sin la tabla no hay nada que listar, y hay que DECIRLO: una tabla vacía se lee como
            //«no se ha mandado ningún correo», que es una conclusión distinta y falsa.
            'tableExists' => MailLogMapper::tableExists(),
            'updateFile' => MailLogMapper::UPDATE_FILE,
            'windowHours' => MailLogMapper::ALERT_WINDOW_HOURS,
            'counts' => MailLogMapper::recentCounts(),
            //Sin el permiso del cuerpo, la columna NO existe para ese usuario (ADR 0048 §5): ni
            //cabecera, ni celda, ni un aviso de que hay algo que no puede ver.
            'canSeeBody' => self::canSeeMailBody(),
        ], false);
    }

    /**
     * @return void
     */
    public static function registerLogTab(): void
    {
        LogTabRegistry::register(new LogTab(
            self::MAIL_LOG_TAB,
            __(self::LANG_GROUP, 'Correos enviados'),
            'envelope open outline',
            self::$baseRouteName . '-mail-log-datatables',
            fn(Request $request, string $tabURL): string => self::mailLogTab($request, $tabURL),
            20
        ));
        LogTabRegistry::register(new LogTab(
            self::TASK_RUNS_TAB,
            __(self::LANG_GROUP, 'Tareas programadas'),
            'clock outline',
            self::$baseRouteName . '-task-runs-datatables',
            fn(Request $request, string $tabURL): string => self::taskRunsTab($request, $tabURL),
            60
        ));
    }

    /**
     * La pestaña «Tareas programadas» de «Registros»: el estado de cada tarea registrada y, debajo, el libro de
     * ejecuciones (G10). Solo root, como el resto de lo que anota el sistema.
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function taskRunsTab(Request $request, string $tabURL): string
    {
        add_global_asset(SystemStatusRoutes::staticRoute('js/task-runs.js'), 'js');
        $now = new \DateTime();
        $latest = TaskRunMapper::latestByTask();
        $tasks = [];
        foreach (CronJobTask::getCronJobs() as $task) {
            $name = $task->getName();
            $state = $task->getState();
            //Sin franja no hay .json: lo último que se sabe es la última línea del libro.
            $lastResult = $state['lastResult'] ?? null;
            $lastAt = $state['lastAttemptAt'] ?? null;
            $lastError = $state['lastError'] ?? null;
            if ($lastResult === null && isset($latest[$name])) {
                $lastResult = $latest[$name]['status'] === CronJobTask::STATUS_EXECUTED ? 'success' : 'failed';
                $lastAt = $latest[$name]['startedAt'];
                $lastError = $lastResult === 'failed' ? $latest[$name]['message'] : null;
            }
            $next = $task->nextDueSlot($now);
            $tasks[] = [
                'name' => $name,
                'label' => self::taskDisplayName($name),
                'schedule' => self::describeSchedule($task->getSchedule()),
                'lastResult' => $lastResult,
                'lastAt' => is_string($lastAt) && strtotime($lastAt) !== false ? date('d-m-Y H:i', (int) strtotime($lastAt)) : '',
                'lastError' => is_string($lastError) ? $lastError : '',
                'next' => $next !== null ? $next->format('d-m-Y H:i') : '',
            ];
        }
        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('task-runs', [
            'pieces' => new HelperController(),
            'langGroup' => self::LANG_GROUP,
            'processTableLink' => self::routeName('task-runs-datatables'),
            'tableExists' => TaskRunMapper::tableExists(),
            'updateFile' => TaskRunMapper::UPDATE_FILE,
            'tasks' => $tasks,
            'cron' => self::cronCheatsheet(self::registeredRuns($latest), $now),
        ], false);
    }

    /**
     * La ayuda del cron solo mira tareas registradas: una fila de otra (sembrada, retirada) no dice si el cron corre hoy.
     *
     * @param array<string,array{status:string,startedAt:string,message:string|null}> $latest TaskRunMapper::latestByTask()
     * @return array<string,array{status:string,startedAt:string,message:string|null}>
     */
    public static function registeredRuns(array $latest): array
    {
        $names = array_map(static fn (CronJobTask $task): string => $task->getName(), CronJobTask::getCronJobs());
        return array_intersect_key($latest, array_flip($names));
    }

    /**
     * La chuleta del cron de ESTA instalación, derivada y no escrita a mano: la línea de crontab con la ruta real de su
     * `index.php` (en un servidor no hay `src/`: su contenido es la raíz desplegada), la URL de la ruta por HTTP y si hay
     * llave. La llave no sale nunca de aquí: solo si está configurada. Abierta si no hay ejecuciones en 24 h.
     *
     * @param array<string,array{status:string,startedAt:string,message:string|null}> $latest TaskRunMapper::latestByTask()
     * @param \DateTime $now
     * @return array{line:string,url:string,keyConfigured:bool,lastRunAgo:string|null,open:bool}
     */
    public static function cronCheatsheet(array $latest, \DateTime $now): array
    {
        $last = null;
        foreach ($latest as $run) {
            $at = strtotime($run['startedAt']);
            if ($at !== false && ($last === null || $at > $last)) {
                $last = $at;
            }
        }
        $ago = null;
        if ($last !== null) {
            $minutes = max(0, intdiv($now->getTimestamp() - $last, 60));
            $ago = $minutes < 60
                ? sprintf(__(self::LANG_GROUP, 'hace %d min'), $minutes)
                : ($minutes < 48 * 60 ? sprintf(__(self::LANG_GROUP, 'hace %d h'), intdiv($minutes, 60)) : sprintf(__(self::LANG_GROUP, 'hace %d días'), intdiv($minutes, 24 * 60)));
        }
        $key = get_config('CronJobKey');
        return [
            'line' => '* * * * * php8.5 ' . basepath('index.php') . ' cli run-cronjobs run',
            'url' => \API\Controllers\APIController::routeName('cron-jobs', ['actionType' => 'run'], true),
            'keyConfigured' => is_string($key) && $key !== '',
            'lastRunAgo' => $ago,
            'open' => $last === null || $now->getTimestamp() - $last > 24 * 3600,
        ];
    }

    /**
     * El libro de ejecuciones, filtrable por tarea y por resultado. Todo filtro va por marcador y todo lo que sale,
     * escapado: el mensaje es el de la tarea.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function taskRunsDataTables(Request $request, Response $response)
    {
        $table = TaskRunMapper::TABLE;
        $where = [];
        $task = $request->getQueryParam('task', '');
        if (is_string($task) && trim($task) !== '') {
            $where[] = new WhereItem("{$table}.taskName", WhereItem::EQUAL_OPERATOR, mb_substr(trim($task), 0, 191), WhereItem::AND_OPERATOR);
        }
        $status = $request->getQueryParam('status', '');
        if (is_string($status) && array_key_exists($status, self::taskRunStatusLabels())) {
            $where[] = new WhereItem("{$table}.status", WhereItem::EQUAL_OPERATOR, $status, WhereItem::AND_OPERATOR);
        }

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $where !== [] ? new WhereSegment($where) : null,
            'select_fields' => [
                "{$table}.id",
                "{$table}.taskName",
                "{$table}.slot",
                "{$table}.triggeredBy",
                "{$table}.status",
                "{$table}.attempt",
                "{$table}.startedAt",
                "{$table}.durationMs",
                "{$table}.message",
            ],
            'columns_order' => ['startedAt', 'taskName', 'triggeredBy', 'status', 'attempt', 'durationMs'],
            'custom_order' => ['id' => 'DESC'],
            'mapper' => new TaskRunMapper(),
            'request' => $request,
            'on_set_data' => function ($e): array {
                $status = is_string($e->status ?? null) ? $e->status : '';
                [$color, $label] = self::taskRunStatusLabel($status);
                $message = is_string($e->message ?? null) ? $e->message : '';
                $slot = is_string($e->slot ?? null) && strtotime($e->slot) !== false ? date('d-m-Y H:i', (int) strtotime($e->slot)) : '';
                return [
                    escape_html(is_string($e->startedAt ?? null) && strtotime($e->startedAt) !== false ? date('d-m-Y H:i:s', (int) strtotime($e->startedAt)) : ''),
                    '<div class="ui vertical fitted flex"><span>' . escape_html(self::taskDisplayName((string) ($e->taskName ?? ''))) . '</span>' . ($slot !== '' ? '<small class="task-runs-slot ui muted text">' . escape_html(sprintf(__(self::LANG_GROUP, 'franja %s'), $slot)) . '</small>' : '') . '</div>',
                    escape_html((string) ($e->triggeredBy ?? '')),
                    '<div class="ui vertical left aligned tight flex">' . StatusLabel::html($label, $color)
                        . ($message !== '' ? '<small class="task-runs-message ui muted text">' . escape_html($message) . '</small>' : '') . '</div>',
                    escape_html((string) ($e->attempt ?? '')),
                    escape_html(isset($e->durationMs) && is_numeric($e->durationMs) ? $e->durationMs . ' ms' : ''),
                ];
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * Los resultados que el libro de ejecuciones anota, con su color y su etiqueta.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function taskRunStatusLabels(): array
    {
        return [
            CronJobTask::STATUS_EXECUTED => ['green', __(self::LANG_GROUP, 'Ejecutada')],
            CronJobTask::STATUS_FAILED => ['red', __(self::LANG_GROUP, 'Fallida')],
            CronJobTask::STATUS_LOCKED => ['yellow', __(self::LANG_GROUP, 'Saltada: otra en curso')],
            CronJobTask::STATUS_ALREADY_DONE => ['grey', __(self::LANG_GROUP, 'Saltada: ya hecha en su franja')],
        ];
    }

    /**
     * Cómo se PINTA el nombre de una tarea: el nombre es su identificador en el libro y no cambia; si el diccionario del
     * grupo lo trae, sale traducido, y si no (una tarea de un clon), tal cual. Sin pasar por __() lo que no está: en local
     * cada clave que falta se anota como traducción pendiente.
     *
     * @param string $name
     * @return string
     */
    public static function taskDisplayName(string $name): string
    {
        $messages = __(self::LANG_GROUP);
        return is_array($messages) && is_string($messages[$name] ?? null) ? $messages[$name] : $name;
    }

    /**
     * El color y la etiqueta de un resultado. Uno desconocido sale tal cual, en gris: es un dato del registro.
     *
     * @param string $status
     * @return array{0: string, 1: string}
     */
    public static function taskRunStatusLabel(string $status): array
    {
        return self::taskRunStatusLabels()[$status] ?? ['grey', $status];
    }

    /**
     * La programación de una tarea, legible.
     *
     * @param array{kind: string, minute?: int, time?: string, day?: int, period: string}|null $schedule
     * @return string
     */
    protected static function describeSchedule(?array $schedule): string
    {
        if ($schedule === null) {
            return __(self::LANG_GROUP, 'Sin franja: cada vez que se disparan las tareas y su condición se cumple');
        }
        $days = [__(self::LANG_GROUP, 'domingo'), __(self::LANG_GROUP, 'lunes'), __(self::LANG_GROUP, 'martes'), __(self::LANG_GROUP, 'miércoles'), __(self::LANG_GROUP, 'jueves'), __(self::LANG_GROUP, 'viernes'), __(self::LANG_GROUP, 'sábado')];
        return match ($schedule['kind']) {
            'hourly' => sprintf(__(self::LANG_GROUP, 'Cada hora, al minuto %d'), $schedule['minute'] ?? 0),
            'daily' => sprintf(__(self::LANG_GROUP, 'Cada día a las %s'), $schedule['time'] ?? ''),
            'weekly' => sprintf(__(self::LANG_GROUP, 'Cada semana, el %s a las %s'), $days[$schedule['day'] ?? 0] ?? '', $schedule['time'] ?? ''),
            default => $schedule['kind'],
        };
    }

    /**
     * Las filas del registro de correos para la tabla.
     *
     * Todo lo que sale de la base va ESCAPADO: el asunto y los destinatarios de un correo pueden
     * venir de un formulario público, y esta tabla pinta HTML.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function mailLogDataTables(Request $request, Response $response)
    {
        $tabla = MailLogMapper::TABLE;
        $etiquetas = [
            MailLogMapper::RESULT_DELIVERED => ['green', __(self::LANG_GROUP, 'Entregado')],
            MailLogMapper::RESULT_OUTBOX => [StatusLabel::WARNING, __(self::LANG_GROUP, 'En el buzón')],
            MailLogMapper::RESULT_FAILED => ['red', __(self::LANG_GROUP, 'No llegó')],
        ];
        $escapar = fn($valor): string => htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
        $verCuerpo = self::canSeeMailBody();
        $rotuloVer = __(self::LANG_GROUP, 'Ver cuerpo');

        //El cuerpo NO entra aquí, por construcción: solo si la fila lo tiene, como booleano. Y sin la
        //columna en la tabla ni eso, que el SELECT fallaría.
        $tieneCuerpo = MailLogMapper::bodyColumnExists() ? "({$tabla}.body IS NOT NULL) AS hasBody" : '0 AS hasBody';

        //Los filtros llegan en la dirección de los datos (mail-log.js) y van con marcadores: solo valores conocidos o
        //texto acotado, nunca SQL.
        $where = [];
        $from = self::mailLogDate($request->getQueryParam('from', ''));
        if ($from !== null) {
            $where[] = new WhereItem("{$tabla}.sentAt", WhereItem::GREATER_OR_EQUAL_OPERATOR, $from . ' 00:00:00', WhereItem::AND_OPERATOR);
        }
        $to = self::mailLogDate($request->getQueryParam('to', ''));
        if ($to !== null) {
            $where[] = new WhereItem("{$tabla}.sentAt", WhereItem::LESS_OR_EQUAL_OPERATOR, $to . ' 23:59:59', WhereItem::AND_OPERATOR);
        }
        $filterResult = $request->getQueryParam('result', '');
        if (is_string($filterResult) && in_array($filterResult, MailLogMapper::RESULTS, true)) {
            $where[] = new WhereItem("{$tabla}.result", WhereItem::EQUAL_OPERATOR, $filterResult, WhereItem::AND_OPERATOR);
        }
        $filterDelivery = $request->getQueryParam('delivery', '');
        if (is_string($filterDelivery) && in_array($filterDelivery, self::MAIL_LOG_DELIVERIES, true)) {
            $where[] = new WhereItem("{$tabla}.delivery", WhereItem::EQUAL_OPERATOR, $filterDelivery, WhereItem::AND_OPERATOR);
        }
        $filterOrigin = $request->getQueryParam('origin', '');
        if (is_string($filterOrigin) && trim($filterOrigin) !== '') {
            $where[] = new WhereItem("{$tabla}.origin", WhereItem::LIKE_OPERATOR, '%' . mb_substr(trim($filterOrigin), 0, 100) . '%', WhereItem::AND_OPERATOR);
        }

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $where !== [] ? new WhereSegment($where) : null,
            'select_fields' => [
                "{$tabla}.id",
                "{$tabla}.sentAt",
                "{$tabla}.recipients",
                "{$tabla}.subject",
                "{$tabla}.origin",
                "{$tabla}.delivery",
                "{$tabla}.result",
                "{$tabla}.reason",
                $tieneCuerpo,
            ],
            'columns_order' => [
                'sentAt',
                'recipients',
                'subject',
                'origin',
                'delivery',
                'result',
            ],
            'custom_order' => [
                'id' => 'DESC',
            ],
            'mapper' => new MailLogMapper(),
            'request' => $request,
            'on_set_data' => function ($e) use ($etiquetas, $escapar, $verCuerpo, $rotuloVer): array {
                $resultado = isset($e->result) && is_string($e->result) ? $e->result : '';
                [$color, $rotulo] = $etiquetas[$resultado] ?? ['grey', $resultado];
                $motivo = isset($e->reason) && is_string($e->reason) ? $e->reason : '';

                $columnas = [];
                $columnas[] = $escapar($e->sentAt ?? '');
                $columnas[] = $escapar($e->recipients ?? '');
                $columnas[] = $escapar($e->subject ?? '');
                $columnas[] = $escapar($e->origin ?? '');
                $columnas[] = $escapar($e->delivery ?? '');
                //El resultado con su motivo pegado: un «No llegó» sin motivo no dice qué arreglar.
                $columnas[] = '<div class="ui vertical left aligned tight flex">' . StatusLabel::html($rotulo, $color)
                    . ($motivo !== '' ? '<small class="mail-log-reason ui muted text">' . $escapar($motivo) . '</small>' : '') . '</div>';
                if ($verCuerpo) {
                    $id = isset($e->id) ? (int) $e->id : 0;
                    $columnas[] = $id > 0 && !empty($e->hasBody)
                        ? '<button type="button" class="ui basic button" data-mail-log-body-url="'
                        . $escapar(self::routeName('mail-log-body', ['id' => $id])) . '">' . $escapar($rotuloVer) . '</button>'
                        : '';
                }
                return $columnas;
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * El cuerpo de UNA fila del registro, descifrado (ADR 0048 §5).
     *
     * **Su nombre de ruta ES el permiso**, y es otro que el de la pantalla: un clon puede dar el
     * registro a un administrador y los cuerpos no. **Responde JSON, nunca HTML**: el cuerpo puede
     * llevar texto de quien llenó un formulario, y abierto como página sería un XSS contra root. La
     * vista lo pinta en un `iframe` con `sandbox` vacío, y nunca con `innerHTML`.
     *
     * **Se comprueba a sí misma** además de la capa de rutas: devuelve datos personales descifrados, y
     * un fallo de configuración del acceso no debe bastar para leerlos. Cada lectura, legible o no,
     * deja su línea en el registro de acciones: quién y qué fila, nunca el contenido ni a quién iba.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function mailLogBody(Request $request, Response $response, array $args = [])
    {
        $user = getLoggedFrameworkUser();
        if ($user === null || !\PiecesPHP\Core\Roles::hasPermissions(self::$baseRouteName . '-mail-log-body', (int) $user->type, true)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No tiene permiso para ver el cuerpo de los correos.')], 403);
        }
        $id = $args['id'] ?? null;
        if (!is_string($id) || preg_match('/^[1-9][0-9]{0,9}$/', $id) !== 1) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No existe ese registro.')], 404);
        }
        if (!MailLogMapper::tableExists() || !MailLogMapper::bodyColumnExists()) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Esta instalación no guarda el cuerpo de los correos.')], 404);
        }

        $fila = MailLogMapper::bodyOf((int) $id);
        if ($fila === null) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No existe ese registro.')], 404);
        }
        //Un ilegible y un vacío NO se confunden: «no hay cuerpo» y «hay uno que no se puede leer»
        //llevan a decisiones distintas.
        if ($fila['body'] === null) {
            return $response->withJson(['status' => 'sin-cuerpo', 'body' => null, 'message' => __(self::LANG_GROUP, 'Esta fila no tiene cuerpo guardado.')]);
        }
        //Con la clave derivada del ADR 0051; nunca lanza: lo que no se lee es «ilegible», no un 500.
        $claro = MailLogMapper::decryptBody($fila['body']);
        if ($claro === null) {
            self::logAction(LogsMapper::MSG_MAIL_BODY_UNREADABLE, ['%username%' => self::currentUsername(), '%id%' => (string) (int) $id], (string) (int) $id);
            return $response->withJson(['status' => 'ilegible', 'body' => null, 'message' => __(self::LANG_GROUP, 'No se pudo descifrar el cuerpo: se guardó con otra clave o está dañado.')]);
        }
        self::logAction(LogsMapper::MSG_MAIL_BODY_VIEWED, ['%username%' => self::currentUsername(), '%id%' => (string) (int) $id], (string) (int) $id);
        //Un cuerpo con UTF-8 inválido se enseña con U+FFFD donde falle: json_encode sin la opción lanza y daba 500.
        return $response->withJson(['status' => 'ok', 'body' => $claro], null, \JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Si quien mira tiene el permiso de los cuerpos. Por su ruta, como todo permiso aquí.
     *
     * @return bool
     */
    protected static function canSeeMailBody(): bool
    {
        return self::allowedRoute('mail-log-body', ['id' => 1]);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $root = [UsersModel::TYPE_USER_ROOT];

        $group->register([
            new Route("{$startRoute}/health[/]", self::class . ':health', self::$baseRouteName . '-health', 'GET', true, null, $root),
            new Route("{$startRoute}/alerts[/]", self::class . ':alerts', self::$baseRouteName . '-alerts', 'GET', true, null, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]),
            new Route("{$startRoute}/alerts/toggle[/]", self::class . ':alertsToggle', self::$baseRouteName . '-alerts-toggle', 'POST', true, null, $root),
            new Route("{$startRoute}/maintenance[/]", self::class . ':maintenance', self::$baseRouteName . '-maintenance', 'GET', true, null, $root),
            new Route("{$startRoute}/maintenance/broken-links[/]", self::class . ':maintenanceBrokenLinks', self::$baseRouteName . '-maintenance-broken-links', 'POST', true, null, $root),
            new Route("{$startRoute}/maintenance/clean[/]", self::class . ':maintenanceClean', self::$baseRouteName . '-maintenance-clean', 'POST', true, null, $root),
            //El MODO del sitio, con su propio guardado: mientras compartió la acción genérica de
            //configuraciones, el administrador general podía apagar el sitio.
            new Route("{$startRoute}/site-maintenance[/]", self::class . ':siteMaintenance', self::$baseRouteName . '-site-maintenance', 'GET', true, null, $root),
            new Route("{$startRoute}/site-maintenance/save[/]", self::class . ':siteMaintenanceSave', self::$baseRouteName . '-site-maintenance-save', 'POST', true, null, $root),
            //El registro de correos: solo root, porque lista DESTINATARIOS (datos personales).
            new Route("{$startRoute}/mail-log[/]", self::class . ':mailLog', self::$baseRouteName . '-mail-log', 'GET', true, null, $root),
            new Route("{$startRoute}/mail-log/datatables[/]", self::class . ':mailLogDataTables', self::$baseRouteName . '-mail-log-datatables', 'GET', true, null, $root),
            //El cuerpo, con SU PROPIO nombre: es otro permiso que el del listado (ADR 0048 §5).
            new Route("{$startRoute}/mail-log/body/{id}[/]", self::class . ':mailLogBody', self::$baseRouteName . '-mail-log-body', 'GET', true, null, $root),
            //El libro de las tareas programadas (G10).
            new Route("{$startRoute}/task-runs/datatables[/]", self::class . ':taskRunsDataTables', self::$baseRouteName . '-task-runs-datatables', 'GET', true, null, $root),
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
