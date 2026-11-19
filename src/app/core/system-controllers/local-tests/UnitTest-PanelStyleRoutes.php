<?php

//Las direcciones de Estilo del panel por HTTP y en proceso. Borra al salir sus usuarios zz-agente-direccion-* y sus sesiones.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/panel-style-routes', function ($args) {

    echoTerminal("\e[33m[TEST:PanelStyleRoutes] Estilo del panel en appearance/panel-style; las dos direcciones viejas redirigen\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    //──── [d] Lo declarado: el nombre de la ruta es el permiso y no cambia ───────────────
    echoTerminal('[d] Las rutas');
    $estilo = get_route_info('configurations-appearance-colors');
    $legada = get_route_info('configurations-appearance-colors-legacy');
    $check(is_array($estilo) && str_ends_with((string) ($estilo['route'] ?? ''), 'appearance/panel-style[/]'), 'd1 appearance-colors (el permiso, mismo nombre) vive en appearance/panel-style', (string) json_encode($estilo['route'] ?? null));
    $check(is_array($legada) && str_ends_with((string) ($legada['route'] ?? ''), 'appearance/colors[/]'), 'd2 appearance-colors-legacy ocupa la dirección vieja appearance/colors', (string) json_encode($legada['route'] ?? null));
    $check(is_array($estilo) && is_array($legada) && ($estilo['roles_allowed'] ?? null) === ($legada['roles_allowed'] ?? null) && ($legada['roles_allowed'] ?? []) === SettingsController::ROLES_VIEW_CONFIGURATIONS_VIEW, 'd3 la legada tiene los roles de la pantalla', (string) json_encode([$estilo['roles_allowed'] ?? null, $legada['roles_allowed'] ?? null]));

    //──── Por HTTP ───────────────────────────────────────────────────────────────────────
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    echoTerminal(' ');
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $location = '';
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
            CURLOPT_HEADERFUNCTION => function ($curl, string $linea) use (&$location): int {
                if (stripos($linea, 'Location:') === 0) {
                    $location = trim(substr($linea, 9));
                }
                return strlen($linea);
            },
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : '', 'location' => $location];
    };

    $prefijo = 'zz-agente-direccion-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaSesiones = UserSessionsModel::TABLE;
    $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo): int {
        $usuario = new UsersModel();
        $usuario->username = "{$prefijo}-{$sufijo}";
        $usuario->email = "{$prefijo}-{$sufijo}@example.com";
        $usuario->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $usuario->firstname = 'Zz';
        $usuario->secondname = '';
        $usuario->firstLastname = 'Direccion';
        $usuario->secondLastname = '';
        $usuario->type = $tipo;
        $usuario->status = UsersModel::STATUS_USER_ACTIVE;
        $usuario->failedAttempts = 0;
        $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $usuario->createdAt = new \DateTime();
        $usuario->modifiedAt = $usuario->createdAt;
        $usuario->save();
        return (int) $usuario->id;
    };
    //El banco de Roles y el usuario actual son estado estático del proceso: se fotografían y se reponen.
    $propiedadRoles = new \ReflectionProperty(Roles::class, 'roles');
    $rolesAntes = $propiedadRoles->getValue();
    $usuarioActualAntes = get_config('current_user');
    $usuarioGuardadoAntes = get_config('pcsphp_current_user_stored');

    try {
        $jwtRoot = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT));
        $estiloURL = (string) SettingsController::routeName('appearance-colors', [], true);

        echoTerminal(' ');
        echoTerminal('[h] Por HTTP, como root');
        $r = $pedir('admin/configurations/appearance/panel-style/', $jwtRoot);
        $check($r['status'] === 200 && str_contains($r['body'], 'data-style-panel-tabs'), 'h1 appearance/panel-style/ da 200 con la pantalla de pestañas', "HTTP {$r['status']}");
        $r = $pedir('admin/configurations/appearance/colors/', $jwtRoot);
        $check($r['status'] === 302 && $camino($r['location']) === $camino($estiloURL) && str_ends_with($r['location'], 'appearance/panel-style/'), 'h2 appearance/colors/ redirige (302) a appearance/panel-style/, sin fragmento', "HTTP {$r['status']} → {$r['location']}");
        $r = $pedir('admin/configurations/appearance/accessibility/', $jwtRoot);
        $partes = explode('#', $r['location'], 2);
        $check($r['status'] === 302 && $camino($partes[0]) === $camino($estiloURL) && ($partes[1] ?? null) === 'accesibilidad', 'h3 appearance/accessibility/ redirige (302) a appearance/panel-style/#accesibilidad', "HTTP {$r['status']} → {$r['location']}");

        //──── [s] En proceso: un rol con las direcciones viejas y sin appearance-colors ─────
        echoTerminal(' ');
        echoTerminal('[s] Sin permiso de appearance-colors, las dos viejas dan 403 y no redirigen a sí mismas');
        $idAdmin = $crearUsuario('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $roles = $rolesAntes;
        $tocado = false;
        foreach ($roles as $i => $rol) {
            if ((int) $rol['code'] === UsersModel::TYPE_USER_ADMIN_GRAL) {
                $roles[$i]['all'] = false;
                $roles[$i]['allowed_routes'] = array_values(array_filter($rol['allowed_routes'], fn ($ruta): bool => $ruta !== 'configurations-appearance-colors'));
                $tocado = true;
            }
        }
        $propiedadRoles->setValue(null, $roles);
        set_config('current_user', (object) ['id' => $idAdmin]);
        getLoggedFrameworkUser(true);
        $check($tocado && SettingsController::routeName('appearance-colors') === '' && SettingsController::routeName('appearance-colors-legacy') !== '' && SettingsController::routeName('appearance-accessibility') !== '', 's1 DISCRIMINANTE: ese usuario no tiene appearance-colors y sí las dos viejas');
        $peticion = new RequestRoute('GET', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-direccion'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream(''));
        $controlador = new SettingsController();
        //throw403() lanza HttpForbiddenException la primera vez; por HTTP la convierte en 403 el manejador de errores.
        foreach (['accessibilityView' => 's2 appearance-accessibility', 'panelStyleLegacyView' => 's3 appearance-colors-legacy'] as $metodo => $nombre) {
            $resultado = '';
            try {
                $respuesta = $controlador->{$metodo}($peticion, new ResponseRoute());
                $resultado = "HTTP {$respuesta->getStatusCode()} → {$respuesta->getHeaderLine('Location')}";
            } catch (\Slim\Exception\HttpForbiddenException $e) {
                $resultado = '403';
            }
            $check($resultado === '403', "{$nombre} responde 403 (HttpForbiddenException), sin redirigir", $resultado);
        }
        $propiedadRoles->setValue(null, $rolesAntes);
        getLoggedFrameworkUser(true);
        $respuesta = $controlador->panelStyleLegacyView($peticion, new ResponseRoute());
        $check($respuesta->getStatusCode() === 302 && $camino($respuesta->getHeaderLine('Location')) === $camino($estiloURL), 's4 DISCRIMINANTE: con el permiso devuelto, el mismo usuario redirige a appearance/panel-style', "HTTP {$respuesta->getStatusCode()} → {$respuesta->getHeaderLine('Location')}");
    } finally {
        $propiedadRoles->setValue(null, $rolesAntes);
        set_config('current_user', $usuarioActualAntes);
        set_config('pcsphp_current_user_stored', $usuarioGuardadoAntes);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Todo zz-agente-direccion-, no solo esta corrida: recoge lo que dejara una corrida que murió a medias.
        $patron = $database->quote('zz-agente-direccion-%');
        $tablaAprobaciones = \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE;
        foreach ($database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE {$patron}")->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaUsuarios, (string) $id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando los usuarios que quedan.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
        }
        $database->exec("DELETE s FROM `{$tablaSesiones}` s INNER JOIN `{$tablaUsuarios}` u ON u.id = s.userID WHERE u.username LIKE {$patron}");
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE {$patron}");
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE {$patron}")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Estilo del panel vive en appearance/panel-style con el nombre de ruta appearance-colors; appearance/colors (appearance-colors-legacy) y appearance/accessibility redirigen a ella, y sin su permiso dan 403.')->setEffects([CliActions::EFFECT_DATABASE])->register();
