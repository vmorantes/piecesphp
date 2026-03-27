<?php

/**
 * MauticEmailAdapter.php
 */

namespace API\Adapters;

use API\APILang;
use Exception;
use PiecesPHP\Core\Http\HttpClient;

/**
 * MauticEmailAdapter.
 *
 * @package     API\Adapters
 */
class MauticEmailAdapter
{
    protected string $baseURL;
    protected string $clientID;
    protected string $clientSecret;
    protected array $errorsHistory = [];
    protected string $errorsFile = '';
    protected string $authToken = '';
    protected string $tokenFile = '';

    /**
     * @param string $baseURL URL base de la instancia de Mautic
     * @param string $clientID Client ID de OAuth2
     * @param string $clientSecret Client Secret de OAuth2
     */
    public function __construct(string $baseURL, string $clientID, string $clientSecret)
    {
        $this->baseURL = rtrim($baseURL, '/');
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;

        //Archivo de errores
        $mauticLogErrorsDir = basepath('app/logs/mautic_errors');
        $this->errorsFile = append_to_path_system($mauticLogErrorsDir, 'log.json');
        $this->tokenFile = append_to_path_system($mauticLogErrorsDir, 'token.json');
        if (!is_dir($mauticLogErrorsDir)) {
            mkdir($mauticLogErrorsDir, 0755, true);
        }
        if (file_exists($this->errorsFile)) {
            $errorFileData = file_get_contents($this->errorsFile);
            if ($errorFileData !== false) {
                $decoded = json_decode($errorFileData, true);
                if (json_last_error() === \JSON_ERROR_NONE  && is_array($decoded)) {
                    $this->errorsHistory = $decoded;
                } else {
                    $this->errorsHistory = [];
                }
            }
        }
    }

    /**
     * Envía un email a través de Mautic. Se envía a sus segmentos asociados.
     *
     * @param int $emailID ID del email a enviar
     * @return int Cantidad de envíos exitosos
     * @see https://developer.mautic.org/?php#send-email-to-segment
     */
    public function sendEmail(int $emailID): int
    {
        $res = $this->makeRequest("/api/emails/{$emailID}/send", 'POST');
        $success = $res['success'] && is_array($res['data']) && array_key_exists('sentCount', $res['data']);
        $this->reponseResultToLog($res, 'sendEmailResponse', !$success);

        return (int) ($success ? $res['data']['sentCount'] : 0);
    }

    /**
     * Crea contactos en Mautic
     *
     * @param array<array<string,mixed>> $contacts Cada entrada debe contener 'email', 'names', 'lastNames' y puede contener 'otherFields' que es un array clave=>valor para más campos.
     * @return int[] IDs de los contactos creados
     */
    public function createBatchContacts(array $contacts): array
    {
        $contactsData = [];

        foreach ($contacts as $contact) {
            $contactName = $contact['names'] ?? '';
            $contactLastName = $contact['lastNames'] ?? '';
            $contactEmail = $contact['email'];
            $contactOtherFields = $contact['otherFields'] ?? [];
            $contactData = [
                'email' => $contactEmail,
                'firstname' => $contactName,
                'lastname' => $contactLastName,
            ];
            foreach ($contactOtherFields as $otherFieldName => $otherFieldValue) {
                if (is_string($otherFieldName)) {
                    $contactData[$otherFieldName] = $otherFieldValue;
                }
            }
            $contactsData[] = $contactData;
        }

        $res = $this->makeRequest('/api/contacts/batch/new', 'POST', $contactsData);
        $createdContacts = (is_array($res['data']) && array_key_exists('contacts', $res['data'])) ? $res['data']['contacts'] : [];

        $this->reponseResultToLog($res, 'createBatchContactResponse', empty($createdContacts));

        return is_array($createdContacts) ? array_map(function ($contact) {
            return $contact['id'] ?? null;
        }, $createdContacts) : [];
    }

    /**
     * Crea un contacto en Mautic
     *
     * @param string $email Correo electrónico del contacto
     * @param string $names Nombres del contacto
     * @param string $lastNames Apellidos del contacto
     * @param array $otherFields Otros campos del contacto. Array clave=>valor.
     *                          Por ejemplo: ['phone' => '123456789', 'company' => 'Example Corp']
     * @return int|null ID del contacto creado o null si falla
     */
    public function createContact(string $email, string $names, string $lastNames, array $otherFields = []): ?int
    {
        $contactData = [
            'email' => $email,
            'firstname' => $names,
            'lastname' => $lastNames,
        ];
        foreach ($otherFields as $otherFieldName => $otherFieldValue) {
            if (is_string($otherFieldName)) {
                $contactData[$otherFieldName] = $otherFieldValue;
            }
        }

        $res = $this->makeRequest('/api/contacts/new', 'POST', $contactData);
        $contactID = (is_array($res['data']) && isset($res['data']['contact']['id'])) ? $res['data']['contact']['id'] : null;
        $this->reponseResultToLog($res, 'createContactResponse', $contactID === null);

        return $contactID;
    }

