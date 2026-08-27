<?php

use API\Adapters\MauticEmailAdapter;
use PiecesPHP\Core\BaseController;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'tests';
$cliTaskFlag = 'mautic-batch-send';
$cliTaskDescription = "Prueba de envío masivo de correos con Mautic.";
CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) use ($langGroup) {

    echoTerminal('[TEST:Mautic] Iniciando proceso de prueba de Mautic...');

    $response = [
        'success' => false,
        'message' => '',
        'extra_data' => [],
    ];

    //Buscar credeenciales
    $crendentials = explode('::', getKeyFromSecureKeys('mautic'));
    $baseURL = $crendentials[0] ?? null;
    $clientID = $crendentials[1] ?? null;
    $clientSecret = $crendentials[2] ?? null;
    $fromEmail = $crendentials[3] ?? null;

    if (!($baseURL !== null && $clientID !== null && $clientSecret !== null && $fromEmail !== null)) {
        $response['message'] = 'Credenciales de Mautic no encontradas.';
        return $response;
    }

    //Controlador
    $controller = new BaseController();
    $basePathView = realpath(__DIR__ . '/../../system-views');
    if ($basePathView !== false) {
        $controller->setViewDir($basePathView);
    }

    //Listado de personas
    $emails = include __DIR__ . '/../test-data/persons.php';

    //Configuración de Mautic
    $mauticAdapter = new MauticEmailAdapter($baseURL, $clientID, $clientSecret);
    $prefix = 'AutomaticTestingSend_';

    //El recorrido vive en `Mautic-BatchFlow.php` y lo comparte con la mitad que NO sale a
    //la red, `unit-tests:core/mautic-batch-logic`. Aquí solo se le da el transporte real.
    $response = pcsphp_mautic_batch_flow($mauticAdapter, $emails, $fromEmail, $controller, $prefix, $langGroup);

    echoTerminal('[TEST:Mautic] Proceso de prueba de Mautic finalizado.');

    return $response;
})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_EMAIL])->register();
