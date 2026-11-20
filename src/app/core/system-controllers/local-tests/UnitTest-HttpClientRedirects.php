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
                if (!isset($this->hops[$url])) {
                    $this->last = null;
                    return false;
                }
                $hop = $this->hops[$url];
                $this->last = ["HTTP/1.1 {$hop[0]} X", 'X-Hop: ' . count($this->visited)];
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

        echoTerminal('[1/4] El esquema, en minúsculas y nada más');
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

        echoTerminal('[2/4] El Location se resuelve contra la dirección que lo dio');
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

        echoTerminal('[3/4] Qué se sigue y qué se corta');
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

        echoTerminal('[4/4] Lo que no es http no busca redirecciones');
        $client = new HttpClient('data://text/plain,OK');
        $check($client->request('', 'GET') === 'OK', 'd1 un data:// se sigue leyendo');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('HttpClient pone el esquema en minúsculas antes de abrir (CVE-2026-128064), resuelve cada Location, sigue cinco redirecciones como mucho y corta la de HTTPS a HTTP igual que un error de red, sin salir de la máquina.')->setEffects([CliActions::EFFECT_NONE])->register();
