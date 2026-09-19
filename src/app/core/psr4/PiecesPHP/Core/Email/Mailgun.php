<?php

/**
 * Mailgun.php
 */
namespace PiecesPHP\Core\Email;

/**
 * Mailgun - Adaptador de envíos con API Mailgun
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class Mailgun
{
    private $apiKey;
    private $domain;
    private $from;
    private $statusCode = 400;
    private $response;
    private $statusOperation = false;

    public function __construct(string $apiKey, string $domain, string $fromMailUser = 'notifications', string $fromName = 'Notifications')
    {
        $this->apiKey = $apiKey;
        $this->domain = $domain;
        $this->from = "{$fromName} <$fromMailUser@{$domain}>";
    }

    public function setFrom(string $from)
    {
        $this->from = $from;
        return $this;
    }

    public function send(string $to, string $subject, string $html, array $attachments = [], array $inlineAttachments = [])
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/{$this->domain}/messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "api:{$this->apiKey}");
            curl_setopt($ch, CURLOPT_POST, true);

            $postData = [
                'from' => $this->from,
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
            ];

            // Adjuntos normales
            if (!empty($attachments)) {
                $i = 0;
                foreach ($attachments as $filename => $path) {
                    if (file_exists($path)) {
                        $postData["attachment[$i]"] = curl_file_create($path, null, is_string($filename) ? $filename : basename($path));
                        $i++;
                    }
                }
            }

            // Adjuntos inline (referenciados por cid: en el HTML)
            if (!empty($inlineAttachments)) {
                $i = 0;
                foreach ($inlineAttachments as $cid => $path) {
                    if (file_exists($path)) {
                        $postData["inline[$i]"] = curl_file_create($path, null, is_string($cid) ? $cid : basename($path));
                        $i++;
                    }
                }
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $this->statusCode = $httpCode;

            if ($response === false) {
                $this->response = [
                    'curl_error' => curl_error($ch),
                    'http_code' => $httpCode,
                ];
            } else {
                $this->response = [
                    'http_code' => $httpCode,
                    'response_raw' => $response,
                    'response_json' => json_decode($response, true),
                ];
            }

            $this->statusOperation = ($httpCode >= 200 && $httpCode < 300);
            curl_close($ch);
            return $this->response;

        } catch (\Throwable $th) {
            return ['exception' => $th->getMessage()];
        }
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function isSuccess()
    {
        return $this->statusOperation;
    }
}