    /**
     * Crea una plantilla de email en Mautic
     *
     * @param string $fromAddress Dirección de correo del remitente
     * @param string $fromName Nombre del remitente
     * @param string $subject Asunto del email
     * @param string $bodyHTML Cuerpo del mensaje HTML
     * @param string $nameMailID Nombre único para la plantilla
     * @return int|null ID de la plantilla creada o null si falla
     * @see https://developer.mautic.org/?php#create-email
     */
    public function createEmailTemplate(string $fromAddress, string $fromName, string $subject, string $bodyHTML, array $otherConfigurations = [], ?string $nameMailID = null): ?int
    {
        $configurations = [
            'name' => $nameMailID ?? uniqid('email_template_'),
            'subject' => $subject,
            'customHtml' => $bodyHTML,
            'fromAddress' => $fromAddress,
            'fromName' => $fromName,
            'isPublished' => true,
        ];

        $payload = array_merge($otherConfigurations, $configurations);
        $res = $this->makeRequest('/api/emails/new', 'POST', $payload);
        $emailID = (is_array($res['data']) && isset($res['data']['email']['id'])) ? $res['data']['email']['id'] : null;
        $this->reponseResultToLog($res, 'createEmailTemplateResponse', $emailID === null);

        return $emailID;
    }

    /**
     * Crea un segmento en Mautic
     *
     * @param array $configurations Configuraciones del segmento. Debe contener 'name' y puede contener 'isGlobal', 'isPublished', etc.
     * @param int[] $contactIDs IDs de los contactos a agregar al segmento. Si se proporciona, se agregan al crear el segmento.
     * @return int|null ID del segmento creado o null si falla
     * @see https://developer.mautic.org/?php#create-segment
     */
    public function createSegment(array $configurations, array $contactIDs = []): ?int
    {
        $segmentName = $configurations['name'] ?? uniqid('segment_');
        $defaultConfigurations = [
            'name' => $segmentName,
            'isGlobal' => true,
            'isPublished' => true,
        ];

        $payload = array_merge($defaultConfigurations, $configurations);
        $res = $this->makeRequest('/api/segments/new', 'POST', $payload);
        $segmentID = (is_array($res['data']) && isset($res['data']['list']['id'])) ? $res['data']['list']['id'] : null;
        $this->reponseResultToLog($res, 'createSegmentResponse', $segmentID === null);

        if ($segmentID !== null && !empty($contactIDs)) {
            $this->addContactsToSegment($segmentID, $contactIDs);
        }
        return $segmentID;
    }

    /**
     * Agrega contactos a un segmento en Mautic
     *
     * @param int $segmentID ID del segmento
     * @param int[] $contactIDs IDs de los contactos a agregar
     * @return bool true si la operación fue exitosa, false en caso contrario
     * @see https://developer.mautic.org/?php#add-contact-to-a-segment
     */
    public function addContactsToSegment(int $segmentID, array $contactIDs): bool
    {
        if (empty($contactIDs)) {
            return false;
        }

        $res = $this->makeRequest("/api/segments/{$segmentID}/contacts/add", 'POST', [
            'ids' => $contactIDs,
        ]);
        $this->reponseResultToLog($res, 'addContactsToSegmentResponse', !$res['success']);

        return $res['success'];
    }

