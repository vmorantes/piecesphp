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

    //Proceso de envío de correos
    $do = function (&$response, $mauticAdapter, $emails, $fromEmail, $controller, $prefix, $langGroup) {
        //Crear contactos en Mautic
        echoTerminal('[1/4] Creando contactos en Mautic...');
        $contactsToCreate = [];
        $counter = 0;
        foreach ($emails as $email) {
            $contactsToCreate[] = [
                'email' => $email['email'],
                'names' => $email['names'],
                'lastNames' => $email['lastNames'],
            ];
            $counter++;
        }
        $contactIDs = $mauticAdapter->createBatchContacts($contactsToCreate);
        if (empty($contactIDs)) {
            $response['message'] = 'No se pudo crear el contacto.';
            return;
        }

        //Crear segmento en Mautic
        echoTerminal('[2/4] Creando segmento en Mautic...');
        $segmentID = $mauticAdapter->createSegment([
            'name' => uniqid($prefix),
        ], $contactIDs);
        if ($segmentID === null) {
            $response['message'] = 'No se pudo crear el segmento.';
            return;
        }

        //Crear plantilla de mensaje asociada al segmento
        echoTerminal('[3/4] Creando plantilla de mensaje asociada al segmento...');
        $templateForMautic = $controller->render(
            'mailing/template_mautic',
            [],
            false,
            false
        );
        $templateForMautic = mb_convert_encoding(strReplaceTemplate($templateForMautic, [
            '{name}' => '{contactfield=firstname} {contactfield=lastname}',
        ]), 'UTF-8');
        if ($templateForMautic === null) {
            $response['message'] = 'No se pudo crear la plantilla.';
            return;
        }

        $fromAddress = $fromEmail;
        $fromName = __($langGroup, 'PicesPHP - Testing');

        $templateEmailID = $mauticAdapter->createEmailTemplate(
            $fromAddress, //Correo del remitente
            $fromName, //Nombre del remitente
            __($langGroup, 'Prueba de uso de mautic'), //Asunto del correo
            $templateForMautic, //Cuerpo del mensaje
            [
                'emailType' => 'list', //Tipo segmento
                'lists' => [ //Segmentos a los que se enviará el mensaje
                    $segmentID,
                ],
            ],
            uniqid($prefix) //ID (nombre) único para la plantilla
        );

        $sentCount = 0;
        echoTerminal('[4/4] Enviando correo...');
        $sentCount = $mauticAdapter->sendEmail($templateEmailID);
        if ($sentCount > 0) {
            $response['success'] = true;
            $response['message'] = 'Proceso completado correctamente. Se enviaron ' . $sentCount . ' correos.';
            $response['extra_data'] = [
                'contactIDs' => $contactIDs,
                'segmentID' => $segmentID,
                'templateEmailID' => $templateEmailID,
                'sentCount' => $sentCount,
            ];
        } else {
            $response['message'] = 'No se pudo enviar el correo.';
        }
    };
    $do($response, $mauticAdapter, $emails, $fromEmail, $controller, $prefix, $langGroup);

    echoTerminal('[TEST:Mautic] Proceso de prueba de Mautic finalizado.');

    return $response;
})->setDescription($cliTaskDescription)->register();
