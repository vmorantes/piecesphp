<?php

use App\Controller\PublicAreaController;
use App\Model\AppConfigModel;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\DependenciesInjector;
use PiecesPHP\Core\Routing\InvocationStrategy;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\ResponseRouteFactory;
use PiecesPHP\Core\Routing\Router;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\TerminalData;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Spatie\Url\Url as URLManager;
use SystemApprovals\SystemApprovalsMiddleware;
use SystemApprovals\SystemApprovalsRoutes;
use Terminal\Controllers\TerminalController;

/**
 * --------------------------------------------------------------------------
 * Inicialización del Núcleo (Core Bootstrap)
 * --------------------------------------------------------------------------
 * Carga el archivo principal de inicialización que prepara el entorno
 * de la aplicación (constantes, helpers, autoloaders, etc.).
 */
require __DIR__ . '/app/core/bootstrap.php';

/**
 * --------------------------------------------------------------------------
 * Registro de Assets Globales
 * --------------------------------------------------------------------------
 * Carga las definiciones de recursos estáticos globales (hojas de estilo y scripts).
 */
require_once basepath("app/config/assets.php");

/**
 * --------------------------------------------------------------------------
 * Inyección de Dependencias y Rutas
 * --------------------------------------------------------------------------
 * Define los contenedores base requeridos por Slim Framework.
 */
require_once basepath("app/config/containers.php");

// Asignación de la instancia del inyector de dependencias (DI) al sistema
set_config(
    'slim_container',
    new DependenciesInjector($container_configurations)
);

/**
 * --------------------------------------------------------------------------
 * Assets del Sistema de Sesión
 * --------------------------------------------------------------------------
 * Añade los scripts de control de sesión de usuario en el frontend si
 * el parámetro 'control_access_login' está habilitado.
 */
if (get_config('control_access_login') === true) {
    add_global_requireds_assets([
        base_url('statics/core/js/user-system/PiecesPHPSystemUserHelper.js'),
        base_url('statics/core/js/user-system/main_system_user.js'),
        base_url('statics/core/js/user-system/PiecesPHPGenericHandlerSession.js'),
    ], 'js');
}

/**
 * --------------------------------------------------------------------------
 * Configuración de la Aplicación por Defecto
 * --------------------------------------------------------------------------
 * Inicializa los parámetros visuales (favicons, logos) y valores por
 * defecto como correos y títulos de la aplicación.
 */
if (APP_CONFIGURATION_MODULE) {

    $default_configurations_values = [
        'favicon' => 'statics/images/favicon.png',
        'favicon-back' => 'statics/images/favicon-back.png',
        'logo' => 'statics/images/logo.png',
        'backgrounds' => [
            'statics/login-and-recovery/images/login/bg1.jpg',
            'statics/login-and-recovery/images/login/bg2.jpg',
            'statics/login-and-recovery/images/login/bg3.jpg',
            'statics/login-and-recovery/images/login/bg4.jpg',
            'statics/login-and-recovery/images/login/bg5.jpg',
        ],
        'backgoundProblems' => 'statics/login-and-recovery/images/login/problems-background.jpg',
        'partners' => 'statics/images/partners.png',
        'partnersVertical' => 'statics/images/partners-vertical.png',
        'open_graph_image' => 'statics/images/open_graph.jpg',
    ];

    $default_configurations_values['title_app'] = get_config('title_app');
    $default_configurations_values['mail'] = get_config('mail');
    $default_configurations_values['mail'] = $default_configurations_values['mail'] !== false ? $default_configurations_values['mail'] : [
        'auto_tls' => true,
        'protocol' => 'ssl',
        'host' => 'smtp.zoho.com',
        'auth' => true,
        'user' => 'correo@correo.com',
        'password' => '123456',
        'port' => 465,
    ];
    $default_configurations_values['owner'] = get_config('owner') !== false ? get_config('owner') : '';
    $default_configurations_values['description'] = get_config('description') !== false ? get_config('description') : 'Descripción de la página.';
    if (get_config('osTicketAPI') !== false) {
        $default_configurations_values['osTicketAPI'] = get_config('osTicketAPI');
    }
    if (get_config('osTicketAPIKey') !== false) {
        $default_configurations_values['osTicketAPIKey'] = get_config('osTicketAPIKey');
    }
    $default_configurations_values['meta_theme_color'] = get_config('meta_theme_color') !== false ? get_config('meta_theme_color') : '#13436C';
    $default_configurations_values['keywords'] = get_config('keywords') !== false ? get_config('keywords') : [
        'Website',
    ];
    $default_configurations_values['check_aud_on_auth'] = get_config('check_aud_on_auth') !== false ? get_config('check_aud_on_auth') : true;

    ksort($default_configurations_values);
    AppConfigModel::initializateConfigurations($default_configurations_values);
}

