<?php

//Las pantallas de usuarios del prototipo, por HTTP: contadores, búsqueda, alta y edición, y las sesiones (Expulsar y
//Cerrar sesión, sus etiquetas, el filtro por correo, el orden por inicio y las fechas en <time>).
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/users-screens', function ($args) {

    echoTerminal("\e[33m[TEST:UsersScreens] Las pantallas de usuarios del prototipo\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que mail-settings-tests.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, string $jwt, ?array $query = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/') . ($query !== null ? (str_contains($ruta, '?') ? '&' : '?') . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        $datos = json_decode($body, true);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'data' => is_array($datos) ? $datos : []];
    };
    $dom = function (string $html): \DOMXPath {
        $documento = new \DOMDocument();
        @$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new \DOMXPath($documento);
    };
    //Lo que pide DataTables, con las ocho columnas buscables del listado.
    $tabla = function (string $busqueda = '', int $columnas = 8): array {
        $cols = [];
        for ($i = 0; $i < $columnas; $i++) {
            $cols[] = ['data' => $i, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']];
        }
        return ['draw' => 1, 'start' => 0, 'length' => 5000, 'columns' => $cols, 'order' => [['column' => 0, 'dir' => 'desc']], 'search' => ['value' => $busqueda, 'regex' => 'false']];
    };
    $cuantos = fn(mixed $lista): int => $lista instanceof \DOMNodeList ? $lista->length : 0;
    $idsDe = fn(array $r): array => array_map(fn($fila) => (int) ($fila[0] ?? 0), is_array($r['data']['data'] ?? null) ? $r['data']['data'] : []);

    $rutaLista = $camino(UsersController::routeName('list', [], true));
    $rutaDatos = $camino(UsersController::routeName('datatables', [], true));
    $rutaSesiones = $camino(UserSessionsController::routeName('datatables', [], true));

    $prefijo = 'zz-prueba-usuarios-' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, int $type, int $status = UsersModel::STATUS_USER_ACTIVE, ?int $organization = null, string $apellido = 'Usuarios') use ($prefijo, &$ids): UsersModel {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = $apellido;
        $u->secondLastname = '';
        $u->type = $type;
        $u->status = $status;
        $u->failedAttempts = 0;
        $u->organization = $organization ?? OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return $u;
    };

    try {
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        //El listado lo ven root y el administrador general (users-list); este no ve a los root.
        $adminGeneral = $crear('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL);
        $crear('root-2', UsersModel::TYPE_USER_ROOT);
        $jwtRoot = UserSessions::issueToken((int) $root->id);
        $jwtAdminGeneral = UserSessions::issueToken((int) $adminGeneral->id);

        //─── c · Los contadores, con la misma visibilidad que el listado ────────────────────────────────
        echoTerminal('[c] Cada pestaña cuenta lo que su listado enseña a quien mira, y dos tipos ven distinto');
        $estados = ['actives-elements' => UsersModel::STATUS_USER_ACTIVE, 'inactives' => UsersModel::STATUS_USER_INACTIVE, 'all' => null, 'blocked' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'pending' => UsersModel::STATUS_USER_APPROVED_PENDING, 'rejected' => UsersModel::STATUS_USER_REJECTED];
        $totales = [];
        foreach (['root' => $jwtRoot, 'administrador general' => $jwtAdminGeneral] as $quien => $jwt) {
            $x = $dom($pedir($rutaLista, $jwt)['body']);
            $pestanas = [];
            foreach ($x->query('//*[contains(@class,"tabs-controls")]//*[@data-tab]') ?: [] as $item) {
                if ($item instanceof \DOMElement) {
                    $pestanas[] = $item->getAttribute('data-tab');
                }
            }
            $check($pestanas === array_keys($estados), "c1 {$quien}: las pestañas, en el orden del prototipo y abriendo en Activos", json_encode($pestanas) ?: '');
            $iguales = true;
            $detalle = [];
            foreach ($estados as $pestana => $estado) {
                $contador = $x->query('//*[@data-tab="' . $pestana . '"]/*[@data-users-count]')?->item(0);
                $mostrado = $contador instanceof \DOMElement ? (int) trim($contador->textContent) : -1;
                $listado = $pedir($rutaDatos, $jwt, $tabla() + ($estado !== null ? ['with-status' => $estado] : []));
                $cuenta = (int) ($listado['data']['recordsTotal'] ?? -2);
                $iguales = $iguales && $mostrado === $cuenta;
                $detalle[] = "{$pestana} {$mostrado}/{$cuenta}";
                $totales[$quien][$pestana] = $mostrado;
            }
            $check($iguales, "c2 DISCRIMINANTE: {$quien}: cada contador es el total de su listado", implode(', ', $detalle));
        }
        $check(($totales['root']['all'] ?? 0) > ($totales['administrador general']['all'] ?? 0), 'c3 root ve más que el administrador general (que no ve a los root)', json_encode($totales) ?: '');
        echoTerminal(' ');

        //─── s · La búsqueda: nombre completo y organización ────────────────────────────────────────────
        echoTerminal('[s] El buscador encuentra por nombre completo y por organización');
        $apellido = 'Busq' . bin2hex(random_bytes(3));
        $organizacion = $database->query('SELECT id, name FROM `' . OrganizationMapper::TABLE . '` WHERE id <> ' . (int) OrganizationMapper::INITIAL_ID_GLOBAL . ' ORDER BY id DESC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        $buscado = $crear('buscado', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE, is_array($organizacion) ? (int) $organizacion['id'] : null, $apellido);
        $r = $pedir($rutaDatos, $jwtRoot, $tabla("Zz {$apellido}"));
        $check(in_array((int) $buscado->id, $idsDe($r), true), 's1 DISCRIMINANTE: «nombre apellido» (el nombre completo) lo encuentra', "HTTP {$r['status']}");
        if ($check(is_array($organizacion), 'banco: hay una organización que no es la base')) {
            $r = $pedir($rutaDatos, $jwtRoot, $tabla((string) $organizacion['name']));
            $check(in_array((int) $buscado->id, $idsDe($r), true), 's2 DISCRIMINANTE: el nombre de su organización lo encuentra', "HTTP {$r['status']}, " . count($idsDe($r)) . ' filas');
        }
        $bloqueado = $crear('bloqueado', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ATTEMPTS_BLOCK);
        $r = $pedir($rutaDatos, $jwtRoot, $tabla((string) $bloqueado->username) + ['with-status' => UsersModel::STATUS_USER_ATTEMPTS_BLOCK]);
        $tarjeta = $dom(implode('', is_array($r['data']['rawData'] ?? null) ? $r['data']['rawData'] : []))->query('//*[@data-user-status]')?->item(0);
        $check($tarjeta instanceof \DOMElement && trim($tarjeta->textContent) === __(UsersController::LANG_GROUP, 'Bloqueado') && $tarjeta->getAttribute('title') === UsersModel::statuses()[UsersModel::STATUS_USER_ATTEMPTS_BLOCK], 's3 la tarjeta dice «Bloqueado» y la descripción larga va en el title', $tarjeta instanceof \DOMElement ? trim($tarjeta->textContent) . ' / ' . $tarjeta->getAttribute('title') : 'sin tarjeta');
        echoTerminal(' ');

        //─── f · Agregar y editar ───────────────────────────────────────────────────────────────────────
        echoTerminal('[f] Agregar: migas y contraseñas obligatorias; Editar: no, «Sin foto» y el orden de Sesiones');
        $alta = $pedir($camino(UsersController::routeName('form-create', ['type' => UsersController::signedType(UsersModel::TYPE_USER_GENERAL)], true)), $jwtRoot);
        $x = $dom($alta['body']);
        $obligatoria = fn(\DOMXPath $x, string $nombre): bool => $cuantos($x->query('//input[@name="' . $nombre . '"][@required]')) === 1 && $cuantos($x->query('//input[@name="' . $nombre . '"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " required ") and contains(concat(" ", normalize-space(@class), " "), " field ")]')) === 1;
        $check($alta['status'] === 200 && $obligatoria($x, 'password') && $obligatoria($x, 'password2'), 'f1 DISCRIMINANTE: al crear, Contraseña y Confirmar llevan el asterisco (required field) y required', "HTTP {$alta['status']}");
        $migas = array_map(fn($n) => $n instanceof \DOMElement ? trim($n->textContent) : '', iterator_to_array($x->query('//*[contains(@class,"breadcrumb")]//*[contains(@class,"section")]') ?: []));
        $check($migas === [__(UsersController::LANG_GROUP, 'Inicio'), __(UsersController::LANG_GROUP, 'Tipo de usuario'), __(UsersController::LANG_GROUP, 'Agregar usuario')], 'f2 las migas: Inicio › Tipo de usuario › Agregar usuario', json_encode($migas, \JSON_UNESCAPED_UNICODE) ?: '');
        $check($cuantos($x->query('//*[contains(@class,"user-form-component")]//*[contains(@class,"secondary menu")]')) === 0, 'f3 al crear, sin barra de pestañas (una sola)');
        $marcador = $x->query('//select[@name="organization"]/option[@value=""]')?->item(0);
        $check(!($marcador instanceof \DOMElement) || ($marcador->hasAttribute('disabled') && trim($marcador->textContent) === __(UsersController::LANG_GROUP, 'Selecciona una organización')), 'f4 la organización, con «Selecciona una organización» deshabilitada como marcador');
        $edicion = $pedir($camino(UsersController::routeName('form-edit', ['id' => $buscado->id], true)), $jwtRoot);
        $x = $dom($edicion['body']);
        $check($edicion['status'] === 200 && !$obligatoria($x, 'password') && !$obligatoria($x, 'password2'), 'f5 al editar, las contraseñas no son obligatorias', "HTTP {$edicion['status']}");
        $vacio = $x->query('//*[@data-image-card-empty]')?->item(0);
        $check($vacio instanceof \DOMElement && trim($vacio->textContent) === __(UsersController::LANG_GROUP, 'Sin foto'), 'f6 DISCRIMINANTE: sin foto, la tarjeta dice «Sin foto»');
        $iconos = [];
        foreach ($x->query('//*[contains(@class,"user-form-component")]//*[contains(@class,"secondary menu")]/a/i') ?: [] as $icono) {
            if ($icono instanceof \DOMElement) {
                $iconos[] = $icono->getAttribute('class');
            }
        }
        $check($iconos === ['user icon', 'image icon', 'desktop icon'], 'f7 los iconos de las pestañas: user, image, desktop', json_encode($iconos) ?: '');
        $cerrarTodas = strpos($edicion['body'], 'data-user-sessions-close-all');
        $tablaSesiones = strpos($edicion['body'], 'data-user-sessions-table');
        $check($cerrarTodas !== false && $tablaSesiones !== false && $cerrarTodas < $tablaSesiones, 'f8 DISCRIMINANTE: Sesiones, primero «Cerrar todas sus sesiones» y después la tabla');
        $check(str_contains($edicion['body'], 'userID=' . $buscado->id), 'f9 la tabla pide las sesiones de ESE usuario (userID)');
        echoTerminal(' ');

        //─── x · Las sesiones: el filtro exacto y «Expulsar» ────────────────────────────────────────────
        echoTerminal('[x] La tabla de la ficha: solo las de ese usuario, y «Expulsar» en rojo');
        $uno = $crear('s1', UsersModel::TYPE_USER_GENERAL);
        $diez = $crear('s10', UsersModel::TYPE_USER_GENERAL);
        UserSessions::issueToken((int) $uno->id);
        UserSessions::issueToken((int) $diez->id);
        $r = $pedir($rutaSesiones, $jwtRoot, $tabla('', 7) + ['userID' => $uno->id]);
        $check((int) ($r['data']['recordsTotal'] ?? -1) === 1, "x1 DISCRIMINANTE: con userID, solo la suya (el nombre «{$prefijo}-s1» es parte de «…-s10»)", (string) ($r['data']['recordsTotal'] ?? $r['status']));
        $acciones = (string) (($r['data']['data'][0] ?? [])[6] ?? '');
        $check(str_contains($acciones, 'ui negative basic icon button') && str_contains($acciones, 'aria-label="' . __(UserSessionsController::LANG_GROUP, 'Expulsar') . '"') && str_contains($acciones, 'sign out alternate'), 'x2 DISCRIMINANTE: «Expulsar», en rojo y con su icono', mb_substr($acciones, 0, 200));
        $check(str_contains($acciones, 'calendar plus'), 'x3 «Extender», con el icono calendar plus');
        $etiquetasDe = fn(string $celda): array => [str_contains($celda, '>' . __(UserSessionsController::LANG_GROUP, 'Actual') . '<'), str_contains($celda, '>' . __(UserSessionsController::LANG_GROUP, 'Otras sesiones') . '<')];
        $check($etiquetasDe((string) (($r['data']['data'][0] ?? [])[0] ?? '')) === [false, false], 'x4 en la sesión de otro usuario, sin etiqueta');
        echoTerminal(' ');

        //─── y · Las propias: «Cerrar sesión» y sus etiquetas; buscar por correo; el orden; las fechas en <time> ───
        echoTerminal('[y] Las sesiones propias, el filtro por correo, el orden por inicio y las fechas');
        UserSessions::issueToken((int) $root->id);
        $r = $pedir($rutaSesiones, $jwtRoot, $tabla('', 7) + ['userID' => $root->id]);
        $filas = is_array($r['data']['data'] ?? null) ? $r['data']['data'] : [];
        $porEtiqueta = [];
        foreach ($filas as $fila) {
            $porEtiqueta[json_encode($etiquetasDe((string) ($fila[0] ?? '')))] = (string) ($fila[6] ?? '');
        }
        $cerrar = 'aria-label="' . __(UserSessionsController::LANG_GROUP, 'Cerrar sesión') . '"';
        $expulsar = 'aria-label="' . __(UserSessionsController::LANG_GROUP, 'Expulsar') . '"';
        $check(count($filas) === 2 && isset($porEtiqueta['[true,false]'], $porEtiqueta['[false,true]']), 'y1 DISCRIMINANTE: la de esta petición, «Actual»; la otra del mismo usuario, «Otras sesiones»', json_encode(array_keys($porEtiqueta)) ?: '');
        $check(count($porEtiqueta) === 2 && array_filter($porEtiqueta, fn($a) => !str_contains($a, $cerrar) || str_contains($a, $expulsar)) === [], 'y2 DISCRIMINANTE: en las propias, «Cerrar sesión» y no «Expulsar»', mb_substr((string) reset($porEtiqueta), 0, 200));
        $r = $pedir($rutaSesiones, $jwtRoot, $tabla('', 7) + ['user' => "{$prefijo}-s1@"]);
        $usuarios = array_map(fn($fila) => strip_tags(explode('<small', (string) ($fila[0] ?? ''))[0]), is_array($r['data']['data'] ?? null) ? $r['data']['data'] : []);
        $check($usuarios === ["{$prefijo}-s1"], 'y3 DISCRIMINANTE: el filtro Usuario encuentra por correo (un trozo que solo está en el correo)', json_encode($usuarios, \JSON_UNESCAPED_UNICODE) ?: '');
        //La más antigua por inicio es la más reciente por actividad: así el orden viejo (por actividad) da la contraria.
        $previas = $database->query('SELECT id FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ' . (int) $diez->id)->fetchAll(\PDO::FETCH_COLUMN);
        UserSessions::issueToken((int) $diez->id);
        $database->prepare('UPDATE `' . UserSessionsModel::TABLE . '` SET createdAt = ?, lastActivityAt = ? WHERE userID = ? AND id IN (' . implode(',', array_map('intval', $previas) ?: [0]) . ')')->execute([date('Y-m-d H:i:s', time() - 7200), date('Y-m-d H:i:s'), $diez->id]);
        $database->prepare('UPDATE `' . UserSessionsModel::TABLE . '` SET lastActivityAt = ? WHERE userID = ? AND id NOT IN (' . implode(',', array_map('intval', $previas) ?: [0]) . ')')->execute([date('Y-m-d H:i:s', time() - 3600), $diez->id]);
        $sinOrden = $tabla('', 7) + ['userID' => $diez->id];
        unset($sinOrden['order']);
        $r = $pedir($rutaSesiones, $jwtRoot, $sinOrden);
        $inicios = array_map(fn($fila) => preg_match('/datetime="([^"]+)"/', (string) ($fila[3] ?? ''), $m) === 1 ? $m[1] : '', is_array($r['data']['data'] ?? null) ? $r['data']['data'] : []);
        $check(count($inicios) === 2 && $inicios[0] !== '' && strcmp($inicios[0], $inicios[1]) > 0, 'y4 DISCRIMINANTE: sin orden pedido, la más reciente por inicio primero', json_encode($inicios) ?: '');
        $patronTime = '/^<time datetime="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}">\d{2}-\d{2}-\d{4} \d{2}:\d{2}<\/time>$/';
        $fila = ($r['data']['data'][0] ?? []);
        $check(preg_match($patronTime, (string) ($fila[3] ?? '')) === 1 && preg_match($patronTime, (string) ($fila[4] ?? '')) === 1 && preg_match($patronTime, (string) ($fila[5] ?? '')) === 1, 'y5 DISCRIMINANTE: Inicio, Última actividad y Caduca, en <time datetime>', mb_substr((string) ($fila[3] ?? ''), 0, 120));
        if (class_exists(\MySpace\Controllers\MySpaceController::class) && \MySpace\Controllers\MySpaceController::routeName('user-security') !== '') {
            $mias = $pedir($camino(\MySpace\Controllers\MySpaceController::routeName('user-security', [], true)), $jwtRoot);
            $x = $dom($mias['body']);
            $actual = $x->query('//*[@data-sessions-list]//tr[@data-session-current="yes"]//*[contains(@class,"label")]')?->item(0);
            $otra = $x->query('//*[@data-sessions-list]//tr[@data-session-current="no"]')?->item(0);
            $check($mias['status'] === 200 && $actual instanceof \DOMElement && trim($actual->textContent) === __(UserSessionsController::LANG_GROUP, 'Actual'), 'y6 DISCRIMINANTE: Mis sesiones, la de esta petición con «Actual»', "HTTP {$mias['status']}");
            $check($otra instanceof \DOMElement && !str_contains($otra->textContent, __(UserSessionsController::LANG_GROUP, 'Otras sesiones')) && str_contains($mias['body'], $cerrar) && $cuantos($x->query('//*[@data-sessions-list]//time[@datetime]')) === 6, 'y7 Mis sesiones: la otra, sin etiqueta y con «Cerrar sesión»; las seis fechas en <time datetime>');
        } else {
            echoTerminal('   [OMITIDO] y6-y7: el módulo MySpace no tiene Mis sesiones');
        }
        $ingles = require basepath('app/classes/PiecesPHP/UserSystem/lang/en.php');
        $check(is_array($ingles) && ($ingles['Expulsar'] ?? '') !== '' && ($ingles['Expulsar'] ?? '') !== ($ingles['Cerrar sesión'] ?? ''), 'y8 DISCRIMINANTE: en inglés, «Expulsar» y «Cerrar sesión» no dicen lo mismo', json_encode([$ingles['Expulsar'] ?? null, $ingles['Cerrar sesión'] ?? null]) ?: '');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no quedan los usuarios de la prueba ni sus sesiones', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las pantallas de usuarios del prototipo, por HTTP: contadores con la visibilidad del listado, búsqueda por nombre completo y organización, «Bloqueado», alta (migas, contraseñas obligatorias) y edición («Sin foto», iconos, orden de Sesiones), y la tabla de sesiones de la ficha con «Expulsar»; las propias con «Cerrar sesión», «Actual» y «Otras sesiones», el filtro por correo, el orden por inicio y las fechas en <time> (también en Mis sesiones).')->setEffects([CliActions::EFFECT_DATABASE])->register();
