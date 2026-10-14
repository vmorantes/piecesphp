<?php

//11f: las dos capas de acceso no pueden contradecirse. El reparto por roles de una ruta se traduce a permisos DE ROL
//al arrancar (AppHelpers::set_route -> Roles::addPermission), y el 403 por rol solo se emite si la ruta exige login.

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/permissions-coherence', function ($args) {

    echoTerminal("\e[33m[TEST:PermissionsCoherence] Las dos capas de acceso: rutas, roles y tipos de usuario\e[39m");
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

    //Lo que esta puerta puede ver. Si el universo no está, las comprobaciones saldrían verdes por vacío.
    $rutas = get_routes();
    $roles = Roles::getRoles();
    $tipos = UsersModel::TYPES_USERS;

    echoTerminal('[canario] El universo de la puerta');
    $check(is_array($rutas) && count($rutas) >= 300, 'c1 ve el catálogo de rutas', 'rutas: ' . (is_array($rutas) ? count($rutas) : gettype($rutas)));
    $check(is_array($roles) && count($roles) >= 6, 'c2 ve los roles registrados', 'roles: ' . (is_array($roles) ? count($roles) : gettype($roles)));
    $check(count($tipos) >= 6, 'c3 ve los tipos de usuario declarados', 'tipos: ' . count($tipos));
    if ($failed > 0) {
        echoTerminal("\e[31m El universo no está completo: las comprobaciones siguientes no significarían nada. \e[39m");
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    echoTerminal(' ');

    //─── 1 · Una ruta sin login no puede repartir por roles ──────────────────────────────────────────
    echoTerminal('[1] Los roles de una ruta sin `requireLogin` no se aplican nunca');
    //index.php emite el 403 por rol solo si la ruta declara require_login, así que declarar roles sin login
    //es decoración: engaña a quien lo lea creyendo que hay una restricción.
    $sinLoginConRoles = [];
    foreach ($rutas as $nombre => $info) {
        $exigeLogin = (bool) ($info['require_login'] ?? false);
        $rolesRuta = $info['roles_allowed'] ?? [];
        if (!$exigeLogin && is_array($rolesRuta) && count($rolesRuta) > 0) {
            $sinLoginConRoles[] = (string) $nombre;
        }
    }
    sort($sinLoginConRoles);
    //DECISIÓN PENDIENTE para las cuatro. En las tres de la API el reparto NO es adorno aunque no se aplique: es lo que
    //satisface la comprobación de `verify-integrity` que exige declarar `require_login` O `roles_allowed`.
    $excepcionesDeclaradas = [
        'api-admin-cron-jobs',
        'api-admin-reports-actions',
        'api-admin-users-actions',
        'components-provider-provide',
    ];
    $check($sinLoginConRoles === $excepcionesDeclaradas,
        '1a las únicas rutas sin login que reparten por roles son las cuatro declaradas, pendientes de decisión',
        'encontradas: ' . (implode(', ', $sinLoginConRoles) ?: 'ninguna') . ' | declaradas: ' . implode(', ', $excepcionesDeclaradas));
    echoTerminal(' ');

    //─── 2 · Una ruta con login tiene que estar concedida a alguien ──────────────────────────────────
    echoTerminal('[2] Toda ruta que exige login está concedida al menos a un rol');
    //Si el NOMBRE de la ruta no está en ningún catálogo, Roles::hasPermissions devuelve false para todos y la ruta
    //responde 403 a cualquiera, root incluido. Pasó con las cinco `locations-*-ajax-search`.
    $concedidas = [];
    foreach ($roles as $rol) {
        foreach (($rol['allowed_routes'] ?? []) as $nombreConcedido) {
            $concedidas[(string) $nombreConcedido] = true;
        }
    }
    $huerfanas = [];
    foreach ($rutas as $nombre => $info) {
        if (!(bool) ($info['require_login'] ?? false)) {
            continue;
        }
        //La clave se resuelve a su NOMBRE porque es lo que hace `Roles::hasPermissions()`: la clave puede ser un ALIAS.
        //Sin resolver, daba huérfano a `terminal-h` mientras `bin/cli h` funcionaba (#529).
        $nombreReal = (string) ($info['name'] ?? $nombre);
        if (!isset($concedidas[$nombreReal])) {
            $huerfanas[$nombreReal] = true;
        }
    }
    $huerfanas = array_keys($huerfanas);
    sort($huerfanas);
    $check($huerfanas === [],
        '2a ninguna ruta con login se queda sin un rol que la conceda',
        count($huerfanas) . ' encontrada(s): ' . (implode(', ', array_slice($huerfanas, 0, 8)) ?: 'ninguna'));
    echoTerminal(' ');

    //─── 3 · Cada tipo de usuario declarado tiene su rol ─────────────────────────────────────────────
    echoTerminal('[3] Todo tipo de usuario de TYPES_USERS tiene su rol en el catálogo');
    $sinRol = [];
    foreach ($tipos as $codigo => $nombreTipo) {
        if (!Roles::roleExists((int) $codigo)) {
            $sinRol[] = $codigo . ' (' . $nombreTipo . ')';
        }
    }
    $check($sinRol === [], '3a los tipos usables tienen rol', implode(' · ', $sinRol) ?: '-');
    echoTerminal(' ');

    //─── 4 · Un tipo sin rol se salta la capa de permisos ────────────────────────────────────────────
    echoTerminal('[4] Ningún tipo de usuario declarado se queda sin rol: sin rol NO hay comprobación de permisos');
    //index.php solo cruza permisos si Roles::getCurrentRole() devuelve algo; sin rol, $has_permissions se queda en
    //null y el 403 no se emite. Falla ABIERTO, así que el día que se añada un tipo hay que añadir su rol.
    $codigosDeRol = [];
    foreach ($roles as $rol) {
        $codigosDeRol[(int) ($rol['code'] ?? -1)] = true;
    }
    $tiposConstantes = [
        UsersModel::TYPE_USER_ROOT => 'TYPE_USER_ROOT',
        UsersModel::TYPE_USER_ADMIN_GRAL => 'TYPE_USER_ADMIN_GRAL',
        UsersModel::TYPE_USER_ADMIN_ORG => 'TYPE_USER_ADMIN_ORG',
        UsersModel::TYPE_USER_GENERAL => 'TYPE_USER_GENERAL',
        UsersModel::TYPE_USER_INSTITUCIONAL => 'TYPE_USER_INSTITUCIONAL',
        UsersModel::TYPE_USER_COMUNICACIONES => 'TYPE_USER_COMUNICACIONES',
        UsersModel::TYPE_USER_GOOGLE_PLAY => 'TYPE_USER_GOOGLE_PLAY',
    ];
    $usablesSinRol = [];
    $noUsables = [];
    foreach ($tiposConstantes as $codigo => $constante) {
        $esUsable = array_key_exists($codigo, $tipos);
        $tieneRol = isset($codigosDeRol[(int) $codigo]);
        if ($esUsable && !$tieneRol) {
            $usablesSinRol[] = $constante . ' (' . $codigo . ')';
        }
        if (!$esUsable) {
            $noUsables[] = $constante . ' (' . $codigo . ')';
        }
    }
    $check($usablesSinRol === [],
        '4a ningún tipo usable se queda sin rol: si se descomenta TYPE_USER_GOOGLE_PLAY en TYPES_USERS, hay que darle el suyo en roles.php',
        implode(' · ', $usablesSinRol) ?: '-');
    $check(count($noUsables) === 1 && str_contains($noUsables[0], 'TYPE_USER_GOOGLE_PLAY'),
        '4b y el único tipo fuera de TYPES_USERS sigue siendo TYPE_USER_GOOGLE_PLAY, que por eso no tiene rol',
        implode(' · ', $noUsables) ?: 'ninguno');
    echoTerminal(' ');

    //─── 5 · Las rutas de los registros declaran su rol ──────────────────────────────────────────────
    echoTerminal('[5] Las rutas de los registros (errores, acciones, correos, intentos) declaran sus roles, no los heredan');
    //Sin roles declarados, lo que niega es la regla por omisión del catálogo (root tiene `all`), no la ruta. Un cambio
    //en esa regla o un require_login quitado abriría la traza completa de los errores (pendientes.md 434.1).
    $patronRegistros = '/^(admin-error-log|actions-logs-|system-status-mail-log|login-attempts-)/';
    $deRegistros = [];
    $sinDeclarar = [];
    foreach ($rutas as $nombre => $info) {
        $nombreReal = (string) ($info['name'] ?? $nombre);
        if (preg_match($patronRegistros, $nombreReal) !== 1) {
            continue;
        }
        $deRegistros[$nombreReal] = true;
        if (!(bool) ($info['require_login'] ?? false) || ($info['roles_allowed'] ?? []) === []) {
            $sinDeclarar[] = $nombreReal;
        }
    }
    $check(count($deRegistros) >= 10, '5a CANARIO: el patrón ve las rutas de registros', count($deRegistros) . ': ' . implode(', ', array_keys($deRegistros)));
    $check($sinDeclarar === [], '5b ninguna ruta de registros se queda sin login y sin roles declarados', implode(', ', $sinDeclarar) ?: '-');
    $rolesError = array_values(array_map('intval', (array) ($rutas['admin-error-log']['roles_allowed'] ?? [])));
    $check(isset($rutas['admin-error-log']) && $rolesError === [UsersModel::TYPE_USER_ROOT], '5c admin-error-log, que entrega la traza entera, declara solo root', '[' . implode(', ', $rolesError) . ']');
    echoTerminal(' ');

    //─── 6 · El acceso EFECTIVO de las pantallas que decidió el PO ───────────────────────────────────
    echoTerminal('[6] El acceso efectivo (declaración y catálogo juntos) de banners, imagen de inicio y tiempo en plataforma');
    //Se mide con get_route_roles_allowed(), que une lo declarado con roles.php: declarar concede pero no restringe, así
    //que mirar solo roles_allowed no diría quién entra (pendientes.md 468).
    $efectivo = function (string $nombre) use ($rutas): ?array {
        if (!isset($rutas[$nombre])) {
            return null;
        }
        $codigos = array_values(array_map('intval', get_route_roles_allowed($nombre)));
        sort($codigos);
        return $codigos;
    };
    $ordenar = function (array $codigos): array {
        sort($codigos);
        return $codigos;
    };
    $comunicacionYSuperiores = $ordenar([UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL, UsersModel::TYPE_USER_COMUNICACIONES]);
    $soloPrincipal = [UsersModel::TYPE_USER_ROOT];
    $todos = $ordenar(array_map('intval', array_keys(UsersModel::TYPES_USERS)));
    $esperado = [];
    foreach (['-list', '-forms-add', '-forms-edit', '-ajax-all', '-datatables', '-actions-add', '-actions-edit', '-actions-delete'] as $sufijo) {
        $esperado['built-in-banner-admin' . $sufijo] = [$comunicacionYSuperiores, 'Comunicaciones y superiores'];
    }
    $esperado['helpers-system-generic-content-admin-forms-home-image'] = [$soloPrincipal, 'solo Principal'];
    //La portada se guarda por la acción común de los ajustes, abierta a todos A PROPÓSITO: quien la cierra a Principal es
    //la comprobación interna con HOME_IMAGE_PERMISSIONS, que se mide en 6c.
    $esperado['helpers-system-generic-content-admin-actions-edit'] = [$todos, 'todos, y la imagen la filtra el controlador'];
    //Cada usuario registra SU tiempo: abierta a todos es deliberado, y lo que impide sumarlo a otro es la sección [7].
    $esperado['timing-add'] = [$todos, 'todos, deliberado: cada uno registra su propio tiempo'];

    $vacias = [];
    foreach (array_keys($esperado) as $nombre) {
        $codigos = $efectivo($nombre);
        if ($codigos === null || $codigos === []) {
            $vacias[] = $nombre . ($codigos === null ? ' (no existe)' : ' (vacía)');
        }
    }
    $check($vacias === [], '6a CANARIO: las ' . count($esperado) . ' rutas existen y el instrumento les ve algún rol', implode(', ', $vacias) ?: '-');
    foreach ($esperado as $nombre => [$roles, $texto]) {
        $codigos = $efectivo($nombre) ?? [];
        $check($codigos === $roles, "6b {$nombre}: {$texto}", 'efectivo [' . implode(', ', $codigos) . '] | esperado [' . implode(', ', $roles) . ']');
    }
    $imagen = array_values(array_map('intval', \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::HOME_IMAGE_PERMISSIONS));
    $check($imagen === $soloPrincipal, '6c HOME_IMAGE_PERMISSIONS, que filtra la imagen dentro de la acción común, es solo Principal', '[' . implode(', ', $imagen) . ']');
    echoTerminal(' ');

    //─── 7 · El tiempo en plataforma se suma al usuario de la SESIÓN ─────────────────────────────────
    echoTerminal('[7] timeOnPlatform() toma el usuario de la sesión y no vuelve a leer `user_id` de la petición');
    //Por estructura y sin tocar la base: addTime() escribe. Se tokeniza el cuerpo del método, así que un comentario que
    //nombre `user_id` no cuenta.
    $metodo = new \ReflectionMethod(\PiecesPHP\UserSystem\Controllers\TimerController::class, 'timeOnPlatform');
    $lineas = file((string) $metodo->getFileName()) ?: [];
    $cuerpo = implode('', array_slice($lineas, $metodo->getStartLine() - 1, $metodo->getEndLine() - $metodo->getStartLine() + 1));
    $tokens = token_get_all('<?php ' . $cuerpo);
    $leeUserId = 0;
    $llamaSesion = 0;
    foreach ($tokens as $i => $token) {
        if (!is_array($token)) {
            continue;
        }
        if ($token[0] === T_CONSTANT_ENCAPSED_STRING && trim($token[1], '\'"') === 'user_id') {
            $leeUserId++;
        }
        if ($token[0] === T_STRING && $token[1] === 'getLoggedFrameworkUser' && ($tokens[$i + 1] ?? null) === '(') {
            $llamaSesion++;
        }
    }
    $check(mb_strlen($cuerpo) > 0 && str_contains($cuerpo, 'addTime'), '7a CANARIO: el censo ve el cuerpo del método, con su addTime()', mb_strlen($cuerpo) . ' bytes');
    $check($leeUserId === 0, '7b ninguna cadena `user_id` en el código del método: el cuerpo de la petición no decide a quién se le suma', $leeUserId . ' ocurrencia(s)');
    $check($llamaSesion === 1, '7c el usuario sale de getLoggedFrameworkUser()', $llamaSesion . ' llamada(s)');
    echoTerminal(' ');

    //─── 8 · La acción común de los ajustes sigue filtrando la imagen con su constante ───────────────
    echoTerminal('[8] GenericContentController::action() sigue comprobando HOME_IMAGE_PERMISSIONS');
    //6c mide el VALOR de la constante; esto mide que la acción común, abierta a todos por ruta, la siga usando.
    $accion = new \ReflectionMethod(\PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::class, 'action');
    $lineasAccion = file((string) $accion->getFileName()) ?: [];
    $cuerpoAccion = implode('', array_slice($lineasAccion, $accion->getStartLine() - 1, $accion->getEndLine() - $accion->getStartLine() + 1));
    $usosConstante = 0;
    foreach (token_get_all('<?php ' . $cuerpoAccion) as $token) {
        if (is_array($token) && $token[0] === T_STRING && $token[1] === 'HOME_IMAGE_PERMISSIONS') {
            $usosConstante++;
        }
    }
    $check(str_contains($cuerpoAccion, 'CONTENT_HOME_IMAGE'), '8a CANARIO: el censo ve el cuerpo de action(), con su rama de la imagen de inicio', mb_strlen($cuerpoAccion) . ' bytes');
    $check($usosConstante >= 1, '8b action() nombra HOME_IMAGE_PERMISSIONS en código, no en un comentario', $usosConstante . ' uso(s)');
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('11f: el reparto por roles de las rutas y el catálogo de roles no se contradicen, y ningún tipo de usuario se queda sin rol.')->setEffects([CliActions::EFFECT_NONE])->register();
