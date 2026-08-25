<?php

namespace App\Integrations;

class FirecrawlClient
{
    private const ENDPOINT = 'https://api.firecrawl.dev/v1/search';
    private const LIMITE_RESULTADOS = 3;

    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function buscarArtigos(string $titulo): array
    {
        $payload = json_encode([
            'query' => "como resolver: {$titulo}",
            'limit' => self::LIMITE_RESULTADOS,
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
        curl_close($ch);

        if ($resposta === false || $erroCurl !== '') {
            return [];
        }

        $corpo = json_decode($resposta, true);
        $resultados = $corpo['data'] ?? null;

        if (!is_array($resultados)) {
            return [];
        }

        $artigos = [];
        foreach (array_slice($resultados, 0, self::LIMITE_RESULTADOS) as $item) {
            $artigos[] = [
                'titulo' => $item['title'] ?? '',
                'url' => $item['url'] ?? '',
                'resumo' => $item['description'] ?? '',
            ];
        }

        return $artigos;
    }
}
