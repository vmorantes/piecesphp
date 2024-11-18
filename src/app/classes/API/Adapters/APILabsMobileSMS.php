<?php

/**
 * APILabsMobileSMS.php
 */

namespace API\Adapters;

use PiecesPHP\Core\Http\HttpClient;

/**
 * APILabsMobileSMS.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APILabsMobileSMS
{
    /**
     * @var string
     */
    protected $apiKey = '';
    /**
     * @var string
     */

    protected $baseURL = 'https://api.labsmobile.com/json/send';

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $phoneNumber
     * @param string $lapse
     * @return array
     */
    public function sendSMS(string $phoneNumber, string $message)
    {

        $httpClient = $this->httpClientWithApiKeyHeader();

        $httpClient->request('', 'POST', [
            'recipient' => [
                "msisdn" => $phoneNumber,
            ],
            'message' => $message,
        ]);

        $codesMessages = [
            "0" => "El mensaje ha sido enviado exitosamente",
            "21" => "El elemento del mensaje no puede estar vacío.",
            "22" => "Mensaje demasiado largo. Hay un límite de 160 caracteres de 7 bits.",
            "23" => "No hay destinatarios",
            "24" => "Demasiados recipientes",
            "25" => "TPOA está excediendo la longitud máxima",
            "26" => "No se permite el cambio de TPOA para esta cuenta",
            "27" => "Este mensaje contenía uno o más caracteres no válidos.",
            "28" => "Subid está excediendo la longitud máxima",
            "30" => "Hubo un error al enviar el mensaje.",
            "31" => "Se ha proporcionado AckLevel pero falta AckUrl",
            "32" => "Se ha proporcionado AckUrl pero falta AckLevel",
            "33" => "Se ha proporcionado un valor desconocido para AckLevel. Los valores permitidos son puerta de enlace, operador o teléfono.",
            "34" => "Campo de etiqueta demasiado largo",
            "35" => "La cuenta no tiene crédito suficiente para este envío.",
            "36" => "El formato msisdn [número] no está permitido",
            "37" => "La cuenta ha alcanzado el máximo de mensajes por día.",
            "38" => "Se produjo un error al enviar el mensaje a este MSISDN ('')",
            "39" => "El valor del campo Programado no tiene un formato de fecha y hora válido",
            "40" => "El nombre de usuario no puede enviar mensajes programados.",
            "41" => "Los mensajes programados no se pueden enviar en modo de prueba",
            "52" => "Mensajes programados no encontrados.",
            "400" => "Solicitud incorrecta",
            "401" => "No autorizado",
            "403" => "Prohibido",
            "500" => "Error interno",
        ];
        $response = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $response['code'] = (int) $response['code'];
        $response['codeMessage'] = $codesMessages[$response['code']];

        return $response;

    }

    /**
     * @return HttpClient
     */
    public function httpClientWithApiKeyHeader()
    {
        $httpClient = new HttpClient($this->baseURL);
        if ($this->apiKey != '') {
            $b64ApiKey = base64_encode($this->apiKey);
            $httpClient->setDefaultRequestHeaders([
                'Authorization' => "Basic {$b64ApiKey}",
                'Cache-Control' => "no-cache",
                'Content-Type' => "application/json",
            ]);
        }
        return $httpClient;
    }

}
