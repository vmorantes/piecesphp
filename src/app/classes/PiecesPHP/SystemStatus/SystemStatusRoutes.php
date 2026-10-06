<?php

/**
 * SystemStatusRoutes.php
 */

namespace PiecesPHP\SystemStatus;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\Routing\InvalidRoutes;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;

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
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            //Antes de registrar los avisos: sus rótulos se traducen al registrarse.
            SystemStatusLang::injectLang();
            self::registerCoreAlerts();
            $groupAdministration = SystemStatusController::routes($groupAdministration);
            self::staticResolver($groupAdministration);
        }
        return $groupAdministration;
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
                fn() => __(AdminPanelController::ADMIN_LANG_GROUP, 'La app_key es la de relleno: las sesiones y los tokens se firman con una clave conocida. Genere una con bin/cli generate-app-key y póngala en config.php.'),
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
            [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL],
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
            'system-status-mail-log',
            __(SystemStatusController::LANG_GROUP, 'Registro de correos')
        ));

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
