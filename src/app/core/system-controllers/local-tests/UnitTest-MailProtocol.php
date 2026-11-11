<?php

//El protocolo del correo: el servidor guarda solo '', 'tls' y 'ssl' y rechaza otro; un texto libre de antes se enseña
//marcado. Por HTTP, como root; repone `mail` y `mail_delivery` en crudo. Guardar no envía correo.
//@areas src/app/classes/PiecesPHP/Settings/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/mail-protocol', function ($args) {

    echoTerminal("\e[33m[TEST:MailProtocol] El protocolo del correo: tres valores y ningún otro\e[39m");
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
    $ruta = (string) parse_url(SettingsController::routeName('integrations-mail', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $jwt, ?array $post) use ($base, $ruta, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', "{$cabeceraToken}: {$jwt}"],
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'data' => is_array($datos) ? $datos : []];
    };

    $tabla = (new SettingsModel())->getModel()->getTable();
    $crudo = function (string $opcion) use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$opcion]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $reponer = function (string $opcion, ?string $valor) use ($database, $tabla): void {
        if ($valor === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcion]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$valor, $opcion]);
        }
    };
    //Lo guardado se lee de la base y no de la configuración de este proceso, que se cargó al arrancar.
    $guardado = function (): string {
        set_config(MailConfig::CONFIG_NAME, SettingsModel::getConfigValue(MailConfig::CONFIG_NAME));
        $protocolo = (new MailConfig())->protocol();
        return is_string($protocolo) ? $protocolo : '(no es texto)';
    };
    $crudoCorreo = $crudo(MailConfig::CONFIG_NAME);
    $crudoEntrega = $crudo(MailDelivery::CONFIG_NAME);
    $blobPrevio = get_config(MailConfig::CONFIG_NAME);

    //Lo que hay, tal cual: solo cambia el protocolo de una petición a otra.
    //Como MailTester::route(): los getters devuelven el valor o el objeto, y se estrecha por tipo.
    $texto = fn(mixed $valor): string => is_string($valor) || is_int($valor) ? (string) $valor : '';
    $actual = new MailConfig();
    $formulario = [
        'host' => $texto($actual->host()),
        'port' => $texto($actual->port()),
        'user' => $texto($actual->user()),
        'password' => '',
        'name' => $texto($actual->name()),
        'auth' => $actual->auth() === true ? '1' : '0',
        'auto_tls' => $actual->autoTls() === true ? '1' : '0',
        'mail_delivery' => MailDelivery::declared(),
        'test_host' => $texto($actual->testHost()),
        'test_port' => $texto($actual->testPort()),
    ];

    $prefijo = 'zz-prueba-protocolo-' . bin2hex(random_bytes(3));
    $ids = [];

    try {
        $check($formulario['host'] !== '' && (int) $formulario['port'] > 0, 'banco: la configuración actual tiene host y puerto para reenviarlos', "{$formulario['host']}:{$formulario['port']}");
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Protocolo';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        $jwt = UserSessions::issueToken((int) $u->id);

        //─── g · Los tres valores se guardan ────────────────────────────────────────────────────────
        echoTerminal('[g] STARTTLS, SSL/TLS y Ninguno, y sus nombres habituales, se guardan con el valor de PHPMailer');
        foreach (['tls' => 'tls', '' => '', 'ssl' => 'ssl', ' SSL ' => 'ssl', 'TLS' => 'tls', 'STARTTLS' => 'tls', 'SSL/TLS' => 'ssl', 'smtps' => 'ssl', 'Ninguno' => '', 'none' => '', 'starttls' => 'tls'] as $enviado => $esperado) {
            $r = $pedir($jwt, $formulario + ['protocol' => $enviado]);
            $check($r['status'] === 200 && ($r['data']['success'] ?? null) === true && $guardado() === $esperado, 'g1 ' . var_export($enviado, true) . ' se guarda como ' . var_export($esperado, true), "HTTP {$r['status']}, guardado " . var_export($guardado(), true) . ', ' . mb_substr((string) ($r['data']['message'] ?? ''), 0, 120));
        }
        echoTerminal(' ');

        //─── r · Cualquier otro se rechaza ──────────────────────────────────────────────────────────
        echoTerminal('[r] Un valor que no es de la lista, o ninguno, se rechaza y no cambia lo guardado');
        $mensaje = __(SettingsController::LANG_GROUP, 'El protocolo debe ser STARTTLS, SSL/TLS o Ninguno.');
        foreach (['tls1.2' => 'tls1.2', 'ssl://' => 'ssl://', 'texto libre' => 'zz-inventado'] as $nombre => $enviado) {
            $r = $pedir($jwt, $formulario + ['protocol' => $enviado]);
            $check(($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? '') === $mensaje && $guardado() === 'tls', "r1 DISCRIMINANTE: «{$nombre}» se rechaza con su mensaje y lo guardado sigue en 'tls'", "HTTP {$r['status']}, guardado " . var_export($guardado(), true) . ', ' . mb_substr((string) ($r['data']['message'] ?? ''), 0, 120));
        }
        $r = $pedir($jwt, $formulario);
        $check(($r['data']['success'] ?? null) === false && ($r['data']['message'] ?? '') === $mensaje && $guardado() === 'tls', 'r2 sin el campo, se rechaza: la ausencia no es «Ninguno»', "HTTP {$r['status']}, guardado " . var_export($guardado(), true));
        echoTerminal(' ');

        //─── v · La vista ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[v] La vista: una lista de tres, y un texto libre de antes se enseña marcado');
        $html = $pedir($jwt, null)['body'];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new \DOMXPath($dom);
        $opciones = $xpath->query('//select[@id="email-protocol"]/option');
        $valores = [];
        foreach ($opciones ?: [] as $opcion) {
            if ($opcion instanceof \DOMElement) {
                $valores[] = $opcion->getAttribute('value') . ($opcion->hasAttribute('selected') ? '*' : '');
            }
        }
        $check($valores === ['tls*', 'ssl', ''], 'v1 CANARIO: un select con tls, ssl y vacío, y el guardado elegido', json_encode($valores) ?: '');
        $check($xpath->query('//input[@name="protocol"]')?->length === 0, 'v2 ya no es un campo de texto');
        $viejo = new MailConfig();
        $viejo->protocol('STARTTLS');
        $check(SettingsModel::setConfigValue(MailConfig::CONFIG_NAME, $viejo->toSave()), 'banco: guardado a mano, en crudo, «STARTTLS»');
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pedir($jwt, null)['body']);
        $marcada = (new \DOMXPath($dom))->query('//select[@id="email-protocol"]/option[@selected]')?->item(0);
        $opcionesAlias = (new \DOMXPath($dom))->query('//select[@id="email-protocol"]/option')?->length;
        $check($marcada instanceof \DOMElement && $marcada->getAttribute('value') === 'tls' && $opcionesAlias === 3, 'v3 DISCRIMINANTE: un «STARTTLS» guardado sale como STARTTLS elegido, sin «(valor actual, no válido)»', $marcada instanceof \DOMElement ? $marcada->getAttribute('value') . ' / ' . $opcionesAlias : 'sin opción elegida');
        $raro = new MailConfig();
        $raro->protocol('zz-inventado');
        SettingsModel::setConfigValue(MailConfig::CONFIG_NAME, $raro->toSave());
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $pedir($jwt, null)['body']);
        $marcada = (new \DOMXPath($dom))->query('//select[@id="email-protocol"]/option[@selected]')?->item(0);
        $check($marcada instanceof \DOMElement && $marcada->getAttribute('value') === 'zz-inventado' && str_contains($marcada->textContent, __(SettingsController::LANG_GROUP, '(valor actual, no válido)')), 'v4 un texto inventado se enseña tal cual, marcado como no válido', $marcada instanceof \DOMElement ? $marcada->textContent : 'sin opción elegida');
        $r = $pedir($jwt, $formulario + ['protocol' => 'zz-inventado']);
        $check(($r['data']['success'] ?? null) === false && $guardado() === 'zz-inventado', 'v5 y guardarlo tal cual se rechaza: hay que elegir uno de la lista', var_export($guardado(), true));
        echoTerminal(' ');

        //─── m · El Mailer, en proceso y sin enviar ─────────────────────────────────────────────────
        echoTerminal('[m] El Mailer recibe el protocolo normalizado (con la entrega real declarada solo en memoria: no envía)');
        $entregaEnMemoria = get_config(MailDelivery::CONFIG_NAME);
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::REAL);
        foreach (['STARTTLS' => 'tls', 'SSL/TLS' => 'ssl', 'Ninguno' => '', 'zz-inventado' => 'zz-inventado'] as $guardadoEnCrudo => $esperado) {
            $config = new MailConfig();
            $config->protocol($guardadoEnCrudo);
            set_config(MailConfig::CONFIG_NAME, $config->toSave());
            $enviador = new Mailer(false);
            $check($enviador->SMTPSecure === $esperado, "m1 «{$guardadoEnCrudo}» guardado llega a PHPMailer como " . var_export($esperado, true), var_export($enviador->SMTPSecure, true));
        }
        set_config(MailDelivery::CONFIG_NAME, $entregaEnMemoria);
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $reponer(MailConfig::CONFIG_NAME, $crudoCorreo);
        $reponer(MailDelivery::CONFIG_NAME, $crudoEntrega);
        set_config(MailConfig::CONFIG_NAME, $blobPrevio);
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && $crudo(MailConfig::CONFIG_NAME) === $crudoCorreo && $crudo(MailDelivery::CONFIG_NAME) === $crudoEntrega, 'z1 no queda el usuario y `mail` y `mail_delivery` vuelven a su valor en crudo', "usuarios {$quedan}");
    }

    return $balance();

})->setDescription('El protocolo de Configuración › Correo: se guardan solo STARTTLS, SSL/TLS y Ninguno (tls, ssl y vacío) y sus nombres habituales, normalizados; otro valor o su ausencia se rechazan; un alias guardado sale como el suyo y uno inventado, marcado como no válido; el Mailer recibe el normalizado. Por HTTP; repone las opciones.')->setEffects([CliActions::EFFECT_DATABASE])->register();
