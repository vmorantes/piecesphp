<?php

/**
 * SystemStatusRoutes.php
 */

namespace PiecesPHP\SystemStatus;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\InvalidRoutes;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\TaskRunMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SystemStatusRoutes - Avisos del sistema y mantenimiento: rutas, estáticos y los avisos que trae el núcleo.
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SystemStatusRoutes
{
    const ENABLE = true;

    /**
     * @var bool
     */
    private static $coreAlertsRegistered = false;

    /**
     * @var bool
     */
    private static $coreCleanTargetsRegistered = false;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        //Las opciones que guarda (ADR 0055); fuera del if, porque las filas se leen aunque el módulo esté apagado.
        ConfigOptions::declare(MaintenanceMode::ENABLED_CONFIG, MaintenanceMode::class, ConfigOptions::SHAPE_BOOL);
        ConfigOptions::declare(MaintenanceMode::ALLOWED_ROLES_CONFIG, MaintenanceMode::class, ConfigOptions::SHAPE_JSON);
        ConfigOptions::declare(MaintenanceMode::RETRY_AFTER_CONFIG, MaintenanceMode::class, ConfigOptions::SHAPE_INTEGER);
        ConfigOptions::declare(SystemAlertRegistry::HIDDEN_CONFIG, SystemAlertRegistry::class, ConfigOptions::SHAPE_JSON);
        if (self::ENABLE) {
            //Antes de registrar los avisos: sus rótulos se traducen al registrarse.
            SystemStatusLang::injectLang();
            self::registerCoreAlerts();
            self::registerCoreCleanTargets();
            $groupAdministration = SystemStatusController::routes($groupAdministration);
            SystemStatusController::registerLogTab();
            self::staticResolver($groupAdministration);
        }
        return $groupAdministration;
    }

    /**
     * Las limpiezas del núcleo de «Estado y cachés». Un módulo con caché propia registra la suya igual, desde su Routes
     * (Publications, los listados guardados). `position` ordena los botones y la de `withRow()`, las filas.
     *
     * @return void
     */
    public static function registerCoreCleanTargets(): void
    {
        if (self::$coreCleanTargetsRegistered) {
            return;
        }
        self::$coreCleanTargetsRegistered = true;
        $lang = SystemStatusController::LANG_GROUP;

        CleanTargetRegistry::register((new CleanTarget(
            'statics-stamp',
            fn (): string => __($lang, 'Forzar que todos descarguen de nuevo los archivos estáticos'),
            fn (): string => __($lang, '¿Seguro? Todos los visitantes volverán a descargar los estilos, los scripts y las imágenes de las páginas.'),
            [SystemStatusController::class, 'cleanStaticsStamp'],
            10
        ))->withRow(
            40,
            fn (): string => __($lang, 'Versión de los archivos estáticos'),
            CleanTarget::UNIT_STAMP,
            ['staticsStamp'],
            fn (): array => ['staticsStamp' => (string) static_files_cache_stamp()],
            fn (): string => __($lang, 'Estilos, scripts e imágenes de las páginas (no las imágenes que cargan las hojas de estilo, las que pone el JavaScript ni las del correo)'),
            'sync'
        ));

        CleanTargetRegistry::register((new CleanTarget(
            'webp-cache',
            fn (): string => __($lang, 'Borrar imágenes optimizadas'),
            fn (): string => __($lang, '¿Borrar las imágenes optimizadas? Se vuelven a crear la primera vez que se pida cada una.'),
            fn (): array => SystemStatusController::cleanCacheDirectory(
                basepath(ServerStatics::WEBP_CACHE_DIRECTORY),
                __($lang, 'No se pudieron borrar las imágenes optimizadas: el sistema no tiene permiso sobre su carpeta.'),
                __($lang, 'Imágenes optimizadas borradas. Se vuelven a crear al pedirse.')
            ),
            20
        ))->withRow(
            20,
            fn (): string => __($lang, 'Imágenes optimizadas'),
            CleanTarget::UNIT_BYTES,
            ['webpCacheBytes'],
            fn (): array => ['webpCacheBytes' => ServerDelegatedLinks::directorySize(basepath(ServerStatics::WEBP_CACHE_DIRECTORY))],
            icon: 'images'
        ));

        CleanTargetRegistry::register((new CleanTarget(
            'server-delegated-links',
            fn (): string => __($lang, 'Borrar todos los accesos directos'),
            fn (): string => __($lang, '¿Borrar todos los accesos directos, no solo los rotos? Se vuelven a crear al pedirse cada archivo.'),
            [SystemStatusController::class, 'cleanServerDelegatedLinks'],
            40
        ))->withRow(
            10,
            fn (): string => __($lang, 'Todos los accesos directos'),
            CleanTarget::UNIT_NUMBER,
            //Los rotos tienen su fila fija en la vista (maintenance.php), con su ruta y su respuesta.
            ['linksTotal'],
            [SystemStatusController::class, 'linksStatus'],
            icon: 'linkify'
        ));
    }

    /**
     * Los avisos del núcleo. Un módulo registra los suyos igual, desde su propio Routes.
     *
     * @return void
     */
    public static function registerCoreAlerts(): void
    {
        if (self::$coreAlertsRegistered) {
            return;
        }
        self::$coreAlertsRegistered = true;

        //La marca vieja (casilla de «Seguridad e IA») se sigue respetando, y «Mostrar» la desmarca.
        SystemAlertRegistry::register(
            new SystemAlert(
                'app-key-placeholder',
                SystemAlert::SEVERITY_DANGER,
                fn() => __(AdminPanelController::ADMIN_LANG_GROUP, 'La app_key es la de relleno: las sesiones y los tokens se firman con una clave conocida. Genere una con bin/cli generate-app-key, que la guarda en secure-keys/app-key.json.'),
                fn() => Config::app_key_is_placeholder(),
                true,
                [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL],
                true,
                'configurations-system-security',
                __(SystemStatusController::LANG_GROUP, 'Seguridad')
            ),
            fn() => get_config('hide_app_key_warning') === true,
            function (): void {
                SettingsModel::setConfigValue('hide_app_key_warning', false);
                set_config('hide_app_key_warning', false);
            }
        );

        //El llavero existe y este proceso no lo lee (permisos): firmaría y cifraría con otra clave que la de verdad.
        SystemAlertRegistry::register(new SystemAlert(
            'app-keyring-unreadable',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => __(SystemStatusController::LANG_GROUP, 'El llavero secure-keys/app-key.json existe pero el servidor no lo puede leer: se está usando otra clave. Revise sus permisos (lo tienen que poder leer el servidor web y la terminal).'),
            fn(): bool => Config::app_keyring_unreadable(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //La clave en config.php, que se versiona (ADR 0061 §5): se lee de respaldo, y esto pide moverla al llavero.
        SystemAlertRegistry::register(new SystemAlert(
            'app-key-in-config',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => __(SystemStatusController::LANG_GROUP, 'La app_key está en config.php, que se versiona: un clon que la suba a su repositorio la publica. Muévala con bin/cli store-app-key (no la cambia) y quítela de config.php.'),
            fn(): bool => AppKeyring::get()->source() === AppKeyring::SOURCE_CONFIG && !Config::app_key_is_placeholder(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //La carpeta de extensiones del clon cambió de nombre: la vieja se sigue cargando, y esto pide moverla.
        SystemAlertRegistry::register(new SystemAlert(
            'legacy-extensions-folder',
            SystemAlert::SEVERITY_WARNING,
            fn() => __(SystemStatusController::LANG_GROUP, 'La carpeta app/config/final-configurations-includes cambió de nombre: muévela a app/config/extensions. Mientras tanto se sigue cargando.'),
            fn() => is_dir(basepath('app/config/final-configurations-includes')),
            false,
            [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL],
            true
        ));

        //El respaldo que se dejó de hacer. Mira el nombre del archivo más reciente, no su fecha
        //en disco, que cambia al copiar la carpeta (ADR 0038 §2).
        SystemAlertRegistry::register(new SystemAlert(
            'backup-overdue',
            SystemAlert::SEVERITY_WARNING,
            fn() => __(SystemStatusController::LANG_GROUP, 'No hay un respaldo reciente de la base de datos: el último es más viejo que dos veces el intervalo configurado, o no hay ninguno. Revise que las tareas programadas del sistema se estén ejecutando.'),
            fn() => self::backupOverdue(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            'configurations-system-backups',
            __(SystemStatusController::LANG_GROUP, 'Respaldos')
        ));

        //Recorre un árbol: no va como aviso flotante, solo se evalúa en la página de avisos.
        SystemAlertRegistry::register(new SystemAlert(
            'server-delegated-broken-links',
            SystemAlert::SEVERITY_WARNING,
            fn() => sprintf(__(SystemStatusController::LANG_GROUP, 'Hay %d acceso(s) directo(s) roto(s).'), count(ServerDelegatedLinks::scan()['broken'])),
            fn() => count(ServerDelegatedLinks::scan()['broken']) > 0,
            false,
            [UsersModel::TYPE_USER_ROOT],
            false,
            'system-status-maintenance',
            __(SystemStatusController::LANG_GROUP, 'Estado y cachés')
        ));

        //El correo retenido: informativo en local, aviso fuera. Desde el 2026-10-03 lo decide `MailDelivery`
        //y no el antiguo «modo de pruebas» (ADR 0043 §1); el texto ya no nombra un modo que no decide.
        SystemAlertRegistry::register(new SystemAlert(
            'mail-test-mode',
            is_local() ? SystemAlert::SEVERITY_INFO : SystemAlert::SEVERITY_WARNING,
            function (): string {
                $mailConfig = new MailConfig();
                $host = $mailConfig->testHost();
                $port = $mailConfig->testPort();
                return sprintf(
                    __(SystemStatusController::LANG_GROUP, 'El correo está retenido: todo va a %s:%d y, si ahí no hay nada escuchando, al buzón en disco. Nada sale al exterior.'),
                    is_string($host) ? $host : '',
                    is_int($port) ? $port : 0
                );
            },
            fn() => (new MailConfig())->testModeActive(),
            false,
            //Solo el principal: la configuración del correo es suya, y el texto lleva el host del sumidero.
            [UsersModel::TYPE_USER_ROOT],
            false,
            'configurations-integrations-mail',
            __(SystemStatusController::LANG_GROUP, 'Correo')
        ));

        //Los tres desajustes del correo (ADR 0043 §2). NINGUNO bloquea: las tres situaciones son
        //legítimas en algún caso; lo que no es legítimo es que no se vean.
        SystemAlertRegistry::register(new SystemAlert(
            'mail-sin-declarar',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => __(SystemStatusController::LANG_GROUP, 'Esta instalación no declara su entorno, así que el correo NO se envía: se retiene. Cree «app/config/environment.php» con «local» o «production», o declare la entrega en la pantalla de Correo.'),
            fn(): bool => !AppEnvironment::isConfigured(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            false,
            'configurations-integrations-mail',
            __(SystemStatusController::LANG_GROUP, 'Correo')
        ));

        SystemAlertRegistry::register(new SystemAlert(
            'mail-retenido-en-produccion',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => __(SystemStatusController::LANG_GROUP, 'Esta instalación es de producción y el correo está RETENIDO: nadie recibe nada, ni la recuperación de contraseña ni los códigos de acceso. Ponga la entrega en «Real» en la pantalla de Correo.'),
            fn(): bool => AppEnvironment::isConfigured()
                && AppEnvironment::get() === AppEnvironment::PRODUCTION
                && MailDelivery::goesToSink(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            false,
            'configurations-integrations-mail',
            __(SystemStatusController::LANG_GROUP, 'Correo')
        ));

        //Mira el valor EFECTIVO, no solo el entorno (ADR 0045 §4): antes del 2026-10-03, con el antiguo
        //modo en «off» y máquina local, el correo salía de verdad y no avisaba nada.
        SystemAlertRegistry::register(new SystemAlert(
            'mail-real-en-local',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => __(SystemStatusController::LANG_GROUP, 'Esta máquina es local y el correo SALE DE VERDAD por el SMTP configurado: un correo de prueba puede llegarle a una persona real. Ponga la entrega en «Retenido» en la pantalla de Correo.'),
            fn(): bool => AppEnvironment::isConfigured()
                && AppEnvironment::get() === AppEnvironment::LOCAL
                && !MailDelivery::goesToSink(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            false,
            'configurations-integrations-mail',
            __(SystemStatusController::LANG_GROUP, 'Correo')
        ));

        //Los dos van FLOTANTES, y cuestan una consulta por página: `nagsFor()` filtra por rol antes
        //de evaluar, y lo contado se guarda por petición (MailLogMapper::$recentMemo).
        SystemAlertRegistry::register(new SystemAlert(
            'mail-con-fallos',
            SystemAlert::SEVERITY_DANGER,
            function (): string {
                $cuentas = MailLogMapper::recentCounts();
                $ultimo = MailLogMapper::lastFailure();
                //El MOTIVO del último, porque «3 fallos» no dice qué arreglar y el motivo casi siempre sí.
                $motivo = $ultimo !== null && isset($ultimo->reason) && is_string($ultimo->reason) && $ultimo->reason !== ''
                    ? (string) $ultimo->reason
                    : __(SystemStatusController::LANG_GROUP, 'sin motivo anotado');
                return sprintf(
                    __(SystemStatusController::LANG_GROUP, '%d correo(s) NO llegaron a ningún sitio en las últimas %d horas. El último falló por: %s. Mire el registro de correos para ver cuáles, y compruebe el servidor de correo con «bin/cli mail-doctor».'),
                    $cuentas['failed'],
                    MailLogMapper::ALERT_WINDOW_HOURS,
                    mb_substr($motivo, 0, 200)
                );
            },
            fn(): bool => MailLogMapper::recentCounts()['failed'] >= MailLogMapper::ALERT_FAILED_THRESHOLD,
            //NO se puede ocultar: se apaga solo cuando la ventana pasa, y un fallo de correo que se
            //puede silenciar es otra vez un fallo que nadie ve.
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            'logs-admin-index',
            __(SystemStatusController::LANG_GROUP, 'Registro de correos'),
            ['tab' => SystemStatusController::MAIL_LOG_TAB]
        ));

        //Errores nuevos desde la última visita de root a Registros › Errores (ADR 0060 §4).
        \PiecesPHP\Logs\NewErrorsAlert::register();

        //Sin la tabla, `record()` calla: «no hay fallos» y «no hay datos» se leerían igual. El aviso
        //nombra el archivo que falta aplicar porque es lo único que salva a un clon que no lo aplicó.
        SystemAlertRegistry::register(new SystemAlert(
            'mail-log-sin-tabla',
            SystemAlert::SEVERITY_DANGER,
            fn(): string => sprintf(
                __(SystemStatusController::LANG_GROUP, 'Falta la tabla del registro de correos, así que NO se está anotando ningún envío: ni los que fallan. Aplique «%s» en la base de datos de esta instalación.'),
                MailLogMapper::UPDATE_FILE
            ),
            fn(): bool => !MailLogMapper::tableExists(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //La tabla está pero sin la columna del cuerpo: las filas se guardan, el contenido no, y lo que
        //no se guarda hoy no se recupera (ADR 0048). No es `mail-log-sin-tabla`: eso diría algo falso.
        SystemAlertRegistry::register(new SystemAlert(
            'mail-log-sin-cuerpo',
            SystemAlert::SEVERITY_WARNING,
            fn(): string => sprintf(
                __(SystemStatusController::LANG_GROUP, 'El registro de correos anota cada envío, pero NO guarda lo que decía: a su tabla le falta la columna del cuerpo. Aplique «%s» en la base de datos de esta instalación.'),
                MailLogMapper::BODY_UPDATE_FILE
            ),
            fn(): bool => MailLogMapper::tableExists() && !MailLogMapper::bodyColumnExists(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //Sin el libro, las tareas corren igual pero nada anota cuándo corrieron ni si fallaron (G10).
        SystemAlertRegistry::register(new SystemAlert(
            'task-runs-missing',
            SystemAlert::SEVERITY_WARNING,
            fn(): string => sprintf(
                __(SystemStatusController::LANG_GROUP, 'Falta la tabla del libro de tareas programadas: las tareas se ejecutan, pero no se anota cuándo ni si fallaron. Aplique «%s» en la base de datos de esta instalación.'),
                TaskRunMapper::UPDATE_FILE
            ),
            fn(): bool => !TaskRunMapper::tableExists(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //Rutas descartadas por su patrón (P57): fuera de local no tumban la instalación, pero tampoco existen.
        SystemAlertRegistry::register(new SystemAlert(
            'invalid-route-pattern',
            SystemAlert::SEVERITY_DANGER,
            function (): string {
                $rutas = InvalidRoutes::all();
                $nombres = array_map(fn(array $r): string => (string) $r['name'], $rutas);
                $muestra = array_slice($nombres, 0, 5);
                $resto = count($nombres) - count($muestra);
                $lista = implode(', ', $muestra) . ($resto > 0 ? ' y ' . $resto . ' más' : '');
                return sprintf(__(SystemStatusController::LANG_GROUP, 'Se descartaron %d ruta(s) por tener un patrón que no se puede analizar: %s. Están en el log; corrija su patrón.'), count($rutas), $lista);
            },
            fn() => count(InvalidRoutes::all()) > 0,
            false,
            [UsersModel::TYPE_USER_ROOT],
            true
        ));

        //Sin environment.php la instalación es producción aunque se sirva desde localhost (P58).
        SystemAlertRegistry::register(new SystemAlert(
            'environment-not-configured',
            SystemAlert::SEVERITY_INFO,
            fn() => __(SystemStatusController::LANG_GROUP, 'El entorno no está configurado: la instalación funciona como producción. Crea src/app/config/environment.php desde environment.example.php.'),
            fn() => !AppEnvironment::isConfigured(),
            false,
            [UsersModel::TYPE_USER_ROOT],
            false
        ));

        //El código no trae llaves de MapBox: sin la del entorno en la base, los mapas del panel no cargan.
        SystemAlertRegistry::register(new SystemAlert(
            'mapbox-key-missing',
            SystemAlert::SEVERITY_WARNING,
            fn() => __(SystemStatusController::LANG_GROUP, 'No hay llave de MapBox para este entorno: los mapas del panel no cargan. Configúrela en Configuración › Sistema › Llaves de Mapas.'),
            fn() => self::mapboxKeyMissing(GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS), is_local()),
            true,
            [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL],
            true,
            'helpers-system-generic-content-admin-forms-mapbox-keys',
            __(SystemStatusController::LANG_GROUP, 'Llaves de Mapas')
        ));
    }

    /**
     * Si falta la llave de MapBox del entorno: `keyLocal` en local y `keyDomain` fuera. Puro, para probarlo sin base.
     *
     * @param mixed $keys Lo guardado en CONTENT_MAPBOX_KEYS
     * @param bool $local
     * @return bool
     */
    public static function mapboxKeyMissing(mixed $keys, bool $local): bool
    {
        if (!is_array($keys)) {
            return true;
        }
        $key = $keys[$local ? 'keyLocal' : 'keyDomain'] ?? null;
        return !is_string($key) || trim($key) === '';
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::ENABLE);
    }

    /**
     * Si el respaldo de la base lleva demasiado sin hacerse: más de dos intervalos, o ninguno.
     *
     * Con la política apagada no hay nada que echar en falta. La edad sale del NOMBRE del
     * archivo más reciente, que no cambia al copiar la carpeta (ADR 0038 §2).
     *
     * @return bool
     */
    protected static function backupOverdue(): bool
    {
        $policy = BackupPolicy::current();
        if ($policy['enabled'] !== true) {
            return false;
        }
        $latest = BackupRotation::latestDate(BackupPolicy::dumpsDirectory());
        if ($latest === null) {
            return true;
        }
        $elapsedMinutes = (time() - $latest->getTimestamp()) / 60;
        return $elapsedMinutes > ($policy['interval_minutes'] * 2);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    protected static function staticResolver(RouteGroup $group)
    {
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };
        $group->register([
            new Route('system-status/statics/[{params:.*}]', $callableHandler, self::class),
        ]);
        return $group;
    }
}