/**
 * --------------------------------------------------------------------------
 * Sobrescritura de Configuraciones desde la Base de Datos
 * --------------------------------------------------------------------------
 * Carga y aplica los ajustes de sistema guardados en tiempo real
 * y configura el módulo de correos y título principal.
 */
if (APP_CONFIGURATION_MODULE) {
    //Configuraciones de la aplicación tomadas desde la base de datos
    $configurations = AppConfigModel::getConfigurations();

    foreach ($configurations as $name => $value) {
        set_config($name, $value);
    }

    //Configuración de correo
    (function ($config) {
        if (!is_scalar($config)) {
            set_config('mail', (new MailConfig)->toArray());
        }
    })(get_config('mail'));

    //Configuración del título
    if (mb_strlen(get_title()) == 0) {
        set_title(AppConfigModel::getConfigValue('title_app'));
    }
}

/**
 * --------------------------------------------------------------------------
 * Configuración del Enrutador (Router)
 * --------------------------------------------------------------------------
 * Crea la instancia principal de la aplicación enrutadora y ajusta
 * el BasePath de la URIs enviadas al servidor.
 */
$app = Router::createRouter(get_config('slim_container'));
$routerBasePath = appbase();
$routerBasePath = trim($routerBasePath, '/');
$routerBasePath = "/" . $routerBasePath;
$routerBasePath = $routerBasePath == '/' ? '' : $routerBasePath;
$app->setBasePath($routerBasePath);

/**
 * --------------------------------------------------------------------------
 * MIDDLEWARE GLOBAL PRINCIPAL (PRE-ENRUTAMIENTO)
 * --------------------------------------------------------------------------
 * Intercepta toda petición HTTP. Contiene la lógica core de seguridad,
 * validación de sesiones, expiración por inactividad, control de idiomas (i18n)
 * y jerarquía de permisos de roles, previas al controlador de destino.
 */
