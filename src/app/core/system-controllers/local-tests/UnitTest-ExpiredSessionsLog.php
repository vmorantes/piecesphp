<?php

//Sesiones caducadas: apagado por omisión, una línea sin el token y con tope. ADR 0039.
//Corre sobre una carpeta temporal propia: `app/logs/` no se toca.

use PiecesPHP\Core\Logs\ExpiredSessionsLog;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/expired-sessions-log', function ($args) {

    echoTerminal("\e[33m[TEST:ExpiredSessionsLog] Una línea por caducidad, sin el token, con tope y apagada por omisión\e[39m");
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

    //Carpeta temporal propia: `app/logs/` no se toca en ningún apartado.
    $temporal = rtrim(sys_get_temp_dir(), '/') . '/zz-expired-sessions-' . getmypid() . '-' . bin2hex(random_bytes(4));
    //RETORNO-IGNORADO: si no se crea, el primer apartado falla solo.
    mkdir($temporal, 0775, true);

    //Un JWT de pega, con la forma de uno real: es la cadena que NO debe aparecer en el registro.
    $tokenFalso = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.zzCARGAFALSADEPRUEBA.zzFIRMAFALSADEPRUEBA';
    $previo = get_config(ExpiredSessionsLog::CONFIG_NAME);
    $anotar = fn (?int $usuario = 7, bool $renovable = false): bool => ExpiredSessionsLog::record(
        $usuario,
        'zz-ruta-de-prueba',
        '/zz/pedido?token=' . $tokenFalso,
        '203.0.113.7',
        1790000000,
        1790003600,
        $renovable,
        $temporal
    );
    $contenido = fn (): string => is_file(ExpiredSessionsLog::path($temporal)) ? (string) file_get_contents(ExpiredSessionsLog::path($temporal)) : '';
    $lineas = fn (): int => $contenido() === '' ? 0 : count(array_filter(explode("\n", $contenido()), fn (string $l): bool => trim($l) !== ''));

    try {

        //──── a · Apagada por omisión: no escribe NI MIRA el disco ──────────────────────────────
        echoTerminal('[a] Con la opción apagada no se escribe nada, y no se toca el disco');
        set_config(ExpiredSessionsLog::CONFIG_NAME, null);
        $check(ExpiredSessionsLog::enabled() === false, 'a1 sin opción guardada está APAGADA (por omisión)');
        set_config(ExpiredSessionsLog::CONFIG_NAME, false);
        $check(ExpiredSessionsLog::enabled() === false, 'a2 con la opción en false, apagada');
        //Un valor que no sea exactamente `true` no la enciende: así un «1» de una configuración mal
        //escrita no empieza a registrar sin que nadie lo pida.
        set_config(ExpiredSessionsLog::CONFIG_NAME, '1');
        $check(ExpiredSessionsLog::enabled() === false, 'a3 y con «1» tampoco: hace falta el booleano true');

        set_config(ExpiredSessionsLog::CONFIG_NAME, false);
        //Se demuestra llenando la carpeta vieja y comprobando que nadie la toca: el formato
        //anterior la recorría y borraba los de más de 30 días DENTRO de la petición.
        $carpetaVieja = ExpiredSessionsLog::legacyFolder($temporal);
        //RETORNO-IGNORADO: lo comprueba el conteo de abajo.
        mkdir($carpetaVieja, 0775, true);
        $viejos = [];
        for ($i = 0; $i < 40; $i++) {
            //Con fecha de hace un año: el formato viejo los habría borrado por los 30 días.
            $nombre = (new DateTimeImmutable('-400 days'))->format('d-m-Y_h-i-s-U.u_A') . "-{$i}.json";
            $ruta = "{$carpetaVieja}/{$nombre}";
            //RETORNO-IGNORADO: a6 cuenta los 40 archivos, así que uno que no se escriba se ve ahí.
            file_put_contents($ruta, '{"zz":"archivo de prueba"}');
            $viejos[] = $ruta;
        }
        $antesDeLaLlamada = count(array_filter($viejos, 'is_file'));
        $escribio = $anotar();
        $check($escribio === false, 'a4 apagada, record() devuelve false');
        $check($lineas() === 0 && !is_file(ExpiredSessionsLog::path($temporal)), 'a5 y no crea el archivo de registro');
        $check(count(array_filter($viejos, 'is_file')) === $antesDeLaLlamada && $antesDeLaLlamada === 40,
            'a6 los 40 archivos viejos siguen ahí: la carpeta NO se recorrió ni se limpió',
            'antes ' . $antesDeLaLlamada . ', ahora ' . count(array_filter($viejos, 'is_file')));

        //──── b · Encendida: una caducidad, una línea ───────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Encendida, una caducidad añade UNA línea');
        set_config(ExpiredSessionsLog::CONFIG_NAME, true);
        $check(ExpiredSessionsLog::enabled() === true, 'b1 con la opción en true, encendida');
        $check($anotar() === true, 'b2 record() devuelve true');
        $check($lineas() === 1, 'b3 y hay UNA línea: ' . $lineas());
        $anotar();
        $check($lineas() === 2, 'b4 una segunda caducidad añade otra, no sobrescribe: ' . $lineas());
        $primera = $contenido();
        $check(str_contains($primera, 'zz-ruta-de-prueba') && str_contains($primera, '203.0.113.7'), 'b5 la línea trae la ruta y la IP');
        $check(str_contains($primera, '[usuario 7]'), 'b6 y el id del usuario');
        $check(str_contains($primera, '[renovable no]'), 'b7 y si la ruta era candidata a renovación');

        //──── c · La línea NO contiene el token ─────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[c] La prueba que justifica el ADR: el token NO está en el archivo');
        $texto = $contenido();
        $check(!str_contains($texto, $tokenFalso), 'c1 el JWT completo NO aparece en el registro');
        $check(!str_contains($texto, 'zzCARGAFALSADEPRUEBA'), 'c2 ni su carga');
        $check(!str_contains($texto, 'zzFIRMAFALSADEPRUEBA'), 'c3 ni su firma');
        //El `aud` y el `data` del token tampoco: la clase no los recibe, así que no puede escribirlos.
        $check(!str_contains($texto, 'aud') && !str_contains($texto, 'data'), 'c4 ni el aud ni el data del token');
        //Y las fechas SÍ, que es lo que sirve para depurar.
        $check(str_contains($texto, '[iat 20') && str_contains($texto, '[exp 20'), 'c5 y sí están iat y exp, como fechas legibles');

        //──── d · El tope y la rotación ─────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] Al pasar el tope rota, y quedan dos archivos como mucho');
        //RETORNO-IGNORADO: si no se escribe, d1 falla al no haber nada que rotar.
        file_put_contents(ExpiredSessionsLog::path($temporal), str_repeat('z', ExpiredSessionsLog::MAX_BYTES + 10));
        $tamanoAntes = (int) filesize(ExpiredSessionsLog::path($temporal));
        $anotar();
        $rotoExiste = is_file(ExpiredSessionsLog::rotatedPath($temporal));
        $check($rotoExiste, 'd1 el registro lleno se movió a .log.1');
        //Sin el `is_file`, una provocación que impida rotar mata la suite aquí en vez de dejarla seguir.
        $check($rotoExiste && (int) filesize(ExpiredSessionsLog::rotatedPath($temporal)) === $tamanoAntes, 'd2 y el rotado tiene lo que había');
        $check($lineas() === 1, 'd3 el registro nuevo empieza de cero y tiene una sola línea: ' . $lineas());
        //Una segunda rotación sobrescribe el `.log.1`: dos archivos como mucho, nunca tres.
        //RETORNO-IGNORADO: lo mismo; d4 comprueba el contenido del rotado.
        file_put_contents(ExpiredSessionsLog::path($temporal), str_repeat('y', ExpiredSessionsLog::MAX_BYTES + 10));
        $anotar();
        $rotado = is_file(ExpiredSessionsLog::rotatedPath($temporal)) ? (string) file_get_contents(ExpiredSessionsLog::rotatedPath($temporal)) : '';
        $check(str_starts_with($rotado, 'y'), 'd4 la segunda rotación sobrescribe el .log.1 anterior');
        $cuantos = count(array_filter(
            scandir($temporal) ?: [],
            fn (string $e): bool => str_starts_with($e, 'expired-sessions.log')
        ));
        $check($cuantos === 2, 'd5 nunca hay más de dos archivos de registro: ' . $cuantos);

        //──── e · Sin usuario, «anónimo», y NO el id 1 ──────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] Sin id de usuario se registra «anónimo», no el 1');
        //RETORNO-IGNORADO: el archivo se reescribe entero para aislar esta comprobación.
        file_put_contents(ExpiredSessionsLog::path($temporal), '');
        $anotar(null);
        $texto = $contenido();
        $check(str_contains($texto, '[usuario anónimo]'), 'e1 dice «anónimo»', $texto);
        $check(!str_contains($texto, '[usuario 1]'), 'e2 y NO atribuye al id 1 (276.2 es otro defecto, aparte)');

        //──── f · El canario: la suite vería el caso bueno ──────────────────────────────────────
        echoTerminal('');
        echoTerminal('[f] El canario: sin esto, un «no se escribió nada» pasaría gratis');
        //RETORNO-IGNORADO: lo comprueba f1 contando la línea.
        file_put_contents(ExpiredSessionsLog::path($temporal), '');
        $check($anotar() === true && $lineas() === 1, 'f1 CANARIO: con la opción encendida SÍ se escribe, así que los «no escribe» de arriba significan algo');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //──── z · Limpieza ──────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[z] Limpieza');
    set_config(ExpiredSessionsLog::CONFIG_NAME, $previo);
    foreach (glob(ExpiredSessionsLog::legacyFolder($temporal) . '/*') ?: [] as $archivo) {
        //RETORNO-IGNORADO: lo comprueba z1.
        unlink($archivo);
    }
    if (is_dir(ExpiredSessionsLog::legacyFolder($temporal))) {
        //RETORNO-IGNORADO: lo comprueba z1.
        rmdir(ExpiredSessionsLog::legacyFolder($temporal));
    }
    foreach (glob($temporal . '/*') ?: [] as $archivo) {
        if (is_file($archivo)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            unlink($archivo);
        }
    }
    //RETORNO-IGNORADO: lo comprueba z1.
    rmdir($temporal);
    $check(!is_dir($temporal), 'z1 la carpeta temporal de la prueba se borró', $temporal);
    $check(get_config(ExpiredSessionsLog::CONFIG_NAME) === $previo, 'z2 y la opción quedó como estaba');

    return $balance();

})->setDescription('El registro de sesiones caducadas: apagado por omisión y sin recorrer la carpeta, una línea por caducidad, sin el token ni su aud o data, con rotación por tope y «anónimo» cuando no hay id.')->setEffects([CliActions::EFFECT_FILES])->register();
