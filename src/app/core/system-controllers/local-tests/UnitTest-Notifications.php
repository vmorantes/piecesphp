<?php

//El canal de notificaciones (ADR 0073): notify() en proceso; la lista y las marcas, por HTTP, como las suites de sesiones.
//Crea usuarios y noticias zz-prueba-notif-*, y sus notificaciones; lo retira todo y lo comprueba.
//@areas src/app/classes/PiecesPHP/Notifications/
//@areas src/app/classes/News/

use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\Mappers\NewsReadedMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Notifications\Controllers\NotificationsController;
use PiecesPHP\Notifications\Mappers\NotificationMapper;
use PiecesPHP\Notifications\NotificationMessage;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationsLang;
use PiecesPHP\Notifications\Sources\NewsNotificationSource;
use PiecesPHP\Notifications\Sources\TableNotificationSource;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\AccountChangeNotice;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/notifications', function ($args) {

    echoTerminal("\e[33m[TEST:Notifications] El canal de notificaciones por usuario y las noticias como fuente\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que session-revocation.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $bd = Notifications::database();
    if (!$check($base !== '', 'p1 PRE: hay una base HTTP para pedir', $base)) {
        return $balance();
    }

    $ruta = function (string $nombre): string {
        $url = (string) NotificationsController::routeName($nombre, [], true);
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $metodo, string $path, ?string $jwt, array $datos = [], bool $xhr = true) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $url = $base . '/' . ltrim($path, '/') . ($metodo === 'GET' && $datos !== [] ? '?' . http_build_query($datos) : '');
        $opciones = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge($jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [], $xhr ? ['X-Requested-With: XMLHttpRequest'] : []),
        ];
        if ($metodo === 'POST') {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($datos);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => $body, 'json' => json_decode($body, true), 'location' => (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL)];
    };

    $prefijo = 'zz-prueba-notif-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $noticias = [];
    $crear = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Notificaciones';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $noticia = function (string $sufijo, array $perfiles) use ($prefijo, &$noticias): int {
        $n = new NewsMapper();
        $n->newsTitle = "{$prefijo}-{$sufijo}";
        $n->content = '<p>Zz noticia de prueba.</p>';
        $n->category = NewsCategoryMapper::UNCATEGORIZED_ID;
        $n->folder = "{$prefijo}-{$sufijo}";
        $n->createdBy = 1;
        $n->status = NewsMapper::ACTIVE;
        $n->profilesTarget = $perfiles;
        $n->startDate = (new \DateTime())->modify('-1 day');
        $n->endDate = (new \DateTime())->modify('+1 day');
        //El mapper pide un usuario actual al guardar: se le presta root, como al crear organizaciones en session-revocation.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => 1]);
            set_config('pcsphp_current_user_stored', null);
            $n->save();
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $noticias[$sufijo] = (int) $n->id;
        return (int) $n->id;
    };
    $filas = function (int $usuario) use ($bd): array {
        $consulta = $bd->prepare('SELECT id, kind, readAt, createdAt FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ? ORDER BY id');
        $consulta->execute([$usuario]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    };
    $mensaje = fn(string $kind, array $params = []) => new NotificationMessage(kind: $kind, level: NotificationMessage::LEVEL_INFO, langGroup: NotificationsLang::LANG_GROUP, titleKey: 'Notificaciones', bodyKey: 'Hola %name%', params: $params);
    $registroErrores = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
    $lineasRegistro = fn(): int => is_file($registroErrores) ? count(file($registroErrores) ?: []) : 0;
    $idiomaPrevio = get_config('app_lang');
    $ultimoLog = (int) $bd->query('SELECT COALESCE(MAX(id), 0) FROM `' . \EventsLog\Mappers\LogsMapper::TABLE . '`')->fetchColumn();

    try {
        $a = $crear('a', UsersModel::TYPE_USER_GENERAL);
        $b = $crear('b', UsersModel::TYPE_USER_GENERAL);
        $jwtA = UserSessions::issueToken($a);
        $jwtB = UserSessions::issueToken($b);
        $check($a > 0 && $b > 0, 'banco: dos usuarios de prueba');

        //─── n1 · notify ────────────────────────────────────────────────────────────────────────
        echoTerminal('[n1] notify() crea una por usuario válido y descarta el resto sin lanzar');
        $creadas = Notifications::notify([$a, $b], $mensaje('zz-n1'));
        $check($creadas === 2 && count($filas($a)) === 1 && count($filas($b)) === 1, 'n1 dos usuarios, dos filas', "devolvió {$creadas}");
        $inexistente = (int) $bd->query('SELECT COALESCE(MAX(id), 0) + 1000 FROM `' . UsersModel::TABLE . '`')->fetchColumn();
        $creadas = Notifications::notify([$a, $inexistente], $mensaje('zz-n1b'));
        $check($creadas === 1 && count($filas($a)) === 2, 'n1 con un id que no existe, crea la válida y no lanza', "devolvió {$creadas}");
        echoTerminal(' ');

        //─── n2 · el idioma de quien lee ──────────────────────────────────────────────────────────
        echoTerminal('[n2] El texto se pinta al leer, en el idioma de la petición, con sus parámetros');
        Notifications::notify($a, $mensaje('zz-n2', ['%name%' => 'Ana']));
        $fuente = new TableNotificationSource();
        try {
            set_config('app_lang', 'es');
            $es = $fuente->items($a, null, 1)[0] ?? [];
            set_config('app_lang', 'en');
            $en = $fuente->items($a, null, 1)[0] ?? [];
        } finally {
            set_config('app_lang', $idiomaPrevio);
        }
        $check(($es['title'] ?? null) === 'Notificaciones' && ($en['title'] ?? null) === 'Notifications', 'n2 DISCRIMINANTE: el título, en el idioma de quien lee', ($es['title'] ?? '?') . ' / ' . ($en['title'] ?? '?'));
        $check(($es['body'] ?? null) === 'Hola Ana' && ($en['body'] ?? null) === 'Hola Ana', 'n2 y los parámetros se sustituyen', (string) ($es['body'] ?? '?'));
        echoTerminal(' ');

        //─── n3 · un fallo no tumba a quien llama ─────────────────────────────────────────────────
        echoTerminal('[n3] Un fallo devuelve 0, deja su línea en el registro de errores y no lanza');
        $antes = $lineasRegistro();
        $filasAntes = count($filas($a));
        $lanzo = false;
        try {
            $resultado = Notifications::notify($a, $mensaje('zz-n3', ['%name%' => "\xB1\x31"]));
        } catch (\Throwable $e) {
            $lanzo = true;
            $resultado = -1;
        }
        $check(!$lanzo && $resultado === 0 && count($filas($a)) === $filasAntes, 'n3 DISCRIMINANTE: un mensaje que no se puede guardar devuelve 0 y no lanza', "devolvió {$resultado}");
        $check($lineasRegistro() === $antes + 1, 'n3 y deja una línea en error.log.jsonl', "{$antes} → " . $lineasRegistro());
        echoTerminal(' ');

        //─── n4 · la lista ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[n4] La lista: los cuatro filtros, los contadores y la paginación');
        //Una leída para A, y una noticia visible para un general.
        $visible = $noticia('visible', [UsersModel::TYPE_USER_GENERAL]);
        $primera = (int) ($filas($a)[0]['id'] ?? 0);
        $bd->prepare('UPDATE `' . NotificationMapper::TABLE . '` SET readAt = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $primera]);
        $listar = fn(string $filtro, int $pagina = 1, int $porPagina = 10) => $pedir('GET', $ruta('list'), $jwtA, ['filter' => $filtro, 'page' => $pagina, 'perPage' => $porPagina]);
        $todas = $listar('all');
        $contadores = $todas['json']['counts'] ?? [];
        $propias = count($filas($a));
        $deNoticias = (int) ($contadores['news'] ?? -1);
        $check($todas['status'] === 200 && is_array($todas['json']['items'] ?? null), 'n4 la lista responde', "HTTP {$todas['status']} " . mb_substr($todas['body'], 0, 160));
        $check(isset($contadores['all'], $contadores['news'], $contadores['unread'], $contadores['read']) && $contadores['all'] === $propias + $deNoticias && $contadores['read'] + $contadores['unread'] === $contadores['all'],
            'n4 los contadores cuadran: all = sus notificaciones + noticias, y leídas + no leídas = all', (string) json_encode($contadores));
        $soloNoticias = $listar('news');
        $check($soloNoticias['status'] === 200 && array_filter($soloNoticias['json']['items'] ?? [], fn($i) => ($i['source'] ?? '') !== NewsNotificationSource::KEY) === [] && in_array((string) $visible, array_column($soloNoticias['json']['items'] ?? [], 'id'), true),
            'n4 «news» solo trae noticias, y la visible está', mb_substr($soloNoticias['body'], 0, 160));
        $noLeidas = $listar('unread');
        $leidas = $listar('read');
        $check(array_filter($noLeidas['json']['items'] ?? [], fn($i) => ($i['read'] ?? true) !== false) === [] && array_filter($leidas['json']['items'] ?? [], fn($i) => ($i['read'] ?? false) !== true) === [] && in_array((string) $primera, array_column($leidas['json']['items'] ?? [], 'id'), true),
            'n4 «unread» y «read» filtran por su estado');
        $item = ($todas['json']['items'] ?? [])[0] ?? [];
        $check(array_keys($item) === ['source', 'id', 'level', 'icon', 'title', 'body', 'createdAt', 'read', 'url', 'news'] && \DateTime::createFromFormat(\DATE_ATOM, (string) ($item['createdAt'] ?? '')) !== false,
            'n4 cada elemento trae sus campos, con la fecha en ISO', (string) json_encode($item));
        $pagina1 = $listar('all', 1, 2);
        $ultima = $listar('all', (int) ceil($contadores['all'] / 2), 2);
        $check(count($pagina1['json']['items'] ?? []) === 2 && ($pagina1['json']['hasMore'] ?? null) === true && ($ultima['json']['hasMore'] ?? null) === false,
            'n4 paginación: hasMore en la primera y no en la última', (string) json_encode([$pagina1['json']['hasMore'] ?? null, $ultima['json']['hasMore'] ?? null]));
        $check($pedir('GET', $ruta('list'), $jwtA, ['filter' => 'zz-no-existe'])['status'] === 400, 'n4 un filtro que no existe responde 400');
        echoTerminal(' ');

        //─── n5 · lo ajeno ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[n5] Marcar una ajena responde 404 y no cambia nada');
        $deB = (int) ($filas($b)[0]['id'] ?? 0);
        $r = $pedir('POST', $ruta('mark-read'), $jwtA, ['source' => TableNotificationSource::KEY, 'id' => $deB]);
        $filaDeB = $filas($b)[0] ?? null;
        $check($r['status'] === 404 && is_array($filaDeB) && $filaDeB['readAt'] === null, 'n5 DISCRIMINANTE: la de otro, 404 y sigue sin leer', "HTTP {$r['status']}");
        $propia = (int) ($filas($a)[1]['id'] ?? 0);
        $r = $pedir('POST', $ruta('mark-read'), $jwtA, ['source' => TableNotificationSource::KEY, 'id' => $propia]);
        $check($r['status'] === 200 && ($filas($a)[1]['readAt'] ?? null) !== null, 'n5 CANARIO: la propia se marca', "HTTP {$r['status']}");
        $check($pedir('POST', $ruta('mark-read'), $jwtA, ['source' => 'zz-fuente', 'id' => $propia])['status'] === 404, 'n5 una fuente que no existe, 404');
        echoTerminal(' ');

        //─── n6 · todas ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[n6] Marcar todas marca las de las dos fuentes, y solo las suyas');
        $r = $pedir('POST', $ruta('mark-all-read'), $jwtA);
        $sinLeer = array_filter($filas($a), fn($f) => $f['readAt'] === null);
        $leidaNoticia = (int) $bd->query('SELECT COUNT(*) FROM `' . NewsReadedMapper::TABLE . "` WHERE readerUser = {$a} AND news = {$visible}")->fetchColumn();
        $check($r['status'] === 200 && $sinLeer === [] && $leidaNoticia === 1 && (($r['json']['counts']['unread'] ?? -1) === 0),
            'n6 DISCRIMINANTE: las suyas y la noticia, leídas; ninguna sin leer', "HTTP {$r['status']} · sin leer " . count($sinLeer) . " · noticia {$leidaNoticia}");
        $deBSinLeer = array_filter($filas($b), fn($f) => $f['readAt'] === null);
        $noticiaDeB = (int) $bd->query('SELECT COUNT(*) FROM `' . NewsReadedMapper::TABLE . "` WHERE readerUser = {$b}")->fetchColumn();
        $check(count($deBSinLeer) === count($filas($b)) && $noticiaDeB === 0, 'n6 CANARIO: lo del otro usuario, intacto');
        $pedir('POST', $ruta('mark-all-read'), $jwtA);
        $check((int) $bd->query('SELECT COUNT(*) FROM `' . NewsReadedMapper::TABLE . "` WHERE readerUser = {$a} AND news = {$visible}")->fetchColumn() === 1, 'n6 repetirlo no duplica la lectura de la noticia');
        echoTerminal(' ');

        //─── n7 · la purga ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[n7] Al escribir se purgan las leídas de más de ' . NotificationMapper::RETENTION_DAYS . ' días; las no leídas se quedan');
        $vieja = date('Y-m-d H:i:s', time() - (NotificationMapper::RETENTION_DAYS + 1) * 86400);
        $insertar = $bd->prepare('INSERT INTO `' . NotificationMapper::TABLE . '` (`user`, kind, level, langGroup, titleKey, createdAt, readAt) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insertar->execute([$b, 'zz-vieja-leida', 'info', NotificationsLang::LANG_GROUP, 'Notificaciones', $vieja, $vieja]);
        $viejaLeida = (int) $bd->lastInsertId();
        $insertar->execute([$b, 'zz-vieja-sin-leer', 'info', NotificationsLang::LANG_GROUP, 'Notificaciones', $vieja, null]);
        $viejaSinLeer = (int) $bd->lastInsertId();
        Notifications::notify($a, $mensaje('zz-n7'));
        $ids = array_map(fn($f) => (int) $f['id'], $filas($b));
        $check(!in_array($viejaLeida, $ids, true), 'n7 DISCRIMINANTE: la leída de hace ' . (NotificationMapper::RETENTION_DAYS + 1) . ' días desapareció');
        $check(in_array($viejaSinLeer, $ids, true), 'n7 la no leída igual de vieja se queda');
        echoTerminal(' ');

        //─── n8 · las noticias que no le llegan ────────────────────────────────────────────────────
        echoTerminal('[n8] Una noticia que no le llega no aparece ni se puede marcar');
        $oculta = $noticia('oculta', [UsersModel::TYPE_USER_INSTITUCIONAL]);
        $lista = $pedir('GET', $ruta('list'), $jwtB, ['filter' => 'news', 'perPage' => 50]);
        $idsNoticias = array_column($lista['json']['items'] ?? [], 'id');
        $check(in_array((string) $visible, $idsNoticias, true), 'n8 CANARIO: la noticia dirigida a su tipo sí aparece');
        $check(!in_array((string) $oculta, $idsNoticias, true), 'n8 DISCRIMINANTE: la dirigida a otro tipo no aparece');
        $r = $pedir('POST', $ruta('mark-read'), $jwtB, ['source' => NewsNotificationSource::KEY, 'id' => $oculta]);
        $marcada = (int) $bd->query('SELECT COUNT(*) FROM `' . NewsReadedMapper::TABLE . "` WHERE readerUser = {$b} AND news = {$oculta}")->fetchColumn();
        $check($r['status'] === 404 && $marcada === 0, 'n8 DISCRIMINANTE: y no se puede marcar: 404, sin fila de lectura', "HTTP {$r['status']} · filas {$marcada}");
        echoTerminal(' ');

        //─── n9 · un enlace a una ruta que ya no existe ────────────────────────────────────────────
        echoTerminal('[n9] Un destino que ya no existe da url null, sin lanzar');
        Notifications::notify($a, new NotificationMessage(kind: 'zz-n9', level: NotificationMessage::LEVEL_WARNING, langGroup: NotificationsLang::LANG_GROUP, titleKey: 'Notificaciones', routeName: 'zz-ruta-que-no-existe'));
        $conRutaFalsa = array_values(array_filter((new TableNotificationSource())->items($a, null, 50), fn($i) => $i['level'] === NotificationMessage::LEVEL_WARNING));
        $check(count($conRutaFalsa) === 1 && $conRutaFalsa[0]['url'] === null, 'n9 la ruta que no existe da url null', (string) json_encode($conRutaFalsa));
        $check(Notifications::url('notifications-list', [], UsersModel::TYPE_USER_GENERAL) !== null, 'n9 CANARIO: una ruta que existe da su url');
        $deNoticia = array_values(array_filter($pedir('GET', $ruta('list'), $jwtB, ['filter' => 'news'])['json']['items'] ?? [], fn($i) => ($i['id'] ?? '') === (string) $visible))[0] ?? [];
        $check(is_array($deNoticia['news'] ?? null) && ($deNoticia['news']['title'] ?? '') === "{$prefijo}-visible" && str_contains((string) ($deNoticia['news']['content'] ?? ''), 'Zz noticia'),
            'n9 una noticia trae lo que pinta su ventana: título y contenido', (string) json_encode($deNoticia['news'] ?? null));
        echoTerminal(' ');

        //─── n10 · la campana en la barra ──────────────────────────────────────────────────────────
        echoTerminal('[n10] La campana sale en el panel con sesión, con la insignia y su número');
        $sinLeerA = Notifications::counts($a)[Notifications::FILTER_UNREAD];
        $sinLeerB = Notifications::counts($b)[Notifications::FILTER_UNREAD];
        //En la vista central, que lleva la barra: el inicio manda a un general sin perfil completo a completarlo (302).
        $pagina = $pedir('GET', $ruta('view'), $jwtB, [], false);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pagina['body']);
        $xpath = new \DOMXPath($dom);
        $insignia = $xpath->query('//*[@notifications-toggle]//*[@notifications-badge]')?->item(0);
        $campana = $xpath->query('//*[@notifications-toggle]')?->item(0);
        $check($pagina['status'] === 200 && $insignia instanceof \DOMElement, 'n10 la campana y su insignia están en la página', "HTTP {$pagina['status']}");
        $check($insignia instanceof \DOMElement && trim($insignia->textContent) === (string) $sinLeerB && $insignia->hasAttribute('hidden') === ($sinLeerB === 0),
            'n10 DISCRIMINANTE: la insignia dice sus no leídas, y se oculta con 0', ($insignia instanceof \DOMElement ? $insignia->textContent : '?') . " / {$sinLeerB}");
        $check($campana instanceof \DOMElement && str_contains($campana->getAttribute('aria-label'), (string) $sinLeerB), 'n10 y el aria-label lleva el número', $campana instanceof \DOMElement ? $campana->getAttribute('aria-label') : '');
        $controla = $campana instanceof \DOMElement ? $campana->getAttribute('aria-controls') : '';
        $check($controla !== '' && $xpath->query('//*[@id="' . $controla . '"][@notifications-popup]')?->length === 1 && $campana instanceof \DOMElement && $campana->getAttribute('aria-expanded') === 'false',
            'n10 la campana dice qué abre: aria-controls al id del popup, y aria-expanded', $controla);
        $check($xpath->query('//*[@notifications-popup][@notifications-list]')?->length === 1 && $xpath->query('//*[@news-popup]')?->length === 0, 'n10 el popup de la campana sustituye al de noticias');
        $chips = $xpath->query('//*[@notifications-popup]//*[contains(concat(" ", normalize-space(@class), " "), " chip labels ")][@role="tablist"]/button[@role="tab"][@notifications-filter]');
        $docked = $xpath->query('//*[@notifications-popup][contains(concat(" ", normalize-space(@class), " "), " docked ")]');
        $check($chips?->length === count(Notifications::filters()) && $docked?->length === 1, 'n10 el popup es el del patrón del tema: docked, con un chip por filtro', ($chips instanceof \DOMNodeList ? $chips->length : 0) . ' chips');
        echoTerminal(' ');

        //─── n11 · la vista central ────────────────────────────────────────────────────────────────
        echoTerminal('[n11] La vista «Todas las notificaciones»: con sesión, la página; sin ella, al acceso');
        $vista = $pedir('GET', $ruta('view'), $jwtA, [], false);
        $check($vista['status'] === 200 && str_contains($vista['body'], 'notifications-view-list'), 'n11 con sesión responde 200 con su lista', "HTTP {$vista['status']}");
        $sinSesion = $pedir('GET', $ruta('view'), null, [], false);
        $check($sinSesion['status'] === 302 && $sinSesion['location'] !== '', 'n11 sin sesión redirige', "HTTP {$sinSesion['status']} → {$sinSesion['location']}");
        echoTerminal(' ');

        //─── n12 · sin noticias, sin su pestaña ────────────────────────────────────────────────────
        //NEWS_MODULE es una constante y no se cambia en una prueba: se quita la fuente, que es lo que decide la pestaña.
        echoTerminal('[n12] Sin la fuente de noticias no hay pestaña «news» (filters() decide; NEWS_MODULE no se puede cambiar aquí)');
        $fuenteNoticias = Notifications::source(NewsNotificationSource::KEY);
        $check($fuenteNoticias !== null && in_array(Notifications::FILTER_NEWS, Notifications::filters(), true), 'n12 CANARIO: con la fuente, la pestaña está');
        Notifications::remove(NewsNotificationSource::KEY);
        try {
            $sinFiltro = !in_array(Notifications::FILTER_NEWS, Notifications::filters(), true);
            $marcado = NotificationsController::listMarkup('zz-lista', true);
            $sinContador = !array_key_exists(Notifications::FILTER_NEWS, Notifications::counts($a));
        } finally {
            if ($fuenteNoticias !== null) {
                Notifications::register($fuenteNoticias);
            }
        }
        $check($sinFiltro && $sinContador && !str_contains($marcado, 'notifications-filter="news"') && substr_count($marcado, 'role="tab"') === 3, 'n12 DISCRIMINANTE: sin ella, ni filtro, ni contador, ni chip', mb_substr($marcado, 0, 120));
        unset($sinLeerA);
        echoTerminal(' ');

        //─── e · Los emisores ──────────────────────────────────────────────────────────────────────
        echoTerminal('[e] Los emisores: una aprobación resuelta y un administrador sobre la cuenta de otro');
        //Direcciones .invalid: aprobar no envía correo a nadie (SystemApprovalsController::noticeCanReach()).
        $sinCorreo = function (int $id) use ($bd, $prefijo): void {
            $bd->prepare('UPDATE `' . UsersModel::TABLE . '` SET email = ? WHERE id = ?')->execute(["{$prefijo}-{$id}@example.invalid", $id]);
        };
        $camino = fn(string $url): string => '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        $deTipo = function (int $usuario, string $kind) use ($bd): array {
            $consulta = $bd->prepare('SELECT level, titleKey, routeName FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ? AND kind = ? ORDER BY id');
            $consulta->execute([$usuario, $kind]);
            return $consulta->fetchAll(\PDO::FETCH_ASSOC);
        };
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $jwtRoot = UserSessions::issueToken($root);
        $tablaAprobaciones = SystemApprovalsMapper::TABLE;
        $filaAprobacion = function (int $usuario, string $sufijo) use ($bd, $tablaAprobaciones, $prefijo): int {
            $ahora = date('Y-m-d H:i:s');
            $bd->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute(["{$prefijo}-{$sufijo}", (string) $usuario, UsersModel::TABLE, $ahora, $ahora, $usuario, SystemApprovalsMapper::STATUS_PENDING]);
            return (int) $bd->lastInsertId();
        };
        $resolver = fn(int $fila, string $estado, string $motivo = '') => $pedir('POST', $camino((string) SystemApprovalsController::routeName('actions-approval', ['id' => $fila], true)), $jwtRoot, ['approvalStatus' => $estado, 'reason' => $motivo]);

        $aprobado = $crear('e-aprobado', UsersModel::TYPE_USER_GENERAL);
        $sinCorreo($aprobado);
        $r = $resolver($filaAprobacion($aprobado, 'e-aprobado'), SystemApprovalsMapper::STATUS_APPROVED);
        $avisos = $deTipo($aprobado, 'approval-resolved');
        $check(($r['json']['success'] ?? null) === true, 'e0 CANARIO: root aprueba la fila', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));
        $check(count($avisos) === 1 && $avisos[0]['level'] === NotificationMessage::LEVEL_SUCCESS && $avisos[0]['routeName'] === null, 'e1 DISCRIMINANTE: aprobar crea una success a quien recibe el correo, sin destino', (string) json_encode($avisos));
        $check($deTipo($root, 'approval-resolved') === [], 'e1 y ninguna a quien aprueba');
        $rechazado = $crear('e-rechazado', UsersModel::TYPE_USER_GENERAL);
        $sinCorreo($rechazado);
        $resolver($filaAprobacion($rechazado, 'e-rechazado'), SystemApprovalsMapper::STATUS_REJECTED, 'Falta un dato');
        $avisos = $deTipo($rechazado, 'approval-resolved');
        $check(count($avisos) === 1 && $avisos[0]['level'] === NotificationMessage::LEVEL_WARNING, 'e2 rechazar crea una warning', (string) json_encode($avisos));

        //e3 · un administrador cambia la contraseña de otro
        $otro = $crear('e-otro', UsersModel::TYPE_USER_GENERAL);
        $nuevaClave = 'Zz-Nueva-' . bin2hex(random_bytes(4)) . '-9c';
        $r = $pedir('POST', $camino((string) UsersController::routeName('edit-request', [], true)), $jwtRoot, ['id' => $otro, 'username' => "{$prefijo}-e-otro", 'email' => "{$prefijo}-e-otro@example.com", 'firstname' => 'Zz', 'first_lastname' => 'Notificaciones', 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => OrganizationMapper::INITIAL_ID_GLOBAL, 'password' => $nuevaClave, 'password2' => $nuevaClave]);
        $avisos = $deTipo($otro, AccountChangeNotice::KIND);
        $check(($r['json']['success'] ?? null) === true, 'e3 CANARIO: root cambia la contraseña de otro', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));
        $check(count($avisos) === 1 && $avisos[0]['titleKey'] === 'Un administrador cambió tu contraseña' && $avisos[0]['level'] === NotificationMessage::LEVEL_WARNING,
            'e3 DISCRIMINANTE: una al otro', (string) json_encode($avisos));
        $check($deTipo($root, AccountChangeNotice::KIND) === [], 'e3 y ninguna al administrador');

        //e4 · uno mismo cambia su contraseña: ninguna
        $propio = $crear('e-propio', UsersModel::TYPE_USER_GENERAL);
        $claveVieja = 'Zz-Clave-' . bin2hex(random_bytes(4)) . '-9d';
        $bd->prepare('UPDATE `' . UsersModel::TABLE . '` SET password = ? WHERE id = ?')->execute([password_hash($claveVieja, \PASSWORD_DEFAULT), $propio]);
        $r = $pedir('POST', $camino((string) UsersController::routeName('edit-request', [], true)), UserSessions::issueToken($propio), ['id' => $propio, 'is_profile' => 'yes', 'firstname' => 'Zz', 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => OrganizationMapper::INITIAL_ID_GLOBAL, 'password' => $nuevaClave, 'password2' => $nuevaClave, 'current-password' => $claveVieja]);
        $check(($r['json']['success'] ?? null) === true && $deTipo($propio, AccountChangeNotice::KIND) === [], 'e4 DISCRIMINANTE: cambiarse la propia no avisa', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 120));

        //e5 · cerrar una sesión ajena
        $cerrado = $crear('e-cerrado', UsersModel::TYPE_USER_GENERAL);
        UserSessions::issueToken($cerrado);
        $idSesion = (int) $bd->query('SELECT id FROM `' . UserSessions::TABLE . "` WHERE userID = {$cerrado} ORDER BY id DESC LIMIT 1")->fetchColumn();
        $r = $pedir('POST', $camino((string) UserSessionsController::routeName('close', ['id' => $idSesion], true)), $jwtRoot);
        $avisos = $deTipo($cerrado, AccountChangeNotice::KIND);
        $check(($r['json']['success'] ?? null) === true && count($avisos) === 1 && $avisos[0]['titleKey'] === 'Un administrador cerró una de tus sesiones',
            'e5 DISCRIMINANTE: cerrar una sesión ajena avisa a su dueño', "HTTP {$r['status']} · " . json_encode($avisos));

        //e6 · cerrar todas las sesiones de otro
        $revocado = $crear('e-revocado', UsersModel::TYPE_USER_GENERAL);
        $r = $pedir('POST', $camino((string) UsersController::routeName('revoke-sessions-request', [], true)), $jwtRoot, ['id' => $revocado]);
        $avisos = $deTipo($revocado, AccountChangeNotice::KIND);
        $check(($r['json']['success'] ?? null) === true && count($avisos) === 1 && $avisos[0]['titleKey'] === 'Un administrador cerró todas tus sesiones',
            'e6 cerrar todas las de otro avisa a su dueño', "HTTP {$r['status']} · " . json_encode($avisos));
        $check(($avisos[0]['routeName'] ?? null) === \MySpace\Controllers\MySpaceController::securityRouteName(), 'e6 con destino «Mis sesiones»', (string) ($avisos[0]['routeName'] ?? 'null'));

        //e7 · con notify() fallando, la respuesta del emisor es la misma (en proceso: el interruptor no cruza a Apache)
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        $revocar = function (int $objetivo): string {
            $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-notificaciones'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody(['id' => $objetivo]);
            return (string) (new UsersController())->revokeUserSessions($peticion, new ResponseRoute())->getBody();
        };
        try {
            set_config('current_user', (object) ['id' => $root]);
            set_config('pcsphp_current_user_stored', null);
            $fallido = $crear('e-fallido', UsersModel::TYPE_USER_GENERAL);
            $antesRegistro = $lineasRegistro();
            Notifications::failForTesting(true);
            try {
                $conFallo = $revocar($fallido);
            } finally {
                Notifications::failForTesting(false);
            }
            $lineasConFallo = $lineasRegistro() - $antesRegistro;
            $sinFallo = $revocar($fallido);
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }
        $check($conFallo === $sinFallo && (json_decode($conFallo, true)['success'] ?? null) === true, 'e7 DISCRIMINANTE: con notify() fallando, la misma respuesta', mb_substr($conFallo, 0, 120) . ' / ' . mb_substr($sinFallo, 0, 120));
        $check($lineasConFallo === 1 && count($deTipo($fallido, AccountChangeNotice::KIND)) === 1, 'e7 el fallo deja su línea y no crea; sin fallo, crea', "líneas {$lineasConFallo}");
        echoTerminal(' ');

        //─── n13-n15 · la regla de las noticias, plana, y su coste fijo ──────────────────────────────
        //El borde de endDate se mide en el minuto actual: si está a punto de cambiar, se espera al siguiente.
        if ((int) date('s') >= 57) {
            sleep(4);
        }
        $minuto = new \DateTime(date('Y-m-d H:i:00'));
        $dia = fn(string $mod): \DateTime => (clone $minuto)->modify($mod);
        $general = UsersModel::TYPE_USER_GENERAL;
        $usuarioGeneral = new \PiecesPHP\UserSystem\UserDataPackage($a);
        $usuarioRoot = new \PiecesPHP\UserSystem\UserDataPackage(1);
        echoTerminal('[n13] La regla pura da lo esperado y lo mismo que isVisibleTo() en cada caso');
        //[nombre, status, draft, start, end, perfiles, usuario, esperado]
        $casos = [
            ['activa sin fechas', NewsMapper::ACTIVE, 0, null, null, [$general], $usuarioGeneral, true],
            ['inactiva', NewsMapper::INACTIVE, 0, null, null, [$general], $usuarioGeneral, false],
            ['borrador', NewsMapper::ACTIVE, 1, null, null, [$general], $usuarioGeneral, false],
            ['dentro de fechas', NewsMapper::ACTIVE, 0, $dia('-1 day'), $dia('+1 day'), [$general], $usuarioGeneral, true],
            ['antes de empezar', NewsMapper::ACTIVE, 0, $dia('+1 day'), null, [$general], $usuarioGeneral, false],
            ['ya vencida', NewsMapper::ACTIVE, 0, null, $dia('-1 day'), [$general], $usuarioGeneral, false],
            ['empieza este minuto', NewsMapper::ACTIVE, 0, $dia('+0 minutes'), null, [$general], $usuarioGeneral, true],
            ['BORDE: vence este minuto', NewsMapper::ACTIVE, 0, null, $dia('+0 minutes'), [$general], $usuarioGeneral, false],
            ['vence el minuto siguiente', NewsMapper::ACTIVE, 0, null, $dia('+1 minute'), [$general], $usuarioGeneral, true],
            ['perfiles sin su tipo', NewsMapper::ACTIVE, 0, null, null, [UsersModel::TYPE_USER_ADMIN_GRAL], $usuarioGeneral, false],
            ['perfiles como texto', NewsMapper::ACTIVE, 0, null, null, [(string) $general], $usuarioGeneral, true],
            ['root sin perfiles (CAN_VIEW_TARGET_ALL)', NewsMapper::ACTIVE, 0, null, null, [], $usuarioRoot, true],
            ['sin usuario', NewsMapper::ACTIVE, 0, null, null, [$general], null, false],
        ];
        $discrepancias = [];
        foreach ($casos as [$nombre, $status, $draft, $start, $end, $perfiles, $usuario, $esperado]) {
            $m = new NewsMapper();
            $m->id = 999999;
            $m->status = $status;
            $m->draft = $draft;
            $m->startDate = $start;
            $m->endDate = $end;
            $m->profilesTarget = $perfiles;
            $pura = NewsMapper::isVisibleByValues($status, $draft, $start, $end, $perfiles, $usuario !== null ? (int) $usuario->type : null);
            $porMapper = $m->isVisibleTo($usuario);
            if ($pura !== $esperado || $porMapper !== $esperado) {
                $discrepancias[] = "{$nombre}: pura " . var_export($pura, true) . ', mapper ' . var_export($porMapper, true) . ', esperado ' . var_export($esperado, true);
            }
        }
        $check($discrepancias === [], 'n13 DISCRIMINANTE: ' . count($casos) . ' casos, la regla pura y isVisibleTo() dan lo esperado', implode(' · ', $discrepancias));
        $check((new NewsMapper())->isVisibleTo($usuarioGeneral) === false, 'n13 y una noticia sin id no es visible');

        echoTerminal('[n14] counts() e items() de la fuente dan lo mismo que la regla aplicada noticia a noticia');
        $lote = [];
        foreach (['ok', 'inactiva', 'borrador', 'futura', 'vencida', 'borde', 'sinfechas', 'otroperfil'] as $sufijo) {
            $lote[$sufijo] = $noticia("l-{$sufijo}", $sufijo === 'otroperfil' ? [UsersModel::TYPE_USER_ADMIN_GRAL] : [$general]);
        }
        $fecha = fn(\DateTime $d): string => $d->format('Y-m-d H:i:s');
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . '` SET status = ? WHERE id = ?')->execute([NewsMapper::INACTIVE, $lote['inactiva']]);
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . "` SET meta = JSON_SET(meta, '$.draft', 1) WHERE id = ?")->execute([$lote['borrador']]);
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . '` SET startDate = ? WHERE id = ?')->execute([$fecha($dia('+1 day')), $lote['futura']]);
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . '` SET endDate = ? WHERE id = ?')->execute([$fecha($dia('-1 day')), $lote['vencida']]);
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . '` SET endDate = ? WHERE id = ?')->execute([$fecha($minuto), $lote['borde']]);
        $bd->prepare('UPDATE `' . NewsMapper::TABLE . '` SET startDate = NULL, endDate = NULL WHERE id = ?')->execute([$lote['sinfechas']]);
        NewsReadedMapper::addRecord($a, $lote['ok']);
        //Lo esperado, como lo calculaba la fuente antes: un mapper por noticia, isVisibleTo() y una traducción al idioma actual.
        $esperadas = function (int $usuario): array {
            $paquete = new \PiecesPHP\UserSystem\UserDataPackage($usuario);
            $lang = \PiecesPHP\Core\Config::get_lang();
            $ids = [];
            foreach (Notifications::database()->query('SELECT id FROM `' . NewsMapper::TABLE . '` WHERE status = ' . NewsMapper::ACTIVE . ' ORDER BY createdAt DESC, id DESC')->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $m = new NewsMapper((int) $id);
                $langData = $m->langData;
                if ($m->isVisibleTo($paquete) && ($m->baseLang === $lang || (is_object($langData) && isset($langData->$lang)))) {
                    $ids[] = (string) $id;
                }
            }
            return $ids;
        };
        $fuente = new NewsNotificationSource();
        foreach (['general' => $a, 'root' => 1] as $quien => $usuario) {
            $ids = $esperadas($usuario);
            $leidas = array_map('strval', $bd->query('SELECT news FROM `' . NewsReadedMapper::TABLE . '` WHERE readerUser = ' . (int) $usuario)->fetchAll(\PDO::FETCH_COLUMN));
            $nLeidas = count(array_intersect($ids, $leidas));
            $cuentas = $fuente->counts($usuario);
            $deItems = array_map(fn(array $i) => $i['id'], $fuente->items($usuario, null, 1000));
            $check($cuentas === ['all' => count($ids), 'unread' => count($ids) - $nLeidas, 'read' => $nLeidas] && $deItems === $ids,
                "n14 DISCRIMINANTE ({$quien}): los contadores y los ids, en orden, iguales a la regla noticia a noticia", json_encode($cuentas) . ' · ' . count($deItems) . ' de ' . count($ids));
        }
        $deA = array_map(fn(array $i) => $i['id'], $fuente->items($a, null, 1000));
        $check(in_array((string) $lote['ok'], $deA, true) && in_array((string) $lote['sinfechas'], $deA, true) && array_intersect($deA, array_map('strval', [$lote['inactiva'], $lote['borrador'], $lote['futura'], $lote['vencida'], $lote['borde'], $lote['otroperfil']])) === [],
            'n14 CANARIO: del lote, solo la vigente y la sin fechas', implode(',', $deA));
        $noLeidas = array_map(fn(array $i) => $i['id'], $fuente->items($a, false, 1000));
        $soloLeidas = array_map(fn(array $i) => $i['id'], $fuente->items($a, true, 1000));
        $check(in_array((string) $lote['ok'], $soloLeidas, true) && !in_array((string) $lote['ok'], $noLeidas, true) && count($fuente->items($a, null, 1)) === 1,
            'n14 items() filtra por leída y respeta el límite');

        echoTerminal('[n15] unread() hace las mismas consultas con 5 noticias más que con 50');
        $memo = new \ReflectionProperty(Notifications::class, 'unreadMemo');
        $consultasDe = function (int $usuario) use ($bd, $memo): array {
            $preguntas = fn(): int => (int) $bd->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(\PDO::FETCH_NUM)[1];
            $memo->setValue(null, []);
            $antes = $preguntas();
            $n = Notifications::unread($usuario);
            return [$preguntas() - $antes - 1, $n];
        };
        $columnas = array_values(array_filter($bd->query('SHOW COLUMNS FROM `' . NewsMapper::TABLE . '`')->fetchAll(\PDO::FETCH_COLUMN), fn($c) => !in_array($c, ['id', 'newsTitle', 'preferSlug', 'folder'], true)));
        $lista = '`' . implode('`, `', $columnas) . '`';
        $copiar = $bd->prepare('INSERT INTO `' . NewsMapper::TABLE . "` (`newsTitle`, `preferSlug`, `folder`, {$lista}) SELECT ?, NULL, ?, {$lista} FROM `" . NewsMapper::TABLE . '` WHERE id = ?');
        $anadir = function (int $cuantas, int $desde) use ($copiar, $bd, $prefijo, $lote, &$noticias): void {
            for ($i = $desde; $i < $desde + $cuantas; $i++) {
                $copiar->execute(["{$prefijo}-n15-{$i}", "{$prefijo}-n15-{$i}", $lote['ok']]);
                $noticias["n15-{$i}"] = (int) $bd->lastInsertId();
            }
        };
        $anadir(5, 0);
        [$con5, $sinLeer5] = $consultasDe($a);
        $anadir(45, 5);
        [$con50, $sinLeer50] = $consultasDe($a);
        $check($sinLeer50 - $sinLeer5 === 45, 'n15 CANARIO: las 45 nuevas cuentan en la insignia', "{$sinLeer5} → {$sinLeer50}");
        //Questions es de la sesión: si unread() fuera por otra conexión, contaría 0 y 0 y pasaría sin medir nada.
        $mismaConexion = Notifications::database() === $bd;
        $check($mismaConexion, 'n15 CANARIO: Notifications::database() es la misma conexión que la de la suite');
        $check($mismaConexion && $con5 === $con50, 'n15 DISCRIMINANTE: las mismas consultas con 5 que con 50 noticias de más',
            $mismaConexion ? "{$con5} / {$con50}" : 'conexiones distintas: Questions no ve las consultas de unread()');

        //─── n16 · «99+» desde 100 ─────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[n16] La insignia dice «99+» desde 100; la etiqueta accesible, la cifra real');
        $check(Notifications::badgeText(0) === '0' && Notifications::badgeText(99) === '99' && Notifications::badgeText(100) === '99+' && Notifications::badgeText(1500) === '99+',
            'n16 badgeText(): 0 → «0», 99 → «99», 100 y 1500 → «99+»', Notifications::badgeText(99) . ' / ' . Notifications::badgeText(100));
        $leerInsignia = function () use ($pedir, $ruta, $jwtB): array {
            $pagina = $pedir('GET', $ruta('view'), $jwtB, [], false);
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pagina['body']);
            $xpath = new \DOMXPath($dom);
            $insignia = $xpath->query('//*[@notifications-toggle]//*[@notifications-badge]')?->item(0);
            $campana = $xpath->query('//*[@notifications-toggle]')?->item(0);
            return [
                'texto' => $insignia instanceof \DOMElement ? trim($insignia->textContent) : null,
                'oculta' => $insignia instanceof \DOMElement && $insignia->hasAttribute('hidden'),
                'aria' => $campana instanceof \DOMElement ? $campana->getAttribute('aria-label') : '',
            ];
        };
        $pedir('POST', $ruta('mark-all-read'), $jwtB);
        $cero = $leerInsignia();
        $check(Notifications::counts($b)[Notifications::FILTER_UNREAD] === 0 && $cero['oculta'], 'n16 con 0 la insignia de la página está oculta', (string) json_encode($cero));
        $insertarSinLeer = $bd->prepare('INSERT INTO `' . NotificationMapper::TABLE . '` (`user`, kind, level, langGroup, titleKey, createdAt, readAt) VALUES (?, ?, ?, ?, ?, ?, NULL)');
        for ($i = 0; $i < 99; $i++) {
            $insertarSinLeer->execute([$b, 'zz-n16', 'info', NotificationsLang::LANG_GROUP, 'Notificaciones', date('Y-m-d H:i:s')]);
        }
        $noventaYNueve = $leerInsignia();
        $check(Notifications::counts($b)[Notifications::FILTER_UNREAD] === 99 && $noventaYNueve['texto'] === '99' && !$noventaYNueve['oculta'], 'n16 con 99 la página dice «99»', (string) json_encode($noventaYNueve));
        $insertarSinLeer->execute([$b, 'zz-n16', 'info', NotificationsLang::LANG_GROUP, 'Notificaciones', date('Y-m-d H:i:s')]);
        $cien = $leerInsignia();
        $check(Notifications::counts($b)[Notifications::FILTER_UNREAD] === 100 && $cien['texto'] === '99+', 'n16 DISCRIMINANTE: con 100 la página dice «99+»', (string) json_encode($cien));
        $check(str_contains($cien['aria'], '100') && !str_contains($cien['aria'], '99+'), 'n16 y con 100 el aria-label dice 100, la cifra real', $cien['aria']);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        set_config('app_lang', $idiomaPrevio);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($noticias as $idNoticia) {
                $bd->prepare('DELETE FROM `' . NewsReadedMapper::TABLE . '` WHERE news = ?')->execute([$idNoticia]);
                $bd->prepare('DELETE FROM `' . NewsMapper::TABLE . '` WHERE id = ?')->execute([$idNoticia]);
            }
            //Los registros de acciones de esta prueba (aprobar, editar, cerrar sesiones) apuntan a sus usuarios: van antes.
            $bd->prepare('DELETE FROM `' . \EventsLog\Mappers\LogsMapper::TABLE . '` WHERE id > ? AND textMessageVariables LIKE ?')->execute([$ultimoLog, "%{$prefijo}%"]);
            foreach ($usuarios as $idUsuario) {
                $bd->prepare('DELETE FROM `' . \EventsLog\Mappers\LogsMapper::TABLE . '` WHERE id > ? AND createdBy = ?')->execute([$ultimoLog, $idUsuario]);
                //Las aprobaciones que resolvió (approvalBy) o que eran suyas, antes de borrar a ningún usuario.
                $bd->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE approvalBy = ? OR (referenceTable = ? AND referenceValue = ?)')->execute([$idUsuario, UsersModel::TABLE, (string) $idUsuario]);
            }
            foreach ($usuarios as $idUsuario) {
                $bd->prepare('DELETE FROM `' . NewsReadedMapper::TABLE . '` WHERE readerUser = ?')->execute([$idUsuario]);
                $bd->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$idUsuario]);
                $bd->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $idUsuario]);
                $bd->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$idUsuario]);
                //Las notificaciones caen con el usuario (ON DELETE CASCADE): es lo que se comprueba abajo.
                $bd->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$idUsuario]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanUsuarios = (int) $bd->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $bd->quote("{$prefijo}%"))->fetchColumn();
        $quedanNoticias = (int) $bd->query('SELECT COUNT(*) FROM `' . NewsMapper::TABLE . '` WHERE folder LIKE ' . $bd->quote("{$prefijo}%"))->fetchColumn();
        $quedanNotificaciones = $usuarios === [] ? 0 : (int) $bd->query('SELECT COUNT(*) FROM `' . NotificationMapper::TABLE . '` WHERE `user` IN (' . implode(', ', array_map('intval', $usuarios)) . ')')->fetchColumn();
        $check($quedanUsuarios === 0 && $quedanNoticias === 0 && $quedanNotificaciones === 0, 'z1 no queda nada de la suite: sus notificaciones cayeron con sus usuarios', "usuarios {$quedanUsuarios}, noticias {$quedanNoticias}, notificaciones {$quedanNotificaciones}");
    }

    return $balance();

})->setDescription('El canal de notificaciones (ADR 0073): notify() crea, descarta los ids que no son y nunca lanza; el texto sale en el idioma de quien lee; la lista con sus filtros y contadores; lo ajeno da 404; marcar todas; la purga; las noticias que no le llegan. Por HTTP y en proceso; crea y retira sus usuarios y noticias.')->setEffects([CliActions::EFFECT_DATABASE])->register();