$app->add(function (RequestRoute $request, RequestHandlerInterface $handler) {

    // Atrapa mensajes 'flash' (sesiones volátiles de una sola vez) y excepciones previas al enrutamiento
    $flashMessagesExceptionRender = get_flash_messages(BaseController::class);
    $flashMessagesExceptionRender = array_key_exists('render_exception', $flashMessagesExceptionRender) ? $flashMessagesExceptionRender['render_exception'] : null;

    // Plantilla de respuesta HTTP vacía para mutarla y retornar en caso de rechazos 403 o 404
    $emptyResponse = new ResponseRoute();
    // Obtiene el objeto ruta resuelto tras machear la URI solicitada
    $route = $request->getRoute();

    if (empty($route)) {
        throw new NotFoundException($request, $emptyResponse);
    }

    if ($flashMessagesExceptionRender !== null) {
        throw $flashMessagesExceptionRender;
    }

    // --- 1. Gestión de Internacionalización y Multi-idioma (i18n) ---
    // Identifica el lenguaje actual y establece las alternativas URL para dicho idioma.
    $isGenericView = $route->getName() == 'public-generic';
    $allowedLangs = Config::get_allowed_langs();
    $currentLang = Config::get_lang();
    $alternativesURL = [];
    $alternativesURLIncludeCurrent = [];
    $setAlternativesLangsURLs = function (bool $isExternalCall = false) use ($route, $allowedLangs, &$currentLang, $isGenericView, &$alternativesURL, &$alternativesURLIncludeCurrent) {

        $currentLang = Config::get_lang();
        $alternativesURL = [];
        $alternativesURLIncludeCurrent = [];

        foreach ($allowedLangs as $lang) {

            $isDiffOfCurrentLang = $currentLang != $lang;
            $alternativesURLIncludeCurrent[$lang] = get_lang_url($currentLang, $lang);
            if ($isDiffOfCurrentLang) {
                $alternativesURL[$lang] = get_lang_url($currentLang, $lang);
            }

            if ($isGenericView) {
                $arguments = $route->getArguments();

                if (array_key_exists('name', $arguments)) {

                    $nameGenericView = $arguments['name'];

                    if (is_string($nameGenericView)) {
                        $nameGenericViewLang = lang(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, $nameGenericView, $lang);
                        $alternativesURLIncludeCurrent[$lang] = str_replace($nameGenericView, $nameGenericViewLang, $alternativesURLIncludeCurrent[$lang]);
                        if ($isDiffOfCurrentLang) {
                            $alternativesURL[$lang] = str_replace($nameGenericView, $nameGenericViewLang, $alternativesURL[$lang]);
                        }
                    }
                }
            }
        }

        foreach ($alternativesURL as $lang => $url) {
            $urlNoParams = remove_url_params($url, []);
            $alternativesURL[$lang] = URLManager::fromString($urlNoParams)->withQueryParameter('i18n', $lang)->__toString();
        }

        foreach ($alternativesURLIncludeCurrent as $lang => $url) {
            $urlNoParams = remove_url_params($url, []);
            $alternativesURLIncludeCurrent[$lang] = URLManager::fromString($urlNoParams)->withQueryParameter('i18n', $lang)->__toString();
        }

        Config::set_config('alternatives_url', $alternativesURL);
        Config::set_config('alternatives_url_include_current', $alternativesURLIncludeCurrent);

    };
    ($setAlternativesLangsURLs)();
    Config::set_config('calculate_alternatives_langs_urls', function () use ($setAlternativesLangsURLs) {($setAlternativesLangsURLs)(true);});

    // --- 2. Preparación y Validación Inicial de Sesiones ---
    // Obtiene el token emitido y el nombre de la ruta solicitada
    $JWT = SessionToken::getJWTReceived();
    $name_route = $route->getName(); //Nombre de la ruta
    $methods = $route->getMethods(); //Métodos que acepta la ruta solicitada
    $arguments = $route->getArguments(); //Argumentos pasados en la url
    $user = null; // Variable principal que contendrá la entidad Usuario si este superó todas las validaciones

    //Control de acceso por login
    // Bandera maestra: Determina si el sistema exige autenticación de forma predeterminada para cualquier vista
    $control_access_login = get_config('control_access_login');

    //Verifica validez del JWT
    // Comprobación de P1 (Firma y Vigencia). ¿El token JWT provisto fue sellado por esta API y no ha caducado?
    $isActiveSession = SessionToken::isActiveSession($JWT);

    //En caso de ser desde la terminal conectarse al root

    // --- 3. Ejecución desde Consola ---
    // Fuerzo un login de ROOT si se está ejecutando en CLI (Terminal) sin sesión.
    if (!$isActiveSession && get_config('terminalData')->isTerminal()) {
        $rootUser = new UsersModel(1);
        if ($rootUser->id !== null) {
            $JWT = SessionToken::generateToken([
                'id' => $rootUser->id,
                'type' => $rootUser->type,
            ]);
            $_SERVER["HTTP_" . mb_strtoupper(SessionToken::TOKEN_NAME)] = $JWT;
            // Comprobación de P1 (Firma y Vigencia). ¿El token JWT provisto fue sellado por esta API y no ha caducado?
            $isActiveSession = SessionToken::isActiveSession($JWT);
        }
    }

    $getQualifiedRouteName = function ($classname, $simpleName) {

        $name = $simpleName;

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $prefix = uniqid($classname);
        if (property_exists($classname, 'baseRouteName')) {
            $reflectionClass = new ReflectionClass($classname);
            $reflectionProperty = $reflectionClass->getProperty('baseRouteName');
            $reflectionProperty->setAccessible(true);
            $prefix = $reflectionProperty->getValue();
        }

        $name = !is_null($name) ? $prefix . $name : $prefix;

        return $name;
    };

    //Rutas que se reautentican si está desconectado (para consultas de terceros)
    // Lista Blanca (Whitelist) de nombres de ruteo interno a las que intencionalmente
    // se les renovará un token caducado de forma automática. Ej: Webhooks, Endpoints de terceros.
    $ignoreExpiredForRoutesName = [
        ($getQualifiedRouteName)('NOMBRE_CALIFICADO_DE_LA_CLASE', 'NOMBRE_SIMPLE_DE_LA_RUTA'),
    ];

    // --- 4. Manejo de Expiración de Sesión ---
    // Generación de un log persistente en /app/logs si el token se vence,
    // con autolimpieza de historiales con más de 30 días de antigüedad.
    if (!$isActiveSession) {

        // Desencripta la info del token brincándose la validación de tiempo para rescatar qué usuario intentó operar.
        $expiredUserData = (object) BaseToken::getData($JWT, null, null, true);

        $expiredSessionsFolder = basepath('app/logs/expired-sessions');
        if (!file_exists($expiredSessionsFolder)) {
            @mkdir($expiredSessionsFolder, 0777, true);
        }

        $expiredSessionDataToJSON = [
            'token' => $JWT,
            'decodeToken' => BaseToken::decode($JWT, BaseToken::getSecretKey(), BaseToken::$encrypt, true),
            'data' => $expiredUserData,
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
        ];
        $addToExpired = false;
        if (is_object($expiredSessionDataToJSON['decodeToken'])) {
            $tokenCreatedDate = (new \DateTime());
            $tokenCreatedDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $tokenCreatedDate->setTimestamp($expiredSessionDataToJSON['decodeToken']->iat);
            $expiredSessionDataToJSON['tokenCreatedDate'] = $tokenCreatedDate->format('d-m-Y h:i:s A P');
            $addToExpired = true;
        }

        $oldFilesExpiredSessions = file_exists($expiredSessionsFolder) ? array_diff(scandir($expiredSessionsFolder), ['..', '.']) : [];
        array_map(function ($e) use ($expiredSessionsFolder) {

            $fullPath = $expiredSessionsFolder . \DIRECTORY_SEPARATOR  . $e;
            if ($e == '.keep' || mb_strpos($e, '.json') === false) {
                return;
            }

            $fullDateSegments = explode('_', str_replace('.json', '', $e));
            $dateSegments = explode('-', $fullDateSegments[0]);
            $timeSegments = explode('-', $fullDateSegments[1]);
            $amPm = $fullDateSegments[2];
            $day = $dateSegments[0];
            $month = $dateSegments[1];
            $year = $dateSegments[2];
            $hour = $timeSegments[0];
            $minute = $timeSegments[1];
            $second = $timeSegments[2];
            $milisecond = $timeSegments[3];
            $dateString = "{$day}-{$month}-{$year} {$hour}:{$minute}:{$second} {$amPm}";

            $date = \DateTime::createFromFormat('d-m-Y h:i:s A', $dateString);
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $date30Days = \DateTime::createFromFormat('d-m-Y h:i:s A', $dateString);
            $date30Days->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $date30Days->modify('+30 days');

            $now = new \DateTime();

            if ($date30Days < $now) {
                @unlink($fullPath);
            }
        }, $oldFilesExpiredSessions);

        if ($addToExpired) {
            @file_put_contents($expiredSessionsFolder . \DIRECTORY_SEPARATOR  . (new \DateTime)->format('d-m-Y_h-i-s-U.u_A') . '.json', json_encode($expiredSessionDataToJSON, \JSON_UNESCAPED_UNICODE));
        }

        // Renueva token si la ruta es una de excepción configurada (Ej. Cron, consultas 3ros, Webhooks)
        $ignoreExpired = in_array($route->getName(), $ignoreExpiredForRoutesName);

        if ($ignoreExpired && is_object($expiredUserData) && isset($expiredUserData->id)) {
            $expiredUser = new UsersModel((int) $expiredUserData->id);
            if ($expiredUser->id !== null) {
                $JWT = SessionToken::generateToken([
                    'id' => $expiredUser->id,
                    'type' => $expiredUser->type,
                ]);
                $_SERVER["HTTP_" . mb_strtoupper(SessionToken::TOKEN_NAME)] = $JWT;
                // Comprobación de P1 (Firma y Vigencia). ¿El token JWT provisto fue sellado por esta API y no ha caducado?
                $isActiveSession = SessionToken::isActiveSession($JWT);
            }
        }
    }

    //Verifica la validez del usuario activo si hay una sesion activa
    $organizationID = null;
    $organizationMapper = null;
    $user = null;
    $userIsOrganizationAdministrator = false;

    // --- 5. Extracción y Verificación Activa en Base de Datos ---
    // Si existe sesión validada internamente, se confirma extrae del
    // Token JWT y procede a comprobar en la Base de Datos que el
    // usuario no haya sido inhabilitado, borrado, etc.
    if ($isActiveSession) {

        $userJWTData = BaseToken::getData($JWT); //Información del usuario
        // --- Clase Anónima de Validación DTO ---
        // Envuelve la data del token para asegurar que posee las propiedades 'id' y 'type' con valores enteros fiables
        // antes de cruzar los datos contra MySQL.
        $validationUserObject = new class($userJWTData)
        {
            /**
             * El usuario entrante
             *
             * @var \stdClass
             */
            private $element = null;
            /**
             * El usuario
             *
             * @param \stdClass $user
             */
            public function __construct(\stdClass $user)
            {
                $this->setUser($user);
            }
            /**
             * El usuario
             *
             * @param \stdClass $user
             */
            public function setUser(\stdClass $user)
            {
                $this->element = $user;
            }
            /**
             * Trata de buscar al usuario en la base de datos
             *
             * @return \stdClass|null
             */
            /**
             * Consumo a Base de Datos: Comprueba si el ID desencriptado del token existe físicamente
             * en MySQL y arma su nombre completo. Esto blinda los casos donde un Token es válido
             * pero el usuario fue eliminado de la base de datos.
             *
             * @return \stdClass|null
             */
            public function getUserFromDatabase()
            {
                $user = null;

                if ($this->isValid()) {

                    $usersModel = UsersModel::model()->select()->where(['id' => $this->element->id]);
                    $usersModel->execute();
                    $user = $usersModel->result();
                    $user = count($user) > 0 ? $user[0] : null;

                    if ($user !== null) {
                        $fullname = [
                            trim(is_string($user->firstname) ? $user->firstname : ''),
                            trim(is_string($user->first_lastname) ? $user->first_lastname : ''),
                            trim(is_string($user->secondname) ? $user->secondname : ''),
                            trim(is_string($user->second_lastname) ? $user->second_lastname : ''),
                        ];
                        $user->fullName = trim(implode(' ', $fullname));
                    }
                }

                return $user;
            }
            /**
             * Valida que la variable de entrada sea un objeto con las
             * propiedades id y type válidas
             *
             * @return bool
             */
            public function isValid()
            {
                return $this->hasID() && $this->validType();
            }
            /**
             * Valida que sea un objeto
             *
             * @return bool
             */
            public function isObject()
            {
                return $this->element instanceof \stdClass;
            }
            /**
             * Valida que tenga un ID válido
             *
             * @return bool
             */
            public function hasID()
            {
                $e = $this->element;
                return $this->isObject() && isset($e->id) && $this->isInteger($e->id);
            }
            /**
             * Valida que tenga un type de tipo válido
             *
             * @return bool
             */
            public function hasType()
            {
                $e = $this->element;
                return $this->isObject() && isset($e->type) && $this->isInteger($e->type);
            }
            /**
             * Valida que el type exista
             *
             * @return bool
             */
            public function validType()
            {
                $e = $this->element;
                return $this->hasType() && in_array((int) $e->type, array_keys(UsersModel::TYPES_USERS));
            }
            /**
             * Valida que sea un entero válido
             *
             * @param string|int $value
             * @return bool
             */
            public function isInteger($value)
            {
                return (is_string($value) && ctype_digit((string) $value)) || is_int($value);
            }
        };

        $user = $validationUserObject->getUserFromDatabase();

        //Conectarse arbitrariamente como un usuario si se es root

        // --- 6. Impersonación (Login as) ---
        // Capacidad exclusiva del usuario ROOT para autenticarse temporalmente
        // en el contexto del sistema como si fuera otro usuario en tiempo de ejecución.
        // Intercepción de parámetros GET o Cookies para activar la Suplantación de Identidad (Impersonation)
        $anotherUserID = isset($_GET) && array_key_exists(CONNECT_AS_ANOTHER_USER_ID_GET_PARAM_NAME, $_GET) ? $_GET[CONNECT_AS_ANOTHER_USER_ID_GET_PARAM_NAME] : null;
        $anotherUserID = $anotherUserID !== null ? $anotherUserID : getCookie(CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME);
        set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, null);
        // El privilegio ROOT es el único autorizado a intercambiar su variable en memoria y operar del lado del servidor simulando ser otro usuario de menor rango.
        if ($user !== null && $user->type == UsersModel::TYPE_USER_ROOT) {
            set_config(ROOT_ORIGINAL_ID_CONFIG_NAME, $user->id);
            $anotherUserID = Validator::isInteger($anotherUserID) ? (int) $anotherUserID : null;
            if ($anotherUserID !== null && $anotherUserID > 0) {
                setCookieByConfig(CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME, $anotherUserID);
                $userJWTData->id = $anotherUserID;
                $validationUserObject->setUser($userJWTData);
                $user = $validationUserObject->getUserFromDatabase();
                if ($user === null) {
                    die('El id de usuario con el que intenta ingresar no existe');
                }
            } else {
                $anotherUserID = null;
                setCookieByConfig(CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME, null);
            }
            set_config(ROOT_ID_AS_CONNECT_CONFIG_NAME, $anotherUserID ?? $user->id);
        }

        if ($user !== null) {

            //Verificar status de la organización si aplica
            $organizationID = $user->organization;
            // Verifica que si un usuario pertenece a una organización, dicha entidad tenga al menos un estatus ACTIVO/PENDIENTE.
            $organizationMapper = $organizationID !== null  ?OrganizationMapper::objectToMapper(OrganizationMapper::getBy($organizationID, 'id')) : null;
            $allowedStatusesOrganization = [
                OrganizationMapper::ACTIVE,
                OrganizationMapper::PENDING_APPROVAL,
            ];
            if ($organizationMapper == null || in_array($organizationMapper->status, $allowedStatusesOrganization)) {
                set_config('current_user', $user);
                if ($organizationMapper !== null && $organizationMapper->administrator !== null) {
                    $userIsOrganizationAdministrator = $organizationMapper->administrator->id == $user->id;
                }
                Roles::setCurrentRole($user->type); //Se establece el rol
            } else {
                $isActiveSession = false;
                SessionToken::setMinimumDateCreated(new \DateTime());
            }

            //Verificar el status del usuario
            // Suspensión Forzosa: Si la base de datos marca `inactivo`, destrozamos forzosamente la sesión desestimando la firma JWT.
            if ($user->status == UsersModel::STATUS_USER_INACTIVE) {
                $isActiveSession = false;
                SessionToken::setMinimumDateCreated(new \DateTime());
            }

        } else {
            $isActiveSession = false;
            SessionToken::setMinimumDateCreated(new \DateTime());
        }
    }

    //Verifica si el control automático de acceso por login está activado

    // --- 8. Capa de Restricción de Acceso y Reglas de Login ---
    // Cierre de puertas al sistema si la petición requiere logueo y
    // el usuario no posee la sesión activa.
    if ($control_access_login) {

        $info_route = get_route_info($name_route); //Información de la ruta actual

        //Verifica si la ruta requiere estar logueado
        if ($info_route['require_login']) {

            //Acciones en caso de no estar logueado
            if (!$isActiveSession) {

                if ($name_route != 'users-form-login') {

                    if ($request->isXhr()) {

                        $url_login = remove_last_char_on('/', get_route('users-form-login'));
                        $referer = $request->getHeader('HTTP_REFERER');
                        $referer = isset($referer[0]) ? $referer[0] : '';
                        $referer = remove_last_char_on('/', $referer);

                        if ($referer != $url_login) {
                            $emptyResponse = $emptyResponse->withStatus(403);
                            return $emptyResponse->withJson([
                                'error' => 'RESTRICTED_AREA',
                                'message' => __('errors', 'RESTRICTED_AREA'),
                            ]);
                        }
                    } else {

                        if (!TerminalData::getInstance()->isTerminal()) {
                            set_flash_message('requested_uri', get_current_url());
                            return $emptyResponse->withRedirect(get_route('users-form-login'));
                        } else {
                            return $emptyResponse->write("Esta ruta necesita autenticación \r\n");
                        }
                    }
                }
            }
        }

        //Redirección al area administrativa desde formulario de logueo en caso de haber una session
        $login_redirect = get_config('admin_url');
        $relative_url = $login_redirect !== false ? (isset($login_redirect['relative']) ? $login_redirect['relative'] : true) : true;
        $relative_url = !is_bool($relative_url)  ?true : $relative_url;
        $admin_url = $login_redirect !== false ? (isset($login_redirect['url']) ? $login_redirect['url'] : '') : '';
        if ($relative_url) {
            $admin_url = baseurl($admin_url);
        }

        $admin_url = convert_lang_url($admin_url, get_config('default_lang'), get_config('app_lang'));

        //Verifica que esté logueado
        if ($isActiveSession) {

            if ($name_route == 'users-form-login') {
                return $emptyResponse->withRedirect($admin_url);
            }
        }

        //Control de permisos por roles
        // --- Inicia Pipeline de Autorización Dinámica de Roles (RBAC) ---
        $roles_control = get_config('roles');
        $active_roles_control = isset($roles_control['active']) ? $roles_control['active'] : false;
        $current_role = $user !== null  ?Roles::getCurrentRole() : null;
        $has_permissions = null;

        //Modificación de permisos del usuario en caso de tener una organización encargada

        // Expanden los permisos y roles temporales del usuario si es el
        // encargado administrador de la organización en curso.
        if ($userIsOrganizationAdministrator) {

            // Recuperamos rutas especiales reservadas para roles gerenciales (organización)
            $addPermissionsRoutes = OrganizationMapper::PERMISSIONS_ON_ADMINISTRATOR;

            // Extrae el mapa global de Roles en RAM y le inyecta permisos de administrador temporales
            // si el usuario es el encargado designado de dicha organización.
            $allRolesConfig = Roles::getRoles();

            foreach ($allRolesConfig as $roleConfigKey => $roleConfig) {
                if ($current_role['code'] == $roleConfig['code']) {
                    $roleConfig['allowed_routes'] = array_merge($roleConfig['allowed_routes'], $addPermissionsRoutes);
                    $allRolesConfig[$roleConfigKey] = $roleConfig;
                }
            }

            Roles::registerRoles($allRolesConfig, true);
        }

        //Acciones en caso de estar el sistema de aprobaciones activo
        if (SystemApprovalsRoutes::ENABLE) {
            $systemApprovalsReturnValue = SystemApprovalsMiddleware::handle($request, (new ResponseRouteFactory())->createResponse(), [], $handler);
            if ($systemApprovalsReturnValue instanceof ResponseRoute) {
                return $systemApprovalsReturnValue;
            }
        }

        //Verifica si está activada la comprobación automática de roles
        if ($current_role !== null && $active_roles_control === true) {

            // Cruza el Identificador de ruta solicitada (Ej: 'users-delete') vs el catálogo de permisos concedidos de su Rol respectivo.
            $has_permissions = Roles::hasPermissions($name_route, $current_role['name']);
        }

        //Acciones en caso de no tener permisos

        // Retorno Forzado de HTTP 403 (Permiso Denegado) si la comprobación
        // de privilegios del rol del usuario arroja negativo.
        if ($has_permissions !== null && !$has_permissions && $info_route['require_login']) {
            return (function ($request) {
                return throw403($request, []);
            })($request);
        }
    }

    // --- 9. Representación Final de Menú ---
    // Importa el mapa del Menú principal del Sistema filtrado sobre
    // los roles procesados anteriormente para esta instancia de usuario.
    $silentModeRolesSetted = Roles::getSilentMode();
    Roles::setSilentMode(true);
    require_once basepath("app/config/menu.php");
    Roles::setSilentMode($silentModeRolesSetted);

    /**
     * @var ResponseRoute $response
     */
    $response = $handler->handle($request);
    return $response;
});

