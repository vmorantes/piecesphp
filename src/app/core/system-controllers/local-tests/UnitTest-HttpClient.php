<?php

//El cliente HTTP contra el sumidero LOCAL del ADR 0041, no contra internet. Lo que no necesita
//salir —cómo se construye la petición— vive en `core/http-client-request-build`. T130, 311.

use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/http-client', function ($args) {

    echoTerminal("\e[33m[TEST:HttpClient] La petición sale, la respuesta vuelve, y el tiempo se honra\e[39m");
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

    $responde = function (int $puerto, float $espera = 0.5): bool {
        $conexion = @fsockopen('127.0.0.1', $puerto, $errno, $errstr, $espera);
        if ($conexion === false) {
            return false;
        }
        //RETORNO-IGNORADO: la pregunta era si el puerto responde, y eso ya lo contestó fsockopen().
        fclose($conexion);
        return true;
    };

    //El puerto lo da el sistema (`:0`) y no se escribe a mano: uno fijo choca el día que algo más
    //lo use, y la prueba fallaría sin culpa del producto. Nunca el 1025 ni el 8025, que son de Mailpit.
    $puerto = 0;
    for ($intento = 0; $intento < 10 && $puerto === 0; $intento++) {
        $sonda = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sonda === false) {
            continue;
        }
        $nombre = (string) stream_socket_get_name($sonda, false);
        //RETORNO-IGNORADO: la sonda solo servía para que el sistema diera un puerto; cerrarla no puede fallar de forma útil.
        fclose($sonda);
        $candidato = (int) substr($nombre, (int) strrpos($nombre, ':') + 1);
        if ($candidato > 0 && !in_array($candidato, [1025, 8025], true)) {
            $puerto = $candidato;
        }
    }
    if (!$check($puerto > 0, 'p0 hay un puerto libre para el sumidero', 'ninguno en 10 intentos')) {
        return $balance();
    }

    $raizProyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $router = "{$raizProyecto}/bin/tools/sumidero-http/router.php";
    if (!$check(is_file($router), 'p1 el sumidero existe en el repositorio', $router)) {
        return $balance();
    }

    $base = "http://127.0.0.1:{$puerto}";
    $tuberias = [];
    //Se lanza con ARRAY, no con una cadena: así no hay shell intermedio y `proc_terminate()` mata
    //al servidor y no a un `sh` que lo envuelve.
    $proceso = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:{$puerto}", '-t', dirname($router), $router],
        [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
        $tuberias
    );

    try {

        if (!$check(is_resource($proceso), 'p2 el sumidero arranca')) {
            return $balance();
        }
        for ($espera = 0; $espera < 60 && !$responde($puerto); $espera++) {
            usleep(50000);
        }

        //─── 0 · EL CANARIO: sin esto, «no devuelve un cuerpo inventado» pasa con el sumidero apagado ───
        echoTerminal('[0] El canario: el sumidero está vivo y es el nuestro');
        $canario = new HttpClient($base);
        $canario->timeout(5);
        $cuerpoCanario = $canario->request('/echo', 'GET');
        $estadoCanario = $canario->getResponseStatus();
        $sobreCanario = is_string($cuerpoCanario) ? json_decode($cuerpoCanario, true) : null;
        $check((int) $estadoCanario === 200, 'c1 CANARIO: el sumidero responde 200 en 127.0.0.1', var_export($estadoCanario, true));
        $vivo = is_array($sobreCanario) && ($sobreCanario['sumidero'] ?? null) === 'piecesphp-sumidero-http';
        if (!$check($vivo, 'c2 CANARIO: y es el sumidero del repositorio, por su marca', mb_substr((string) $cuerpoCanario, 0, 120))) {
            return $balance();
        }
        echoTerminal(' ');

        //─── 1 · La respuesta vuelve con su código y su cuerpo ───────────────────────────────────────────
        echoTerminal('[1] Un GET trae estado y cuerpo');
        $cliente = new HttpClient($base);
        $cliente->setDefaultRequestHeaders(['Accept' => 'application/json']);
        $cliente->timeout(15);
        $cuerpo = $cliente->request('/echo', 'GET', ['q' => 'prueba']);
        $estado = $cliente->getResponseStatus();
        $check($estado !== null, '1a la respuesta trae código de estado', var_export($estado, true));
        $check(is_string($cuerpo) && mb_strlen($cuerpo) > 0, '1b y un cuerpo no vacío',
            is_string($cuerpo) ? mb_strlen($cuerpo) . ' bytes' : var_export($cuerpo, true));
        echoTerminal('   URI: ' . $cliente->getRequestURI());
        echoTerminal('   Estado: ' . ($estado ?? 'sin respuesta'));

        //Esto NO lo puede medir `request-build`, que inspecciona el emisor: aquí se comprueba lo que
        //el otro lado RECIBIÓ de verdad. Es la unión entre las dos suites.
        $sobre = is_string($cuerpo) ? json_decode($cuerpo, true) : null;
        $check(is_array($sobre) && ($sobre['metodo'] ?? null) === 'GET', '1c y al otro lado llegó el método que se pidió', is_array($sobre) ? var_export($sobre['metodo'] ?? null, true) : 'sin sobre');
        $check(is_array($sobre) && (($sobre['consulta']['q'] ?? null) === 'prueba'), '1d y la consulta llegó entera', is_array($sobre) ? var_export($sobre['consulta'] ?? null, true) : 'sin sobre');
        echoTerminal(' ');

        //─── 2 · Un código de error no se confunde con un acierto ────────────────────────────────────────
        echoTerminal('[2] Un 404 y un 500 llegan como lo que son');
        foreach ([404, 500] as $codigoPedido) {
            $conEstado = new HttpClient($base);
            $conEstado->timeout(5);
            $conEstado->request('/estado', 'GET', ['codigo' => $codigoPedido]);
            $check((int) $conEstado->getResponseStatus() === $codigoPedido, "2a el {$codigoPedido} se refleja en el estado, no se traga", var_export($conEstado->getResponseStatus(), true));
        }
        echoTerminal(' ');

        //─── 3 · El tiempo de espera se honra ────────────────────────────────────────────────────────────
        echoTerminal('[3] El tiempo de espera corta la petición');
        //OJO AL ORDEN: `HttpClient::$baseURL` es ESTÁTICA, así que construir este cliente reescribe la
        //base del anterior. Todo lo que use el cliente de arriba tiene que estar hecho ya.
        $lento = new HttpClient($base);
        $lento->timeout(2);
        $inicio = microtime(true);
        $respuesta = @$lento->request('/lento', 'GET', ['segundos' => 8]);
        $duracion = microtime(true) - $inicio;
        $check($duracion >= 2, '3a espera lo declarado antes de rendirse', round($duracion, 2) . ' s');
        //Sin la cota superior, un tiempo de espera ignorado del todo también «pasaría».
        $check($duracion < 6, '3b y no se pasa de largo', round($duracion, 2) . ' s');
        $check($respuesta === false || $respuesta === '', '3c y no devuelve un cuerpo inventado',
            var_export($respuesta, true));

    } finally {
        echoTerminal(' ');
        echoTerminal('[z] El sumidero se apaga');
        if (is_resource($proceso)) {
            proc_terminate($proceso);
            for ($espera = 0; $espera < 40 && $responde($puerto, 0.2); $espera++) {
                usleep(50000);
            }
            //RETORNO-IGNORADO: tras proc_terminate() el código de salida es el de la señal; quien dice si murió es z1.
            proc_close($proceso);
        }
        $check(!$responde($puerto, 0.5), "z1 nada queda escuchando en 127.0.0.1:{$puerto}");
    }

    return $balance();

})->setDescription('HttpClient contra el sumidero HTTP local del ADR 0041, sin salir de la máquina: la respuesta vuelve con su código y su cuerpo, un 404 y un 500 no se tragan, lo que llega al otro lado es lo que se pidió, y el tiempo de espera corta de verdad.')->setEffects([CliActions::EFFECT_NONE])->register();
