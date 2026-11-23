<?php

//El esquema y las redirecciones de HttpClient (CVE-2026-128064), sin red: un servidor local no habla TLS, así que
//las respuestas las da un cliente hijo que sustituye la apertura. Lo que llega de verdad al otro lado, en `core/http-client`.
//@areas nucleo

use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/http-client-redirects', function ($args) {

    echoTerminal("\e[33m[TEST:HttpClientRedirects] El esquema en minúsculas, y ninguna redirección de HTTPS a HTTP\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    //Cada dirección responde lo que diga `$hops`; una que no esté ahí falla como un puerto cerrado.
    $fake = function (string $baseURL, array $hops): HttpClient {
        return new class($baseURL, $hops) extends HttpClient
        {
            /** @var string[] */
            public array $visited = [];
            /** @var string[] */
            public array $methods = [];
            /** @var string[] */
            public array $headers = [];
            public ?string $hopStatusLine = null;
            private ?array $last = null;

            /**
             * @param array<string,array{0:int,1:string,2?:string}> $hops dirección => [código, cuerpo, Location]
             */
            public function __construct(string $baseURL, private array $hops)
            {
                parent::__construct($baseURL);
            }

            protected function openURL(string $url, $context): string|false
            {
                $this->visited[] = $url;
                $options = stream_context_get_options($context);
                $this->methods[] = (string) ($options['http']['method'] ?? '');
                $this->headers[] = (string) ($options['http']['header'] ?? '');
                if (!isset($this->hops[$url])) {
                    $this->last = null;
                    return false;
                }
                $hop = $this->hops[$url];
                $this->last = [$this->hopStatusLine ?? "HTTP/1.1 {$hop[0]} X", 'X-Hop: ' . count($this->visited)];
                if (isset($hop[2])) {
                    $this->last[] = "Location: {$hop[2]}";
                }
                return $hop[1];
            }

            protected function lastResponseHeaders(): ?array
            {
                return $this->last;
            }
        };
    };

    try {

        echoTerminal('[1/6] El esquema, en minúsculas y nada más');
        foreach ([
            'HTTPS://h/x' => 'https://h/x',
            'HtTpS://h/X?Q=A' => 'https://h/X?Q=A',
            'https://h/x' => 'https://h/x',
        ] as $input => $expected) {
            $got = HttpClient::normalizeScheme($input);
            $check($got === $expected, "n1 «{$input}» → «{$expected}»", $got);
        }
        $client = $fake('HTTPS://h', ['https://h/X?Q=A' => [200, 'OK']]);
        $client->request('/X?Q=A', 'GET');
        $check(($client->visited[0] ?? '') === 'https://h/X?Q=A', 'n2 request() abre la dirección con el esquema ya en minúsculas', (string) ($client->visited[0] ?? 'nada'));
        echoTerminal(' ');

        echoTerminal('[2/6] El Location se resuelve contra la dirección que lo dio');
        foreach ([
            ['https://h/a/b', 'HTTP://o/y', 'http://o/y'],
            ['https://h/a/b', '/x', 'https://h/x'],
            ['https://h/a/b', 'x', 'https://h/a/x'],
            ['https://h/a/b', '//o/x', 'https://o/x'],
            ['https://h/a/b', '../x?q=1', 'https://h/x?q=1'],
            ['http://127.0.0.1:8080/a/b?z=1', '?q=2', 'http://127.0.0.1:8080/a/b?q=2'],
        ] as [$base, $location, $expected]) {
            $got = HttpClient::resolveLocation($base, $location);
            $check($got === $expected, "l1 «{$location}» desde {$base} → {$expected}", $got);
        }
        echoTerminal(' ');

        echoTerminal('[3/6] Qué se sigue y qué se corta');
        //La referencia: un error de red de verdad, contra un puerto que el sistema dio y ya está cerrado.
        $probe = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $name = $probe !== false ? (string) stream_socket_get_name($probe, false) : '';
        if ($probe !== false) {
            //RETORNO-IGNORADO: solo hacía falta que el sistema diera un puerto; cerrado, rechaza la conexión.
            fclose($probe);
        }
        $closed = new HttpClient('http://' . $name);
        $closed->timeout(2);
        $networkLoud = $closed->request('/x', 'GET');
        $networkQuiet = @$closed->request('/x', 'GET');
        $networkHeaders = $closed->getResponseHeaders();
        $networkStatus = $closed->getResponseStatus();
        echoTerminal('   error de red: ' . var_export($networkLoud, true) . ' · con @: ' . var_export($networkQuiet, true));
        $shape = fn($value): string => is_string($value) ? (string) preg_replace('/^(\(Level: Warning\) file_get_contents\().*(\): Failed to open stream: ).*$/', '$1…$2…', $value) : var_export($value, true);

        $downgrade = ['https://h/a' => [302, 'cuerpo-302', 'http://h/b'], 'http://h/b' => [200, 'NO-DEBERIA-LLEGAR']];
        $client = $fake('https://h', $downgrade);
        $loud = $client->request('/a', 'GET');
        $quietClient = $fake('https://h', $downgrade);
        $quiet = @$quietClient->request('/a', 'GET');
        $check($client->visited === ['https://h/a'] && $quietClient->visited === ['https://h/a'], 'r1 de https a http no se sigue', implode(', ', $client->visited));
        $check($shape($loud) === $shape($networkLoud) && $shape($networkLoud) !== var_export($networkLoud, true) && $quiet === $networkQuiet, 'r2 y falla como un error de red, con @ y sin ella', var_export($loud, true) . ' · ' . var_export($quiet, true));
        $check($quietClient->getResponseHeaders() === $networkHeaders && $quietClient->getResponseStatus() === $networkStatus, 'r3 sin cabeceras ni estado, como un error de red', (string) json_encode($quietClient->getResponseHeaders()));

        $client = $fake('http://h', ['http://h/a' => [301, '', 'HTTPS://h/b'], 'https://h/b' => [200, 'OK-B']]);
        $body = $client->request('/a', 'GET');
        $check($body === 'OK-B' && $client->visited === ['http://h/a', 'https://h/b'], 'r4 de http a https se sigue', implode(', ', $client->visited));

        $client = $fake('https://h', ['https://h/a/b' => [302, '', 'c'], 'https://h/a/c' => [200, 'OK-C']]);
        $body = $client->request('/a/b', 'GET');
        $check($body === 'OK-C' && (int) $client->getResponseStatus() === 200 && ($client->getResponseHeaders()['X-Hop'] ?? '') === '2', 'r5 entre https se sigue, y el estado y las cabeceras son los del último salto', var_export($body, true) . ' · ' . (string) json_encode($client->getResponseHeaders()));

        $chain = function (int $redirects): array {
            $hops = [];
            for ($i = 0; $i < $redirects; $i++) {
                $hops["https://h/{$i}"] = [302, '', '/' . ($i + 1)];
            }
            $hops["https://h/{$redirects}"] = [200, 'FIN'];
            return $hops;
        };
        $client = $fake('https://h', $chain(HttpClient::MAX_REDIRECTS));
        $body = $client->request('/0', 'GET');
        $check(HttpClient::MAX_REDIRECTS === 5 && $body === 'FIN' && count($client->visited) === 6, 'r6 cinco redirecciones se siguen', count($client->visited) . ' aperturas');
        $client = $fake('https://h', $chain(HttpClient::MAX_REDIRECTS + 1));
        $body = @$client->request('/0', 'GET');
        $check($body === false && count($client->visited) === 6, 'r7 la sexta no: falla sin abrirla', count($client->visited) . ' aperturas · ' . var_export($body, true));

        $client = $fake('https://h', ['https://h/a' => [302, '', '/b'], 'https://h/b' => [200, 'OK']]);
        $client->request('/a', 'POST', ['x' => 1]);
        $keep = $fake('https://h', ['https://h/a' => [307, '', '/b'], 'https://h/b' => [200, 'OK']]);
        $keep->request('/a', 'POST', ['x' => 1]);
        $check($client->methods === ['POST', 'GET'] && $keep->methods === ['POST', 'POST'], 'r8 como seguía PHP: un 302 vuelve GET el POST y un 307 lo conserva', implode(',', $client->methods) . ' · ' . implode(',', $keep->methods));

        $client = $fake('https://h', ['https://h/a' => [302, '', 'ftp://h/b']]);
        $body = @$client->request('/a', 'GET');
        $check($body === false && count($client->visited) === 1, 'r9 un Location que no es http ni https no se abre');
        echoTerminal(' ');

        echoTerminal('[4/6] Lo que no es http no busca redirecciones');
        $client = new HttpClient('data://text/plain,OK');
        $check($client->request('', 'GET') === 'OK', 'd1 un data:// se sigue leyendo');
        echoTerminal(' ');

        echoTerminal('[5/6] Las cookies del visitante solo van a la propia instalación');
        $cookiesBefore = $_COOKIE;
        try {
            $_COOKIE = ['zz-galleta' => 'zz-valor'];
            $sends = fn(string $raw, string $name): bool => preg_match('/^' . $name . ':/mi', $raw) === 1;
            $own = rtrim(HttpClient::normalizeScheme(baseurl()), '/');
            $ownParts = parse_url($own);
            $ownScheme = is_array($ownParts) ? (string) ($ownParts['scheme'] ?? 'http') : 'http';
            $ownHost = is_array($ownParts) ? (string) ($ownParts['host'] ?? 'localhost') : 'localhost';
            $ownPort = is_array($ownParts) ? (int) ($ownParts['port'] ?? ($ownScheme === 'https' ? 443 : 80)) : 80;
            $otherPort = "{$ownScheme}://{$ownHost}:" . ($ownPort === 8443 ? 8444 : 8443);

            $client = $fake($own, ["{$own}/zz-a" => [200, 'OK']]);
            $client->request('/zz-a', 'GET');
            $check(!$sends($client->headers[0] ?? '', 'Cookie'), 'c1 sin decirlo, no se envía ninguna cookie, ni a la propia instalación', $client->headers[0] ?? 'nada');

            $client = $fake('https://zz-otro.test', ['https://zz-otro.test/a' => [200, 'OK']]);
            $client->request('/a', 'GET', [], [], true);
            $check(!$sends($client->headers[0] ?? '', 'Cookie'), 'c2 con true hacia otro host, tampoco', $client->headers[0] ?? 'nada');

            $client = $fake($own, ["{$own}/zz-a" => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], [], true);
            $check(str_contains($client->headers[0] ?? '', "Cookie:zz-galleta=zz-valor\r\n"), 'c3 con true hacia la propia instalación, sí', $own);

            $client = $fake($otherPort, ["{$otherPort}/zz-a" => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], [], true);
            $check(!$sends($client->headers[0] ?? '', 'Cookie'), 'c4 con true hacia el mismo host con otro puerto, no', $otherPort);

            $client = $fake($own, ["{$own}/zz-a" => [302, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], ['Authorization' => 'Bearer zz-token'], true);
            $first = $client->headers[0] ?? '';
            $second = $client->headers[1] ?? '';
            $check(count($client->headers) === 2 && $sends($first, 'Cookie') && $sends($first, 'Authorization') && !$sends($second, 'Cookie') && !$sends($second, 'Authorization'), 'c5 una redirección a otro origen no lleva ni la cookie ni Authorization', count($client->headers) . ' aperturas');

            $_COOKIE = ['zz-salto' => "\n", 'zz-sesion' => 'zz-secreto'];
            $client = $fake($own, ["{$own}/zz-a" => [302, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], ['Authorization' => "Bearer zz-token\n"], true);
            $second = $client->headers[1] ?? '';
            $check(count($client->headers) === 2 && str_contains($client->headers[0], 'zz-secreto') && !str_contains($second, 'zz-secreto') && !str_contains($second, 'zz-token'), 'c7 un salto de línea dentro de un valor no deja pasar la cookie ni el token a otro origen', count($client->headers) . ' aperturas');
            $_COOKIE = ['zz-galleta' => 'zz-valor'];

            $client = $fake($own, ["{$own}/zz-a" => [302, '', '/zz-b'], "{$own}/zz-b" => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], [], true);
            $check(count($client->headers) === 2 && $sends($client->headers[1], 'Cookie'), 'c6 CANARIO: una redirección dentro de la instalación conserva la cookie', count($client->headers) . ' aperturas');
        } finally {
            $_COOKIE = $cookiesBefore;
        }
        echoTerminal(' ');

        echoTerminal('[6/6] Lo que no cabe en una cabecera no sale, y lo sensible no cambia de origen');
        $cookiesBefore = $_COOKIE;
        try {
            $_COOKIE = ['zz-salto' => "a\nb", 'zz-coma' => 'a;b', 'zz-buena' => 'zz-valor'];
            $client = $fake($own, ["{$own}/zz-a" => [200, 'OK']]);
            $client->request('/zz-a', 'GET', [], [], true);
            $raw = $client->headers[0] ?? '';
            $check(str_contains($raw, 'zz-buena=zz-valor') && !str_contains($raw, 'zz-salto') && !str_contains($raw, 'zz-coma'), 'k1 una cookie con salto de línea o «;» no entra en la cabecera; las demás sí', str_replace(["\r", "\n"], ['\\r', '\\n'], $raw));
        } finally {
            $_COOKIE = $cookiesBefore;
        }

        $client = $fake($own, ["{$own}/zz-a" => [200, 'OK']]);
        $client->request('/zz-a', 'GET', [], ['X-Zz-Valor' => "a\r\nX-Inyectada: 1", "X-Zz-Nombre\r\nX-Inyectada2" => 'b', 'X-Zz-Lista' => ['ok', "c\nX-Inyectada3: 1"], 'Accept' => 'text/plain']);
        $raw = $client->headers[0] ?? '';
        $check(!str_contains($raw, 'X-Inyectada') && !str_contains($raw, 'X-Zz-') && str_contains($raw, 'Accept:text/plain'), 'k2 una cabecera del llamador con CR/LF (en el nombre, el valor o una lista) no sale; las demás sí', str_replace(["\r", "\n"], ['\\r', '\\n'], $raw));

        $sensible = ['X-API-Key' => 'zz-clave', 'X-Auth-Token' => 'zz-ficha', 'Accept' => 'text/plain'];
        $client = $fake($own, ["{$own}/zz-a" => [302, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK']]);
        $client->request('/zz-a', 'GET', [], $sensible);
        $first = $client->headers[0] ?? '';
        $second = $client->headers[1] ?? '';
        $check(count($client->headers) === 2 && str_contains($first, 'zz-clave') && str_contains($first, 'zz-ficha') && !str_contains($second, 'zz-clave') && !str_contains($second, 'zz-ficha') && str_contains($second, 'Accept:text/plain'), 'k3 X-API-Key y X-Auth-Token no viajan al segundo salto a otro origen; Accept sí', count($client->headers) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [302, '', '/zz-b'], "{$own}/zz-b" => [200, 'OK']]);
        $client->request('/zz-a', 'GET', [], $sensible);
        $second = $client->headers[1] ?? '';
        $check(count($client->headers) === 2 && str_contains($second, 'zz-clave') && str_contains($second, 'zz-ficha'), 'k4 dentro del mismo origen sí viajan', count($client->headers) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [307, 'cuerpo-307', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'NO-DEBERIA-LLEGAR']]);
        $body = $client->request('/zz-a', 'POST', ['zz-secreto' => 'zz-valor']);
        $check(count($client->visited) === 1 && (int) $client->getResponseStatus() === 307 && $body === 'cuerpo-307', 'k5 un 307 con cuerpo a otro origen no se sigue: se devuelve el 307', count($client->visited) . ' aperturas · ' . var_export($client->getResponseStatus(), true));

        $client = $fake($own, ["{$own}/zz-a" => [308, '', '/zz-b'], "{$own}/zz-b" => [200, 'OK-B']]);
        $body = $client->request('/zz-a', 'POST', ['zz-secreto' => 'zz-valor']);
        $check(count($client->visited) === 2 && $body === 'OK-B' && $client->methods === ['POST', 'POST'], 'k6 un 308 con cuerpo dentro del mismo origen se sigue, con su POST', count($client->visited) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [307, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK-B']]);
        $body = $client->request('/zz-a', 'GET');
        $check(count($client->visited) === 2 && $body === 'OK-B', 'k7 un 307 sin cuerpo a otro origen se sigue como hoy', count($client->visited) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [307, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK-B']]);
        $body = $client->request('/zz-a', 'GET', [], ['Content-Type' => 'application/json']);
        $check(count($client->visited) === 2 && $body === 'OK-B', 'k8 un GET con JSON vacío no cuenta como cuerpo: su 307 a otro origen se sigue', count($client->visited) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [0, 'cuerpo-307', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'NO-DEBERIA-LLEGAR']]);
        $client->hopStatusLine = 'HTTP/1.1 307 Temporary: Redirect';
        $body = $client->request('/zz-a', 'POST', ['zz-secreto' => 'zz-valor']);
        $check(count($client->visited) === 1 && $body === 'cuerpo-307', 'k9 una frase de estado con «:» no cambia el código: el 307 con cuerpo a otro origen tampoco se sigue', count($client->visited) . ' aperturas');

        $client = $fake($own, ["{$own}/zz-a" => [302, '', 'https://zz-otro.test/b'], 'https://zz-otro.test/b' => [200, 'OK']]);
        $client->request('/zz-a', 'GET', [], ['Proxy-Authorization' => 'Basic zz-proxy', 'x_api_key' => 'zz-guion', 'X-Zz-Nulo' => "a\0b", 'Accept' => 'text/plain']);
        $first = $client->headers[0] ?? '';
        $second = $client->headers[1] ?? '';
        $check(!str_contains($first, 'X-Zz-Nulo') && str_contains($first, 'zz-proxy') && !str_contains($second, 'zz-proxy') && !str_contains($second, 'zz-guion') && str_contains($second, 'Accept:text/plain'), 'k10 un NUL descarta la cabecera; Proxy-Authorization y x_api_key no cambian de origen', count($client->headers) . ' aperturas');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('HttpClient pone el esquema en minúsculas antes de abrir (CVE-2026-128064), resuelve cada Location, sigue cinco redirecciones como mucho y corta la de HTTPS a HTTP igual que un error de red, y solo manda las cookies del visitante a la propia instalación, sin salir de la máquina.')->setEffects([CliActions::EFFECT_NONE])->register();