    /**
     * Obtiene token de acceso OAuth2
     *
     * @return string
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        //1. Verificar memoria de la instancia
        if (!empty($this->authToken)) {
            return $this->authToken;
        }

        //2. Verificar persistencia en archivo y expiración
        if (file_exists($this->tokenFile)) {
            $tokenFileData = file_get_contents($this->tokenFile);
            if ($tokenFileData !== false) {
                $tokenData = json_decode($tokenFileData, true);
                if (json_last_error() === \JSON_ERROR_NONE  && is_array($tokenData) && isset($tokenData['access_token']) && isset($tokenData['expires_at'])) {
                    if ($tokenData['expires_at'] > time() + 60) {
                        //Margen de 1 minuto
                        $this->authToken = $tokenData['access_token'];
                        return $this->authToken;
                    }
                }
            }
        }

        //3. Solicitar nuevo token
        $httpClient = new HttpClient($this->baseURL);
        $httpClient->request('/oauth/v2/token', 'POST', [
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $data = $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);

        if (empty($data['access_token'])) {
            throw new Exception(__(APILang::LANG_GROUP, "No se pudo obtener el token de Mautic:") . " " . $httpClient->getResponseBody());
        }

        $this->authToken = $data['access_token'];
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        // 4. Persistir token y tiempo de expiración
        $saveData = [
            'access_token' => $this->authToken,
            'expires_at' => time() + $expiresIn,
        ];
        file_put_contents($this->tokenFile, json_encode($saveData, \JSON_PRETTY_PRINT), \LOCK_EX);

        return $this->authToken;
    }

    /**
     * @return HttpClient
     */
    public function httpClientWithApiKeyHeader()
    {
        $httpClient = new HttpClient($this->baseURL);
        $token = $this->getAccessToken();
        $httpClient->setDefaultRequestHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
        return $httpClient;
    }

    /**
     * Realiza una petición a la API de Mautic centralizando la lógica de tokens, tipos de contenido y reintentos.
     *
     * @param string $path Ruta de la API
     * @param string $method Método HTTP
     * @param array $body Cuerpo de la petición
     * @param bool $retryOn401 Si se debe reintentar en caso de error 401
     * @return array{success: bool, status: int, data: array|null, response: string, httpClient: HttpClient}
     */
    protected function makeRequest(string $path, string $method = 'GET', array $body = [], bool $retryOn401 = true): array
    {
        $httpClient = $this->httpClientWithApiKeyHeader();

        $headers = [];
        $method = strtoupper($method);

        // Mautic prefiere JSON para POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $headers['Content-Type'] = 'application/json';
        }

        $httpClient->request($path, $method, $body, $headers, true, false);

        $status = (int) $httpClient->getResponseStatus();
        $response = $httpClient->getResponseBody();
        /** @var array $data */
        $data = $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);

        // Si es 401 y tenemos reintentos habilitados, invalidamos token y repetimos
        if ($status === 401 && $retryOn401) {
            $this->invalidateToken();
            return $this->makeRequest($path, $method, $body, false);
        }

        $success = $status >= 200 && $status < 300;

        return [
            'httpClient' => $httpClient,
            'success' => $success,
            'status' => $status,
            'data' => $data,
            'response' => $response,
        ];
    }

    /**
     * Invalida el token actual en memoria y en disco.
     * @return void
     */
    protected function invalidateToken(): void
    {
        $this->authToken = '';
        if (file_exists($this->tokenFile)) {
            @unlink($this->tokenFile);
        }
    }

    /**
     * Limpia la respuesta si contiene HTML para no saturar los logs.
     * @param array{success: bool, status: int, data: array|null, response: string, httpClient: HttpClient} $data
     * @param string $nameRequest Nombre de la petición
     * @param bool $log Si se debe guardar en el log
     * @return array
     */
    protected function reponseResultToLog(array $data, string $nameRequest, bool $log = true): array
    {
        $response = $data['response'];
        if (strpos($response, '<!DOCTYPE html>') !== false || strpos($response, '<html>') !== false) {
            $stripped = strip_tags($response);
            $response = mb_substr($stripped, 0, 500) . '... [HTML content stripped]';
        }
        $result = [];
        $result[$nameRequest] = [
            'success' => $data['success'],
            'status' => $data['status'],
            'data' => $data['data'],
            'response' => $response,
            'requestBody' => $data['httpClient']->getRequestBody(true),
            'requestHeaders' => $data['httpClient']->getRequestHeaders(),
        ];
        if ($log) {
            $this->saveErrors($result);
        }
        return $result;
    }

    /**
     * Guarda los errores en el historial y en un archivo
     * @param array $errors Errores a guardar
     * @return array Historial actualizado
     */
    protected function saveErrors(array $errors)
    {
        if (empty($errors)) {
            return $this->errorsHistory;
        }
        $errorsRecords = [
            'timestamp' => date('Y-m-d H:i:s'),
            'errors' => $errors,
        ];
        $this->errorsHistory[] = $errorsRecords;
        //Limitar el tamaño del historial de errores
        if (count($this->errorsHistory) > 100) {
            $this->errorsHistory = array_slice($this->errorsHistory, -100);
        }
        //Guardar en el archivo
        if (!is_dir(dirname($this->errorsFile))) {
            mkdir(dirname($this->errorsFile), 0755, true);
        }
        file_put_contents($this->errorsFile, json_encode($this->errorsHistory, JSON_PRETTY_PRINT), \LOCK_EX);
        return $this->errorsHistory;
    }
}
