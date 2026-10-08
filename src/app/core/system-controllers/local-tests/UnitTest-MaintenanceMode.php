<?php

//El modo mantenimiento. Solo usa `set_config()`, que es memoria del proceso: no escribe en la base ni lo deja encendido.

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Terminal\CliActions;

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
        //UN NOMBRE MAL ESCRITO EN ESA LISTA NO ES UN HUECO, ES UNA PUERTA QUE NO SE ABRE: el acceso
        //quedaría detenido y el sitio muerto. Y al revés, una ruta que desaparezca deja la lista mintiendo.
        $inventario = json_decode((string) @file_get_contents(dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/route-inventory.json'), true);
        $nombres = is_array($inventario) ? array_map(fn (array $r): string => (string) ($r['name'] ?? ''), $inventario) : [];
        $check(count($nombres) > 300, 'e2 el inventario de rutas se pudo leer', count($nombres) . ' rutas');
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
        $colores = basepath('statics/core/js/app_config/colors.js');
        $jsViejo = is_file($colores) ? (string) file_get_contents($colores) : '';
        $check($jsViejo !== '' && mb_strpos($jsViejo, 'maintenance') === false, 'h9 ni su JavaScript');
        //Si ALWAYS_ALLOWED nombrara la pantalla vieja, la vuelta iría a donde ya no están los mandos,
        //y la prueba de que los nombres existen no lo vería: los viejos siguen existiendo.
        $check(in_array('system-status-site-maintenance', MaintenanceMode::ALWAYS_ALLOWED, true), 'h10 ALWAYS_ALLOWED nombra la pantalla nueva');
        $check(in_array('system-status-site-maintenance-save', MaintenanceMode::ALWAYS_ALLOWED, true), 'h11 y su guardado');
        $check(!in_array('configurations-appearance-colors', MaintenanceMode::ALWAYS_ALLOWED, true) && !in_array('configurations-index', MaintenanceMode::ALWAYS_ALLOWED, true), 'h12 y ya no nombra la pantalla vieja (hoy, la de colores), ni el índice');
        $check(!in_array('configurations-generic-save', MaintenanceMode::ALWAYS_ALLOWED, true), 'h13 ni la acción genérica');
        $check(is_array(get_route_info('configurations-appearance-colors')) && is_array(get_route_info('configurations-generic-save')), 'h14 canario: esas dos rutas existen con ese nombre (si se renombran, h12 y h13 no mirarían nada)');

        //─── i · La acción genérica de configuraciones no puede apagar el sitio (P74) ────────────────────
        echoTerminal(' ');
        echoTerminal('[i] Las tres claves del modo están reservadas al principal en la acción genérica');
        $reservadas = \PiecesPHP\Settings\Controllers\SettingsController::ROOT_ONLY_CONFIG_KEYS;
        foreach ([MaintenanceMode::ENABLED_CONFIG, MaintenanceMode::ALLOWED_ROLES_CONFIG, MaintenanceMode::RETRY_AFTER_CONFIG] as $clave) {
            $check(in_array($clave, $reservadas, true), "i1 «{$clave}» está reservada");
        }
        $fuenteAccion = (string) @file_get_contents(basepath('app/classes/PiecesPHP/Settings/Controllers/SettingsController.php'));
        $posGuarda = mb_strpos($fuenteAccion, 'isRootOnlyConfigName($name, $targetName) && !$isRoot');
        $posEscritura = mb_strpos($fuenteAccion, '$success = $option->update();');
        $check($posGuarda !== false, 'i2 la comprobación sigue en la acción genérica');
        //DISCRIMINANTE: una comprobación DESPUÉS de escribir deja pasar la escritura y devuelve 403
        //igual. El orden es la guarda, no su presencia.
        $check($posGuarda !== false && $posEscritura !== false && $posGuarda < $posEscritura,
            'i3 y va ANTES de la escritura');
        $check(mb_strpos($fuenteAccion, 'withJson($result, 403)') !== false, 'i4 el rechazo es un 403');

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
