<?php

namespace App\Integrations;

class AnthropicClient
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const CATEGORIA_PADRAO = 'Outro';
    private const PRIORIDADE_PADRAO = 'Media';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'claude-sonnet-4-6'
    ) {
    }

    public function analisar(string $titulo, string $descricao): array
    {
        $padrao = [
            'categoria' => self::CATEGORIA_PADRAO,
            'prioridade' => self::PRIORIDADE_PADRAO,
            'sugestao' => '',
        ];

        $prompt = <<<PROMPT
            Você é um analista de suporte técnico de TI. Analise o chamado abaixo e
            responda APENAS com um JSON puro, sem markdown e sem texto adicional, no
            formato: {"categoria": "...", "prioridade": "...", "sugestao": "..."}

            - categoria: uma de "Hardware", "Rede", "Software", "Acesso" ou "Outro".
            - prioridade: uma de "Baixa", "Media", "Alta" ou "Urgente".
            - sugestao: uma primeira orientação prática e curta para resolver o problema.

            Título: {$titulo}
            Descrição: {$descricao}
            PROMPT;

        $payload = json_encode([
            'model' => $this->model,
            'max_tokens' => 512,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);

        $resposta = curl_exec($ch);
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($resposta === false || $erroCurl !== '') {
            return $padrao;
        }

        $corpo = json_decode($resposta, true);
        $texto = $corpo['content'][0]['text'] ?? null;

        if (!is_string($texto)) {
            return $padrao;
        }

        $analise = json_decode($texto, true);

        if (!is_array($analise)) {
            return $padrao;
        }

        return [
            'categoria' => $analise['categoria'] ?? self::CATEGORIA_PADRAO,
            'prioridade' => $analise['prioridade'] ?? self::PRIORIDADE_PADRAO,
            'sugestao' => $analise['sugestao'] ?? '',
        ];
    }
}