/**
 * --------------------------------------------------------------------------
 * Ajustes Locales del Sistema
 * --------------------------------------------------------------------------
 */
set_config('upload_dir', basepath('statics/uploads'));
set_config('upload_dir_url', baseurl('statics/uploads'));
set_config('slim_app', $app);

/**
 * --------------------------------------------------------------------------
 * Carga e Inclusión de las Rutas Globales de la App
 * --------------------------------------------------------------------------
 */
require_once basepath("app/config/routes.php");

/**
 * --------------------------------------------------------------------------
 * Inclusión de Configuraciones Adicionales / Finales
 * --------------------------------------------------------------------------
 */
require_once basepath("app/config/final-configurations.php");

/**
 * --------------------------------------------------------------------------
 * Adición de Manejadores Base y Detección de Errores (ErrorMiddleware)
 * --------------------------------------------------------------------------
 */
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(is_local(), false, false);
set_config('errorMiddleware', $errorMiddleware);

$routeCollector = $app->getRouteCollector();
$routeCollector->setDefaultInvocationStrategy(new InvocationStrategy());

/**
 * --------------------------------------------------------------------------
 * Middleware Pre/Post de Inserción de Cabeceras Custom (Idioma esperado)
 * --------------------------------------------------------------------------
 */
$app->add(function (RequestRoute $request, RequestHandlerInterface $handler) {
    //Obtener el idioma deseado de las solicitudes
    // Inspecciona si el cliente demandó una respuesta en idioma estricto por Cabecera HTTP (Práctico para integraciones API/Móvil)
    $responseExpectedLang = $request->getHeaderLine('PCSPHP-Response-Expected-Language');
    if (!is_string($responseExpectedLang) || mb_strlen(trim($responseExpectedLang)) < 1) {
        $responseExpectedLang = null;
    }
    set_config('responseExpectedLang', $responseExpectedLang);
    //Definir el idioma basado en la solicitud
    if (is_string($responseExpectedLang) && mb_strlen($responseExpectedLang) > 1) {
        set_config('app_lang', $responseExpectedLang);
    }
    //Continuar
    $response = $handler->handle($request);
    return $response;
});

