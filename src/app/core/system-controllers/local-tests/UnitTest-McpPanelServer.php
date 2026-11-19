<?php

//El servidor MCP del panel (ADR 0075): registro, permisos, herramientas y la ruta por HTTP.
//Registra herramientas zz_ y crea un usuario zz-agente-mcp-* con sus sesiones e intentos; lo quita y lo borra todo.
//@areas nucleo
//@areas src/app/classes/PiecesPHP/Mcp/
//@areas src/app/classes/PiecesPHP/Notifications/

use Directory\DirectoryRoutes;
use EventsLog\Mappers\LogsMapper;
use MySpace\Controllers\MySpaceController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\PanelSearchSource;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Mcp\McpProtocol;
use PiecesPHP\Core\Mcp\McpTool;
use PiecesPHP\Core\Mcp\McpToolRegistry;
use PiecesPHP\Core\Mcp\PanelServer;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Mcp\McpLang;
use PiecesPHP\Mcp\McpRoutes;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationsRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/mcp-panel-server', function ($args) {

    echoTerminal("\e[33m[TEST:McpPanelServer] El servidor MCP del panel: registro, permisos, herramientas y la ruta\e[39m");
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
    $lanza = function (callable $accion): bool {
        try {
            $accion();
        } catch (\InvalidArgumentException $e) {
            return true;
        }
        return false;
    };
    $esquemaVacio = ['type' => 'object', 'properties' => [], 'additionalProperties' => false];
    $herramienta = fn(string $key, string $ruta, callable $handler, ?array $esquema = null): McpTool => new McpTool($key, 'Zz', 'Zz de prueba', $esquema ?? $esquemaVacio, $ruta, $handler);
    $pedir = fn(UserDataPackage $usuario, string $method, array $params = [], int $id = 1): ?array => PanelServer::handle(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params], $usuario);
    $llamar = fn(UserDataPackage $usuario, string $tool, array $arguments): ?array => $pedir($usuario, 'tools/call', ['name' => $tool, 'arguments' => $arguments]);
    $datos = fn(?array $respuesta): mixed => json_decode((string) ($respuesta['result']['content'][0]['text'] ?? ''), true);

    //Un root y un usuario general de la base, solo para leer.
    $primero = function (int $tipo): int {
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', $tipo), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
        return (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    };
    $idRoot = $primero(UsersModel::TYPE_USER_ROOT);
    $idGeneral = $primero(UsersModel::TYPE_USER_GENERAL);
    if (!$check($idRoot > 0 && $idGeneral > 0, 'c0 hay un root y un usuario general activos en la base', "root {$idRoot}, general {$idGeneral}")) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $root = new UserDataPackage($idRoot);
    $general = new UserDataPackage($idGeneral);
    $usuarioAntes = [get_config('current_user'), get_config('pcsphp_current_user_stored'), get_config('menus')];
    $rolActual = new \ReflectionProperty(Roles::class, 'currentRole');
    $rolAntes = $rolActual->getValue();
    //Lo que hace index.php en una petición con sesión: el usuario, su rol y el menú lateral calculado para él (paso 9).
    //En el terminal no hay menú: sin esto, el buscador no tiene de dónde leer.
    $como = function (int $id, int $tipo): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
        Roles::setCurrentRole($tipo);
        $silencio = Roles::getSilentMode();
        Roles::setSilentMode(true);
        include basepath('app/config/menu.php');
        Roles::setSilentMode($silencio);
    };

    $prueba = ['zz_prueba_repetida', 'zz_prueba_solo_root', 'zz_prueba_lanza', 'zz_prueba_utf8'];
    $secreto = 'zz-secreto-de-la-excepcion-' . bin2hex(random_bytes(3));

    try {
        $como($idRoot, UsersModel::TYPE_USER_ROOT);
        //─── 1 · El registro ────────────────────────────────────────────────────────────────────────
        echoTerminal('[1] El registro');
        McpToolRegistry::register($herramienta('zz_prueba_repetida', 'admin', fn() => ['v' => 1]));
        $check($lanza(fn() => McpToolRegistry::register($herramienta('zz_prueba_repetida', 'admin', fn() => ['v' => 2]))), '1a una key repetida lanza');
        $orden = array_keys(McpToolRegistry::all());
        McpToolRegistry::replace($herramienta('zz_prueba_repetida', 'admin', fn() => ['v' => 3]));
        $check(array_keys(McpToolRegistry::all()) === $orden && (McpToolRegistry::get('zz_prueba_repetida')?->call($root, []) ?? []) === ['v' => 3], '1b replace() sustituye en su sitio del orden');
        $primeraVez = McpToolRegistry::remove('zz_prueba_repetida');
        $segundaVez = McpToolRegistry::remove('zz_prueba_repetida');
        $check($primeraVez && !$segundaVez && McpToolRegistry::get('zz_prueba_repetida') === null, '1c remove(): true la primera vez, false la segunda');
        $check($lanza(fn() => McpToolRegistry::replace($herramienta('zz_prueba_repetida', 'admin', fn() => []))), '1d replace() de una que no está lanza');
        $check($lanza(fn() => $herramienta('Zz-Mala', 'admin', fn() => [])) && $lanza(fn() => $herramienta('9zz', 'admin', fn() => [])) && !$lanza(fn() => $herramienta('zz_buena_9', 'admin', fn() => [])), '1e un name que no casa con ^[a-z][a-z0-9_]{0,63}$ lanza; uno que casa, no');
        $fuera = [
            'una propiedad array' => ['type' => 'object', 'properties' => ['x' => ['type' => 'array']], 'additionalProperties' => false],
            'sin additionalProperties false' => ['type' => 'object', 'properties' => ['x' => ['type' => 'string']]],
            'una palabra no admitida' => ['type' => 'object', 'properties' => ['x' => ['type' => 'string', 'pattern' => '^a']], 'additionalProperties' => false],
            'minimum en un string' => ['type' => 'object', 'properties' => ['x' => ['type' => 'string', 'minimum' => 1]], 'additionalProperties' => false],
            'required de una que no existe' => ['type' => 'object', 'properties' => [], 'required' => ['x'], 'additionalProperties' => false],
        ];
        $aceptados = array_keys(array_filter($fuera, fn(array $esquema): bool => !$lanza(fn() => $herramienta('zz_esquema', 'admin', fn() => [], $esquema))));
        $check($aceptados === [], '1f un esquema fuera del subconjunto lanza al construir (' . implode(', ', array_keys($fuera)) . ')', 'aceptados: ' . implode(', ', $aceptados));
        echoTerminal(' ');

        //─── 2 · tools/list, por permiso ───────────────────────────────────────────────────────────
        echoTerminal('[2] tools/list: cada uno ve lo que su permiso le deja');
        McpToolRegistry::register($herramienta('zz_prueba_solo_root', 'system-status-health', fn() => ['secreto' => 'zz-solo-root']));
        $nombres = fn(UserDataPackage $u): array => array_column($pedir($u, 'tools/list')['result']['tools'] ?? [], 'name');
        $check(in_array('zz_prueba_solo_root', $nombres($root), true), '2a root ve la herramienta de una ruta que solo concede root', implode(', ', $nombres($root)));
        $check(!in_array('zz_prueba_solo_root', $nombres($general), true), '2b DISCRIMINANTE: el general no la ve', implode(', ', $nombres($general)));
        //panel_find es del módulo MCP; notifications_mine, de Notificaciones.
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 2c y D1: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $definicion = array_values(array_filter($pedir($root, 'tools/list')['result']['tools'] ?? [], fn(array $t): bool => $t['name'] === 'panel_find'))[0] ?? [];
            $check(array_keys($definicion) === ['name', 'title', 'description', 'inputSchema', 'annotations'] && (!NotificationsRoutes::ENABLE || in_array('notifications_mine', $nombres($general), true)) && in_array('panel_find', $nombres($general), true), '2c las dos herramientas de fábrica, con name, title, description, inputSchema y annotations; el general las ve');
            $sinSoloLectura = array_column(array_filter($pedir($root, 'tools/list')['result']['tools'] ?? [], fn(array $t): bool => ($t['annotations']['readOnlyHint'] ?? null) !== true), 'name');
            $check($sinSoloLectura === [] && count($pedir($root, 'tools/list')['result']['tools'] ?? []) >= (NotificationsRoutes::ENABLE ? 3 : 2), 'D1 DISCRIMINANTE: cada herramienta de tools/list trae annotations.readOnlyHint === true', implode(', ', $sinSoloLectura));
        }
        echoTerminal(' ');

        //─── 3 · tools/call sin permiso = herramienta que no existe ───────────────────────────────
        echoTerminal('[3] tools/call: sin permiso responde igual que lo que no existe');
        $sinPermiso = $llamar($general, 'zz_prueba_solo_root', []);
        $check(($llamar($root, 'zz_prueba_solo_root', [])['result']['isError'] ?? null) === false, '3a root la llama');
        McpToolRegistry::remove('zz_prueba_solo_root');
        $inexistente = $llamar($general, 'zz_prueba_solo_root', []);
        $check($sinPermiso === $inexistente && ($sinPermiso['error']['code'] ?? null) === McpProtocol::INVALID_PARAMS && !str_contains((string) json_encode($sinPermiso), 'zz-solo-root'), '3b DISCRIMINANTE: el general recibe EXACTAMENTE lo mismo que con una herramienta que no existe (-32602), sin el dato', (string) json_encode($sinPermiso));
        echoTerminal(' ');

        //─── 4 · Argumentos ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[4] Los argumentos, contra el esquema');
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 4 (los seis casos de panel_find): esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $casos = [
                'falta query' => [[], 'query'],
                'query de 101' => [['query' => str_repeat('a', 101)], 'query'],
                'limit 0' => [['query' => 'a', 'limit' => 0], 'limit'],
                'propiedad extra' => [['query' => 'a', 'zz' => 1], 'zz'],
                'tipo equivocado' => [['query' => 5], 'query'],
                'limit de 51' => [['query' => 'a', 'limit' => 51], 'limit'],
            ];
            foreach ($casos as $caso => [$argumentos, $nombre]) {
                $respuesta = $llamar($root, 'panel_find', $argumentos);
                $mensaje = (string) ($respuesta['error']['message'] ?? '');
                $check(($respuesta['error']['code'] ?? null) === McpProtocol::INVALID_PARAMS && str_contains($mensaje, "«{$nombre}»"), "4 {$caso}: -32602 que nombra «{$nombre}»", $mensaje);
            }
        }
        if (!McpRoutes::ENABLE || !NotificationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 4 (el filtro fuera del enum): esta instalación tiene MCP o Notificaciones apagado.");
        } else {
            $check(($llamar($root, 'notifications_mine', ['filter' => 'zz'])['error']['code'] ?? null) === McpProtocol::INVALID_PARAMS && ($llamar($root, 'panel_find', ['query' => 'a'])['result']['isError'] ?? null) === false, '4 un filtro fuera del enum: -32602; unos argumentos buenos: responde');
        }
        echoTerminal(' ');

        //─── 5 · El protocolo ───────────────────────────────────────────────────────────────────────
        echoTerminal('[5] El protocolo');
        $inicio = $pedir($root, 'initialize', ['protocolVersion' => '2025-03-26']);
        $check(($inicio['result']['protocolVersion'] ?? null) === '2025-03-26' && ($inicio['result']['serverInfo']['name'] ?? null) === PanelServer::SERVER_NAME && ($pedir($root, 'initialize', ['protocolVersion' => '1999-01-01'])['result']['protocolVersion'] ?? null) === McpProtocol::PROTOCOL_VERSION, '5a initialize negocia la versión, con serverInfo piecesphp-panel');
        $check(($pedir($root, 'zz/nada')['error']['code'] ?? null) === McpProtocol::METHOD_NOT_FOUND, '5b un método desconocido: -32601');
        $check(PanelServer::handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $root) === null, '5c una notificación no se responde');
        $check((McpProtocol::parseError()['error']['code'] ?? null) === McpProtocol::PARSE_ERROR && ($pedir($root, 'tools/call', ['name' => 'panel_find', 'arguments' => 'zz'])['error']['code'] ?? null) === McpProtocol::INVALID_PARAMS, '5d parseError(): -32700; unos argumentos que no son un objeto: -32602');
        echoTerminal(' ');

        //─── 6 · Una herramienta que falla ──────────────────────────────────────────────────────────
        echoTerminal('[6] Una herramienta que lanza');
        McpToolRegistry::register($herramienta('zz_prueba_lanza', 'admin', function () use ($secreto): array {
            throw new \RuntimeException($secreto);
        }));
        $falla = $llamar($root, 'zz_prueba_lanza', []);
        $texto = (string) ($falla['result']['content'][0]['text'] ?? '');
        $check(($falla['result']['isError'] ?? null) === true && json_decode($texto, true) === __(McpLang::LANG_GROUP, 'La herramienta no pudo responder. El error quedó en el registro de errores de la instalación.') && !str_contains((string) json_encode($falla), $secreto), '6a DISCRIMINANTE: isError true con el texto genérico; ni el mensaje de la excepción ni la pila', mb_substr($texto, 0, 200));
        McpToolRegistry::remove('zz_prueba_lanza');
        echoTerminal(' ');

        //─── 7 · panel_find ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[7] panel_find: lo mismo que Ctrl K para ese usuario');
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 7a-7d: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $clave = fn(array $e): string => $e['category'] . "\x1f" . $e['title'] . "\x1f" . $e['url'];
            $como($idRoot, UsersModel::TYPE_USER_ROOT);
            $deRoot = array_map($clave, PanelSearchSource::entries($root));
            $halladoRoot = $datos($llamar($root, 'panel_find', ['query' => 'a', 'limit' => 50]))['results'] ?? [];
            $como($idGeneral, UsersModel::TYPE_USER_GENERAL);
            $deGeneral = array_map($clave, PanelSearchSource::entries($general));
            $halladoGeneral = $datos($llamar($general, 'panel_find', ['query' => 'a', 'limit' => 50]))['results'] ?? [];
            $check($halladoRoot !== [] && array_diff(array_map($clave, $halladoRoot), $deRoot) === [], '7a root: cada resultado está en su lista de Ctrl K', count($halladoRoot) . ' de ' . count($deRoot));
            $check($halladoGeneral !== [] && array_diff(array_map($clave, $halladoGeneral), $deGeneral) === [], '7b el general: cada resultado está en su lista de Ctrl K', count($halladoGeneral) . ' de ' . count($deGeneral));
            $soloRoot = array_values(array_diff($deRoot, $deGeneral));
            $titulo = $soloRoot !== [] ? explode("\x1f", $soloRoot[0])[1] : '';
            $buscado = $titulo !== '' ? mb_substr($titulo, 0, 100) : 'zz';
            $deEseTitulo = array_map($clave, $datos($llamar($general, 'panel_find', ['query' => $buscado, 'limit' => 50]))['results'] ?? []);
            $check($soloRoot !== [] && !in_array($soloRoot[0], $deEseTitulo, true), '7c DISCRIMINANTE: el general no recibe una entrada que solo ve root, ni buscándola por su título', "«{$titulo}»; " . count($soloRoot) . ' solo de root');
            $check(array_filter($halladoRoot, fn(array $e): bool => !preg_match('#^[a-z]+://#', (string) $e['url'])) === [], '7d las direcciones son absolutas');
        }
        $como($idRoot, UsersModel::TYPE_USER_ROOT);
        echoTerminal(' ');

        //─── M · El manual en el buscador y en panel_find (H333) ───────────────────────────────────
        echoTerminal('[M] El manual: capítulos y secciones que se ven, y panel_find por palabras');
        $catManual = __(AdminPanelController::ADMIN_LANG_GROUP, 'Manual');
        $catModulos = __(AdminPanelController::ADMIN_LANG_GROUP, 'Módulos');
        $buscar = fn(UserDataPackage $u, string $q): array => $datos($llamar($u, 'panel_find', ['query' => $q, 'limit' => 50]))['results'] ?? [];
        $capitulo = array_values(array_filter(MySpaceController::manualChapters(), fn(array $c): bool => $c['slug'] === '30-publicaciones'))[0] ?? null;
        $tituloCapitulo = is_array($capitulo) ? $capitulo['titulo'] : 'zz-sin-capitulo';
        $delCapitulo = fn(array $r): bool => $r['category'] === $catManual && str_starts_with($r['title'], "{$catManual} › ") && str_contains($r['title'], $tituloCapitulo);
        $soloManualDelCapitulo = fn(array $rs): bool => array_filter($rs, $delCapitulo) !== [] && array_filter($rs, fn(array $r): bool => $r['category'] === $catModulos) === [];
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m m1: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $m1 = $buscar($root, 'manual de publicaciones');
            $check($soloManualDelCapitulo($m1), 'm1 DISCRIMINANTE: «manual de publicaciones» da el capítulo de Publicaciones del manual y ninguna entrada de Módulos', implode(' | ', array_column($m1, 'title')));
        }
        if (!McpRoutes::ENABLE || !DirectoryRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m m2 y m1b: esta instalación tiene MCP o Directorio apagado.");
        } else {
            //En proceso el menú lateral no trae Publicaciones (es del banco, no del código): módulo y manual, con Directorio.
            $capituloDirectorio = array_values(array_filter(MySpaceController::manualChapters(), fn(array $c): bool => $c['slug'] === '56-directorio'))[0]['titulo'] ?? 'zz-sin-capitulo';
            $delDirectorio = fn(array $r): bool => $r['category'] === $catManual && str_starts_with($r['title'], "{$catManual} › {$capituloDirectorio}");
            $m2 = $buscar($root, 'directorio');
            $check(array_filter($m2, fn(array $r): bool => $r['category'] === $catModulos) !== [] && array_filter($m2, $delDirectorio) !== [], 'm2 DISCRIMINANTE: «directorio» da el módulo y el manual', implode(' | ', array_column($m2, 'title')));
            $m1b = $buscar($root, 'manual del directorio');
            $check(array_filter($m1b, $delDirectorio) !== [] && array_filter($m1b, fn(array $r): bool => $r['category'] !== $catManual) === [], 'm1b DISCRIMINANTE: «manual del directorio» da solo el manual (dos palabras frente a una del módulo)', implode(' | ', array_column($m1b, 'title')));
        }
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m m3: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $m3 = $buscar($root, 'manual de lo respectivo a publicaciones');
            $check($soloManualDelCapitulo($m3), 'm3 «manual de lo respectivo a publicaciones», lo mismo que m1', implode(' | ', array_column($m3, 'title')));
        }
        $seccion = is_array($capitulo) ? (MySpaceController::manualVisibleSections($capitulo['secciones'])[0] ?? null) : null;
        $deRootManual = array_values(array_filter(PanelSearchSource::entries($root), fn(array $e): bool => $e['category'] === $catManual));
        $check(is_array($seccion) && array_filter($deRootManual, fn(array $e): bool => $e['title'] === "{$catManual} › {$tituloCapitulo} › {$seccion['titulo']}" && str_ends_with($e['url'], '#' . $seccion['id'])) !== [], 'm5 una sección: «Manual › capítulo › sección» con su ancla', is_array($seccion) ? $seccion['id'] : 'sin sección');
        $needle = 'de-la';
        $deHoy = array_values(array_filter(PanelSearchSource::entries($root), fn(array $e): bool => str_contains(\PiecesPHP\Core\StringManipulate::friendlyURLString($e['title']), $needle) || str_contains(\PiecesPHP\Core\StringManipulate::friendlyURLString($e['category']), $needle)));
        $m6 = PanelSearchSource::find($root, 'de la', 50);
        $check($m6 === $deHoy && $m6 !== [], 'm6 «de la» (solo palabras cortas): la consulta entera como subcadena, como antes', count($m6) . ' resultado(s)');
        $check($deRootManual !== [], 'm7 entries() de root (lo que lee Ctrl K) trae la categoría Manual', count($deRootManual) . ' entrada(s) del manual');
        $urlMarca = (string) \PiecesPHP\Settings\Controllers\SettingsController::routeName('appearance-brand-images', [], true);
        $m8 = PanelSearchSource::find($root, 'logo', 50);
        $check($urlMarca !== '' && array_filter($m8, fn(array $e): bool => $e['url'] === $urlMarca) !== [], 'm8 DISCRIMINANTE: «logo» da Imágenes de marca, por sus palabras clave (H339)', implode(' | ', array_column($m8, 'title')));
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m m9: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $m9 = $buscar($root, 'logo');
            $check($m9 !== [] && array_filter($m9, fn(array $r): bool => array_key_exists('keywords', $r)) === [], 'm9 panel_find no devuelve las palabras clave', (string) json_encode(array_keys($m9[0] ?? [])));
        }
        $como($idGeneral, UsersModel::TYPE_USER_GENERAL);
        $rutasCapitulo = is_array($capitulo) && is_array($capitulo['rutas']) ? $capitulo['rutas'] : [];
        $generalSinPublicaciones = $rutasCapitulo !== [] && array_filter($rutasCapitulo, fn(string $ruta): bool => Roles::hasPermissions($ruta, UsersModel::TYPE_USER_GENERAL)) === [];
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m m4: esta instalación tiene MCP apagado y no hay panel_find.");
        } else {
            $m4 = $buscar($general, 'manual de publicaciones');
            $manualGeneral = array_filter(PanelSearchSource::entries($general), fn(array $e): bool => $e['category'] === $catManual);
            $check($generalSinPublicaciones && $manualGeneral !== [] && array_filter($m4, $delCapitulo) === [], 'm4 DISCRIMINANTE: el usuario general (ve el manual, sin las rutas de Publicaciones) no recibe ese capítulo', implode(' | ', array_column($m4, 'title')));
        }
        $como($idRoot, UsersModel::TYPE_USER_ROOT);
        echoTerminal(' ');

        //─── 8 · notifications_mine ─────────────────────────────────────────────────────────────────
        echoTerminal('[8] notifications_mine: el total de no leídas arriba, los totales y una página de Notifications::page()');
        if (!NotificationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 8a-8c: esta instalación tiene Notificaciones apagado y no hay notifications_mine.");
        } else {
            $forma = fn(array $p, string $f): array => ['unreadTotal' => (int) ($p['counts'][Notifications::FILTER_UNREAD] ?? 0), 'totals' => $p['counts'], 'page' => ['filter' => $f, 'size' => count($p['items']), 'hasMore' => $p['hasMore'], 'items' => $p['items']]];
            $mias = $llamar($root, 'notifications_mine', ['filter' => Notifications::FILTER_ALL, 'limit' => 5]);
            $check(($mias['result']['isError'] ?? null) === false && $datos($mias) === json_decode((string) json_encode($forma(Notifications::page($idRoot, Notifications::FILTER_ALL, 1, 5), Notifications::FILTER_ALL)), true), '8a con filtro y límite: unreadTotal, totals y la página de page()');
            $check($datos($llamar($root, 'notifications_mine', [])) === json_decode((string) json_encode($forma(Notifications::page($idRoot, Notifications::FILTER_UNREAD, 1, 10), Notifications::FILTER_UNREAD)), true), '8b sin argumentos: las no leídas, 10');
            //El total no es el tamaño de la página (D3: el modelo leía 10 de una página y eran 12).
            $una = $datos($llamar($root, 'notifications_mine', ['limit' => 1]));
            $sinLeer = Notifications::unread($idRoot);
            $check(is_array($una) && ($una['unreadTotal'] ?? null) === $sinLeer && ($una['page']['size'] ?? null) === min(1, $sinLeer), '8c unreadTotal es el total de no leídas del usuario, no el tamaño de la página', (string) json_encode(['unreadTotal' => $una['unreadTotal'] ?? null, 'size' => $una['page']['size'] ?? null, 'unread' => $sinLeer]));
        }
        echoTerminal(' ');

        //─── A · ids y datos que no se pueden responder ─────────────────────────────────────────────
        echoTerminal('[A] ids que no son de JSON-RPC 2.0 y datos que no son UTF-8 (en proceso; por HTTP, en 9)');
        $idsMalos = ['un float' => 1.5, 'INF' => INF, 'true' => true, 'una lista' => [1]];
        $noRechazados = [];
        foreach ($idsMalos as $caso => $idMalo) {
            $respuesta = PanelServer::handle(['jsonrpc' => '2.0', 'id' => $idMalo, 'method' => 'ping'], $root);
            if (($respuesta['error']['code'] ?? null) !== McpProtocol::INVALID_REQUEST || !array_key_exists('id', $respuesta ?? []) || $respuesta['id'] !== null) {
                $noRechazados[] = $caso;
            }
        }
        $check($noRechazados === [] && ($pedir($root, 'ping', [], 5)['id'] ?? null) === 5 && (PanelServer::handle(['jsonrpc' => '2.0', 'id' => 'zz', 'method' => 'ping'], $root)['id'] ?? null) === 'zz', 'A1 DISCRIMINANTE: un id float, INF, true o una lista → -32600 con id null; un entero o un texto, se devuelven', implode(', ', $noRechazados));
        McpToolRegistry::register($herramienta('zz_prueba_utf8', 'admin', fn(): array => ['texto' => "zz\xB1zz"]));
        $utf8 = $llamar($root, 'zz_prueba_utf8', []);
        $codificable = json_encode($utf8) !== false;
        $estructurado = $utf8['result']['structuredContent'] ?? null;
        $check(($utf8['result']['isError'] ?? null) === false && $codificable && str_contains((string) ($utf8['result']['content'][0]['text'] ?? ''), "zz\u{FFFD}zz") && is_object($estructurado) && ($estructurado->texto ?? null) === "zz\u{FFFD}zz", 'A2 DISCRIMINANTE: una herramienta que devuelve "\\xB1": isError false, el byte sustituido (texto y structuredContent) y la respuesta se puede codificar (sin 500)', $codificable ? '' : 'json_encode da false');
        McpToolRegistry::remove('zz_prueba_utf8');
        echoTerminal(' ');

        //─── E · PanelSearchSource, solo por el usuario de la sesión ───────────────────────────────
        echoTerminal('[E] PanelSearchSource con otro usuario');
        $lanzaLogica = function (callable $accion): bool {
            try {
                $accion();
            } catch (\LogicException $e) {
                return true;
            }
            return false;
        };
        $check($lanzaLogica(fn() => PanelSearchSource::entries($general)) && $lanzaLogica(fn() => PanelSearchSource::find($general, '?', 5)) && !$lanzaLogica(fn() => PanelSearchSource::entries($root)), 'E1 DISCRIMINANTE: con la sesión de root, entries() y find() de otro usuario lanzan \\LogicException; las de root, no');
        echoTerminal(' ');

        //─── G · Los textos, traducibles ───────────────────────────────────────────────────────────
        echoTerminal('[G] Los textos que llegan al cliente, por __() (en inglés, por HTTP en 9)');
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m G0: esta instalación tiene MCP apagado y su diccionario no se carga.");
        } else {
            $check(lang(McpProtocol::LANG_GROUP, 'Herramienta desconocida: ${name}.', 'en') === 'Unknown tool: ${name}.' && McpProtocol::text('Herramienta desconocida: ${name}.', ['${name}' => 'zz']) === 'Herramienta desconocida: zz.', 'G0 el diccionario inglés tiene «Herramienta desconocida» y text() pone el parámetro');
        }
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la parte en proceso corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach ($prueba as $key) {
            McpToolRegistry::remove($key);
        }
        set_config('current_user', $usuarioAntes[0]);
        set_config('pcsphp_current_user_stored', $usuarioAntes[1]);
        set_config('menus', $usuarioAntes[2]);
        $rolActual->setValue(null, $rolAntes);
    }
    $check(array_intersect($prueba, array_keys(McpToolRegistry::all())) === [], 'z0 no queda ninguna herramienta de prueba en el registro');

    //─── 9 · Por HTTP ───────────────────────────────────────────────────────────────────────────────
    if (!McpRoutes::ENABLE) {
        echoTerminal("   \e[33m[OMITIDA]\e[39m 9 (la ruta por HTTP): esta instalación tiene MCP apagado y la ruta mcp-panel-server no existe.");
    } else {
        echoTerminal('[9] La ruta, por HTTP');
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
        if ($base === '') {
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
        $base = rtrim($base, '/');
        $database = (new BaseModel())->getDatabase();
        if ($check($base !== '' && $database !== null, '9 hay una base HTTP para pedir y conexión a la base', $base)) {
            $ruta = function (string $nombre): string {
                $url = get_route($nombre, [], true);
                return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
            };
            $nombreToken = SessionToken::tokenName();
            $http = function (string $path, array $cabeceras, ?string $cuerpo = null, ?array $formulario = null) use ($base): array {
                $handle = \Terminal\TestRun::curlInit();
                $opciones = [
                    CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_HTTPHEADER => $cabeceras,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $formulario !== null ? http_build_query($formulario) : (string) $cuerpo,
                ];
                \Terminal\TestRun::curlSetoptArray($handle, $opciones);
                $respuesta = curl_exec($handle);
                $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                $tamCabecera = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
                //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
                $respuesta = is_string($respuesta) ? $respuesta : '';
                $tipo = '';
                foreach (preg_split('/\R/', substr($respuesta, 0, $tamCabecera)) ?: [] as $linea) {
                    if (stripos($linea, 'content-type:') === 0) {
                        $tipo = trim(substr($linea, 13));
                    }
                }
                $cuerpoRespuesta = substr($respuesta, $tamCabecera);
                return ['status' => $status, 'type' => $tipo, 'body' => $cuerpoRespuesta, 'json' => json_decode($cuerpoRespuesta, true)];
            };
            $mcp = $ruta('mcp-panel-server');
            $prefijo = 'zz-agente-mcp-' . bin2hex(random_bytes(3));
            $clave = bin2hex(random_bytes(12)) . 'Aa1!';
            $usuarios = [];
            $tablaUsuarios = UsersModel::TABLE;
            $tablaIntentos = LoginAttemptsModel::TABLE;
            $maxIntento = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
            $maxLog = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();
            try {
                $u = new UsersModel();
                $u->username = $prefijo;
                $u->email = "{$prefijo}@example.com";
                $u->password = password_hash($clave, \PASSWORD_DEFAULT);
                $u->firstname = 'Zz';
                $u->secondname = '';
                $u->firstLastname = 'Agente';
                $u->secondLastname = '';
                $u->type = UsersModel::TYPE_USER_GENERAL;
                $u->status = UsersModel::STATUS_USER_ACTIVE;
                $u->failedAttempts = 0;
                $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
                $u->createdAt = new \DateTime();
                $u->modifiedAt = $u->createdAt;
                $u->save();
                $usuarios[] = (int) $u->id;
                $acceso = $http($ruta('users-login-request'), ['X-Requested-With: XMLHttpRequest'], null, ['username' => $prefijo, 'password' => $clave, 'twoFactor' => '']);
                $token = is_string($acceso['json']['token'] ?? null) ? $acceso['json']['token'] : '';
                $check($mcp !== '' && $token !== '', '9a CANARIO: la ruta existe y el usuario de la prueba entra', "{$mcp} · HTTP {$acceso['status']}");
                $json = 'Content-Type: application/json';
                $sesion = "{$nombreToken}: {$token}";
                $lista = '{"jsonrpc":"2.0","id":7,"method":"tools/list"}';
                $sin = $http($mcp, [$json], $lista);
                $check($sin['status'] !== 200 && ($sin['json']['jsonrpc'] ?? null) === null, '9b sin sesión: no llega a JSON-RPC', "HTTP {$sin['status']} · " . mb_substr($sin['body'], 0, 120));
                $texto = $http($mcp, ['Content-Type: text/plain', $sesion], $lista);
                $check($texto['status'] === 415 && $texto['body'] === '', '9c con sesión y Content-Type text/plain: 415 sin cuerpo', "HTTP {$texto['status']}");
                $ajeno = $http($mcp, [$json, $sesion, 'Origin: https://zz-otro-sitio.example'], $lista);
                $check($ajeno['status'] === 403 && $ajeno['body'] === '', '9d con un Origin ajeno: 403 sin cuerpo', "HTTP {$ajeno['status']}");
                $bueno = $http($mcp, ['Content-Type: application/json; charset=utf-8', $sesion], $lista);
                $nombres = array_column($bueno['json']['result']['tools'] ?? [], 'name');
                $check($bueno['status'] === 200 && str_starts_with($bueno['type'], 'application/json') && ($bueno['json']['id'] ?? null) === 7 && in_array('panel_find', $nombres, true) && (!NotificationsRoutes::ENABLE || in_array('notifications_mine', $nombres, true)), '9e con sesión y JSON: tools/list responde con las dos', "HTTP {$bueno['status']} · " . implode(', ', $nombres));
                $roto = $http($mcp, [$json, $sesion], '{"jsonrpc":');
                $check(($roto['json']['error']['code'] ?? null) === McpProtocol::PARSE_ERROR, '9f JSON roto: -32700', "HTTP {$roto['status']}");
                $lote = $http($mcp, [$json, $sesion], '[' . $lista . ']');
                $check(($lote['json']['error']['code'] ?? null) === McpProtocol::INVALID_REQUEST, '9g un lote: -32600', "HTTP {$lote['status']}");
                $aviso = $http($mcp, [$json, $sesion], '{"jsonrpc":"2.0","method":"notifications/initialized"}');
                $check($aviso['status'] === 202 && $aviso['body'] === '', '9h una notificación: 202 sin cuerpo', "HTTP {$aviso['status']}");
                //A1 por HTTP: un id desbordado llegaba como INF y daba un 500 con una entrada en el registro de errores.
                $registro = basepath('app/logs/error.log.jsonl');
                $lineas = function () use ($registro): int {
                    clearstatcache(true, $registro);
                    $contenido = is_file($registro) ? (string) file_get_contents($registro) : '';
                    return substr_count($contenido, "\n");
                };
                $antes = $lineas();
                $idsHttp = ['1e999' => '{"jsonrpc":"2.0","id":1e999,"method":"ping"}', 'true' => '{"jsonrpc":"2.0","id":true,"method":"ping"}', '[1]' => '{"jsonrpc":"2.0","id":[1],"method":"ping"}'];
                $malos = [];
                foreach ($idsHttp as $caso => $cuerpo) {
                    $r = $http($mcp, [$json, $sesion], $cuerpo);
                    if ($r['status'] !== 200 || ($r['json']['error']['code'] ?? null) !== McpProtocol::INVALID_REQUEST || !array_key_exists('id', is_array($r['json']) ? $r['json'] : []) || $r['json']['id'] !== null) {
                        $malos[] = "{$caso}: HTTP {$r['status']}";
                    }
                }
                $despues = $lineas();
                $check($malos === [] && $despues === $antes, "A1 HTTP DISCRIMINANTE: id 1e999, true y [1] → HTTP 200, -32600 con id null, y el registro de errores no crece ({$antes} → {$despues} líneas)", implode(', ', $malos));
                //B: el mismo origen, también por Sec-Fetch-Site y sin Origin null.
                $propio = (string) parse_url($base, \PHP_URL_SCHEME) . '://' . (string) parse_url($base, \PHP_URL_HOST) . (parse_url($base, \PHP_URL_PORT) !== null ? ':' . parse_url($base, \PHP_URL_PORT) : '');
                //Con un Referer propio, requestIsSameDomain() aceptaría «Origin: null»: lo frena la comprobación propia.
                $nulo = $http($mcp, [$json, $sesion, 'Origin: null', "Referer: {$base}/admin/"], $lista);
                $check($nulo['status'] === 403 && $nulo['body'] === '', 'B1 DISCRIMINANTE: «Origin: null», aunque el Referer sea propio → 403', "HTTP {$nulo['status']}");
                $cruzado = $http($mcp, [$json, $sesion, "Origin: {$propio}", 'Sec-Fetch-Site: cross-site'], $lista);
                $hermano = $http($mcp, [$json, $sesion, "Origin: {$propio}", 'Sec-Fetch-Site: same-site'], $lista);
                $check($cruzado['status'] === 403 && $hermano['status'] === 403, 'B2 DISCRIMINANTE: Sec-Fetch-Site cross-site y same-site, con el Origin propio → 403', "cross-site HTTP {$cruzado['status']}, same-site HTTP {$hermano['status']}");
                $mismo = $http($mcp, [$json, $sesion, "Origin: {$propio}", 'Sec-Fetch-Site: same-origin'], $lista);
                $check($mismo['status'] === 200 && ($mismo['json']['id'] ?? null) === 7, 'B3 Sec-Fetch-Site same-origin con el Origin propio → pasa (tools/list)', "HTTP {$mismo['status']} · {$propio}");
                //C: el tope del cuerpo.
                $relleno = fn(int $bytes): string => '{"jsonrpc":"2.0","id":9,"method":"ping","zz":"' . str_repeat('a', $bytes - 48) . '"}';
                $grande = $http($mcp, [$json, $sesion], $relleno(65537));
                $justo = $http($mcp, [$json, $sesion], $relleno(65536));
                $check(strlen($relleno(65537)) === 65537 && $grande['status'] === 413 && $justo['status'] === 200 && ($justo['json']['id'] ?? null) === 9, 'C1 DISCRIMINANTE: un cuerpo de 65537 bytes → 413; uno de 65536, se responde', "65537: HTTP {$grande['status']} · 65536: HTTP {$justo['status']}");
                //G1: en inglés.
                $ingles = $http($mcp . '?i18n=en', [$json, $sesion], '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"zz_no_existe","arguments":{}}}');
                $check(($ingles['json']['error']['message'] ?? null) === 'Unknown tool: zz_no_existe.', 'G1 en inglés (?i18n=en), «Herramienta desconocida» sale traducido', (string) ($ingles['json']['error']['message'] ?? "HTTP {$ingles['status']}"));
            } catch (\Throwable $e) {
                $check(false, 'la parte HTTP corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
            } finally {
                try {
                    if ($usuarios !== []) {
                        $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                        $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute($usuarios);
                        $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID IN ({$marcas}) OR usernameAttempt = ?)")->execute(array_merge([$maxIntento], $usuarios, [$prefijo]));
                        $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLog], $usuarios));
                        //Crear el usuario abre su aprobación: sin esto quedaría una fila huérfana por corrida.
                        $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = ? AND referenceValue IN ({$marcas})")->execute(array_merge([$tablaUsuarios], array_map('strval', $usuarios)));
                        $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})")->execute($usuarios);
                    }
                } catch (\Throwable $e) {
                    $check(false, 'z1 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
                }
                $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = ?");
                $consulta->execute([$prefijo]);
                $quedan = (int) $consulta->fetchColumn();
                $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE id > ? AND usernameAttempt = ?");
                $consulta->execute([$maxIntento, $prefijo]);
                $quedan += (int) $consulta->fetchColumn();
                $check($quedan === 0, 'z2 no queda el usuario de la prueba ni sus intentos de acceso', (string) $quedan);
            }
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El servidor MCP del panel (ADR 0075): el registro de herramientas (key, esquema, remove/replace), tools/list y tools/call con los permisos de quien pregunta (sin permiso = no existe), los argumentos contra el esquema, el protocolo, una herramienta que lanza, panel_find contra Ctrl K, el manual en el buscador por permisos y panel_find por palabras y por palabras clave, y notifications_mine contra Notifications::page(); y la ruta por HTTP (sin sesión, 415, Origin ajeno 403, JSON roto, lote, notificación y tools/list). Crea y borra un usuario, sus sesiones y sus intentos de acceso.')->setEffects([CliActions::EFFECT_DATABASE])->register();
