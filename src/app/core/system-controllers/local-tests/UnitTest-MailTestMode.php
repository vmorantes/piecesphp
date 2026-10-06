<?php

//La ENTREGA declarada decide si el correo sale, y con «retenido» no hay reserva por el sistema: va al
//buzón. Antes del 2026-10-03 esto lo decidía `test_mode` y un clon sin entorno enviaba. ADR 0043 y 0045.

use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/mail-test-mode', function ($args) {

    echoTerminal("\e[33m[TEST:MailTestMode] La entrega declarada, la ausencia del entorno y el buzón\e[39m");
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

    $blobPrevio = get_config('mail');
    $existiaOpcion = SettingsModel::optionExists('mail');
    $entregaPrevia = get_config(MailDelivery::CONFIG_NAME);
    $existiaEntrega = SettingsModel::optionExists(MailDelivery::CONFIG_NAME);
    $terminalPrevio = $_SERVER['PCSPHP_TERMINAL_DATA'];
    SystemStatusRoutes::registerCoreAlerts();

    //Los archivos de entorno de la prueba: `useForTesting()` con una ruta que NO existe es lo que
    //reproduce el caso que importa, un clon recién hecho sin `environment.php`.
    $temporal = rtrim(sys_get_temp_dir(), '/') . '/zz-pcsphp-entorno-' . bin2hex(random_bytes(4));
    //RETORNO-IGNORADO: si la carpeta temporal no se crea, los `file_put_contents` de abajo lo delatan.
    @mkdir($temporal, 0775, true);
    $entornos = [];
    foreach (['local' => AppEnvironment::LOCAL, 'production' => AppEnvironment::PRODUCTION] as $nombre => $valor) {
        $entornos[$nombre] = "{$temporal}/{$nombre}.php";
        //RETORNO-IGNORADO: si el archivo no se escribe, `isConfigured()` da false y la matriz [a] falla.
        file_put_contents($entornos[$nombre], "<?php\n\nreturn '{$valor}';\n");
    }
    $entornos['ausente'] = "{$temporal}/no-existe.php";

    $comoEntorno = function (string $cual) use ($entornos): void {
        AppEnvironment::useForTesting($entornos[$cual]);
    };
    //La entrega se escribe como la escribe la pantalla: opción propia, no dentro del array `mail`.
    $declarar = function (?string $entrega): void {
        if ($entrega === null) {
            set_config(MailDelivery::CONFIG_NAME, null);
            return;
        }
        set_config(MailDelivery::CONFIG_NAME, $entrega);
        SettingsModel::setConfigValue(MailDelivery::CONFIG_NAME, $entrega);
    };
    $guardarMail = function (array $valores): void {
        $config = new MailConfig();
        foreach ($valores as $metodo => $valor) {
            $config->$metodo($valor);
        }
        $blob = $config->toSave();
        set_config('mail', $blob);
        SettingsModel::setConfigValue('mail', $blob);
    };

    //El destinatario del envío Y el patrón de la limpieza, en UNA variable y antes del `try`, que es
    //donde el `finally` la necesita (DM 3.2).
    $destinoBuzon = 'zz-prueba-buzon@localhost.test';

    try {

        //─── a · La matriz de la ENTREGA, que es la de antes traducida más el caso que faltaba ──────────
        echoTerminal('[a] La entrega declarada decide, y la ausencia del entorno retiene');
        $declarar(MailDelivery::SINK);
        $comoEntorno('local');
        $check(MailDelivery::goesToSink() === true, 'a1 «retenido» declarado + local → retiene');
        $comoEntorno('production');
        $check(MailDelivery::goesToSink() === true, 'a2 «retenido» declarado + producción → retiene');

        $declarar(MailDelivery::REAL);
        $comoEntorno('local');
        //CANARIO: sin esto, «no envía» pasaría gratis con una implementación que retuviera siempre.
        $check(MailDelivery::goesToSink() === false, 'a3 CANARIO: «real» declarado + local → ENVÍA');
        $comoEntorno('production');
        $check(MailDelivery::goesToSink() === false, 'a4 CANARIO: «real» declarado + producción → ENVÍA');

        $declarar(MailDelivery::AUTO);
        $comoEntorno('local');
        $check(MailDelivery::goesToSink() === true, 'a5 «según el entorno» + local → retiene');
        $comoEntorno('production');
        $check(MailDelivery::goesToSink() === false, 'a6 «según el entorno» + producción → envía');

        //El caso que cierra el agujero: hasta hoy la ausencia valía «production», o sea ENVIAR.
        $comoEntorno('ausente');
        $check(AppEnvironment::isConfigured() === false, 'a7 el entorno no está declarado, como en un clon recién hecho');
        $check(MailDelivery::goesToSink() === true, 'a8 y sin entorno declarado → RETIENE, no envía');
        $check(MailDelivery::reason() === 'sin-entorno', 'a9 y lo dice: el motivo es «sin-entorno»', MailDelivery::reason());

        //Un valor que no es de los tres no se cree: cae a «según el entorno».
        $declarar('zz-basura');
        $comoEntorno('production');
        $check(MailDelivery::declared() === MailDelivery::AUTO, 'a10 un valor inválido cae a «según el entorno»', MailDelivery::declared());
        echoTerminal(' ');

        //─── b · El desvío sigue viviendo en el Mailer ──────────────────────────────────────────────────
        echoTerminal('[b] El desvío vive en el Mailer');
        $comoEntorno('local');
        $declarar(MailDelivery::SINK);
        $guardarMail([
            'testHost' => '127.0.0.1', 'testPort' => 1025,
            'host' => 'smtp.zz-real.example', 'port' => 465, 'auth' => true, 'user' => 'zz-real@example.com',
            'password' => 'zz-real-clave', 'protocol' => 'ssl', 'autoTls' => true,
        ]);
        $blobConModo = get_config('mail');
        $desviado = new Mailer(false);
        $check(
            $desviado->Host === '127.0.0.1' && (int) $desviado->Port === 1025 && $desviado->SMTPAuth === false
                && $desviado->SMTPSecure === '' && $desviado->Username === '' && $desviado->Password === '' && $desviado->SMTPAutoTLS === false,
            'b1 con la entrega retenida: servidor y puerto del sumidero, sin autenticación ni cifrado',
            $desviado->Host . ':' . $desviado->Port . ' auth=' . var_export($desviado->SMTPAuth, true)
        );
        $check(get_config('mail') === $blobConModo, 'b3 construir el Mailer no cambia la configuración guardada');

        $declarar(MailDelivery::REAL);
        $real = new Mailer(false);
        $check(
            $real->Host === 'smtp.zz-real.example' && (int) $real->Port === 465 && $real->SMTPAuth === true && $real->Username === 'zz-real@example.com',
            'b2 con la entrega real: el servidor y el puerto reales',
            $real->Host . ':' . $real->Port
        );
        echoTerminal(' ');

        //─── c · La reserva por el sistema NO se aplica con el correo retenido (ADR 0045) ────────────────
        echoTerminal('[c] Con el correo retenido no hay reserva por el sistema');
        $declarar(MailDelivery::REAL);
        $conReserva = new Mailer(false);
        $conReserva->asGoDaddy();
        $check(
            $conReserva->Host === 'localhost' && (int) $conReserva->Port === 25,
            'c1 CANARIO: con la entrega real, la reserva por el sistema sigue intacta',
            $conReserva->Host . ':' . $conReserva->Port
        );
        $declarar(MailDelivery::SINK);
        $sinReserva = new Mailer(false);
        $hostAntes = $sinReserva->Host;
        $puertoAntes = (int) $sinReserva->Port;
        $sinReserva->asGoDaddy();
        $check(
            $sinReserva->Host === $hostAntes && (int) $sinReserva->Port === $puertoAntes && $hostAntes !== 'localhost',
            'c2 con el correo retenido, la reserva NO cambia el destino: sigue el sumidero',
            $sinReserva->Host . ':' . $sinReserva->Port . ' (antes ' . $hostAntes . ':' . $puertoAntes . ')'
        );
        echoTerminal(' ');

        //─── d · El buzón en disco: el mensaje que no llegó al sumidero NO se pierde ni sale ─────────────
        echoTerminal('[d] Sin sumidero escuchando, el mensaje va al buzón y no es un fallo');
        //Un puerto cerrado de esta máquina hace de «sumidero caído» sin tocar el de nadie.
        $puertoCerrado = 1;
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => $puertoCerrado]);
        $buzon = MailDelivery::outboxDirectory();
        $antes = is_dir($buzon) ? count((array) glob("{$buzon}/*.eml")) : 0;
        $alBuzon = new Mailer(false);
        $alBuzon->setFrom('zz-prueba-remitente@localhost.test', 'ZZ Prueba');
        $alBuzon->addAddress($destinoBuzon);
        $alBuzon->Subject = 'ZZ buzon ' . bin2hex(random_bytes(3));
        $alBuzon->isHTML(true);
        $alBuzon->Body = '<p>zz cuerpo del buzon</p>';
        $enviado = $alBuzon->send();
        $despues = is_dir($buzon) ? count((array) glob("{$buzon}/*.eml")) : 0;
        $check($enviado === true, 'd1 el envío NO cuenta como fallo aunque el sumidero no responda', var_export($enviado, true));
        $check($despues === $antes + 1, "d2 y el mensaje aparece en el buzón ({$antes} → {$despues})");
        $ultimos = (array) glob("{$buzon}/*.eml");
        usort($ultimos, fn ($a, $b): int => filemtime((string) $b) <=> filemtime((string) $a));
        $contenido = count($ultimos) > 0 ? (string) file_get_contents((string) $ultimos[0]) : '';
        $check(str_contains($contenido, $destinoBuzon), 'd3 el .eml guardado lleva su destinatario');
        $check(str_contains($contenido, $alBuzon->Subject), 'd4 y su asunto', mb_substr($alBuzon->Subject, 0, 40));
        $check($alBuzon->Host === '127.0.0.1' && (int) $alBuzon->Port === $puertoCerrado, 'd5 y el destino NUNCA pasó a localhost:25', $alBuzon->Host . ':' . $alBuzon->Port);
        //Lo que la prueba acaba de crear se retira: el buzón es un registro, no un vertedero.
        foreach (array_slice($ultimos, 0, 1) as $recien) {
            //RETORNO-IGNORADO: quien dice si se borró es d6, que cuenta los .eml del buzón.
            @unlink((string) $recien);
        }
        $check(count((array) glob("{$buzon}/*.eml")) === $antes, 'd6 y la prueba retira el .eml que creó');
        echoTerminal(' ');

        //─── e · Los avisos del panel ───────────────────────────────────────────────────────────────────
        echoTerminal('[e] Los tres avisos, y ninguno bloquea');
        $guardarMail(['testHost' => '127.0.0.1', 'testPort' => 1025]);
        $declarar(MailDelivery::SINK);
        $comoEntorno('local');
        $retenido = SystemAlertRegistry::get('mail-test-mode');
        $texto = $retenido !== null ? $retenido->message() : '';
        $check($retenido !== null && SystemAlertRegistry::isActive($retenido), 'e1 con el correo retenido, el aviso de siempre está activo');
        $check(str_contains($texto, '127.0.0.1') && str_contains($texto, '1025'), 'e2 su mensaje dice servidor y puerto', mb_substr($texto, 0, 120));
        $check($retenido !== null && $retenido->audience() === [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL] && !$retenido->showAsNag(), 'e3 root y administrador general, no flotante');
        $check($retenido !== null && $retenido->severity() === (is_local() ? \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_INFO : \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_WARNING), 'e4 INFO en local, ATENCIÓN fuera de local');

        $sinDeclarar = SystemAlertRegistry::get('mail-sin-declarar');
        $retenidoProd = SystemAlertRegistry::get('mail-retenido-en-produccion');
        $realLocal = SystemAlertRegistry::get('mail-real-en-local');
        $check($sinDeclarar !== null && $retenidoProd !== null && $realLocal !== null, 'e5 los tres avisos nuevos están registrados');
        //El `if` va aparte del `$check`: el analizador no estrecha el tipo a través de la función.
        if ($sinDeclarar === null || $retenidoProd === null || $realLocal === null) {
            return $balance();
        }

        $comoEntorno('ausente');
        $check(SystemAlertRegistry::isActive($sinDeclarar), 'e6 sin entorno declarado → avisa «sin declarar»');
        $check(str_contains($sinDeclarar->message(), 'environment.php'), 'e7 y dice QUÉ HACER: nombra el archivo que falta');

        $comoEntorno('production');
        $declarar(MailDelivery::SINK);
        $check(SystemAlertRegistry::isActive($retenidoProd), 'e8 producción + retenido → avisa «retenido en producción»');
        $check(!SystemAlertRegistry::isActive($sinDeclarar), 'e9 y el de «sin declarar» se calla, que el entorno sí está');

        $comoEntorno('local');
        $declarar(MailDelivery::REAL);
        //H3: hasta hoy este caso NO avisaba nada, y es el que saca correo de la máquina de quien desarrolla.
        $check(SystemAlertRegistry::isActive($realLocal), 'e10 local + real → avisa «el correo sale de verdad»');
        $check(!SystemAlertRegistry::isActive($retenidoProd), 'e11 y el de producción se calla');
        $check(
            !$sinDeclarar->isDismissible() && !$retenidoProd->isDismissible() && !$realLocal->isDismissible()
                && !$sinDeclarar->showAsNag() && !$retenidoProd->showAsNag() && !$realLocal->showAsNag(),
            'e12 ninguno de los tres bloquea ni se impone como flotante'
        );

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        AppEnvironment::useForTesting(null);
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalPrevio;
        set_config('mail', $blobPrevio);
        if ($existiaOpcion) {
            SettingsModel::setConfigValue('mail', $blobPrevio);
        } else {
            $borrar = SettingsModel::model();
            $borrar->resetAll();
            $borrar->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([\PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::isEqual('name', 'mail')]))->execute();
        }
        set_config(MailDelivery::CONFIG_NAME, $entregaPrevia);
        if ($existiaEntrega) {
            SettingsModel::setConfigValue(MailDelivery::CONFIG_NAME, is_string($entregaPrevia) ? $entregaPrevia : MailDelivery::AUTO);
        } else {
            $borrarEntrega = SettingsModel::model();
            $borrarEntrega->resetAll();
            $borrarEntrega->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([\PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::isEqual('name', MailDelivery::CONFIG_NAME)]))->execute();
        }
        foreach (['local', 'production'] as $nombre) {
            //RETORNO-IGNORADO: temporal de sys_get_temp_dir() con marca zz- y sello aleatorio.
            @unlink("{$temporal}/{$nombre}.php");
        }
        //RETORNO-IGNORADO: la carpeta temporal no condiciona ningún veredicto de esta suite.
        @rmdir($temporal);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza: entorno real restaurado, opciones como estaban y nada en el buzón de la prueba');

        //El registro es DE LA MÁQUINA: sin esto quedaba una fila por cada `bin/verify`, y las ocho
        //que había inflaban la cifra «al buzón» de la pantalla del panel.
        $modeloRegistro = MailLogMapper::model();
        $modeloRegistro->resetAll();
        $conexionRegistro = $modeloRegistro->getDatabase();
        if ($conexionRegistro !== null && MailLogMapper::tableExists()) {
            $tablaRegistro = MailLogMapper::TABLE;
            $contar = function () use ($conexionRegistro, $tablaRegistro, $destinoBuzon): int {
                $consulta = $conexionRegistro->prepare("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE `recipients` LIKE ?");
                $consulta->execute(["%{$destinoBuzon}%"]);
                return (int) $consulta->fetchColumn();
            };
            $antesRegistro = $contar();
            $borrarRegistro = $conexionRegistro->prepare("DELETE FROM `{$tablaRegistro}` WHERE `recipients` LIKE ?");
            $borrarRegistro->execute(["%{$destinoBuzon}%"]);
            MailLogMapper::forgetMemo();
            $check($contar() === 0, "z2 no queda ninguna fila de la prueba en el registro de correos ({$antesRegistro} retirada(s))");
        } else {
            //Sin la tabla no hay fila que retirar, y eso NO es un pase gratis: se dice.
            $check(true, 'z2 NO CORRIDO: la tabla del registro de correos no existe en esta instalación');
        }
    }

    return $balance();

})->setDescription('La entrega declarada decide si el correo sale: retenido, real o según el entorno, y sin environment.php retiene. Con el correo retenido la reserva por el sistema no se aplica y el mensaje va al buzón en disco. Más los tres avisos del desajuste.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
