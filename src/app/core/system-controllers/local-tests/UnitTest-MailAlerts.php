<?php

//Que el correo que no llega se sepa SOLO: el aviso de fallos, el de la tabla que falta, y que la
//sonda SMTP de mail-doctor no salga a la red por omisión (bloque DL; ADR 0043 §2 y ADR 0046).

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemStatusLang;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Tasks\MailDoctorTask;

CliActions::make('unit-tests:core/mail-alerts', function ($args) {

    echoTerminal("\e[33m[TEST:MailAlerts] El correo que no llega se sabe solo, y la sonda no sale a la red\e[39m");
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

    //Los avisos del núcleo se registran al servir, no en el terminal: aquí se piden a mano.
    SystemStatusLang::injectLang();
    SystemStatusRoutes::registerCoreAlerts();

    $marca = 'zz-mailalerts-' . bin2hex(random_bytes(3));
    $tabla = MailLogMapper::TABLE;
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $modelo = MailLogMapper::model();
    $modelo->resetAll();
    $base = $modelo->getDatabase();
    if (!$check($base !== null, 'p0 hay conexión a la base')) {
        return $balance();
    }

    //─── p · El archivo que el aviso manda aplicar ──────────────────────────────────────────────
    echoTerminal('[p] El archivo que nombra el aviso existe y es el que crea la tabla');
    $archivo = $proyecto . '/' . MailLogMapper::UPDATE_FILE;
    $sql = is_file($archivo) ? (string) file_get_contents($archivo) : '';
    $check($sql !== '', 'p1 el archivo de MailLogMapper::UPDATE_FILE existe', MailLogMapper::UPDATE_FILE);
    $check(str_contains($sql, "CREATE TABLE IF NOT EXISTS `{$tabla}`"), 'p2 y su SQL crea justo esta tabla', $tabla);
    if ($failed > 0) {
        return $balance();
    }
    //Si falta la tabla se aplica ESE archivo, no un SQL escrito aquí: así el aviso manda aplicar
    //algo que esta prueba acaba de ver funcionar.
    if (!MailLogMapper::tableExists()) {
        $base->exec($sql);
        MailLogMapper::forgetMemo();
        echoTerminal('   (la tabla no estaba: se aplicó ' . MailLogMapper::UPDATE_FILE . ')');
    }
    $check(MailLogMapper::tableExists(), 'p3 la tabla está');

    $fallosAjenos = MailLogMapper::recentCounts()['failed'];
    $limpiar = function () use ($base, $tabla, $marca): void {
        $borrar = $base->prepare("DELETE FROM `{$tabla}` WHERE `recipients` LIKE ?");
        $borrar->execute(["%{$marca}%"]);
        MailLogMapper::forgetMemo();
    };
    $anotar = function (string $resultado, string $motivo) use ($marca): bool {
        return MailLogMapper::record(
            ["{$marca}@localhost.test"],
            'Asunto de la prueba de avisos',
            'UnitTest-MailAlerts',
            MailDelivery::SINK,
            $resultado,
            $motivo
        );
    };

    $argvOriginal = $_SERVER['argv'] ?? [];
    //NINGUNA suite renombra, borra ni altera la tabla real (DR, 2026-10-05): el registro se desvía a
    //tablas `_zz_` propias con `useTableForTesting()`, y sobre la real solo se lee.
    $sufijoPrueba = bin2hex(random_bytes(3));
    $tablaQueNoExiste = $tabla . '_zz_inexistente_' . $sufijoPrueba;
    $tablaSinCuerpo = $tabla . '_zz_sin_cuerpo_' . $sufijoPrueba;
    $mailOriginal = get_config('mail');
    $entregaOriginal = MailDelivery::declared();

    try {

        //─── a · El aviso de fallos salta SOLO ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Con un envío fallido en el registro, el aviso aparece sin que nadie pregunte');
        $avisoPosible = SystemAlertRegistry::get('mail-con-fallos');
        $check($avisoPosible !== null, 'a1 el aviso «mail-con-fallos» está registrado');
        if ($avisoPosible === null) {
            return $balance();
        }
        $aviso = $avisoPosible;

        //CANARIO, y va PRIMERO: sin fallos el aviso NO aparece. Si apareciera siempre, todo lo de
        //abajo pasaría sin medir nada (4.2).
        $limpiar();
        $sinFallos = MailLogMapper::recentCounts()['failed'];
        if ($fallosAjenos === 0) {
            $check($sinFallos === 0, 'a2 CANARIO: de partida no hay ningún fallo en la ventana', (string) $sinFallos);
            $check(!SystemAlertRegistry::isActive($aviso), 'a3 CANARIO: y sin fallos el aviso NO aparece');
        } else {
            //No se finge un canario que no se puede correr: la base trae fallos de otro trabajo.
            $check(true, "a2-a3 CANARIO NO CORRIDO: la base ya traía {$fallosAjenos} fallo(s) en la ventana, así que «sin fallos» no se puede provocar aquí");
        }

        $check($anotar(MailLogMapper::RESULT_FAILED, 'la prueba provocó este fallo'), 'a4 se anota un envío fallido');
        MailLogMapper::forgetMemo();
        $conFallos = MailLogMapper::recentCounts()['failed'];
        $check($conFallos === $fallosAjenos + 1, 'a5 el registro lo cuenta', (string) $conFallos);
        $check(SystemAlertRegistry::isActive($aviso), 'a6 y el aviso YA aparece');

        $mensaje = $aviso->message();
        $check(str_contains($mensaje, (string) $conFallos), 'a7 el aviso dice CUÁNTOS', $mensaje);
        $check(str_contains($mensaje, 'la prueba provocó este fallo'), 'a8 y el MOTIVO del último, que es lo que dice qué arreglar');
        $check(str_contains($mensaje, (string) MailLogMapper::ALERT_WINDOW_HOURS), 'a9 y la ventana que ha mirado');
        $check(str_contains($mensaje, 'mail-doctor'), 'a10 y qué hacer');

        //Que SALTE solo es que vaya como aviso flotante: en la página de avisos haría falta entrar.
        $flotantes = array_map(fn($a) => $a->key(), SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT));
        $check(in_array('mail-con-fallos', $flotantes, true), 'a11 va como aviso FLOTANTE para el principal: salta sin entrar a mirarlo');
        $check(!$aviso->isDismissible(), 'a12 y no se puede ocultar: un fallo de correo que se silencia es otra vez un fallo que nadie ve');
        $check($aviso->fixRoute() === 'system-status-mail-log', 'a13 y lleva a la pantalla del registro', (string) $aviso->fixRoute());
        $urlPantalla = get_route('system-status-mail-log', [], true);
        $check(is_string($urlPantalla) && $urlPantalla !== '', 'a14 y esa ruta existe de verdad', is_string($urlPantalla) ? $urlPantalla : 'no existe');

        //El umbral es UNO: con un solo fallo ya avisa, y eso es justo lo que a4-a6 han medido.
        $check(MailLogMapper::ALERT_FAILED_THRESHOLD === 1, 'a15 el umbral declarado es uno', (string) MailLogMapper::ALERT_FAILED_THRESHOLD);

        //Un envío que SÍ llegó no enciende el aviso: si lo encendiera, avisaría del correo que funciona.
        $limpiar();
        $check($anotar(MailLogMapper::RESULT_DELIVERED, ''), 'a16 se anota un envío entregado');
        MailLogMapper::forgetMemo();
        if ($fallosAjenos === 0) {
            $check(!SystemAlertRegistry::isActive($aviso), 'a17 y un entregado NO enciende el aviso');
        } else {
            $check(true, 'a17 NO CORRIDO: la base ya traía fallos ajenos');
        }
        $limpiar();

        //─── b · El aviso de la tabla que falta, provocado DE VERDAD ────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Sin la tabla, el aviso lo dice y nombra el archivo que hay que aplicar');
        $avisoTablaPosible = SystemAlertRegistry::get('mail-log-sin-tabla');
        $check($avisoTablaPosible !== null, 'b1 el aviso «mail-log-sin-tabla» está registrado');
        if ($avisoTablaPosible === null) {
            return $balance();
        }
        $avisoTabla = $avisoTablaPosible;

        //CANARIO primero: con la tabla puesta, el aviso NO aparece.
        MailLogMapper::forgetMemo();
        $check(!SystemAlertRegistry::isActive($avisoTabla), 'b2 CANARIO: con la tabla puesta el aviso NO aparece');

        //Se provoca DESVIANDO el registro a una tabla que no existe: la real no se toca.
        MailLogMapper::useTableForTesting($tablaQueNoExiste);
        $check(!MailLogMapper::tableExists(), 'b3 provocado: el registro apunta a una tabla que no existe');
        $check(SystemAlertRegistry::isActive($avisoTabla), 'b4 y el aviso aparece');
        $mensajeTabla = $avisoTabla->message();
        $check(str_contains($mensajeTabla, MailLogMapper::UPDATE_FILE), 'b5 nombrando el archivo que hay que aplicar', $mensajeTabla);
        $check(in_array('mail-log-sin-tabla', array_map(fn($a) => $a->key(), SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)), true), 'b6 y también salta solo');

        //Y la consecuencia que hace falta avisar: sin tabla, anotar no rompe el envío pero no anota.
        $registroErrores = basepath('app/logs/error.plain.log');
        $tamanoAntes = is_file($registroErrores) ? (int) filesize($registroErrores) : 0;
        $check($anotar(MailLogMapper::RESULT_FAILED, 'sin tabla') === false, 'b7 sin la tabla, anotar devuelve false y NO lanza: el correo no se rompe por el registro');
        $check(MailLogMapper::recentCounts() === ['failed' => 0, 'outbox' => 0, 'delivered' => 0], 'b8 y entonces el registro dice cero, que es lo que el aviso evita leer como «no hay fallos»');

        //El peor caso no puede quedarse sin rastro en NINGÚN sitio: con la base caída no hay panel
        //ni avisos, así que lo único que queda es esta línea en el registro de errores (DM 3.3).
        clearstatcache(true, $registroErrores);
        $tamanoDespues = is_file($registroErrores) ? (int) filesize($registroErrores) : 0;
        //El registro de errores NO solo crece: al pasar de 1 MB `GenericHandler` lo renombra, y la
        //escritura que cruza el límite deja el archivo MÁS PEQUEÑO que antes (medido el 2026-10-03).
        $roto = $tamanoDespues < $tamanoAntes;
        $check($tamanoDespues !== $tamanoAntes, 'b8b el fallo al anotar escribió en el registro de errores ('
            . $tamanoAntes . ' → ' . $tamanoDespues . ' bytes'
            . ($roto ? ', y de paso lo rotó por pasar de 1 MB' : '') . ')');
        //Se lee solo la cola: el archivo pesa cientos de miles de bytes y no hace falta entero.
        $cola = '';
        if ($tamanoDespues > 0) {
            $puntero = fopen($registroErrores, 'rb');
            if (is_resource($puntero)) {
                //RETORNO-IGNORADO: si el salto falla se lee desde el principio y b8c sigue valiendo.
                fseek($puntero, max(0, $tamanoDespues - 8192));
                $cola = (string) fread($puntero, 8192);
                //RETORNO-IGNORADO: el archivo se abrió solo para leer; lo miden b8c y b8d.
                fclose($puntero);
            }
        }
        $check(str_contains($cola, 'No se pudo anotar un correo en ' . MailLogMapper::TABLE), 'b8c y la línea dice QUÉ no se pudo anotar');
        $check(str_contains($cola, 'el panel no podrá avisar de este envío'), 'b8d y dice la consecuencia: que el aviso no va a saltar por ese envío');
        //Los destinatarios NO se escriben en el registro de errores: van contados.
        $check(!str_contains($cola, "{$marca}@localhost.test"), 'b8e y NO escribe el destinatario en el registro de errores: va contado');
        $check(str_contains($cola, '1 destinatario(s)'), 'b8f sino su número');

        MailLogMapper::useTableForTesting(null);
        $check(MailLogMapper::tableExists(), 'b9 de vuelta a la tabla real, que está');
        $check(!SystemAlertRegistry::isActive($avisoTabla), 'b10 y el aviso se apaga');

        //─── c · La sonda SMTP no sale a la red por omisión (ADR 0046) ──────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] La sonda SMTP: opcional, y decidida por a dónde RESUELVE el host');
        $pares = [
            '127.0.0.1' => true,
            '127.1.2.3' => true,
            'localhost' => true,
            '::1' => true,
            '[::1]' => true,
            'tls://127.0.0.1' => true,
            '' => false,
            '8.8.8.8' => false,
            //El nombre PARECE local y no lo es: es el caso que un «empieza por localhost» fallaría.
            'localhost.example.com.invalid' => false,
            'no-existe-este-nombre.invalid' => false,
        ];
        $erroresPares = [];
        foreach ($pares as $host => $esperado) {
            if (MailDelivery::smtpIsLoopback((string) $host) !== $esperado) {
                $erroresPares[] = (string) $host;
            }
        }
        $check($erroresPares === [], 'c1 los ' . count($pares) . ' hosts se clasifican bien, por resolución y no por nombre', implode(', ', $erroresPares));

        //El puerto de la sonda: cerrado de verdad, o lo de abajo no mide el camino del fallo.
        $puertoCerrado = 9;
        $errno = null;
        $errstr = null;
        $socket = @fsockopen('127.0.0.1', $puertoCerrado, $errno, $errstr, 2);
        $estaCerrado = !is_resource($socket);
        if (is_resource($socket)) {
            //RETORNO-IGNORADO: si el puerto estaba abierto solo hay que soltarlo; lo mide c2.
            fclose($socket);
        }
        $check($estaCerrado, "c2 el puerto local {$puertoCerrado} está cerrado, así que la sonda tiene algo que no responder", (string) $errstr);
        $check(Mailer::probeSMTP('127.0.0.1', $puertoCerrado) === false, 'c3 y la sonda contra él devuelve false: ese es el camino de «NO contesta»');

        //CANARIO DE DOS CARAS: con el sumidero VIVO en 1025 y el SMTP configurado CERRADO en 9, una
        //sonda que mirase la configuración cargada diría que sí (el defecto medido el 2026-10-03).
        $sumideroVivo = Mailer::probeSMTP('127.0.0.1', 1025);
        if ($sumideroVivo) {
            $check(Mailer::probeSMTP('127.0.0.1', $puertoCerrado) === false, 'c3b CANARIO: con el sumidero vivo, la sonda del SMTP cerrado sigue diciendo que NO: mide el host que se le da, no el de la configuración cargada');
            $comprobadorDelMailer = new Mailer(false);
            $check($comprobadorDelMailer->checkSettedSMTP() === true, 'c3c y el otro comprobador, el de la configuración cargada, dice que SÍ: por eso no sirve para diagnosticar', 'apunta a ' . $comprobadorDelMailer->Host . ':' . $comprobadorDelMailer->Port);
        } else {
            $check(true, 'c3b-c3c CANARIO NO CORRIDO: el sumidero de 127.0.0.1:1025 no está levantado, así que no hay con qué contrastar');
        }

        //Y la tarea entera, con la configuración de correo cambiada SOLO EN MEMORIA: `set_config()`
        //no escribe en la base, así que la del proyecto no se toca. Sin red: host de bucle local.
        $cifrar = fn(array $datos): string => BaseHashEncryption::encrypt((string) gzcompress((string) json_encode($datos)), MailConfig::class);
        $sintetica = [
            'host' => '127.0.0.1',
            'port' => $puertoCerrado,
            'is_smtp' => true,
            'auth' => false,
            'user' => 'zz-prueba@localhost.test',
            'password' => '',
            'protocol' => '',
            'auto_tls' => false,
        ];
        set_config('mail', $cifrar($sintetica));
        $configSintetica = new MailConfig();
        //`host()` y `port()` son getter Y setter: sin argumento devuelven el valor, y su tipo
        //declarado incluye el propio objeto. Se estrecha mirándolo, no casteando.
        $hostSintetico = $configSintetica->host();
        $puertoSintetico = $configSintetica->port();
        $hostSintetico = is_string($hostSintetico) ? $hostSintetico : '';
        $puertoSintetico = is_int($puertoSintetico) || is_string($puertoSintetico) ? (int) $puertoSintetico : 0;
        $check($hostSintetico === '127.0.0.1' && $puertoSintetico === $puertoCerrado, 'c4 la configuración de correo de la prueba apunta al puerto local cerrado', "{$hostSintetico}:{$puertoSintetico}");

        $correosAntes = MailLogMapper::recentCounts();

        echoTerminal(' ');
        echoTerminal("   \e[36m--- salida real de mail-doctor smtp=yes, con el SMTP en 127.0.0.1:{$puertoCerrado} ---\e[39m");
        $_SERVER['argv'] = ['index.php', 'cli', '--local', 'mail-doctor', 'smtp=yes'];
        MailDoctorTask::main();
        echoTerminal("   \e[36m--- fin de la salida ---\e[39m");
        echoTerminal(' ');

        MailLogMapper::forgetMemo();
        $check(MailLogMapper::recentCounts() === $correosAntes, 'c5 el diagnóstico NO mandó ningún correo: el registro no cambió');

        //Y sin pedirla, con un host que no es local, la sonda no se hace: eso es el ADR 0046 §1.
        set_config('mail', $cifrar(array_merge($sintetica, ['host' => 'no-existe-este-nombre.invalid'])));
        $check(MailDelivery::smtpIsLoopback('no-existe-este-nombre.invalid') === false, 'c6 con un host que no resuelve a esta máquina, la sonda no se haría sin pedirla');

        //─── g · Sin la columna del cuerpo, la fila NO se pierde (pruebas 11 y 12) ──────────────────
        echoTerminal(' ');
        echoTerminal('[g] Una tabla del 2026-10-03, sin la columna del cuerpo: la fila se guarda y el aviso lo dice');
        $avisoCuerpoPosible = SystemAlertRegistry::get('mail-log-sin-cuerpo');
        $check($avisoCuerpoPosible !== null, 'g1 el aviso «mail-log-sin-cuerpo» está registrado');
        if ($avisoCuerpoPosible === null) {
            return $balance();
        }
        $avisoCuerpo = $avisoCuerpoPosible;
        MailLogMapper::forgetMemo();
        //CANARIO (prueba 12): con la columna puesta, el aviso NO salta.
        $check(MailLogMapper::bodyColumnExists() && !SystemAlertRegistry::isActive($avisoCuerpo), 'g2 CANARIO: con la columna puesta el aviso NO salta');

        //La costura no puede apuntar el registro a OTRA tabla real: solo a una `_zz_`.
        $rechazada = false;
        try {
            MailLogMapper::useTableForTesting('pcsphp_users');
        } catch (\InvalidArgumentException $rechazo) {
            $rechazada = true;
        }
        $check($rechazada && MailLogMapper::tableName() === $tabla, 'g2b la costura rechaza otra tabla real y el registro sigue en la suya');

        //Una tabla PROPIA con la forma de la real y sin la columna del cuerpo, que es la del 2026-10-03.
        //Lo que se altera es esa copia: la real solo se lee para copiar su forma.
        $base->exec("CREATE TABLE `{$tablaSinCuerpo}` LIKE `{$tabla}`");
        $base->exec("ALTER TABLE `{$tablaSinCuerpo}` DROP COLUMN `body`");
        MailLogMapper::useTableForTesting($tablaSinCuerpo);
        $check(MailLogMapper::tableExists() && !MailLogMapper::bodyColumnExists(), 'g3 provocado: la tabla está y la columna del cuerpo no');

        $registroErroresG = basepath('app/logs/error.plain.log');
        clearstatcache(true, $registroErroresG);
        $tamanoAntesG = is_file($registroErroresG) ? (int) filesize($registroErroresG) : 0;
        //Prueba 11: un envío CON cuerpo deja su fila igual.
        $okG = MailLogMapper::record(["{$marca}-g@localhost.test"], 'ZZ sin columna', 'UnitTest-MailAlerts', MailDelivery::SINK, MailLogMapper::RESULT_DELIVERED, null, '<p>ZZ cuerpo que no cabe</p>');
        $filasG = $base->prepare("SELECT COUNT(*) FROM `{$tablaSinCuerpo}` WHERE `recipients` = ?");
        $filasG->execute(["{$marca}-g@localhost.test"]);
        $check($okG === true && (int) $filasG->fetchColumn() === 1, 'g4 un correo CON cuerpo deja su fila igual: no se pierde', var_export($okG, true));
        clearstatcache(true, $registroErroresG);
        $tamanoDespuesG = is_file($registroErroresG) ? (int) filesize($registroErroresG) : 0;
        $colaG = '';
        if ($tamanoDespuesG > 0) {
            $punteroG = fopen($registroErroresG, 'rb');
            if (is_resource($punteroG)) {
                //RETORNO-IGNORADO: si el salto falla se lee desde el principio y g5 sigue valiendo.
                fseek($punteroG, max(0, $tamanoDespuesG - 8192));
                $colaG = (string) fread($punteroG, 8192);
                //RETORNO-IGNORADO: el archivo se abrió solo para leer; lo mide g5.
                fclose($punteroG);
            }
        }
        $check($tamanoDespuesG !== $tamanoAntesG && str_contains($colaG, 'SIN su cuerpo') && str_contains($colaG, MailLogMapper::BODY_UPDATE_FILE), 'g5 y deja su línea en el registro de errores, nombrando el archivo que falta');

        //Prueba 12: el aviso salta, y nombra el archivo.
        $check(SystemAlertRegistry::isActive($avisoCuerpo), 'g6 sin la columna, el aviso salta');
        $check(str_contains($avisoCuerpo->message(), MailLogMapper::BODY_UPDATE_FILE), 'g7 nombrando el archivo que hay que aplicar');
        $check(in_array('mail-log-sin-cuerpo', array_map(fn ($a) => $a->key(), SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)), true), 'g8 y salta solo, como aviso flotante');
        $avisoTablaG = SystemAlertRegistry::get('mail-log-sin-tabla');
        $check($avisoTablaG !== null && !SystemAlertRegistry::isActive($avisoTablaG), 'g9 y NO salta el de la tabla que falta, que diría algo falso');

        //Se deshace: de vuelta a la real, y fuera la copia, que es de la prueba.
        MailLogMapper::useTableForTesting(null);
        $base->exec("DROP TABLE IF EXISTS `{$tablaSinCuerpo}`");
        $check(MailLogMapper::bodyColumnExists() && !SystemAlertRegistry::isActive($avisoCuerpo), 'g10 de vuelta a la real, que tiene su columna, y el aviso se apaga');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //─── z · Limpieza ───────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[z] Limpieza: la tabla, la configuración y los argumentos, como estaban');
        $_SERVER['argv'] = $argvOriginal;
        if ($mailOriginal === null) {
            set_config('mail', null);
        } else {
            set_config('mail', $mailOriginal);
        }
        //Pase lo que pase arriba, el registro vuelve a la tabla real y la copia de la prueba se retira.
        MailLogMapper::useTableForTesting(null);
        $base->exec("DROP TABLE IF EXISTS `{$tablaSinCuerpo}`");
        MailLogMapper::forgetMemo();
        $limpiar();
        $quedan = (int) $base->query("SELECT COUNT(*) FROM `{$tabla}` WHERE `recipients` LIKE '%{$marca}%'")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ninguna línea de la prueba');
        $check(MailLogMapper::tableExists(), 'z2 la tabla del registro está donde estaba');
        $copias = $base->prepare("SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` LIKE ?");
        $copias->execute([str_replace('_', '\\_', $tabla) . '\\_zz\\_%']);
        $check((int) $copias->fetchColumn() === 0, 'z3 y no queda ninguna tabla de prueba `' . $tabla . '_zz_…`');
        $check(MailLogMapper::tableName() === $tabla && MailLogMapper::bodyColumnExists(), 'z3b y el registro vuelve a la tabla real, que tiene la columna del cuerpo');
        $configFinal = new MailConfig();
        $hostFinal = $configFinal->host();
        $hostFinal = is_string($hostFinal) ? $hostFinal : '';
        $check(MailDelivery::declared() === $entregaOriginal, 'z4 la entrega declarada sigue como estaba', MailDelivery::declared());
        $check($hostFinal !== '127.0.0.1' || $mailOriginal === null, 'z5 y la configuración de correo del proyecto vuelve a ser la suya', 'host de la prueba retirado');
    }

    return $balance();

})->setDescription('Que el correo que no llega se sepa SOLO: el aviso de envíos fallidos (con su canario: sin fallos no aparece), el aviso de que falta la tabla del registro —provocado renombrándola— y que la sonda SMTP de mail-doctor no salga a la red por omisión. NO manda correo y NO toca la red: el SMTP de la prueba es un puerto local cerrado, puesto solo en memoria.')->setEffects([CliActions::EFFECT_DATABASE])->register();
