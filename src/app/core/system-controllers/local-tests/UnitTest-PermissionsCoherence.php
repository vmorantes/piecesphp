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

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('11f: el reparto por roles de las rutas y el catálogo de roles no se contradicen, y ningún tipo de usuario se queda sin rol.')->setEffects([CliActions::EFFECT_NONE])->register();
