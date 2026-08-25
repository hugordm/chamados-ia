<?php

namespace App\Repositories;

use PDO;

class TokenRedefinicaoSenhaRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function criar(int $usuarioId, string $tokenHash): void
    {
        $sql = <<<SQL
            INSERT INTO tokens_redefinicao_senha (usuario_id, token_hash, expira_em)
            VALUES (:usuario_id, :token_hash, NOW() + INTERVAL '1 hour')
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'token_hash' => $tokenHash,
        ]);
    }

    public function buscarValidoPorHash(string $tokenHash): ?array
    {
        $sql = <<<SQL
            SELECT * FROM tokens_redefinicao_senha
            WHERE token_hash = :token_hash
              AND usado_em IS NULL
              AND expira_em > NOW()
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token_hash' => $tokenHash]);
        $token = $stmt->fetch();

        return $token === false ? null : $token;
    }

    public function marcarComoUsado(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE tokens_redefinicao_senha SET usado_em = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
