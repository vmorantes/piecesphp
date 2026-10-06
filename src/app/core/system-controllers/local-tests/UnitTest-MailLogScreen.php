<?php

//La pantalla del registro de correos: solo el usuario principal la abre, porque lista
//DESTINATARIOS. Y lo que pinta va escapado. Crea dos usuarios zz-agente-maillog-*.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Email\MailDelivery;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/mail-log-screen', function ($args) {

    echoTerminal("\e[33m[TEST:MailLogScreen] El registro de correos: solo el principal, y lo que pinta va escapado\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    if ($base === '') {
        return $balance();
    }

    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $rutas = [
        'vista' => $ruta('system-status-mail-log'),
        'datos' => $ruta('system-status-mail-log-datatables'),
    ];
    foreach ($rutas as $clave => $valor) {
        $check($valor !== '' && $valor !== '/', "c2 existe la ruta de la {$clave}", $valor);
    }
    if ($failed > 0) {
        return $balance();
    }

    if (!$check(MailLogMapper::tableExists(), 'c3 la tabla del registro está: sin ella la pantalla no lista nada', MailLogMapper::UPDATE_FILE)) {
        return $balance();
    }
    echoTerminal(' ');

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt = null) use ($base, $cabeceraToken): array {
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $tipo = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true), 'type' => $tipo];
    };
    $token = fn(int $id): string => SessionToken::generateToken(['id' => $id], null, null, false);

    $prefijo = 'zz-agente-maillog-' . bin2hex(random_bytes(3));
    $crear = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };

    $marca = 'zz-pantalla-' . bin2hex(random_bytes(3));
    $tabla = MailLogMapper::TABLE;
    $modelo = MailLogMapper::model();
    $modelo->resetAll();
    $baseDatos = $modelo->getDatabase();
    $maxLogPrevio = (int) ($baseDatos?->query('SELECT COALESCE(MAX(`id`), 0) FROM `' . LogsMapper::model()->getTable() . '`')->fetchColumn() ?? 0);
    $usuarioPrevio = get_config('current_user');
    $usuarioGuardadoPrevio = get_config('pcsphp_current_user_stored');

    try {

        $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT);
        $idAdmin = $crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $root = $token($idRoot);
        $admin = $token($idAdmin);
        $check($idRoot > 0 && $idAdmin > 0, 'banco: un principal y un administrador general de la prueba');

        //─── a · Solo el principal ──────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Solo el usuario principal entra: la tabla lista destinatarios');
        $comoRoot = $pedir($rutas['vista'], $root);
        $check($comoRoot['status'] === 200, 'a1 el principal abre la pantalla (sin esto, todo 403 sería gratis)', "HTTP {$comoRoot['status']}");
        $check(str_contains($comoRoot['body'], 'mail-log-view'), 'a2 y la pantalla es la del registro de correos');

        $comoAdmin = $pedir($rutas['vista'], $admin);
        $check($comoAdmin['status'] === 403, 'a3 un administrador general recibe 403 en la pantalla', "HTTP {$comoAdmin['status']}");
        $datosComoAdmin = $pedir($rutas['datos'], $admin);
        $check($datosComoAdmin['status'] === 403, 'a4 y 403 también en los datos: la puerta no está solo en la vista', "HTTP {$datosComoAdmin['status']}");
        $check(!str_contains($datosComoAdmin['body'], $marca), 'a5 y su respuesta no trae ninguna línea del registro');

        $sinEntrar = $pedir($rutas['vista']);
        $check($sinEntrar['status'] !== 200, 'a6 sin sesión no se abre', "HTTP {$sinEntrar['status']}");
        $datosSinEntrar = $pedir($rutas['datos']);
        $check($datosSinEntrar['status'] !== 200, 'a7 ni los datos', "HTTP {$datosSinEntrar['status']}");

        //─── b · La pantalla lista de verdad lo que hay en el registro ──────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Una línea sembrada sale en la tabla, con sus columnas');
        $check(MailLogMapper::record(
            ["{$marca}@localhost.test"],
            "Asunto de {$marca}",
            'UnitTest-MailLogScreen',
            MailDelivery::SINK,
            MailLogMapper::RESULT_FAILED,
            "motivo de {$marca}"
        ), 'b1 se siembra una línea fallida');

        $datos = $pedir($rutas['datos'] . '?draw=1&start=0&length=100', $root);
        $check($datos['status'] === 200, 'b2 el principal recibe los datos', "HTTP {$datos['status']}");
        $check(is_array($datos['json']) && isset($datos['json']['data']) && isset($datos['json']['recordsTotal']), 'b3 y vienen con la forma que DataTables espera', mb_substr($datos['body'], 0, 120));
        $check(str_contains($datos['body'], "{$marca}@localhost.test"), 'b4 la línea sembrada sale, con su destinatario');
        $check(str_contains($datos['body'], "Asunto de {$marca}"), 'b5 con su asunto');
        $check(str_contains($datos['body'], 'UnitTest-MailLogScreen'), 'b6 con su origen');
        $check(str_contains($datos['body'], "motivo de {$marca}"), 'b7 y con el MOTIVO pegado al resultado, que es lo que dice qué arreglar');

        //─── c · Lo que se pinta va escapado ────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Un asunto con etiquetas no se pinta como HTML');
        $veneno = "<script>alert(1)</script>";
        $check(MailLogMapper::record(
            ["{$marca}-xss@localhost.test"],
            "{$veneno} {$marca}",
            'UnitTest-MailLogScreen',
            MailDelivery::SINK,
            MailLogMapper::RESULT_DELIVERED,
            null
        ), 'c4 se siembra una línea con etiquetas en el asunto');

        $datosVeneno = $pedir($rutas['datos'] . '?draw=1&start=0&length=100', $root);
        $check(str_contains($datosVeneno['body'], '&lt;script&gt;'), 'c5 el asunto llega escapado');
        //El cuerpo es JSON, así que `<` crudo saldría tal cual: si aparece, DataTables lo pintaría.
        $check(!str_contains($datosVeneno['body'], '<script>'), 'c6 y la etiqueta cruda NO aparece en la respuesta');

        //CANARIO del escapado: el mismo texto SIN escapar sí contendría la etiqueta cruda, así que
        //c6 está mirando algo que puede fallar.
        $check(str_contains($veneno, '<script>'), 'c7 CANARIO: el texto sembrado SÍ trae la etiqueta cruda, así que c6 puede fallar');

        //─── d · El cuerpo: solo con SU permiso, y nunca en el listado (prueba 7) ──────────────────
        echoTerminal(' ');
        echoTerminal('[d] El cuerpo se sirve solo con su permiso, y el listado no lo trae nunca');
        $cuerpoSembrado = "<p>ZZ CUERPO DE LA PANTALLA {$marca}</p>";
        $idDe = function (string $sufijo) use ($baseDatos, $marca): int {
            $consulta = $baseDatos->prepare('SELECT `id` FROM `' . MailLogMapper::TABLE . '` WHERE `recipients` = ? ORDER BY `id` DESC LIMIT 1');
            $consulta->execute(["{$marca}-{$sufijo}@localhost.test"]);
            return (int) $consulta->fetchColumn();
        };
        $rutaCuerpo = function ($id): string {
            $url = get_route('system-status-mail-log-body', ['id' => (string) $id], true);
            return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
        };
        MailLogMapper::record(["{$marca}-cuerpo@localhost.test"], "ZZ con cuerpo {$marca}", 'UnitTest-MailLogScreen', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, $cuerpoSembrado);
        MailLogMapper::record(["{$marca}-sincuerpo@localhost.test"], "ZZ sin cuerpo {$marca}", 'UnitTest-MailLogScreen', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, null);
        MailLogMapper::record(["{$marca}-ilegible@localhost.test"], "ZZ ilegible {$marca}", 'UnitTest-MailLogScreen', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>zz</p>');
        $idCuerpo = $idDe('cuerpo');
        $idSinCuerpo = $idDe('sincuerpo');
        $idIlegible = $idDe('ilegible');
        //Un cuerpo que ya no se puede descifrar: se estropea a propósito en la base.
        $estropear = $baseDatos->prepare('UPDATE `' . MailLogMapper::TABLE . '` SET `body` = ? WHERE `id` = ?');
        $estropear->execute(['zz-esto-no-se-puede-descifrar', $idIlegible]);
        $cifradoEnBase = (string) (MailLogMapper::bodyOf($idCuerpo)['body'] ?? '');
        $check($idCuerpo > 0 && $idSinCuerpo > 0 && $idIlegible > 0 && $cifradoEnBase !== '', 'd0 tres líneas sembradas: con cuerpo, sin cuerpo y con un cuerpo ilegible');

        $comoRootCuerpo = $pedir($rutaCuerpo($idCuerpo), $root);
        //CANARIO de todo lo de abajo: sin un 200 con el cuerpo aquí, cualquier 403 sería gratis.
        $check($comoRootCuerpo['status'] === 200 && ($comoRootCuerpo['json']['status'] ?? null) === 'ok' && ($comoRootCuerpo['json']['body'] ?? null) === $cuerpoSembrado, 'd1 CANARIO: el principal recibe el cuerpo, descifrado e idéntico al sembrado', "HTTP {$comoRootCuerpo['status']}");
        $comoAdminCuerpo = $pedir($rutaCuerpo($idCuerpo), $admin);
        $check($comoAdminCuerpo['status'] === 403, 'd2 un administrador general recibe 403 en la ruta del cuerpo', "HTTP {$comoAdminCuerpo['status']}");
        $check(!str_contains($comoAdminCuerpo['body'], $marca), 'd3 y su respuesta no trae el cuerpo');
        $sinSesionCuerpo = $pedir($rutaCuerpo($idCuerpo));
        $check($sinSesionCuerpo['status'] !== 200 && !str_contains($sinSesionCuerpo['body'], $marca), 'd4 sin sesión, ni 200 ni el cuerpo', "HTTP {$sinSesionCuerpo['status']}");

        $listado = $pedir($rutas['datos'] . '?draw=1&start=0&length=100', $root);
        $check(!str_contains($listado['body'], "ZZ CUERPO DE LA PANTALLA"), 'd5 el JSON del listado NO trae el cuerpo, ni con permiso');
        $check($cifradoEnBase !== '' && !str_contains($listado['body'], $cifradoEnBase), 'd6 ni cifrado');
        $check(str_contains($listado['body'], str_replace('/', '\\/', $rutaCuerpo($idCuerpo))) || str_contains($listado['body'], $rutaCuerpo($idCuerpo)), 'd7 con permiso, la fila con cuerpo trae su botón «Ver cuerpo»');
        $check(!str_contains($listado['body'], str_replace('/', '\\/', $rutaCuerpo($idSinCuerpo))) && !str_contains($listado['body'], $rutaCuerpo($idSinCuerpo)), 'd8 y la fila SIN cuerpo no lo trae');

        $sinCuerpo = $pedir($rutaCuerpo($idSinCuerpo), $root);
        $ilegible = $pedir($rutaCuerpo($idIlegible), $root);
        $check(($sinCuerpo['json']['status'] ?? null) === 'sin-cuerpo' && ($ilegible['json']['status'] ?? null) === 'ilegible', 'd9 «sin cuerpo» e «ilegible» se distinguen', ($sinCuerpo['json']['status'] ?? '?') . ' / ' . ($ilegible['json']['status'] ?? '?'));
        $check(($sinCuerpo['json']['message'] ?? '') !== ($ilegible['json']['message'] ?? ''), 'd10 y dicen cosas distintas');
        $noExiste = $pedir($rutaCuerpo(999999999), $root);
        $check($noExiste['status'] === 404, 'd11 un id que no existe da 404', "HTTP {$noExiste['status']}");

        //Con otra clave el AES a veces «pasa» y lo que falla es gzdecode, que lanzaba: daba 500. Se fabrica
        //ese caso a propósito: cifrado con la clave buena y SIN gzip.
        $claveBuena = substr(hash('sha256', (string) MailLogMapper::bodyKey(), true), 0, 32);
        $iv = random_bytes(16);
        $sinGzip = BaseHashEncryption::urlSafeB64Encode($iv . openssl_encrypt('zz no es gzip', 'aes-256-cbc', $claveBuena, \OPENSSL_RAW_DATA, $iv));
        MailLogMapper::record(["{$marca}-gzip@localhost.test"], "ZZ gzip {$marca}", 'UnitTest-MailLogScreen', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>zz</p>');
        $idGzip = $idDe('gzip');
        $estropear->execute([$sinGzip, $idGzip]);
        $gzip = $pedir($rutaCuerpo($idGzip), $root);
        $check($gzip['status'] === 200 && ($gzip['json']['status'] ?? null) === 'ilegible', 'd12 un cifrado que pasa el AES pero no descomprime es «ilegible», no un 500', "HTTP {$gzip['status']}");

        //UTF-8 inválido en el cuerpo: json_encode lanzaba y daba 500. Se enseña, con U+FFFD donde falla.
        MailLogMapper::record(["{$marca}-utf8@localhost.test"], "ZZ utf8 {$marca}", 'UnitTest-MailLogScreen', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, "<p>zz \xC3\x28 roto \xFF fin</p>");
        $idUtf8 = $idDe('utf8');
        $utf8 = $pedir($rutaCuerpo($idUtf8), $root);
        $cuerpoUtf8 = (string) ($utf8['json']['body'] ?? '');
        $check($utf8['status'] === 200 && ($utf8['json']['status'] ?? null) === 'ok' && str_contains($cuerpoUtf8, 'roto') && substr_count($cuerpoUtf8, "\u{FFFD}") === 2, 'd13 un cuerpo con UTF-8 inválido se enseña, con U+FFFD donde falla, no un 500', "HTTP {$utf8['status']}");

        //─── f · El rastro: cada lectura, legible o no, deja su línea; sin contenido ni destinatarios ──
        echoTerminal(' ');
        echoTerminal('[f] Cada lectura del cuerpo queda en el registro de acciones');
        $rastro = $baseDatos->prepare('SELECT `textMessageVariables`, `createdBy` FROM `' . LogsMapper::model()->getTable() . '` WHERE `id` > ? AND `textMessageVariables` LIKE ?');
        $lineaDe = function (int $id) use ($rastro, $maxLogPrevio): array {
            $rastro->execute([$maxLogPrevio, "%#{$id} del registro%"]);
            return $rastro->fetchAll(\PDO::FETCH_ASSOC);
        };
        $vio = $lineaDe($idCuerpo);
        $check(count($vio) >= 1 && str_contains((string) $vio[0]['textMessageVariables'], 'vio el cuerpo') && (int) $vio[0]['createdBy'] === $idRoot, 'f1 la lectura legible deja su línea, a nombre de quien la pidió', (string) count($vio));
        $noPudo = $lineaDe($idGzip);
        $check(count($noPudo) >= 1 && str_contains((string) $noPudo[0]['textMessageVariables'], 'no se pudo descifrar'), 'f2 la ilegible también, y dice que no se pudo');
        $todo = json_encode([$vio, $noPudo, $lineaDe($idIlegible)]);
        $check(!str_contains((string) $todo, 'ZZ CUERPO') && !str_contains((string) $todo, '@localhost.test'), 'f3 y ninguna lleva el contenido ni a quién iba');
        $check(count($lineaDe($idSinCuerpo)) === 0 && count($lineaDe($idCuerpo)) === 1, 'f4 una fila sin cuerpo no deja línea, y el 403 del administrador tampoco: no hubo lectura');

        //─── g · La ruta se comprueba a sí misma, no solo la capa de rutas ──────────────────────────
        echoTerminal(' ');
        echoTerminal('[g] Llamada directa, sin la capa de rutas: el método decide solo');
        $directa = function () use ($idCuerpo): ResponseRoute {
            $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-cuerpo'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            return (new SystemStatusController())->mailLogBody($peticion, new ResponseRoute(), ['id' => (string) $idCuerpo]);
        };
        $comoUsuario = function (?int $id): void {
            set_config('current_user', $id !== null ? (object) ['id' => $id] : null);
            set_config('pcsphp_current_user_stored', null);
        };
        $comoUsuario(null);
        $r = $directa();
        $check($r->getStatusCode() === 403 && !str_contains((string) $r->getBody(), $marca), 'g1 sin usuario: 403, y sin el cuerpo', 'HTTP ' . $r->getStatusCode());
        $comoUsuario($idAdmin);
        $r = $directa();
        $check($r->getStatusCode() === 403 && !str_contains((string) $r->getBody(), $marca), 'g2 un administrador general: 403, aunque llegue sin pasar por la capa de rutas', 'HTTP ' . $r->getStatusCode());
        //CANARIO: el principal SÍ lo recibe por la misma llamada. Sin esto, g1 y g2 pasarían con un método roto.
        $comoUsuario($idRoot);
        $r = $directa();
        $check($r->getStatusCode() === 200 && str_contains((string) $r->getBody(), $marca), 'g3 CANARIO: el principal, por la misma llamada directa, recibe el cuerpo', 'HTTP ' . $r->getStatusCode());
        $comoUsuario(null);

        //─── e · Aislado: JSON, iframe con sandbox vacío, nunca como marcado (prueba 8) ─────────────
        echoTerminal(' ');
        echoTerminal('[e] El cuerpo se pinta aislado');
        $check(str_starts_with(strtolower($comoRootCuerpo['type']), 'application/json'), 'e1 la ruta del cuerpo responde application/json, nunca HTML', $comoRootCuerpo['type']);
        $sandboxDe = function (string $html): ?string {
            if (preg_match('/<iframe\b[^>]*\bdata-mail-log-body-frame\b[^>]*>/i', $html, $iframe) !== 1) {
                return null;
            }
            return preg_match('/\bsandbox="([^"]*)"/i', $iframe[0], $valor) === 1 ? $valor[1] : null;
        };
        $aislado = fn (?string $sandbox): bool => $sandbox !== null && !str_contains($sandbox, 'allow-scripts') && !str_contains($sandbox, 'allow-same-origin');
        //CANARIO de dos caras de e2: el comprobador rechaza un sandbox con scripts y acepta uno vacío.
        $check(!$aislado('allow-scripts') && !$aislado(null) && $aislado(''), 'e0 CANARIO: el comprobador del sandbox distingue');
        $pantalla = $pedir($rutas['vista'], $root);
        $sandbox = $sandboxDe($pantalla['body']);
        $check($aislado($sandbox), 'e2 la pantalla servida pinta el visor en un iframe con sandbox sin allow-scripts ni allow-same-origin', var_export($sandbox, true));
        $raizSrc = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/src/app/classes/PiecesPHP/SystemStatus/';
        foreach (['Statics/js/mail-log.js', 'Views/mail-log.php'] as $relativo) {
            $fuente = (string) @file_get_contents($raizSrc . $relativo);
            $check($fuente !== '' && !str_contains($fuente, 'innerHTML') && !str_contains($fuente, '.html('), "e3 {$relativo} no usa innerHTML ni .html(");
        }
        $check(!str_contains($pantalla['body'], "ZZ CUERPO DE LA PANTALLA"), 'e4 y la pantalla servida no lleva ningún cuerpo dentro');
        //Sin red dentro del visor: una imagen remota del cuerpo avisaría al remitente de que root lo abrió.
        $js = (string) @file_get_contents($raizSrc . 'Statics/js/mail-log.js');
        $politica = "<meta http-equiv=\"Content-Security-Policy\" content=\"default-src \\'none\\'; img-src data: cid:; style-src \\'unsafe-inline\\'\">";
        $check(str_contains($js, $politica) && str_contains($js, '<meta name="referrer" content="no-referrer">'), 'e5 el visor lleva su CSP sin red (solo data: y cid:) y su referrer vacío');
        $asignaciones = preg_match_all('/frame\.srcdoc\s*=\s*([^\n]+)/', $js, $todas) > 0 ? $todas[1] : [];
        $conCuerpo = array_values(array_filter($asignaciones, fn ($a): bool => str_contains($a, 'data.body')));
        $check(count($conCuerpo) === 1 && preg_match('/^isolation\s*\+\s*data\.body\b/', trim($conCuerpo[0])) === 1, 'e6 y el cuerpo entra SIEMPRE detrás de ella, nunca solo', implode(' | ', $conCuerpo));

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //─── z · Limpieza ───────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[z] Limpieza: las líneas del registro y los usuarios');
        set_config('current_user', $usuarioPrevio);
        set_config('pcsphp_current_user_stored', $usuarioGuardadoPrevio);
        if ($baseDatos !== null) {
            //Las líneas del registro de acciones que dejaron las lecturas: llevan el nombre del usuario de la prueba.
            $borrarRastro = $baseDatos->prepare('DELETE FROM `' . LogsMapper::model()->getTable() . '` WHERE `id` > ? AND `textMessageVariables` LIKE ?');
            $borrarRastro->execute([$maxLogPrevio, "%{$prefijo}%"]);
            $rastroQueda = $baseDatos->prepare('SELECT COUNT(*) FROM `' . LogsMapper::model()->getTable() . '` WHERE `textMessageVariables` LIKE ?');
            $rastroQueda->execute(["%{$prefijo}%"]);
            $check((int) $rastroQueda->fetchColumn() === 0, 'z0 no queda ninguna línea de la prueba en el registro de acciones');
        }
        if ($baseDatos !== null) {
            $borrar = $baseDatos->prepare("DELETE FROM `{$tabla}` WHERE `recipients` LIKE ?");
            $borrar->execute(["%{$marca}%"]);
            MailLogMapper::forgetMemo();
            $quedan = (int) $baseDatos->query("SELECT COUNT(*) FROM `{$tabla}` WHERE `recipients` LIKE '%{$marca}%'")->fetchColumn();
            $check($quedan === 0, 'z1 no queda ninguna línea de la prueba');
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $deLaPrueba = array_map(fn($u) => (int) $u->id, (array) $usuarios->result());
        foreach ($deLaPrueba as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
            $perfiles->delete(['belongsTo' => $id])->execute();
            //`SystemApprovalManager::init()` anota cada usuario nuevo al servir: esa fila no cae con él.
            $aprobaciones = SystemApprovalsMapper::model();
            $aprobaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
            $aprobaciones->delete(new WhereSegment([
                new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
            ]))->execute();
        }
        $usuarios->resetAll();
        //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z2 no queda ningún usuario de la prueba');
    }

    return $balance();

})->setDescription('La pantalla del registro de correos: 200 para el usuario principal y 403 para un administrador general, en la vista Y en los datos; una línea sembrada sale con todas sus columnas; y un asunto con etiquetas llega escapado, con su canario. NO manda correo.')->setEffects([CliActions::EFFECT_DATABASE])->register();
