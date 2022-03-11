<?php

/**
 * MailjetHandler.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Http\HttpClient;

/**
 * MailjetHandler
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class MailjetHandler
{

    /**
     * @var HttpClient
     */
    protected $client;
    /**
     * @var string
     */
    protected $customID;
    /**
     * @var string
     */
    protected $from = 'webmaster@example.com';
    /**
     * @var string
     */
    protected $fromName = 'Mailer';
    /**
     * @var string
     */
    protected $replyTo = 'webmaster@example.com';
    /**
     * @var string
     */
    protected $replyToName = 'Mailer';
    /**
     * @var array<string,string>
     */
    protected $addresses = [];
    /**
     * @var array<string,string>
     */
    protected $cc = [];
    /**
     * @var array<string,string>
     */
    protected $bcc = [];
    /**
     * @var array
     */
    protected $attachments = [];
    /**
     * @var array
     */
    protected $inlineAttachments = [];
    /**
     * @var string
     */
    protected $subject = '';
    /**
     * @var string
     */
    protected $bodyPlain = '';
    /**
     * @var string
     */
    protected $bodyHTML = '';
    /**
     * @var array
     */
    protected $lastSended = [];

    /**
     * @param string $apiKey
     * @param string $secretKey
     * @param string $customID
     */
    public function __construct(string $apiKey, string $secretKey, string $customID = 'GenericMailjet')
    {
        $this->customID = $customID;
        $this->client = new HttpClient("https://{$apiKey}:{$secretKey}@api.mailjet.com/v3.1/");

    }

    /**
     * @param string $fromMail
     * @param string $fromName
     * @return static
     */
    public function from(string $fromMail, string $fromName = '')
    {
        $this->from = $fromMail;
        $this->fromName = $fromName;
        return $this;
    }

    /**
     * @param string $replyToEmail
     * @param string $replyToName
     * @return static
     */
    public function replyTo(string $replyToEmail, string $replyToName = '')
    {
        $this->replyTo = $replyToEmail;
        $this->replyToName = $replyToName;
        return $this;
    }

    /**
     * @param string $subject
     * @return static
     */
    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param string $plainContent
     * @return static
     */
    public function setBodyPlain(string $plainContent)
    {
        $this->bodyPlain = $plainContent;
        return $this;
    }

    /**
     * @param string $html
     * @return static
     */
    public function setBodyHTML(string $html)
    {
        $this->bodyHTML = $html;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return static
     */
    public function addAddress(string $email, string $name = '')
    {
        $this->addresses[$email] = $name;
        return $this;
    }

    /**
     * @param array<string,string> $addresses
     * @return static
     */
    public function setAddresses(array $addresses)
    {
        foreach ($addresses as $email => $name) {
            $this->addAddress($email, $name);
        }
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return static
     */
    public function addCC(string $email, string $name = '')
    {
        $this->cc[$email] = $name;
        return $this;
    }

    /**
     * @param array<string,string> $ccs
     * @return static
     */
    public function setCCs(array $ccs)
    {
        foreach ($ccs as $email => $name) {
            $this->addCC($email, $name);
        }
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return static
     */
    public function addBCC(string $email, string $name = '')
    {
        $this->bcc[$email] = $name;
        return $this;
    }

    /**
     * @param array<string,string> $bccs
     * @return static
     */
    public function setBCCs(array $bccs)
    {
        foreach ($bccs as $email => $name) {
            $this->addBCC($email, $name);
        }
        return $this;
    }

    /**
     * @param string $contentType
     * @param string $filename
     * @param string $base64Content
     * @param string|null $uniqueID
     * @return static
     */
    public function addAtachment(string $contentType, string $filename, string $base64Content, string $uniqueID = null)
    {

        $uniqueID = $uniqueID !== null ? $uniqueID : uniqid();

        $attachment = [
            'ContentType' => $contentType,
            'Filename' => $filename,
            'Base64Content' => $base64Content,
        ];

        $this->attachments[$uniqueID] = $attachment;
        return $this;
    }

    /**
     * @param string $uniqueID
     * @return static
     */
    public function removeAttachmentByUniqueID(string $uniqueID)
    {
        if (array_key_exists($uniqueID, $this->attachments)) {
            unset($this->attachments[$uniqueID]);
        }
        return $this;
    }

    /**
     * @param string $contentType
     * @param string $filename
     * @param string $base64Content
     * @param string $contentID
     * @param string|null $uniqueID
     * @return static
     */
    public function addInlineAtachment(string $contentType, string $filename, string $base64Content, string $contentID, string $uniqueID = null)
    {

        $uniqueID = $uniqueID !== null ? $uniqueID : uniqid();

        $attachment = [
            'ContentType' => $contentType,
            'Filename' => $filename,
            'Base64Content' => $base64Content,
            'ContentID' => $contentID,
        ];

        $this->inlineAttachments[$uniqueID] = $attachment;
        return $this;
    }

    /**
     * @param string $uniqueID
     * @return static
     */
    public function removeInlineAttachmentByUniqueID(string $uniqueID)
    {
        if (array_key_exists($uniqueID, $this->inlineAttachments)) {
            unset($this->inlineAttachments[$uniqueID]);
        }
        return $this;
    }

    /**
     * @return \stdClass
     */
    public function getResponse()
    {
        return $this->client->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
    }

    /**
     * @return array
     */
    public function getMessageStructure()
    {
        $to = [];

        foreach ($this->addresses as $email => $name) {
            $to[] = [
                'Email' => $email,
                'Name' => $name,
            ];
        }

        $cc = [];

        foreach ($this->cc as $email => $name) {
            $cc[] = [
                'Email' => $email,
                'Name' => $name,
            ];
        }

        $bcc = [];

        foreach ($this->bcc as $email => $name) {
            $bcc[] = [
                'Email' => $email,
                'Name' => $name,
            ];
        }

        $messageConfig = [
            'CustomID' => $this->customID,
            'From' => [
                'Email' => $this->from,
                'Name' => $this->fromName,
            ],
            'ReplyTo' => [
                'Email' => mb_strlen($this->replyTo) > 0 ? $this->replyTo : $this->from,
                'Name' => mb_strlen($this->replyTo) > 0 ? $this->replyToName : $this->fromName,
            ],
            'To' => $to,
            'Cc' => $cc,
            'Bcc' => $bcc,
            'Subject' => $this->subject,
            'TextPart' => mb_strlen($this->bodyPlain) > 0 ? $this->bodyPlain : strip_tags($this->bodyHTML),
            'HTMLPart' => $this->bodyHTML,
        ];

        if (!empty($this->attachments)) {
            $messageConfig['Attachments'] = [];
            foreach ($this->attachments as $attachment) {
                $messageConfig['Attachments'][] = $attachment;
            }
        }

        if (!empty($this->inlineAttachments)) {
            $messageConfig['InlinedAttachments'] = [];
            foreach ($this->inlineAttachments as $attachment) {
                $messageConfig['InlinedAttachments'][] = $attachment;
            }
        }

        $bodyRequest = [
            'Messages' => [
                $messageConfig,
            ],
        ];

        return $bodyRequest;
    }

    /**
     * @param bool $b64
     * @return array
     */
    public function getLastSendedData(bool $b64 = false)
    {
        return !$b64 ? $this->lastSended : base64_encode(gzcompress(json_encode($this->lastSended), 9));
    }

    /**
     * @param string $b64
     * @return array
     */
    public function decodeData(string $b64)
    {
        return json_decode(gzuncompress(base64_decode($b64)), true);
    }

    /**
     * @return bool
     */
    public function send()
    {

        $headersRequest = [
            'Content-Type' => 'application/json',
        ];
        $bodyRequest = $this->getMessageStructure();
        $this->lastSended = $bodyRequest;

        $this->client->request('send', 'POST', $bodyRequest, $headersRequest, false, true);

        $success = $this->client->getResponseStatus() == 200;

        if ($success) {

            $success = false;
            $response = $this->getResponse();
            $response = $response instanceof \stdClass ? $response : new \stdClass;

            $messagesPropertyName = 'Messages';
            $statusPropertyName = 'Status';

            if (property_exists($response, $messagesPropertyName)) {

                $Messages = $response->$messagesPropertyName;

                if (is_array($Messages) && !empty($Messages) && property_exists($Messages[0], $statusPropertyName)) {
                    $success = mb_strtolower($Messages[0]->$statusPropertyName) == 'success';
                }

            }

        }

        return $success;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function sendFromData(array $data)
    {

        $headersRequest = [
            'Content-Type' => 'application/json',
        ];

        $bodyRequest = $data;

        $this->lastSended = $bodyRequest;

        $this->client->request('send', 'POST', $bodyRequest, $headersRequest, false, true);

        $success = $this->client->getResponseStatus() == 200;

        if ($success) {

            $success = false;
            $response = $this->getResponse();
            $response = $response instanceof \stdClass ? $response : new \stdClass;

            $messagesPropertyName = 'Messages';
            $statusPropertyName = 'Status';

            if (property_exists($response, $messagesPropertyName)) {

                $Messages = $response->$messagesPropertyName;

                if (is_array($Messages) && !empty($Messages) && property_exists($Messages[0], $statusPropertyName)) {
                    $success = mb_strtolower($Messages[0]->$statusPropertyName) == 'success';
                }

            }

        }

        return $success;
    }

    /**
     * @return HttpClient
     */
    public function getClient()
    {
        return $this->client;
    }

}
