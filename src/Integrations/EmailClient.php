<?php

namespace App\Integrations;

class EmailClient
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $fromEmail
    ) {
    }

    public function enviar(string $paraEmail, string $paraNome, string $assunto, string $corpoHtml): bool
    {
        $payload = json_encode([
            'from' => $this->fromEmail,
            'to' => [$paraEmail],
            'subject' => $assunto,
            'html' => $corpoHtml,
        ]);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $resposta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resposta === false || $erroCurl !== '') {
            return false;
        }

        return $statusCode === 200;
    }
}
