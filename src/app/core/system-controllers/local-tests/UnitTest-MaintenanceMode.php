<?php

//El modo mantenimiento. Solo usa `set_config()`, que es memoria del proceso: no escribe en la base ni lo deja encendido.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/PiecesPHP/SystemStatus/

use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/maintenance-mode', function ($args) {

    echoTerminal("\e[33m[TEST:MaintenanceMode] Los valores por omisión, las configuraciones inválidas y a quién detiene\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $modoPrevio = get_config(MaintenanceMode::ENABLED_CONFIG);
    $rolesPrevios = get_config(MaintenanceMode::ALLOWED_ROLES_CONFIG);

    try {

        //─── a · Por omisión, el sitio está en pie ──────────────────────────────────────────────────────
        echoTerminal('[a] Sin configuración, el modo está apagado y solo root pasaría');
        set_config(MaintenanceMode::ENABLED_CONFIG, null);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, null);
        $check(MaintenanceMode::isEnabled() === false, 'a1 isEnabled() es false sin configuración');
        $check(MaintenanceMode::allowedRoles() === [UsersModel::TYPE_USER_ROOT], 'a2 allowedRoles() es solo root', (string) json_encode(MaintenanceMode::allowedRoles()));
        $check(MaintenanceMode::blocks('users-list', null) === false, 'a3 con el modo apagado no se detiene nada, ni sin sesión');
        echoTerminal(' ');

        //─── b · El valor del modo ──────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Qué se acepta como «encendido» y qué no');
        foreach ([true, 1, '1', 'true'] as $valor) {
            set_config(MaintenanceMode::ENABLED_CONFIG, $valor);
            $check(MaintenanceMode::isEnabled() === true, 'b1 enciende con ' . var_export($valor, true));
        }
        foreach ([false, 0, '0', 'false'] as $valor) {
            set_config(MaintenanceMode::ENABLED_CONFIG, $valor);
            $check(MaintenanceMode::isEnabled() === false, 'b2 no enciende con ' . var_export($valor, true));
        }
        //LO QUE IMPORTA DE LOS INVÁLIDOS: un valor que no se entiende deja el sitio EN PIE. Encender el
        //mantenimiento por un error de configuración apaga el sitio entero, que es el daño caro.
        foreach (['si', 'MANTENIMIENTO', 2, -1, 1.5, [], ['1'], new stdClass()] as $valor) {
            set_config(MaintenanceMode::ENABLED_CONFIG, $valor);
            $check(MaintenanceMode::isEnabled() === false, 'b3 un valor inválido NO enciende: ' . str_replace("\n", '', var_export($valor, true)));
        }
        echoTerminal(' ');

        //─── c · La lista de roles ──────────────────────────────────────────────────────────────────────
        echoTerminal('[c] La lista de roles permitidos, y qué hace cada caso inválido');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);

        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]);
        $check(MaintenanceMode::allowedRoles() === [0, 1], 'c1 una lista buena se usa tal cual', (string) json_encode(MaintenanceMode::allowedRoles()));

        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, ['0', '1']);
        $check(MaintenanceMode::allowedRoles() === [0, 1], 'c2 los códigos en texto valen y salen como enteros');

        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [0, 0, 1]);
        $check(MaintenanceMode::allowedRoles() === [0, 1], 'c3 un código repetido no se devuelve dos veces');

        //La lista vacía significa «SOLO ROOT», no «no pasa nadie»: dejar a root fuera no gana nada.
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, []);
        $check(MaintenanceMode::allowedRolesAreValid([]) === true, 'c4 la lista vacía es VÁLIDA');
        $check(MaintenanceMode::allowedRoles() === [], 'c5 y se guarda tal cual, vacía');
        $check(MaintenanceMode::blocks('users-list', UsersModel::TYPE_USER_ROOT) === false, 'c6 con la lista vacía ROOT PASA IGUAL, por una ruta normal');
        $check(MaintenanceMode::blocks('users-list', UsersModel::TYPE_USER_ADMIN_GRAL) === true, 'c7 y no pasa nadie más');
        $check(MaintenanceMode::blocks('users-form-login', UsersModel::TYPE_USER_ADMIN_GRAL) === false, 'c8 pero el formulario de acceso sigue pasando para todos: es la vuelta');

        //Los inválidos se descartan ENTEROS: aceptar los buenos y tirar los malos daría un reparto que
        //nadie escribió. Y los pares van en lista, no en claves: `1.5` como clave la castea PHP y aborta.
        foreach ([
            ['root', 'un texto'],
            ['0', 'un código suelto sin lista'],
            [true, 'un booleano'],
            [1.5, 'un decimal'],
            [0, 'un entero suelto sin lista'],
        ] as [$valor, $queEs]) {
            set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, $valor);
            $check(MaintenanceMode::allowedRolesAreValid($valor) === false, 'c9 no es válido: ' . $queEs);
            $check(MaintenanceMode::allowedRoles() === [UsersModel::TYPE_USER_ROOT], 'c10 y cae al valor por omisión: ' . $queEs);
        }
        foreach ([[99], [0, 99], ['root'], [null], [[0]]] as $valor) {
            set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, $valor);
            $check(MaintenanceMode::allowedRolesAreValid($valor) === false, 'c11 una lista con algo que no es un código existente NO es válida: ' . (string) json_encode($valor));
            $check(MaintenanceMode::allowedRoles() === [UsersModel::TYPE_USER_ROOT], 'c12 y se descarta ENTERA, no a medias: ' . (string) json_encode($valor));
        }
        echoTerminal(' ');

        //─── d · A quién detiene ────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Con el modo encendido y solo root permitido');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT]);
        $check(MaintenanceMode::blocks('users-list', null) === true, 'd1 sin sesión se detiene');
        $check(MaintenanceMode::blocks('users-list', UsersModel::TYPE_USER_ROOT) === false, 'd2 root pasa');
        //ROOT PASA SIEMPRE, con la lista que sea: la garantía está en código, no en la configuración.
        foreach ([[], [UsersModel::TYPE_USER_GENERAL], 'corrupta', [99]] as $lista) {
            set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, $lista);
            $check(MaintenanceMode::blocks('users-list', UsersModel::TYPE_USER_ROOT) === false, 'd2bis root pasa con la lista ' . str_replace("\n", '', var_export($lista, true)));
        }
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT]);
        foreach ([
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
            UsersModel::TYPE_USER_COMUNICACIONES,
        ] as $rol) {
            $check(MaintenanceMode::blocks('users-list', $rol) === true, 'd3 el rol ' . $rol . ' se detiene');
        }
        echoTerminal(' ');

        //─── e · Lo que pasa SIEMPRE, y que sus nombres existan ─────────────────────────────────────────
        echoTerminal('[e] Las rutas de ALWAYS_ALLOWED pasan sin sesión, y son rutas de verdad');
        foreach (MaintenanceMode::ALWAYS_ALLOWED as $nombre) {
            $check(MaintenanceMode::blocks($nombre, null) === false, 'e1 pasa sin sesión: ' . $nombre);
        }
        //UN NOMBRE MAL ESCRITO EN ESA LISTA ES UNA PUERTA QUE NO SE ABRE (el sitio, muerto), y una ruta que desaparezca la
        //deja mintiendo. Se mira en las rutas del proceso: el inventario de files/dev/ no viaja a los clones.
        $nombres = array_map('strval', array_keys((array) get_routes()));
        $check(count($nombres) > 300, 'e2 las rutas registradas en este proceso se pudieron leer', count($nombres) . ' rutas');
        foreach (MaintenanceMode::ALWAYS_ALLOWED as $nombre) {
            $check(in_array($nombre, $nombres, true), 'e3 y existe como ruta registrada: ' . $nombre);
        }
        echoTerminal(' ');

        //─── f · La vista que promete servir existe ─────────────────────────────────────────────────────
        echoTerminal('[f] La vista del modo');
        $vista = basepath('app/view/' . MaintenanceMode::VIEW . '.php');
        $check(is_file($vista), 'f1 existe el archivo de MaintenanceMode::VIEW', MaintenanceMode::VIEW);
        //RETRY_AFTER no se comprueba aquí: es constante y la comparación siempre da true. Va en el HTTP.

        //─── g · Una ruta sin nombre se detiene ─────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[g] Lo que no se puede nombrar no se puede permitir');
        $check(MaintenanceMode::blocks(null, null) === true, 'g1 una ruta sin nombre y sin sesión se detiene');
        $check(MaintenanceMode::blocks(null, UsersModel::TYPE_USER_ROOT) === false, 'g2 pero un rol permitido sigue pasando por ella');

        //─── i · El Retry-After ─────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[i] Los segundos del Retry-After, y qué hace cada valor inválido');
        $retryPrevio = get_config(MaintenanceMode::RETRY_AFTER_CONFIG);
        set_config(MaintenanceMode::RETRY_AFTER_CONFIG, null);
        $check(MaintenanceMode::retryAfter() === MaintenanceMode::RETRY_AFTER, 'i1 sin configurar vale ' . MaintenanceMode::RETRY_AFTER);
        foreach ([1, 60, '900', MaintenanceMode::RETRY_AFTER_MAX] as $valor) {
            set_config(MaintenanceMode::RETRY_AFTER_CONFIG, $valor);
            $check(MaintenanceMode::retryAfter() === (int) $valor, 'i2 acepta ' . var_export($valor, true));
        }
        //CERO Y NEGATIVO NO: un «vuelve ya» es lo contrario de un mantenimiento. Y pasada la cota, un
        //buscador puede dejar de volver, que es el otro extremo del mismo daño.
        foreach ([0, -1, '0', '-5', 1.5, true, 'pronto', [], MaintenanceMode::RETRY_AFTER_MAX + 1] as $valor) {
            set_config(MaintenanceMode::RETRY_AFTER_CONFIG, $valor);
            $check(MaintenanceMode::retryAfterIsValid($valor) === false, 'i3 no es válido: ' . str_replace("\n", '', var_export($valor, true)));
            $check(MaintenanceMode::retryAfter() === MaintenanceMode::RETRY_AFTER, 'i4 y cae al de por omisión');
        }
        set_config(MaintenanceMode::RETRY_AFTER_CONFIG, $retryPrevio);

        //─── h · Los mandos de la pantalla ──────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[h] La pantalla del modo, que vive en las opciones de sistema y es solo de root');
        $vista = basepath('app/classes/PiecesPHP/SystemStatus/Views/site-maintenance.php');
        $vistaMandos = is_file($vista) ? (string) file_get_contents($vista) : '';
        $check($vistaMandos !== '', 'h1 la vista de la pantalla se puede leer');
        //Si alguien quita el JSON y pone un `allowed_roles[]`, un selector sin nada marcado deja de
        //poder decir «ningún rol»: la lista vacía dejaría de ser expresable.
        $check(mb_strpos($vistaMandos, 'site-maintenance-roles-json') !== false, 'h2 la lista sigue viajando como JSON');
        $check(mb_strpos($vistaMandos, 'UsersModel::getTypesUser()') !== false, 'h3 los roles del selector salen de los tipos que existen');
        $check(mb_strpos($vistaMandos, 'data-warn-off') !== false, 'h4 los textos del aviso viajan en atributos');
        $check(mb_strpos($vistaMandos, 'window.confirm') === false, 'h5 y no hay ningún confirm() del navegador');
        $jsPantalla = basepath('app/classes/PiecesPHP/SystemStatus/Statics/js/site-maintenance.js');
        $vistaJS = is_file($jsPantalla) ? (string) file_get_contents($jsPantalla) : '';
        $check($vistaJS !== '', 'h6 el JS de la pantalla se puede leer');
        //Si alguien saca el reenlace, `condition` se queda con el estado del arranque y el aviso
        //aparece —o no— según lo que hubiera al cargar, no según lo que se va a guardar.
        $check(mb_strpos($vistaJS, 'onChange') !== false && mb_strpos($vistaJS, "addEventListener('change'") !== false, 'h7 y se reenlaza cuando cambia algo');
        //LOS MANDOS YA NO ESTÁN EN «GENERALES»: dos sitios donde tocar lo mismo es peor que uno mal puesto.
        $pestana = basepath('app/classes/PiecesPHP/Settings/Views/panel/pages/app_configurations/inc/configuration-tabs/general.php');
        $vistaVieja = is_file($pestana) ? (string) file_get_contents($pestana) : '';
        $check($vistaVieja !== '' && mb_strpos($vistaVieja, 'maintenance') === false, 'h8 la pestaña de generales ya no tiene los mandos');
        $colores = basepath('app/classes/PiecesPHP/Settings/Statics/js/colors.js');
        $jsViejo = is_file($colores) ? (string) file_get_contents($colores) : '';
        $check($jsViejo !== '' && mb_strpos($jsViejo, 'maintenance') === false, 'h9 ni su JavaScript');
        //Si ALWAYS_ALLOWED nombrara la pantalla vieja, la vuelta iría a donde ya no están los mandos,
        //y la prueba de que los nombres existen no lo vería: los viejos siguen existiendo.
        $check(in_array('system-status-site-maintenance', MaintenanceMode::ALWAYS_ALLOWED, true), 'h10 ALWAYS_ALLOWED nombra la pantalla nueva');
        $check(in_array('system-status-site-maintenance-save', MaintenanceMode::ALWAYS_ALLOWED, true), 'h11 y su guardado');
        $check(!in_array('configurations-appearance-colors', MaintenanceMode::ALWAYS_ALLOWED, true), 'h12 y ya no nombra la pantalla vieja (hoy, la de colores)');
        $check(!in_array('configurations-appearance-colors-save', MaintenanceMode::ALWAYS_ALLOWED, true), 'h13 ni su guardado');
        $check(is_array(get_route_info('configurations-appearance-colors')) && is_array(get_route_info('configurations-appearance-colors-save')), 'h14 canario: esas dos rutas existen con ese nombre (si se renombran, h12 y h13 no mirarían nada)');

        //─── i · La acción de colores no puede apagar el sitio (P74) ────────────────────
        echoTerminal(' ');
        echoTerminal('[i] Las tres claves del modo no están entre lo que escribe la acción de colores');
        $controladorAjustes = \PiecesPHP\Settings\Controllers\SettingsController::class;
        foreach ([MaintenanceMode::ENABLED_CONFIG, MaintenanceMode::ALLOWED_ROLES_CONFIG, MaintenanceMode::RETRY_AFTER_CONFIG] as $clave) {
            $check(!in_array($clave, $controladorAjustes::COLOR_OPTIONS, true) && !in_array($clave, ['theme_font', 'theme_font_size', 'theme_radius'], true), "i1 «{$clave}» no es una opción de la pantalla Colores");
        }
        $fuenteAccion = (string) @file_get_contents(basepath('app/classes/PiecesPHP/Settings/Controllers/SettingsController.php'));
        $posAccion = mb_strpos($fuenteAccion, 'function saveColorsAction');
        $posValidacion = $posAccion !== false ? mb_strpos($fuenteAccion, '$expectedParameters->validate();', $posAccion) : false;
        $posEscritura = $posAccion !== false ? mb_strpos($fuenteAccion, 'SettingsModel::setConfigValue($name, $value)', $posAccion) : false;
        $check($posValidacion !== false, 'i2 la acción valida');
        //DISCRIMINANTE: validar DESPUÉS de escribir deja pasar la escritura y responde error igual. El orden es la guarda.
        $check($posValidacion !== false && $posEscritura !== false && $posValidacion < $posEscritura,
            'i3 y antes de escribir');
        $check(\PiecesPHP\Core\Roles::hasPermissions('configurations-appearance-colors-save', UsersModel::TYPE_USER_ROOT) && !\PiecesPHP\Core\Roles::hasPermissions('configurations-appearance-colors-save', UsersModel::TYPE_USER_GENERAL), 'i4 la abre el principal, no un usuario general');

        //─── j · Quien tenía la sesión abierta puede cerrarla (P299) ────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[j] Con el modo encendido, un tipo no permitido puede cerrar su sesión, y nada más');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]);
        $check(MaintenanceMode::blocks('user-sessions-logout', UsersModel::TYPE_USER_GENERAL) === false, 'j1 un usuario general (no permitido) llega al cierre de sesión');
        $check(is_array(get_route_info('admin')), 'j2 canario: la ruta del panel «admin» existe con ese nombre');
        $check(MaintenanceMode::blocks('admin', UsersModel::TYPE_USER_GENERAL) === true, 'j2 pero el panel le sigue detenido: la excepción es solo el cierre');
        $check(MaintenanceMode::blocks('users-login-request', UsersModel::TYPE_USER_GENERAL) === false, 'j3 llega al acceso');
        $check(MaintenanceMode::blocks('admin', UsersModel::TYPE_USER_ADMIN_GRAL) === false, 'j3 y el administrador general (permitido) vuelve a entrar al panel');
        $check(MaintenanceMode::blocks('users-login-request', null) === false, 'j4 sin sesión se llega al acceso');
        set_config(MaintenanceMode::ENABLED_CONFIG, $modoPrevio);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, $rolesPrevios);

        //─── k · A qué rol detiene, la pregunta que comparten blocks() y el acceso (P299) ──────────────
        echoTerminal(' ');
        echoTerminal('[k] stopsRole(): a quién detiene el modo, y blocks() dice lo mismo fuera de ALWAYS_ALLOWED');
        set_config(MaintenanceMode::ENABLED_CONFIG, false);
        $check(MaintenanceMode::stopsRole(UsersModel::TYPE_USER_GENERAL) === false && MaintenanceMode::stopsRole(null) === false, 'k1 apagado no detiene a nadie, ni sin rol');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]);
        $check(MaintenanceMode::stopsRole(UsersModel::TYPE_USER_ROOT) === false, 'k2 root no se detiene');
        $check(MaintenanceMode::stopsRole(UsersModel::TYPE_USER_ADMIN_GRAL) === false, 'k3 un tipo permitido no se detiene');
        $check(MaintenanceMode::stopsRole(UsersModel::TYPE_USER_GENERAL) === true, 'k4 un tipo no permitido se detiene');
        $check(MaintenanceMode::stopsRole(null) === true, 'k5 sin rol se detiene');
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, []);
        $check(MaintenanceMode::stopsRole(UsersModel::TYPE_USER_ROOT) === false, 'k6 con la lista vacía root sigue sin detenerse');
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]);
        //blocks() reutiliza stopsRole(): si dejaran de coincidir, el acceso y la puerta dirían cosas distintas.
        foreach ([true, false] as $encendido) {
            set_config(MaintenanceMode::ENABLED_CONFIG, $encendido);
            foreach ([null, UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL, UsersModel::TYPE_USER_GENERAL] as $rol) {
                $check(MaintenanceMode::blocks('admin', $rol) === MaintenanceMode::stopsRole($rol), 'k7 blocks() de una ruta normal = stopsRole(), encendido ' . var_export($encendido, true) . ', rol ' . var_export($rol, true));
                $check(MaintenanceMode::blocks('users-login-request', $rol) === false, 'k8 y el acceso pasa igual, encendido ' . var_export($encendido, true) . ', rol ' . var_export($rol, true));
            }
        }

        //─── l · Sin sesión, la pantalla que pide login va al acceso (P299) ──────────────────────────────
        echoTerminal(' ');
        echoTerminal('[l] sendsToLogin(): solo sin sesión, solo con require_login, solo a una pantalla');
        $infoPanel = get_route_info('admin');
        $check(is_array($infoPanel) && !empty($infoPanel['require_login']), 'l0 canario: «admin» pide login (si no, l2 no mira una ruta real)');
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]);
        //La misma composición que src/index.php §7bis: primero blocks(), y solo si detiene, sendsToLogin().
        $alAcceso = fn (?int $rol, bool $controlAcceso = true, bool $pideLogin = true, bool $pideDatos = false, bool $terminal = false): bool => MaintenanceMode::sendsToLogin(MaintenanceMode::blocks('admin', $rol), $rol, $controlAcceso, $pideLogin, $pideDatos, $terminal);
        set_config(MaintenanceMode::ENABLED_CONFIG, false);
        $check($alAcceso(null) === false, 'l1 apagado no se manda al acceso desde aquí (lo hace §8, como siempre)');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        $check($alAcceso(null) === true, 'l2 encendido, sin sesión, pantalla con require_login: al acceso');
        $check($alAcceso(UsersModel::TYPE_USER_ROOT) === false, 'l3 root no se detiene: ni 503 ni acceso');
        $check($alAcceso(UsersModel::TYPE_USER_ADMIN_GRAL) === false, 'l4 un tipo permitido tampoco');
        $check(MaintenanceMode::blocks('admin', UsersModel::TYPE_USER_GENERAL) === true && $alAcceso(UsersModel::TYPE_USER_GENERAL) === false, 'l5 con sesión de un tipo detenido: 503, no acceso (ya ha entrado)');
        $check($alAcceso(null, true, false) === false, 'l6 una ruta sin require_login (la zona pública): 503, no acceso');
        $check($alAcceso(null, true, true, true) === false, 'l7 quien pide datos (XHR, JSON): su JSON 503, no una redirección');
        $check($alAcceso(null, true, true, false, true) === false, 'l8 el terminal: su texto, no una redirección');
        $check($alAcceso(null, false) === false, 'l9 sin control de acceso por login, §8 no redirige y aquí tampoco');
        $check(MaintenanceMode::sendsToLogin(false, null, true, true, false, false) === false, 'l10 lo que blocks() no detiene no se manda a ningún sitio');

        //─── m · Los tres puntos donde se usa, en su sitio ──────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[m] El acceso no da sesión a un tipo detenido, §7bis redirige y la 503 enlaza al acceso');
        $fuenteUsuarios = (string) @file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Controllers/UsersController.php'));
        $inicioLogin = mb_strpos($fuenteUsuarios, 'public function login(');
        $finLogin = $inicioLogin !== false ? mb_strpos($fuenteUsuarios, 'public function verifySession(', $inicioLogin) : false;
        $login = $inicioLogin !== false && $finLogin !== false ? mb_substr($fuenteUsuarios, $inicioLogin, $finLogin - $inicioLogin) : '';
        $check($login !== '', 'm1 se lee el cuerpo de login()');
        $posContrasena = mb_strpos($login, 'password_verify($password');
        $posFactor = mb_strpos($login, 'checkValidityTOTP(');
        $posRechazo = mb_strpos($login, 'MaintenanceMode::stopsRole(');
        $posGasto = mb_strpos($login, 'OTPHandler::toExpireOTP(');
        $posSesion = mb_strpos($login, 'UserSessions::issueToken(');
        //DISCRIMINANTE: el rechazo antes de la contraseña delataría el tipo de una cuenta; después de la sesión, no frenaría nada.
        $check($posRechazo !== false && $posContrasena !== false && $posFactor !== false && $posRechazo > $posContrasena && $posRechazo > $posFactor, 'm2 login() rechaza al tipo detenido DESPUÉS de la contraseña y del segundo factor');
        $check($posRechazo !== false && $posGasto !== false && $posSesion !== false && $posRechazo < $posGasto && $posRechazo < $posSesion, 'm3 y ANTES de gastar el código de un uso y de crear la sesión');
        //Con un `if` en vez del `elseif`, el rechazo se anotaría y la sesión se daría igual en la rama siguiente.
        $check(str_contains($login, '} elseif (!$require2FA || $twoFactorIsValid) {'), 'm3bis el rechazo y la sesión son ramas excluyentes (elseif)');
        $check(mb_substr_count($login, 'UserSessions::issueToken(') === 1 && mb_substr_count($fuenteUsuarios, 'UserSessions::issueToken(') === 1, 'm4 canario: login() es el único sitio del controlador que crea una sesión');
        $fuenteIndice = (string) @file_get_contents(basepath('index.php'));
        $posPuerta = mb_strpos($fuenteIndice, 'MaintenanceMode::blocks(');
        $posAlAcceso = $posPuerta !== false ? mb_strpos($fuenteIndice, 'MaintenanceMode::sendsToLogin(', $posPuerta) : false;
        $posVista = $posPuerta !== false ? mb_strpos($fuenteIndice, 'render(MaintenanceMode::VIEW)', $posPuerta) : false;
        $check($posAlAcceso !== false && $posVista !== false && $posAlAcceso < $posVista, 'm5 §7bis pregunta sendsToLogin() antes de servir la 503');
        $check(mb_substr_count($fuenteIndice, '($redirectToLogin)()') === 2, 'm6 §7bis y §8 redirigen con la misma closure');
        $vista503 = (string) @file_get_contents(basepath('app/view/' . MaintenanceMode::VIEW . '.php'));
        $check(str_contains($vista503, "routeName('form-login')") && str_contains($vista503, "get_config('current_user') instanceof \\stdClass"), 'm7 la 503 enlaza al acceso, condicionado a no tener sesión');

        //─── n · Entrar con 2FA o con el código de un uso (P299) ───────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[n] Lo que el formulario de acceso pide antes de entrar pasa con el modo encendido, y ya era público');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT]);
        foreach ([
            'user-system-features-two-factor-auth-status' => 'n1 el estado del 2FA: sin él, nadie con 2FA entra',
            'user-system-features-generate-otp' => 'n2 el código de un uso: la otra forma de entrar',
        ] as $nombre => $motivo) {
            $info = get_route_info($nombre);
            $check(is_array($info), "{$motivo} — canario: la ruta existe con ese nombre");
            //SI PIDIERA SESIÓN, la usaría con la suya un tipo que el modo detiene (blocks() mira la lista antes que el rol).
            $check(is_array($info) && empty($info['require_login']), "{$motivo} — y no pide sesión");
            $check(in_array($nombre, MaintenanceMode::ALWAYS_ALLOWED, true) && MaintenanceMode::blocks($nombre, null) === false, "{$motivo} — pasa sin sesión");
        }

        //─── o · Root que suplanta pasa el modo (H215) ──────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[o] roleThatCounts(): suplantando cuenta root; sin sesión viva, nadie');
        $general = UsersModel::TYPE_USER_GENERAL;
        $check(MaintenanceMode::roleThatCounts($general, true, false) === $general, 'o1 sin suplantar, el tipo del usuario');
        $check(MaintenanceMode::roleThatCounts($general, true, true) === UsersModel::TYPE_USER_ROOT, 'o2 suplantando a un tipo detenido, cuenta root');
        $check(MaintenanceMode::roleThatCounts($general, false, true) === null && MaintenanceMode::roleThatCounts($general, false, false) === null, 'o3 sin sesión viva no hay rol, suplante o no');
        $check(MaintenanceMode::roleThatCounts(null, false, false) === null, 'o4 sin usuario, nada');
        set_config(MaintenanceMode::ENABLED_CONFIG, true);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, [UsersModel::TYPE_USER_ROOT]);
        $check(MaintenanceMode::blocks('admin', MaintenanceMode::roleThatCounts($general, true, true)) === false, 'o5 y así el panel no le detiene');
        $check(MaintenanceMode::blocks('admin', MaintenanceMode::roleThatCounts($general, true, false)) === true, 'o6 pero el propio tipo detenido, con su sesión, sigue detenido');
        $fuenteIndice = (string) @file_get_contents(basepath('index.php'));
        $check(str_contains($fuenteIndice, '$maintenanceRole = MaintenanceMode::roleThatCounts(') && str_contains($fuenteIndice, 'Impersonation::isImpersonating());'), 'o7 §7bis saca el rol de roleThatCounts() con la suplantación de la petición');

    } finally {
        //El modo NUNCA se queda encendido por una suite, ni aunque algo lance.
        set_config(MaintenanceMode::ENABLED_CONFIG, $modoPrevio);
        set_config(MaintenanceMode::ALLOWED_ROLES_CONFIG, $rolesPrevios);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El modo mantenimiento: sus valores por omisión, qué hace con cada configuración inválida y a quién detiene.')->setEffects([CliActions::EFFECT_NONE])->register();
