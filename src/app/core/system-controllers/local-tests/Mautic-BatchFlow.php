<?php

//El adaptador NO se construye aquí: se recibe, para poder darle uno falso. Ver T125.

use API\Adapters\MauticEmailAdapter;
use PiecesPHP\Core\BaseController;

if (!function_exists('pcsphp_mautic_batch_flow')) {

    /**
     * @param MauticEmailAdapter $mauticAdapter Transporte. Real en `tests:`, falso en `unit-tests:`.
     * @param array<int,array{email:string,names:string,lastNames:string}> $emails
     * @param string $fromEmail
     * @param BaseController $controller Para renderizar la plantilla.
     * @param string $prefix
     * @param string $langGroup
     * @return array{success:bool,message:string,extra_data:array}
     */
    function pcsphp_mautic_batch_flow(
        MauticEmailAdapter $mauticAdapter,
        array $emails,
        string $fromEmail,
        BaseController $controller,
        string $prefix,
        string $langGroup
    ): array {

        $response = [
            'success' => false,
            'message' => '',
            'extra_data' => [],
        ];

        //Crear contactos en Mautic
        echoTerminal('[1/4] Creando contactos en Mautic...');
        $contactsToCreate = [];
        foreach ($emails as $email) {
            $contactsToCreate[] = [
                'email' => $email['email'],
                'names' => $email['names'],
                'lastNames' => $email['lastNames'],
            ];
        }
        $contactIDs = $mauticAdapter->createBatchContacts($contactsToCreate);
        if (empty($contactIDs)) {
            $response['message'] = 'No se pudo crear el contacto.';
            return $response;
        }

        //Crear segmento en Mautic
        echoTerminal('[2/4] Creando segmento en Mautic...');
        $segmentID = $mauticAdapter->createSegment([
            'name' => uniqid($prefix),
        ], $contactIDs);
        if ($segmentID === null) {
            $response['message'] = 'No se pudo crear el segmento.';
            return $response;
        }

        //Crear plantilla de mensaje asociada al segmento
        echoTerminal('[3/4] Creando plantilla de mensaje asociada al segmento...');
        $templateForMautic = $controller->render(
            'mailing/template_mautic',
            [],
            false,
            false
        );
        //`render()` declara `string|null` y `strReplaceTemplate()` exige `string`.
        if (!is_string($templateForMautic)) {
            $response['message'] = 'No se pudo crear la plantilla.';
            return $response;
        }
        $templateForMautic = mb_convert_encoding(strReplaceTemplate($templateForMautic, [
            '{name}' => '{contactfield=firstname} {contactfield=lastname}',
        ]), 'UTF-8');

        $templateEmailID = $mauticAdapter->createEmailTemplate(
            $fromEmail, //Correo del remitente
            __($langGroup, 'PicesPHP - Testing'), //Nombre del remitente
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

        //`createEmailTemplate()` declara `?int`, y `sendEmail()` exige `int`: sin esta guarda
        //un fallo al crear la plantilla revienta con un TypeError en vez de devolver su motivo.
        if ($templateEmailID === null) {
            $response['message'] = 'No se pudo crear la plantilla de correo.';
            return $response;
        }

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

        return $response;

    }

}
