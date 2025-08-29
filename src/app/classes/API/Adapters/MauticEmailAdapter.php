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
        if (!is_dir($mauticLogErrorsDir)) {
            mkdir($mauticLogErrorsDir, 0755, true);
        }
        if (file_exists($this->errorsFile)) {
            $errorFileData = file_get_contents($this->errorsFile);
            if ($errorFileData !== false) {
                $this->errorsHistory = @json_decode($errorFileData, true);
                $this->errorsHistory = is_array($this->errorsHistory) ? $this->errorsHistory : [];
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
    public function sendEmail(int $emailID): bool
    {
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request("/api/emails/{$emailID}/send", 'POST');
        $response = $httpClient->getResponseBody();
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $success = $httpClient->getResponseStatus() === 200 && array_key_exists('sentCount', $data);
        $this->saveErrors(!$success ? [] : [
            'sendEmailResponse' => $response,
        ]);
        return (int) ($success ? $data['sentCount'] : 0);
    }

    /**
     * Crea contactos en Mautic
     *
     * @param array $contacts Cada entrada debe contener 'email', 'names', 'lastNames' y puede contener 'otherFields' que es un array clave=>valor para más campos.
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
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request('/api/contacts/batch/new', 'POST', $contactsData);
        $response = $httpClient->getResponseBody();
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $createdContacts = array_key_exists('contacts', $data) ? $data['contacts'] : [];
        $this->saveErrors(!empty($createdContacts) ? [] : [
            'createBatchContactResponse' => $response,
        ]);
        return is_array($createdContacts) ? array_map(function ($contact) {
            return $contact->id;
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
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request('/api/contacts/new', 'POST', $contactData);
        $response = $httpClient->getResponseBody();
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $contactID = $data['contact']['id'] ?? null;
        $this->saveErrors($contactID !== null ? [] : [
            'createContactResponse' => $response,
        ]);
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
        $configurations = array_merge($otherConfigurations, $configurations);
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request('/api/emails/new', 'POST', array_merge($otherConfigurations, $configurations));
        $response = $httpClient->getResponseBody();
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $emailID = $data['email']->id ?? null;
        $this->saveErrors($emailID !== null ? [] : [
            'createEmailTemplateResponse' => $response,
        ]);
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
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request('/api/segments/new', 'POST', array_merge($defaultConfigurations, $configurations));
        $response = $httpClient->getResponseBody();
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        $segmentID = $data['list']->id ?? null;
        $this->saveErrors($segmentID !== null ? [] : [
            'createSegmentResponse' => $response,
        ]);
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
        $httpClient = $this->httpClientWithApiKeyHeader();
        $httpClient->request("/api/segments/{$segmentID}/contacts/add", 'POST', [
            'ids' => $contactIDs,
        ]);
        $response = $httpClient->getResponseBody();
        $success = $httpClient->getResponseStatus() === 200;
        $this->saveErrors($success ? [] : [
            'addContactsToSegmentResponse' => $response,
        ]);
        return $success;
    }

    /**
     * Obtiene token de acceso OAuth2
     *
     * @return string
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        $httpClient = new HttpClient($this->baseURL);
        $httpClient->request('/oauth/v2/token', 'POST', [
            'client_id' => $this->clientID,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);
        $data = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        if (empty($data['access_token'])) {
            throw new Exception(__(APILang::LANG_GROUP, "No se pudo obtener el token de Mautic:") . " " . $httpClient->getResponseBody());
        }
        return $data['access_token'];
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
        $this->errorsHistory = array_merge($this->errorsHistory, $errorsRecords);
        //Limitar el tamaño del historial de errores
        if (count($this->errorsHistory) > 100) {
            $this->errorsHistory = array_slice($this->errorsHistory, -100);
        }
        //Guardar en el archivo
        if (!is_dir(dirname($this->errorsFile))) {
            mkdir(dirname($this->errorsFile), 0755, true);
        }
        file_put_contents($this->errorsFile, json_encode($this->errorsHistory, JSON_PRETTY_PRINT));
        return $this->errorsHistory;
    }
}
