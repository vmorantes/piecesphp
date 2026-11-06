<?php

//«Correos del sitio» (H189): quién la ve, que cada correo se pinta sin huecos, que el envío real usa su definición y que
//«Enviar prueba» solo sale con la entrega retenida. Lo que se envía va al servidor de pruebas; siembra zz- y lo retira.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\MailCatalog\Controllers\MailCatalogController;
use PiecesPHP\MailCatalog\Definitions\RecoveryPasswordMail;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/mail-catalog', function ($args) {

    echoTerminal("\e[33m[TEST:MailCatalog] Correos del sitio: acceso, muestras sin huecos, un solo camino de render y Enviar prueba\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que core/user-sessions.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $pantalla = $ruta('mail-catalog-list');
    $enviarPrueba = $ruta('mail-catalog-send-test');
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $mailpit = function (string $method, string $path, ?array $payload = null): ?array {
        $opciones = ['method' => $method, 'timeout' => 5, 'ignore_errors' => true];
        if ($payload !== null) {
            $opciones['header'] = 'Content-Type: application/json';
            $opciones['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        $respuesta = @file_get_contents('http://127.0.0.1:8025/api/v1' . $path, false, stream_context_create(['http' => $opciones]));
        $decoded = is_string($respuesta) ? json_decode($respuesta, true) : null;
        return is_array($decoded) ? $decoded : null;
    };
    //Lo de Mailpit que va a una de estas direcciones (la bandeja es de la máquina: solo se toca lo propio).
    $enMailpit = function (array $direcciones) use ($mailpit): array {
        $lista = $mailpit('GET', '/messages?limit=200');
        $propios = [];
        foreach ((array) ($lista['messages'] ?? []) as $mensaje) {
            foreach ((array) ($mensaje['To'] ?? []) as $destino) {
                if (is_array($destino) && in_array(mb_strtolower((string) ($destino['Address'] ?? '')), $direcciones, true)) {
                    $propios[] = $mensaje;
                    continue 2;
                }
            }
        }
        return $propios;
    };
    $texto = fn(string $html): string => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-prueba-catalogo-{$marca}";
    //ADR 0011: las direcciones de prueba, zz-prueba-…@mailinator.com; aquí nunca salen de la máquina (retenido o desviado).
    $destino = "{$prefijo}@mailinator.com";
    $destinoReal = "{$prefijo}-real@mailinator.com";
    $destinoSinRetener = "{$prefijo}-sin-retener@mailinator.com";
    $tablaUsuarios = UsersModel::TABLE;
    $tablaRegistro = MailLogMapper::tableName();
    $usuarios = [];
    $maxLog = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();
    $maxRegistro = (int) $database->query("SELECT COALESCE(MAX(`id`), 0) FROM `{$tablaRegistro}`")->fetchColumn();
    $configOriginal = ['mail' => get_config('mail'), MailDelivery::CONFIG_NAME => get_config(MailDelivery::CONFIG_NAME)];

    $crear = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@mailinator.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Catalogo';
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
    $sesion = fn(int $userID): string => SessionToken::generateToken(['id' => $userID, 'sid' => UserSessions::open($userID)], null, SessionToken::DURATION_MAX, false);

    try {
        $root = $sesion($crear('root', UsersModel::TYPE_USER_ROOT));
        $gral = $sesion($crear('gral', UsersModel::TYPE_USER_ADMIN_GRAL));
        $general = $sesion($crear('general', UsersModel::TYPE_USER_GENERAL));
        $idRoot = $usuarios['root'];

        //─── a · Quién la ve ──────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] La pantalla');
        $deRoot = $pedir($pantalla, $root);
        $tarjetas = preg_match_all('/data-mail-catalog-card="/', $deRoot['body']);
        $check($deRoot['status'] === 200 && $tarjetas === count(MailCatalog::all()) && $tarjetas > 0, 'a1 root la ve, con una tarjeta por correo registrado', "HTTP {$deRoot['status']}, {$tarjetas} de " . count(MailCatalog::all()));
        $check(str_contains($deRoot['body'], 'data-mail-catalog-error') === false, 'a2 ningún correo sale como tarjeta de error');
        $check($pedir($pantalla, $gral)['status'] === 200, 'a3 el administrador general la ve');
        $deGeneral = $pedir($pantalla, $general);
        $check($deGeneral['status'] !== 200 && !str_contains($deGeneral['body'], 'data-mail-catalog-card'), 'a4 un usuario general no', "HTTP {$deGeneral['status']}");

        //─── b · Cada correo, con su muestra, sin huecos (#2449) ───────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Las muestras');
        $huecos = [];
        foreach (MailCatalog::all() as $clave => $definicion) {
            $muestra = MailCatalog::preview($definicion);
            if ($muestra['error'] !== null || trim($muestra['subject']) === '' || $texto($muestra['body']) === '') {
                $huecos[] = "{$clave}: sin pintar (" . ($muestra['error'] ?? 'vacío') . ')';
                continue;
            }
            $dom = new \DOMDocument();
            //RETORNO-IGNORADO: un HTML de correo no es XML válido; lo que importa son los nodos que sí se leen.
            @$dom->loadHTML('<?xml encoding="utf-8"?>' . $muestra['body']);
            foreach (['a', 'button'] as $etiqueta) {
                foreach ($dom->getElementsByTagName($etiqueta) as $nodo) {
                    if (trim($nodo->textContent) === '' && $nodo->getElementsByTagName('img')->length === 0) {
                        $huecos[] = "{$clave}: un <{$etiqueta}> sin texto";
                    }
                }
            }
            if (preg_match('/\{\{|%[sd]\b|\{[A-Z_]{3,}\}/', $muestra['subject'] . ' ' . $texto($muestra['body']), $marcador) === 1) {
                $huecos[] = "{$clave}: marcador sin sustituir «{$marcador[0]}»";
            }
        }
        $check($huecos === [], 'b1 cada correo se pinta con su muestra, sin enlaces ni botones vacíos ni marcadores sin sustituir', implode('; ', $huecos));

        //─── c · El envío real usa su definición ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Un solo camino de render');
        $mailpitVivo = $mailpit('GET', '/info') !== null;
        $check($mailpitVivo, 'c0 Mailpit responde en 127.0.0.1:8025');
        //Solo en memoria: el SMTP de esta prueba es el Mailpit local; nada guardado cambia.
        $desvio = new MailConfig;
        $desvio->isSmtp(true);
        $desvio->smtpDebug(0);
        $desvio->host('127.0.0.1');
        $desvio->port(1025);
        $desvio->auth(false);
        $desvio->protocol('');
        $desvio->autoTls(false);
        $desvio->user('zz-prueba-remitente@mailinator.com');
        $desvio->password('');
        $desvio->name('ZZ Prueba');
        set_config('mail', $desvio->toSave());
        $usuarioReal = (object) ['email' => $destinoReal, 'username' => $prefijo];
        $enviado = (new RecoveryPasswordController())->mailRecoveryPasswordCode('ZZ7C0DE', $usuarioReal, false);
        $mensajes = $mailpitVivo ? $enMailpit([$destinoReal]) : [];
        $detalle = count($mensajes) === 1 ? ($mailpit('GET', '/message/' . rawurlencode((string) ($mensajes[0]['ID'] ?? ''))) ?? []) : [];
        //Sin `renderer`: el camino de la vista previa, que no construye el controlador del envío.
        $esperado = (new RecoveryPasswordMail(false))->render(['code' => 'ZZ7C0DE', 'email' => $destinoReal]);
        $check($enviado === true && $detalle !== [] && $texto((string) ($detalle['HTML'] ?? '')) === $texto($esperado),
            'c1 el correo real de recuperación llega con el mismo cuerpo que su definición pintada como en la vista previa', 'enviado ' . var_export($enviado, true) . ', ' . count($mensajes) . ' en Mailpit');

        //─── d · Enviar prueba ────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Enviar prueba');
        $check(MailDelivery::goesToSink(), 'd0 CANARIO: en esta instalación la entrega está retenida');
        $definicion = MailCatalog::get('contact-form');
        $r = $pedir($enviarPrueba, $root, ['key' => 'contact-form', 'to' => $destino]);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === true, 'd1 con la entrega retenida, root envía la prueba', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));
        $fila = $database->prepare("SELECT subject, result FROM `{$tablaRegistro}` WHERE `id` > ? AND `recipients` LIKE ? ORDER BY `id` DESC LIMIT 1");
        $fila->execute([$maxRegistro, "%{$destino}%"]);
        $registro = $fila->fetch(\PDO::FETCH_ASSOC);
        $asuntoEsperado = MailCatalogController::TEST_SUBJECT_PREFIX . ($definicion !== null ? $definicion->subject($definicion->sampleData()) : '');
        $check(is_array($registro) && $registro['subject'] === $asuntoEsperado && in_array($registro['result'], [MailLogMapper::RESULT_DELIVERED, MailLogMapper::RESULT_OUTBOX], true),
            'd2 queda en el registro de correos, retenida, con «[PRUEBA]» delante del asunto', json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $enBandeja = $mailpitVivo ? $enMailpit([$destino]) : [];
        $check(count($enBandeja) === 1 && str_starts_with((string) ($enBandeja[0]['Subject'] ?? ''), '[PRUEBA] '), 'd3 llega al servidor de pruebas (Mailpit local)', count($enBandeja) . ' mensaje(s): ' . (string) ($enBandeja[0]['Subject'] ?? ''));
        $accion = $database->prepare('SELECT textMessageVariables FROM `' . LogsMapper::TABLE . '` WHERE id > ? AND createdBy = ? AND textMessage = ?');
        $accion->execute([$maxLog, $idRoot, LogsMapper::MESSAGES[LogsMapper::MSG_MAIL_TEST_SENT]]);
        $variables = (string) $accion->fetchColumn();
        $check(str_contains($variables, 'contact-form') && str_contains($variables, $destino), 'd4 deja su fila en el registro de acciones, con quién, qué correo y a quién', $variables);
        $check($pedir($enviarPrueba, $root, ['key' => 'zz-inventada', 'to' => $destino])['status'] === 404, 'd5 una clave que no está registrada se rechaza con 404, no con un 500');
        $check($pedir($enviarPrueba, $root, ['key' => 'contact-form', 'to' => 'no-es-un-correo'])['status'] === 400, 'd6 un destinatario mal escrito se rechaza con 400');
        $check($pedir($enviarPrueba, $general, ['key' => 'contact-form', 'to' => $destino])['status'] !== 200, 'd7 un usuario general no puede enviarla');

        //Sin retener: en proceso, con la entrega «real» SOLO en memoria y el SMTP desviado al Mailpit local, así que ni
        //quitando la comprobación del servidor saldría nada de la máquina.
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::REAL);
        $peticion = (new RequestRoute('POST', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-catalogo'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream('')))
            ->withParsedBody(['key' => 'contact-form', 'to' => $destinoSinRetener]);
        $respuesta = (new MailCatalogController())->sendTest($peticion, new ResponseRoute());
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        set_config(MailDelivery::CONFIG_NAME, $configOriginal[MailDelivery::CONFIG_NAME]);
        $sinRetenerEnBandeja = $mailpitVivo ? count($enMailpit([$destinoSinRetener])) : 0;
        $check($respuesta->getStatusCode() === 409 && is_array($cuerpo) && ($cuerpo['success'] ?? null) === false && $sinRetenerEnBandeja === 0,
            'd8 sin la entrega retenida, el servidor la rechaza aunque se pida a mano, y no envía nada', "HTTP {$respuesta->getStatusCode()}, {$sinRetenerEnBandeja} en Mailpit");

    } catch (\Throwable $e) {
        $check(false, 'z0 la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($configOriginal as $clave => $valor) {
            set_config($clave, $valor === false ? null : $valor);
        }
        try {
            $propios = $enMailpit([$destino, $destinoReal, $destinoSinRetener]);
            $ids = array_values(array_filter(array_map(fn($m): string => (string) ($m['ID'] ?? ''), $propios)));
            if ($ids !== []) {
                //RETORNO-IGNORADO: si el borrado fallara, z1 lo cuenta.
                $mailpit('DELETE', '/messages', ['IDs' => $ids]);
            }
            $database->prepare("DELETE FROM `{$tablaRegistro}` WHERE `id` > ? AND `recipients` LIKE ?")->execute([$maxRegistro, "%{$prefijo}%"]);
            if ($usuarios !== []) {
                $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute(array_values($usuarios));
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLog], array_values($usuarios)));
            }
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql, array $valores) use ($database): int {
            $consulta = $database->prepare($sql);
            $consulta->execute($valores);
            return (int) $consulta->fetchColumn();
        };
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"])
         + $cuenta("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE `id` > ? AND `recipients` LIKE ?", [$maxRegistro, "%{$prefijo}%"])
         + count($enMailpit([$destino, $destinoReal, $destinoSinRetener]));
        $check($quedan === 0, 'z1 no queda ningún usuario, fila del registro de correos ni mensaje de Mailpit de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('Correos del sitio: la ven root y el administrador general; cada correo se pinta con su muestra sin huecos; el correo real de recuperación sale con el cuerpo de su definición; Enviar prueba solo con la entrega retenida, con [PRUEBA], su fila y su rechazo limpio. Crea y borra usuarios zz-, filas del registro y mensajes de Mailpit.')->setEffects([CliActions::EFFECT_DATABASE])->register();
