<?php

namespace App\Validation;

class ChamadoValidator
{
    public static function validar(array $dados): array
    {
        $erros = [];

        foreach (['solicitante', 'setor', 'titulo', 'descricao'] as $campo) {
            if (trim((string)($dados[$campo] ?? '')) === '') {
                $erros[] = "O campo \"{$campo}\" é obrigatório.";
            }
        }

        if (strlen((string)($dados['titulo'] ?? '')) > 150) {
            $erros[] = 'O título deve ter no máximo 150 caracteres.';
        }

        if (strlen(trim((string)($dados['descricao'] ?? ''))) > 0 && strlen(trim((string)($dados['descricao'] ?? ''))) < 10) {
            $erros[] = 'A descrição deve ter no mínimo 10 caracteres.';
        }

        return $erros;
    }
}
