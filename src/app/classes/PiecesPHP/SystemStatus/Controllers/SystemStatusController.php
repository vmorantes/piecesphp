<?php

/**
 * SystemStatusController.php
 */

namespace PiecesPHP\SystemStatus\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;

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
     * Rutas rotas que la página de mantenimiento enumera.
     */
    const BROKEN_LINKS_SHOWN = 50;

    /**
     * Los destinos que la pantalla de mantenimiento sabe borrar por separado.
     *
     * Los cuatro son solo root y de coste comparable, así que van por UNA acción con destino
     * validado, no por cuatro rutas.
     *
     * @var string[]
     */
    const CLEAN_TARGETS = [
        'statics-stamp',
        'webp-cache',
        'publications-cache',
        'server-delegated-links',
    ];

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
            'statics/core/css/app_config/system-status.css',
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
        ]);
        $this->helpController->render('panel/layout/footer');

        return $response;
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
        self::logAction(sprintf(
            __(self::LANG_GROUP, '%s %s el aviso del sistema «%s».'),
            self::currentUsername(),
            $hidden === 'yes' ? __(self::LANG_GROUP, 'ocultó') : __(self::LANG_GROUP, 'mostró'),
            (string) $key
        ));
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
            'statics/core/css/app_config/system-status.css',
        ], 'css');

        $title = __(self::LANG_GROUP, 'Estado y cachés');
        set_title($title);

        $this->helpController->render('panel/layout/header');
        $this->render('maintenance', array_merge(self::maintenanceStatus(), [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'brokenLinksURL' => self::routeName('maintenance-broken-links'),
            'cleanURL' => self::routeName('maintenance-clean'),
            'cacheCleanURL' => SettingsController::routeName('system-cache-clean'),
        ]));
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Lo que enseña la página de mantenimiento.
     *
     * @return array{linksTotal:int,linksBroken:int,brokenShown:string[],webpCacheBytes:int,publicationsCacheBytes:int,staticsStamp:string}
     */
    public static function maintenanceStatus(): array
    {
        $links = ServerDelegatedLinks::scan();
        return [
            'linksTotal' => $links['total'],
            'linksBroken' => count($links['broken']),
            'brokenShown' => array_slice($links['broken'], 0, self::BROKEN_LINKS_SHOWN),
            'webpCacheBytes' => ServerDelegatedLinks::directorySize(basepath(ServerStatics::WEBP_CACHE_DIRECTORY)),
            'publicationsCacheBytes' => ServerDelegatedLinks::directorySize(basepath('app/cache/Publications')),
            'staticsStamp' => (string) static_files_cache_stamp(),
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
        self::logAction(sprintf(
            __(self::LANG_GROUP, '%s borró %d enlace(s) roto(s) de server-delegated y %d carpeta(s) vacía(s); %d no se pudieron borrar por falta de permiso. Enlaces: %s'),
            self::currentUsername(),
            count($result['deleted']),
            count($result['emptiedDirectories']),
            $result['failed'],
            count($result['deleted']) > 0 ? implode(', ', $result['deleted']) : '—'
        ));
        return $response->withJson($result);
    }

    /**
     * @param SystemAlert $alert
     * @return string
     */
    private static function fixURL(SystemAlert $alert): string
    {
        //La ruta de arreglo puede ser de cualquier módulo: se pregunta por su nombre completo, con permiso.
        $name = (string) $alert->fixRoute();
        $user = getLoggedFrameworkUser();
        if ($user !== null && !\PiecesPHP\Core\Roles::hasPermissions($name, (int) $user->type)) {
            return '';
        }
        $url = get_route($name, [], true);
        return is_string($url) ? $url : '';
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
     * En el registro de acciones del panel (EventsLog): quién y cuándo los pone el mapper.
     *
     * @param string $message
     * @return void
     */
    private static function logAction(string $message): void
    {
        if (LogsRoutes::ENABLE) {
            LogsMapper::addLog(LogsMapper::MSG_GENERIC, ['%message%' => $message]);
        }
    }

    /**
     * Comparación ESTRICTA y sensible a mayúsculas: un destino que no esté en la lista no se toca.
     *
     * @param mixed $target
     * @return bool
     */
    public static function isCleanTarget(mixed $target): bool
    {
        return is_string($target) && in_array($target, self::CLEAN_TARGETS, true);
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

        if (!self::isCleanTarget($target)) {
            //La lista de opciones válidas es para quien depura: va al log, no a la pantalla.
            log_exception(new \InvalidArgumentException('Mantenimiento: target no válido; tiene que ser uno de ' . implode(', ', self::CLEAN_TARGETS) . '.'));
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Petición no válida: esa opción no existe.')], 400);
        }

        $target = (string) $target;
        $before = 0;
        $after = 0;
        $message = '';
        $extra = [];

        if ($target === 'statics-stamp') {

            $before = (string) static_files_cache_stamp();
            static_files_cache_stamp(true);
            $after = (string) static_files_cache_stamp();
            //El coste va en el acuse: quien lo pulsa tiene que verlo, no deducirlo.
            $message = __(self::LANG_GROUP, 'Hecho. Todos los visitantes volverán a descargar los estilos y los scripts.');

        } elseif ($target === 'server-delegated-links') {

            //En bytes no se mide: un enlace no ocupa, así que dar bytes aquí sería una cifra que no
            //puede cambiar. La unidad de este destino es el NÚMERO de enlaces.
            $before = ServerDelegatedLinks::scan()['total'];
            $result = ServerDelegatedLinks::deleteAll();
            $after = ServerDelegatedLinks::scan()['total'];
            $extra = $result;
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

        } else {

            $directory = $target === 'webp-cache'
                ? basepath(ServerStatics::WEBP_CACHE_DIRECTORY)
                : basepath('app/cache/Publications');

            //Sin permiso de escritura (la creó otro usuario con 0755) NO se intenta: delete() haría un
            //unlink que falla con un aviso, y aquí un aviso aborta la operación entera.
            if (is_dir($directory) && !is_writable($directory)) {
                //La ruta del servidor es para quien depura: va al log, no a la pantalla.
                log_exception(new \RuntimeException("Mantenimiento: sin permiso de escritura sobre {$directory}; la creó otro usuario del sistema."));
                return $response->withJson(['error' => $target === 'webp-cache'
                    ? __(self::LANG_GROUP, 'No se pudieron borrar las imágenes optimizadas: el sistema no tiene permiso sobre su carpeta.')
                    : __(self::LANG_GROUP, 'No se pudieron borrar los listados guardados: el sistema no tiene permiso sobre su carpeta.'),
                ], 409);
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
            $message = $target === 'webp-cache'
                ? __(self::LANG_GROUP, 'Imágenes optimizadas borradas. Se vuelven a crear al pedirse.')
                : __(self::LANG_GROUP, 'Listados guardados borrados. Se vuelven a crear al pedirse.');

        }

        //`freed` es de BYTES: solo tiene sentido en los dos destinos que son carpetas.
        $freed = in_array($target, ['webp-cache', 'publications-cache'], true) && is_int($before) && is_int($after)
            ? max(0, $before - $after)
            : 0;

        self::logAction($target === 'server-delegated-links'
            ? sprintf(
                __(self::LANG_GROUP, '%s borró «%s» desde mantenimiento: %d enlace(s) borrado(s); %d no se pudieron borrar por falta de permiso.'),
                self::currentUsername(),
                $target,
                count((array) ($extra['deleted'] ?? [])),
                (int) ($extra['failed'] ?? 0)
            )
            : sprintf(
                __(self::LANG_GROUP, '%s borró «%s» desde mantenimiento: %d byte(s) liberado(s).'),
                self::currentUsername(),
                $target,
                $freed
            ));

        //`status` recalculado, y es obligatorio: sin él la tabla sigue diciendo la cifra vieja al lado
        //de un mensaje que dice que se borró.
        return $response->withJson(array_merge($extra, [
            'success' => true,
            'target' => $target,
            'message' => $message,
            'before' => $before,
            'after' => $after,
            'freed' => $freed,
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
            'statics/core/css/app_config/system-status.css',
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
            //Sin breadcrumb: las pantallas de configuración no lo llevan, y ésta es una de ellas.
            //Vuelve cuando las configuraciones tengan una jerarquía real que mostrar (PO, 2026-09-25).
            'currentRole' => $usuario !== null ? (int) $usuario->type : null,
        ]);
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

        self::logAction(sprintf(
            __(self::LANG_GROUP, '%s %s el modo mantenimiento del sitio.'),
            self::currentUsername(),
            $enabled ? __(self::LANG_GROUP, 'encendió') : __(self::LANG_GROUP, 'apagó')
        ));

        return $response->withJson([
            'success' => true,
            'message' => $enabled
                ? __(self::LANG_GROUP, 'El sitio está en mantenimiento.')
                : __(self::LANG_GROUP, 'El sitio vuelve a estar disponible.'),
            'enabled' => $enabled,
        ]);
    }

    /**
     * La pantalla del registro de correos: qué se mandó, a dónde y si llegó.
     *
     * **Solo el usuario principal**, y el motivo no es la costumbre: la tabla guarda los
     * DESTINATARIOS de cada correo, que son datos personales de quien no pidió salir en ninguna
     * lista. Lo mismo que el registro de actividad.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function mailLog(Request $request, Response $response)
    {
        set_custom_assets([
            SystemStatusRoutes::staticRoute('js/mail-log.js'),
        ], 'js');
        set_custom_assets([
            'statics/core/css/app_config/system-status.css',
        ], 'css');

        $title = __(self::LANG_GROUP, 'Registro de correos');
        set_title($title);

        $this->helpController->render('panel/layout/header');
        $this->render('mail-log', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
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
        ]);
        $this->helpController->render('panel/layout/footer');

        return $response;
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
            MailLogMapper::RESULT_OUTBOX => ['orange', __(self::LANG_GROUP, 'En el buzón')],
            MailLogMapper::RESULT_FAILED => ['red', __(self::LANG_GROUP, 'No llegó')],
        ];
        $escapar = fn($valor): string => htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
        $verCuerpo = self::canSeeMailBody();
        $rotuloVer = __(self::LANG_GROUP, 'Ver cuerpo');

        //El cuerpo NO entra aquí, por construcción: solo si la fila lo tiene, como booleano. Y sin la
        //columna en la tabla ni eso, que el SELECT fallaría.
        $tieneCuerpo = MailLogMapper::bodyColumnExists() ? "({$tabla}.body IS NOT NULL) AS hasBody" : '0 AS hasBody';

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
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
                $columnas[] = '<span class="ui ' . $escapar($color) . ' label">' . $escapar($rotulo) . '</span>'
                    . ($motivo !== '' ? '<small class="mail-log-reason">' . $escapar($motivo) . '</small>' : '');
                if ($verCuerpo) {
                    $id = isset($e->id) ? (int) $e->id : 0;
                    $columnas[] = $id > 0 && !empty($e->hasBody)
                        ? '<button type="button" class="ui mini button" data-mail-log-body-url="'
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
            self::logAction(sprintf(__(self::LANG_GROUP, '%s pidió el cuerpo del correo #%d del registro, y no se pudo descifrar.'), self::currentUsername(), (int) $id));
            return $response->withJson(['status' => 'ilegible', 'body' => null, 'message' => __(self::LANG_GROUP, 'No se pudo descifrar el cuerpo: se guardó con otra clave o está dañado.')]);
        }
        self::logAction(sprintf(__(self::LANG_GROUP, '%s vio el cuerpo del correo #%d del registro.'), self::currentUsername(), (int) $id));
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
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