/**
 * --------------------------------------------------------------------------
 * Construcción Árbol de Rutas y Despacho de Evento (InitRoutes)
 * --------------------------------------------------------------------------
 */
RouteGroup::initRoutes(false);
set_config('AppRoutesInit', true);
BaseEventDispatcher::dispatch("AppRoutes", 'InitRoutes', null);

/**
 * --------------------------------------------------------------------------
 * Parche y Simulador de Peticiones para Terminal (CLI)
 * --------------------------------------------------------------------------
 * En caso de detectar entorno consola, permite resolver peticiones Web
 * mogueando variables nativas ($_SERVER, HTTP headers, URI)
 */
if (TerminalData::getInstance()->isTerminal()) {

    $terminalDataInstance = TerminalData::getInstance();
    $routeName = TerminalController::routeID($terminalDataInstance->route());
    $routeInformation = get_route_info($routeName, [], true);
    $_SERVER['REQUEST_URI'] = '';

    if ($routeInformation !== null) {

        $routeURLSegment = str_replace([
            'http://',
            'https://',
            'localhost',
        ], '', get_route($routeName));

        $container = $app->getDI();

        $basicServerVariables = $terminalDataInstance->basicServerVariables();

        $basicServerVariables['REQUEST_URI'] = $routeURLSegment;

        foreach ($basicServerVariables as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $container->add('environment', \PiecesPHP\Core\Routing\Slim3Compatibility\Http\Environment::mock($basicServerVariables));
    } else {
        echo "La ruta solicitada no existe\r\n";
        exit;
    }
}

/**
 * --------------------------------------------------------------------------
 * Rutinas Personalizadas de Exceptions / HTTP Status Callbacks
 * --------------------------------------------------------------------------
 * Manejo decorativo de pantallas 404 (No Encontrado), 403 (Acceso Denegado),
 * 405 (Método no permitido) o en caso general un 500 (Internal Server Error).
 */
$handle404 = function (RequestRoute $request, Throwable $exception, bool $displayErrorDetails) {
    if ($exception instanceof HttpNotFoundException) {
        return get_router()->getDI()->get('notFoundHandler')($exception);
    }
};
$handle403 = function (RequestRoute $request, Throwable $exception, bool $displayErrorDetails) {
    if ($exception instanceof HttpForbiddenException) {
        return get_router()->getDI()->get('forbiddenHandler')($exception);
    }
};
$customGlobalExceptionHandler = function (RequestRoute $request, Throwable $exception, bool $displayErrorDetails) {
    $originalException = $exception;
    if ($originalException instanceof HttpNotFoundException || $originalException instanceof NotFoundException) {
        return get_router()->getDI()->get('notFoundHandler')($originalException);
    } elseif ($originalException instanceof HttpMethodNotAllowedException) {
        $exception = new NotFoundException($request, new ResponseRoute(StatusCode::HTTP_NOT_FOUND));
        return get_router()->getDI()->get('notFoundHandler')($exception, [
            'requestMethod' => $request->getMethod(),
            'allowedMethods' => $originalException->getAllowedMethods(),
        ]);
    } elseif ($originalException instanceof HttpForbiddenException) {
        return get_router()->getDI()->get('forbiddenHandler')($originalException);
    } else {
        global_custom_exception_handler($originalException, 'RouterSetErrorHandler');
        $response = new ResponseRoute();
        return $response->withStatus(500);
    }
};
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, $handle404);
$errorMiddleware->setErrorHandler(HttpForbiddenException::class, $handle403);
$errorMiddleware->setErrorHandler(NotFoundException::class, $handle404);
$errorMiddleware->setErrorHandler([
    \ErrorException::class,
    \Error::class,
    \Exception::class,
    \TypeError::class,
    \Throwable::class,
    \BadFunctionCallException::class,
    \BadMethodCallException::class,
    \DomainException::class,
    \InvalidArgumentException::class,
    \LengthException::class,
    \LogicException::class,
    \OutOfBoundsException::class,
    \OutOfRangeException::class,
    \OverflowException::class,
    \RangeException::class,
    \RuntimeException::class,
    \UnderflowException::class,
    \UnexpectedValueException::class,
], $customGlobalExceptionHandler, true);

/**
 * --------------------------------------------------------------------------
 * INICIALIZACIÓN MÁQUINA DE APP
 * --------------------------------------------------------------------------
 * Dispara finalmente el proceso de vida de la aplicación basándose en
 * los Globales del Servidor Web (Apache/Nginx)
 */
$app->run(RequestRouteFactory::createFromGlobals());
