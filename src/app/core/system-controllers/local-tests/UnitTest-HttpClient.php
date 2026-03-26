<?php
use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/http-client';
$cliTaskDescription = 'Pruebas unitarias de ' . HttpClient::class;
CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {
    echoTerminal('[TEST:HTTPClient] Iniciando suite de pruebas unitarias...');
    echoTerminal('');
    set_config('terminal_color', '33');

    $webhookURL = 'https://webhook.site/1948a039-b070-48f2-8ade-4421cdd7889c';
    $client = new HttpClient($webhookURL);

    // Cabeceras por defecto
    $client->setDefaultRequestHeaders([
        'Authorization' => 'Bearer DEFAULT_TOKEN',
        'Accept' => 'application/json',
    ]);

    $checkResult = function ($condition, $name) {
        $status = $condition ? '[PASÓ]' : '[FALLÓ]';
        echoTerminal("   $status $name");
        return $condition;
    };

    // --- CASO 1: GET con Parámetros ---
    echoTerminal('[1/5] Probando GET con parámetros de consulta...');
    $params = ['search' => 'test@example.com', 'limit' => 1];
    $client->request('', 'GET', $params);

    $uri = $client->getRequestURI();
    $hasParams = strpos($uri, 'search=test%40example.com') !== false && strpos($uri, 'limit=1') !== false;
    $gotStatus = $client->getResponseStatus() !== null;

    $checkResult($hasParams && $gotStatus, 'GET Params y Conectividad');
    echoTerminal('   URL: ' . $uri);
    echoTerminal('   Status: ' . ($client->getResponseStatus() ?? 'ERROR/TIMEOUT'));
    echoTerminal('   Response: ' . ($client->getResponseBody()));
    echoTerminal(' ');

    // --- CASO 2: POST con JSON ---
    echoTerminal('[2/5] Probando POST con cuerpo JSON...');
    $body = ['name' => 'Test Item', 'value' => 123];
    $client->request('', 'POST', $body, ['Content-Type' => 'application/json']);

    $sentBody = $client->getRequestBody();
    $isJson = @json_decode($sentBody) !== null;
    $hasValues = strpos($sentBody, '"name":"Test Item"') !== false;

    $checkResult($isJson && $hasValues, 'Cuerpo POST codificado como JSON');
    echoTerminal('   Status: ' . $client->getResponseStatus());
    echoTerminal('   Body: ' . $sentBody);
    echoTerminal(' ');

    // --- CASO 3: Fusión de cabeceras (override_defaults = true) ---
    echoTerminal('[3/5] Probando fusión con override_defaults = true...');
    $client->request('', 'GET', [], ['Accept' => 'text/plain'], true, true);
    $headers = $client->getRequestHeaders();

    $isOverridden = ($headers['Accept'] ?? '') === 'text/plain';
    $authMissing = !isset($headers['Authorization']); // Si override=true y no lo pasamos, se pierde

    $checkResult($isOverridden && $authMissing, 'Sobrescritura total de cabeceras');
    echoTerminal('   Status: ' . $client->getResponseStatus());
    echoTerminal('   Accept: ' . ($headers['Accept'] ?? 'N/A'));
    echoTerminal('   Auth: ' . (isset($headers['Authorization']) ? 'Presente (Error)' : 'Ausente (OK)'));
    echoTerminal(' ');

    // --- CASO 4: Fusión de cabeceras (override_defaults = false) ---
    echoTerminal('[4/5] Probando fusión con override_defaults = false...');
    $client->request('', 'POST', ['hi' => 1], ['Content-Type' => 'application/json'], true, false);
    $headers = $client->getRequestHeaders();

    $hasCustom = ($headers['Content-Type'] ?? '') === 'application/json';
    $hasDefault = ($headers['Authorization'] ?? '') === 'Bearer DEFAULT_TOKEN';

    $checkResult($hasCustom && $hasDefault, 'Fusión de cabeceras (Mantiene defaults)');
    echoTerminal('   Status: ' . $client->getResponseStatus());
    echoTerminal('   Content-Type: ' . ($headers['Content-Type'] ?? 'N/A'));
    echoTerminal('   Auth: ' . ($headers['Authorization'] ?? 'N/A'));
    echoTerminal(' ');

    // --- CASO 5: Timeout ---
    echoTerminal('[5/5] Probando Timeout configurado...');
    $timeoutClient = new HttpClient('http://10.255.255.1');
    $timeoutClient->timeout(2); // bajamos a 2s para rapidez
    $startTime = microtime(true);
    @$timeoutClient->request('', 'GET');
    $duration = microtime(true) - $startTime;

    $worked = $duration >= 2 && $duration < 4; // Margen de error
    $checkResult($worked, "Timeout detectado en ~$duration s");
    echoTerminal(' ');

    set_config('terminal_color', null);
    echoTerminal('[TEST:HTTPClient] Suite finalizada.');
    echoTerminal('Pruebas completadas. Revisa logs de terminal y Webhook.site');

})->setDescription($cliTaskDescription)->register();
