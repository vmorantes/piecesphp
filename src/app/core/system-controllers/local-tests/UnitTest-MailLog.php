<?php

//Queda registro de cada correo y de si llegó: tres resultados, el cuerpo CIFRADO y con tope.
//ADR 0043 §5; nace de que el buzón hizo que una entrega mal declarada guarde en silencio (322.11).

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\MailDoctorTask;

CliActions::make('unit-tests:core/mail-log', function ($args) {

    echoTerminal("\e[33m[TEST:MailLog] Cada correo deja su línea, con su resultado y su cuerpo cifrado\e[39m");
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

    $marca = 'zz-maillog-' . bin2hex(random_bytes(3));
    $tabla = MailLogMapper::TABLE;
    $modelo = MailLogMapper::model();
    $modelo->resetAll();
    $base = $modelo->getDatabase();
    if (!$check($base !== null, 'p0 hay conexión a la base')) {
        return $balance();
    }

    //Una prueba NO migra la instalación (DR, 2026-10-05): sin la tabla o sin la columna del cuerpo, lo
    //dice como precondición y para. El SQL lo aplica el operador desde databases/actualizaciones/.
    $existeTabla = (bool) $base->query("SHOW TABLES LIKE '{$tabla}'")->fetchColumn();
    if (!$check($existeTabla, 'p1 la tabla del registro existe', "{$tabla}: aplica databases/actualizaciones/2026-10-03-registro-de-correos.sql")) {
        return $balance();
    }
    if (!$check(MailLogMapper::bodyColumnExists(), 'p2 y tiene la columna del cuerpo', 'aplica ' . MailLogMapper::BODY_UPDATE_FILE)) {
        return $balance();
    }

    $blobPrevio = get_config('mail');
    $entornoReal = AppEnvironment::get();
    $entornoH = null;
    $entregaPrevia = get_config(MailDelivery::CONFIG_NAME);
    $filasAntes = (int) $base->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn();
    echoTerminal("   líneas en el registro antes: {$filasAntes}");

    $guardarMail = function (array $valores): void {
        $config = new MailConfig();
        foreach ($valores as $metodo => $valor) {
            $config->$metodo($valor);
        }
        //Solo en memoria: todo envío de la suite es de este proceso, y la base no se toca.
        set_config('mail', $config->toSave());
    };
    $correo = function (string $sufijo) use ($marca): Mailer {
        $mailer = new Mailer(false);
        $mailer->setFrom('zz-prueba-remitente@localhost.test', 'ZZ Prueba');
        $mailer->addAddress("{$marca}-{$sufijo}@localhost.test");
        $mailer->Subject = "ZZ asunto {$marca} {$sufijo}";
        $mailer->isHTML(true);
        $mailer->Body = '<p>ZZ CUERPO SECRETO QUE SOLO SE GUARDA CIFRADO · canción ñandú</p>';
        return $mailer;
    };
    //El sumidero es DE LA MÁQUINA y tiene correo de otros trabajos: se retira SOLO lo propio, por
    //sus identificadores. Sin esto, cada bin/verify dejaba un mensaje en la bandeja de quien lo corre.
    $retirarDelSumidero = function () use ($marca): int {
        $api = 'http://127.0.0.1:8025/api/v1';
        $contexto = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]]);
        $cuerpo = @file_get_contents("{$api}/messages?limit=200", false, $contexto);
        $lista = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $ids = [];
        foreach ((is_array($lista) ? ($lista['messages'] ?? []) : []) as $mensaje) {
            foreach ((array) (is_array($mensaje) ? ($mensaje['To'] ?? []) : []) as $destino) {
                $direccion = is_array($destino) ? (string) ($destino['Address'] ?? '') : '';
                if (str_contains($direccion, $marca)) {
                    $ids[] = (string) (is_array($mensaje) ? ($mensaje['ID'] ?? '') : '');
                    continue 2;
                }
            }
        }
        $ids = array_values(array_filter($ids));
        if ($ids === []) {
            return 0;
        }
        $borrado = stream_context_create(['http' => [
            'method' => 'DELETE',
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => 'Content-Type: application/json',
            'content' => json_encode(['IDs' => $ids], JSON_THROW_ON_ERROR),
        ]]);
        //RETORNO-IGNORADO: quien dice si se borraron es el conteo de la comprobación z2.
        @file_get_contents("{$api}/messages", false, $borrado);
        return count($ids);
    };
    $enSumidero = function () use ($marca): int {
        $contexto = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true]]);
        $cuerpo = @file_get_contents('http://127.0.0.1:8025/api/v1/messages?limit=200', false, $contexto);
        $lista = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $propios = 0;
        foreach ((is_array($lista) ? ($lista['messages'] ?? []) : []) as $mensaje) {
            foreach ((array) (is_array($mensaje) ? ($mensaje['To'] ?? []) : []) as $destino) {
                $direccion = is_array($destino) ? (string) ($destino['Address'] ?? '') : '';
                if (str_contains($direccion, $marca)) {
                    $propios++;
                    continue 2;
                }
            }
        }
        return $propios;
    };
    $lineaDe = function (string $sufijo) use ($base, $tabla, $marca): ?array {
        $statement = $base->prepare("SELECT * FROM `{$tabla}` WHERE `recipients` LIKE ? ORDER BY `id` DESC LIMIT 1");
        $statement->execute(["%{$marca}-{$sufijo}@%"]);
        $fila = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($fila) ? $fila : null;
    };

    try {

        //─── a · Llegó al sumidero ───────────────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[a] Un correo que llega deja su línea con «entregado»');
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::SINK);
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => 1025]);
        $entregado = $correo('a')->send();
        $filaA = $lineaDe('a');
        $check($entregado === true, 'a1 el envío al sumidero sale bien', var_export($entregado, true));
        $check($filaA !== null, 'a2 y deja su línea en el registro');
        $check($filaA !== null && $filaA['result'] === MailLogMapper::RESULT_DELIVERED, 'a3 con resultado «entregado»', (string) ($filaA['result'] ?? ''));
        $check($filaA !== null && $filaA['delivery'] === MailDelivery::SINK, 'a4 y la entrega que estaba declarada', (string) ($filaA['delivery'] ?? ''));
        $check($filaA !== null && str_contains((string) $filaA['subject'], $marca), 'a5 y su asunto');
        $check($filaA !== null && is_string($filaA['origin']) && $filaA['origin'] !== '', 'a6 y de dónde salió el envío', (string) ($filaA['origin'] ?? ''));
        $origen = $filaA !== null && is_string($filaA['origin']) ? (string) $filaA['origin'] : '';
        //Esto se pinta en una pantalla del panel: ni la ruta del servidor ni un byte de control.
        $check(!str_contains($origen, '/'), 'a6b el origen NO trae la ruta del servidor, solo el nombre del archivo', $origen);
        $check(preg_match('/[\x00-\x1F\x7F]/', $origen) !== 1, 'a6c ni ningún carácter de control, que en una pantalla no se pinta');
        //Un closure se nombra por su archivo y su línea, que es el sitio exacto del envío: el nombre
        //de la clase sería el del cargador, porque PHP le atribuye el ámbito donde se creó.
        $check(preg_match('/\.php:\d+$/', $origen) === 1 || str_contains($origen, '::'), 'a6d y nombra un archivo:línea o una clase::método', $origen);

        //─── b · Acabó en el buzón ───────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Un correo retenido que no encuentra sumidero deja su línea con «buzón»');
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => 1]);
        $buzon = MailDelivery::outboxDirectory();
        $emlAntes = count((array) glob("{$buzon}/*.eml"));
        $alBuzon = $correo('b')->send();
        $filaB = $lineaDe('b');
        $check($alBuzon === true, 'b1 el envío no cuenta como fallo');
        $check($filaB !== null && $filaB['result'] === MailLogMapper::RESULT_OUTBOX, 'b2 y su línea dice «buzón», que es un resultado y no un silencio', (string) ($filaB['result'] ?? ''));
        $check($filaB !== null && is_string($filaB['reason']) && $filaB['reason'] !== '', 'b3 con el motivo por el que no llegó', mb_substr((string) ($filaB['reason'] ?? ''), 0, 90));
        foreach (array_slice(array_diff((array) glob("{$buzon}/*.eml"), []), $emlAntes) as $recien) {
            //RETORNO-IGNORADO: el .eml de la prueba se retira; si quedara, lo diría b5.
            @unlink((string) $recien);
        }
        $check(count((array) glob("{$buzon}/*.eml")) === $emlAntes, 'b5 y la prueba retira el .eml que creó');

        //─── c · No llegó a ningún sitio: el caso que nadie veía ─────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Un correo REAL con el SMTP caído deja su línea con «falló»');
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::REAL);
        $guardarMail(['host' => '127.0.0.1', 'port' => 1, 'auth' => false, 'protocol' => '', 'autoTls' => false, 'isSmtp' => true]);
        $fallo = null;
        try {
            $fallo = $correo('c')->send();
        } catch (\Throwable $throwable) {
            $fallo = $throwable;
        }
        $filaC = $lineaDe('c');
        $check($fallo === false || $fallo instanceof \Throwable, 'c1 el envío no sale (falla o lanza)', is_object($fallo) ? get_class($fallo) : var_export($fallo, true));
        $check($filaC !== null && $filaC['result'] === MailLogMapper::RESULT_FAILED, 'c2 y su línea dice «falló», con el SMTP real caído', (string) ($filaC['result'] ?? ''));
        $check($filaC !== null && $filaC['delivery'] === MailDelivery::REAL, 'c3 y la entrega declarada era «real»', (string) ($filaC['delivery'] ?? ''));
        $check($filaC !== null && is_string($filaC['reason']) && $filaC['reason'] !== '', 'c4 con su motivo', mb_substr((string) ($filaC['reason'] ?? ''), 0, 90));

        //─── d · El cuerpo SÍ está, pero CIFRADO (ADR 0048) ──────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] El registro guarda el cuerpo, y lo guarda cifrado');
        $cuerpoEnviado = $correo('d')->Body;
        //Prueba 2: en la base el texto NO aparece, en ninguna columna, tampoco en la del cuerpo.
        $enClaro = $base->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE CONCAT_WS(' ', `recipients`, `subject`, `origin`, `delivery`, `result`, IFNULL(`reason`, ''), IFNULL(`body`, '')) LIKE ?");
        $enClaro->execute(['%CUERPO SECRETO%']);
        $check((int) $enClaro->fetchColumn() === 0, 'd1 el texto del correo NO aparece en ninguna columna, tampoco en la del cuerpo: está cifrado');
        //Prueba 1: ida y vuelta, carácter a carácter, en los tres resultados.
        foreach (['a' => $filaA, 'b' => $filaB, 'c' => $filaC] as $letra => $fila) {
            $cifrado = is_array($fila) && is_string($fila['body'] ?? null) ? (string) $fila['body'] : '';
            $claro = $cifrado !== '' ? MailLogMapper::decryptBody($cifrado) : null;
            $check($claro === $cuerpoEnviado, "d2{$letra} la línea «{$letra}» guarda el cuerpo y descifrado coincide carácter a carácter con el enviado", $claro === null ? 'sin cuerpo o ilegible' : 'longitud ' . strlen($claro) . ' contra ' . strlen($cuerpoEnviado));
        }

        //─── d2 · La marca de la instalación, para que una bandeja compartida sea legible ────────────────
        echoTerminal(' ');
        echoTerminal('[f] Cada correo lleva la marca de su instalación');
        $etiqueta = MailDelivery::installationTag();
        $check($etiqueta !== '', 'f1 la instalación tiene una marca, sacada de base_url', $etiqueta);
        //Desde la terminal `base_url` es http://localhost: la marca sale de la carpeta, o dos clones serían iguales.
        $deTerminalA = MailDelivery::installationTag('http://localhost', '/var/www/html/zz-clon-uno');
        $deTerminalB = MailDelivery::installationTag('http://localhost', '/var/www/html/zz-clon-dos');
        $check($deTerminalA === 'zz-clon-uno' && $deTerminalB === 'zz-clon-dos', 'f1b con base_url http://localhost, dos instalaciones en carpetas distintas dan dos marcas distintas', "{$deTerminalA} / {$deTerminalB}");
        $check(MailDelivery::installationTag('http://127.0.0.1/', '/srv/tienda') === 'tienda' && MailDelivery::installationTag('', '/srv/tienda') === 'tienda', 'f1c 127.0.0.1 y vacío, igual: la carpeta');
        //CANARIO: con un base_url de verdad, la marca de siempre, y la carpeta no cuenta.
        $check(MailDelivery::installationTag('https://ejemplo.com/tienda/', '/srv/otra-cosa') === 'ejemplo-com-tienda', 'f1d CANARIO: con un base_url real, la marca de siempre (host y ruta), sin mirar la carpeta', MailDelivery::installationTag('https://ejemplo.com/tienda/', '/srv/otra-cosa'));
        //Se mira el .eml del buzón, que es el mensaje tal y como salió: ahí está la cabecera de verdad.
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::SINK);
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => 1]);
        $buzonF = MailDelivery::outboxDirectory();
        $antesF = (array) glob("{$buzonF}/*.eml");
        $correo('f')->send();
        $despuesF = array_values(array_diff((array) glob("{$buzonF}/*.eml"), $antesF));
        $crudo = count($despuesF) === 1 ? (string) file_get_contents((string) $despuesF[0]) : '';
        $check($crudo !== '', 'f2 el mensaje quedó en el buzón para poder mirarlo');
        $check(str_contains($crudo, 'X-Tags:'), 'f3 y lleva la cabecera X-Tags, que es la que Mailpit agrupa (medido el 2026-10-03)');
        $check(str_contains($crudo, $etiqueta), 'f4 con la marca de esta instalación dentro', $etiqueta);
        foreach ($despuesF as $recienF) {
            //RETORNO-IGNORADO: lo comprueba f5 contando los .eml del buzón.
            @unlink((string) $recienF);
        }
        $check(count((array) glob("{$buzonF}/*.eml")) === count($antesF), 'f5 y la prueba retira el .eml que creó');

        //ADR 0051: con la clave DERIVADA. La cruda descifra también lo que llega de fuera, y con ella NO se lee.
        $cifradoA = is_array($filaA) && is_string($filaA['body'] ?? null) ? (string) $filaA['body'] : '';
        try {
            $conLaCruda = BaseHashEncryption::decryptBidirectionalHash($cifradoA, (string) Config::app_key());
        } catch (\Throwable $e) {
            $conLaCruda = null;
        }
        $check($cifradoA !== '' && $conLaCruda !== $cuerpoEnviado, 'd3 con la clave CRUDA de la aplicación el cuerpo NO se descifra: se cifró con la derivada');
        $check(MailLogMapper::bodyKey() === Config::app_key_derived(MailLogMapper::BODY_KEY_PURPOSE) && MailLogMapper::bodyKey() !== Config::app_key(), 'd4 y la derivada es la de su uso, distinta de la cruda');

        //─── e · El tope: la tabla no crece sin fin ──────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] El tope recorta lo viejo y deja lo nuevo');
        $tope = MailLogMapper::maxRows();
        echoTerminal("   el tope en uso es de {$tope} línea(s)");
        //Con la tabla por debajo del tope, `trim()` NO debe borrar nada: es el canario del recorte.
        $retiradas = MailLogMapper::trim();
        $check($retiradas === 0, 'e2 CANARIO: por debajo del tope no retira ninguna línea', (string) $retiradas);

        //Tres líneas propias, con el id que da la base: el recorte se prueba por su mecanismo —dejar
        //las N más recientes por id— sin tocar la constante de producción.
        $sembrar = $base->prepare("INSERT INTO `{$tabla}` (`sentAt`, `recipients`, `subject`, `delivery`, `result`) VALUES (NOW(), ?, ?, ?, ?)");
        $ids = [];
        foreach ([1, 2, 3] as $n) {
            $sembrar->execute(["{$marca}-tope-{$n}@localhost.test", "ZZ tope {$n}", MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED]);
            $ids[] = (int) $base->lastInsertId();
        }
        if (!$check(count($ids) === 3 && $ids[2] > $ids[0], 'e3 CANARIO: hay tres líneas propias y sus id crecen', implode(',', $ids))) {
            return $balance();
        }

        //El recorte, con el mismo SQL que `trim()`: conservar 1 y quitar lo anterior.
        $corte = $ids[2];
        $recorte = $base->prepare("DELETE FROM `{$tabla}` WHERE `id` < ? AND `recipients` LIKE ?");
        $recorte->execute([$corte, "%{$marca}-tope-%"]);
        $check($recorte->rowCount() === 2, 'e4 el recorte por id quita las dos anteriores', (string) $recorte->rowCount());
        $quedanTope = (int) $base->query("SELECT COUNT(*) FROM `{$tabla}` WHERE `recipients` LIKE '%{$marca}-tope-%'")->fetchColumn();
        $check($quedanTope === 1, 'e5 y deja exactamente la más reciente', (string) $quedanTope);
        $check((int) $base->query("SELECT COUNT(*) FROM `{$tabla}` WHERE `id` = {$corte}")->fetchColumn() === 1, 'e6 que es la que tenía el id mayor');

        //El tope del clon, con su suelo: por debajo de MIN_ROWS se queda en MIN_ROWS, sin lanzar.
        MailLogMapper::setMaxRows(5);
        $check(MailLogMapper::maxRows() === MailLogMapper::MIN_ROWS, 'e7 un tope por debajo del suelo se queda en el suelo', (string) MailLogMapper::maxRows());
        MailLogMapper::setMaxRows(50000);
        $check(MailLogMapper::maxRows() === 50000, 'e8 uno por encima se respeta: no hay techo');
        MailLogMapper::setMaxRows(null);
        $check(MailLogMapper::maxRows() === MailLogMapper::MAX_ROWS, 'e9 y con null vuelve al de fábrica');
        //Y `trim()` usa el tope EN USO: sobre una tabla de prueba propia con el suelo + 2 líneas.
        $tablaTope = $tabla . '_zz_tope_' . bin2hex(random_bytes(3));
        $base->exec("CREATE TABLE `{$tablaTope}` LIKE `{$tabla}`");
        try {
            $valores = implode(', ', array_fill(0, MailLogMapper::MIN_ROWS + 2, "(NOW(), 'zz-tope@localhost.test', 'zz', 'sink', 'delivered')"));
            $base->exec("INSERT INTO `{$tablaTope}` (`sentAt`, `recipients`, `subject`, `delivery`, `result`) VALUES {$valores}");
            MailLogMapper::useTableForTesting($tablaTope);
            MailLogMapper::setMaxRows(MailLogMapper::MIN_ROWS);
            $retiradasTope = MailLogMapper::trim();
            $quedanEnCopia = (int) $base->query("SELECT COUNT(*) FROM `{$tablaTope}`")->fetchColumn();
            $check($retiradasTope === 2 && $quedanEnCopia === MailLogMapper::MIN_ROWS, 'e10 trim() recorta al tope EN USO, no a la constante', "{$retiradasTope} retiradas, quedan {$quedanEnCopia}");
        } finally {
            MailLogMapper::setMaxRows(null);
            MailLogMapper::useTableForTesting(null);
            $base->exec("DROP TABLE IF EXISTS `{$tablaTope}`");
        }

        //─── h · Lo que el clon decide, los adjuntos y el cifrado que falla ─────────────────────────────
        echoTerminal(' ');
        echoTerminal('[h] El transformer del clon, los adjuntos y el cifrado que falla');
        //Al buzón en disco: ni Mailpit ni red. Cada .eml se retira al terminar el bloque.
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::SINK);
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => 1]);
        $buzonH = MailDelivery::outboxDirectory();
        $emlAntesH = (array) glob("{$buzonH}/*.eml");
        $cuerpoDe = function (?array $fila): ?string {
            $cifrado = is_array($fila) && is_string($fila['body'] ?? null) ? (string) $fila['body'] : '';
            return $cifrado !== '' ? MailLogMapper::decryptBody($cifrado) : null;
        };

        //Prueba 3: un transformer que devuelve null no guarda cuerpo, y la fila existe.
        MailLogMapper::setBodyTransformer(fn (string $body): ?string => null);
        $correo('h3')->send();
        $filaH3 = $lineaDe('h3');
        $check($filaH3 !== null && $filaH3['result'] === MailLogMapper::RESULT_OUTBOX, 'h3a con el transformer a null la fila existe, con su resultado', (string) ($filaH3['result'] ?? 'sin fila'));
        $check($filaH3 !== null && $filaH3['body'] === null, 'h3b y sin cuerpo');

        //Prueba 4: un transformer que tacha guarda lo tachado, no el original.
        MailLogMapper::setBodyTransformer(fn (string $body): ?string => str_replace('SECRETO', '[TACHADO]', $body));
        $correo('h4')->send();
        $claroH4 = $cuerpoDe($lineaDe('h4'));
        $check(is_string($claroH4) && str_contains($claroH4, '[TACHADO]'), 'h4a con el transformer que tacha se guarda lo transformado');
        $check(is_string($claroH4) && !str_contains($claroH4, 'SECRETO'), 'h4b y NO el original');
        MailLogMapper::setBodyTransformer(null);

        //Prueba 5: con adjunto, el adjunto no está en ninguna parte: ni en la base ni en disco.
        $marcaAdjunto = 'ZZ-ADJUNTO-' . bin2hex(random_bytes(6));
        $adjuntoB64 = base64_encode($marcaAdjunto);
        $inicioH5 = time();
        $conAdjunto = $correo('h5');
        $conAdjunto->addStringAttachment("contenido del adjunto {$marcaAdjunto}", 'zz-adjunto.txt');
        $conAdjunto->send();
        $filaH5 = $lineaDe('h5');
        $check($filaH5 !== null, 'h5a con adjunto, la fila existe');
        $crudoH5 = is_array($filaH5) ? implode(' ', array_map(fn ($v): string => (string) $v, $filaH5)) : '';
        $claroH5 = (string) $cuerpoDe($filaH5);
        $check(!str_contains($crudoH5, $marcaAdjunto) && !str_contains($crudoH5, $adjuntoB64), 'h5b el adjunto no está en la fila, ni en claro ni en base64');
        $check($claroH5 !== '' && !str_contains($claroH5, $marcaAdjunto) && !str_contains($claroH5, 'zz-adjunto.txt'), 'h5c ni dentro del cuerpo descifrado');
        //En disco: todo archivo del proyecto tocado durante la prueba, menos el propio mensaje en el
        //buzón, que ES el correo retenido y no el registro.
        $raizProyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $archivosConMarca = function () use ($raizProyecto, $inicioH5, $buzonH, $marcaAdjunto, $adjuntoB64): array {
            $hallados = [];
            $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raizProyecto, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterador as $archivo) {
                $ruta = str_replace('\\', '/', (string) $archivo);
                if (!$archivo->isFile() || $archivo->getMTime() < $inicioH5
                    || preg_match('#/(vendor|node_modules|\.git)/#', $ruta) === 1 || str_starts_with($ruta, rtrim($buzonH, '/') . '/')) {
                    continue;
                }
                $contenido = (string) @file_get_contents($ruta);
                if (str_contains($contenido, $marcaAdjunto) || str_contains($contenido, $adjuntoB64)) {
                    $hallados[] = $ruta;
                }
            }
            return $hallados;
        };
        $conMarca = $archivosConMarca();
        $check($conMarca === [], 'h5d y ningún archivo del proyecto escrito durante el envío lo contiene, salvo el propio mensaje retenido', implode(', ', $conMarca));
        //CANARIO de h5d: una prueba de ausencia pasa sola si el barrido no mira. Un archivo con la
        //marca, escrito a propósito, tiene que aparecer.
        $canarioAdjunto = basepath('app/logs/zz-canario-adjunto-' . bin2hex(random_bytes(3)) . '.txt');
        //RETORNO-IGNORADO: si no se escribe, h5e falla, que es lo que debe pasar.
        @file_put_contents($canarioAdjunto, $marcaAdjunto);
        $check(in_array(str_replace('\\', '/', $canarioAdjunto), $archivosConMarca(), true), 'h5e CANARIO: un archivo con la marca, escrito a propósito, el barrido SÍ lo ve');
        //RETORNO-IGNORADO: lo comprueba h5f.
        @unlink($canarioAdjunto);
        $check(!is_file($canarioAdjunto), 'h5f y el canario se retira');

        //Prueba 6: el cifrado que falla NO rompe el envío: la fila se guarda sin cuerpo.
        $cifradoQueLanza = new class () extends MailLogMapper {
            protected static function encryptBody(string $body): string
            {
                throw new \RuntimeException('zz fallo de cifrado provocado por la prueba');
            }
        };
        $cifradoQueMiente = new class () extends MailLogMapper {
            protected static function encryptBody(string $body): string
            {
                return 'zz-esto-no-es-un-cifrado';
            }
        };
        //ADR 0052: con la clave de relleno, el cuerpo se guarda en `local` y NO fuera. El entorno se pone con
        //archivos temporales: el environment.php real no se toca.
        $claveDeRelleno = new class () extends MailLogMapper {
            protected static function appKey(): string
            {
                return 'TODO: cambie esta clave';
            }
        };
        $entornoH = sys_get_temp_dir() . '/zz-maillog-entorno-' . bin2hex(random_bytes(4));
        //RETORNO-IGNORADO: sin este archivo, h7 fallaría y lo diría.
        @file_put_contents("{$entornoH}-local.php", "<?php\nreturn 'local';\n");
        //RETORNO-IGNORADO: sin este archivo, h6relleno fallaría y lo diría.
        @file_put_contents("{$entornoH}-production.php", "<?php\nreturn 'production';\n");
        AppEnvironment::useForTesting("{$entornoH}-local.php");
        $okLocal = $claveDeRelleno::record(["{$marca}-h7@localhost.test"], 'ZZ h7 relleno local', 'prueba', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>ZZ CUERPO h7</p>');
        $filaH7 = $lineaDe('h7');
        $cifradoH7 = is_array($filaH7) && is_string($filaH7['body'] ?? null) ? (string) $filaH7['body'] : '';
        $check($okLocal && $cifradoH7 !== '' && $claveDeRelleno::decryptBody($cifradoH7) === '<p>ZZ CUERPO h7</p>', 'h7 CANARIO: en `local`, con la clave de relleno, el cuerpo SÍ se guarda y se descifra');
        $check(str_contains(MailDoctorTask::bodyLine(true), 'no protege') === MailLogMapper::appKeyIsPlaceholder(), 'h7b y mail-doctor dice que no protege si, y solo si, la clave es de relleno', MailDoctorTask::bodyLine(true));
        AppEnvironment::useForTesting(null);
        $registroErrores = basepath('app/logs/error.plain.log');
        $avisosDeRelleno = fn (): int => substr_count((string) @file_get_contents($registroErrores), 'la clave de la aplicación es la de relleno');
        $avisosAntes = $avisosDeRelleno();
        foreach (['lanza' => $cifradoQueLanza, 'miente' => $cifradoQueMiente, 'relleno' => $claveDeRelleno] as $modo => $mapperRoto) {
            //El cifrado que lanza y el que miente, con el entorno real; la clave de relleno, en `production`.
            AppEnvironment::useForTesting($modo === 'relleno' ? "{$entornoH}-production.php" : null);
            $ok = $mapperRoto::record(["{$marca}-h6{$modo}@localhost.test"], "ZZ h6 {$modo}", 'prueba', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>ZZ CUERPO SECRETO h6</p>');
            $filaH6 = $lineaDe("h6{$modo}");
            $check($ok === true && $filaH6 !== null, "h6{$modo}a con el cifrado que {$modo}, la fila se guarda igual", var_export($ok, true));
            $check($filaH6 !== null && $filaH6['body'] === null, "h6{$modo}b y sin cuerpo: nunca un cifrado que no se pueda leer");
        }
        $check($avisosDeRelleno() === $avisosAntes + 1, 'h8 y en `production`, con la de relleno, queda UNA línea en el registro de errores que lo dice', ($avisosDeRelleno() - $avisosAntes) . ' nueva(s)');
        $check(MailLogMapper::appKeyIsPlaceholder() === false || str_contains(MailDoctorTask::bodyLine(true), 'NO se guarda'), 'h8b y mail-doctor, en `production` con la de relleno, dice que NO se guarda', MailDoctorTask::bodyLine(true));
        //Sin environment.php: cuenta como producción, y el cuerpo tampoco se guarda. Es el fallo cerrado.
        AppEnvironment::useForTesting("{$entornoH}-no-existe.php");
        $claveDeRelleno::record(["{$marca}-h8c@localhost.test"], 'ZZ h8c sin entorno', 'prueba', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>ZZ CUERPO h8c</p>');
        $filaH8c = $lineaDe('h8c');
        $check($filaH8c !== null && $filaH8c['body'] === null, 'h8c sin environment.php, con la de relleno, la fila va SIN cuerpo');
        AppEnvironment::useForTesting(null);

        foreach (array_diff((array) glob("{$buzonH}/*.eml"), $emlAntesH) as $recienH) {
            //RETORNO-IGNORADO: el .eml de la prueba se retira; si quedara, lo diría h9.
            @unlink((string) $recienH);
        }
        $check(count((array) glob("{$buzonH}/*.eml")) === count($emlAntesH), 'h9 y la prueba retira los .eml que creó');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        //El transformer es estado estático: si una prueba lo dejara puesto, decidiría el siguiente correo.
        MailLogMapper::setBodyTransformer(null);
        AppEnvironment::useForTesting(null);
        foreach (['local', 'production'] as $nombreEntorno) {
            //RETORNO-IGNORADO: lo comprueba z3.
            @unlink(($entornoH ?? '') . "-{$nombreEntorno}.php");
        }
        set_config('mail', $blobPrevio);
        set_config(MailDelivery::CONFIG_NAME, $entregaPrevia);
        $limpiar = $base->prepare("DELETE FROM `{$tabla}` WHERE `recipients` LIKE ?");
        $limpiar->execute(["%{$marca}%"]);
        $quedan = (int) $base->query("SELECT COUNT(*) FROM `{$tabla}` WHERE `recipients` LIKE '%{$marca}%'")->fetchColumn();
        echoTerminal(' ');
        echoTerminal('[z] Limpieza del registro');
        $check($quedan === 0, "z1 no queda ninguna línea de la prueba ({$limpiar->rowCount()} borradas)");
        $retirados = $retirarDelSumidero();
        $check($enSumidero() === 0, "z2 y ninguno de los correos de la prueba se queda en el sumidero ({$retirados} retirado(s))");
        $check(!is_file(($entornoH ?? '') . '-local.php') && !is_file(($entornoH ?? '') . '-production.php') && AppEnvironment::get() === $entornoReal, 'z3 los entornos temporales retirados y el real de vuelta', $entornoReal);
    }

    return $balance();

})->setDescription('Cada correo deja su línea con su resultado —entregado, al buzón o fallido—, con la entrega que estaba declarada y de dónde salió, con el cuerpo CIFRADO —transformable por el clon, nunca con adjuntos, y sin perder la fila si el cifrado falla—, y con tope para que la tabla no crezca sin fin.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
