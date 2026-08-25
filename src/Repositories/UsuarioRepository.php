<?php

namespace App\Repositories;

use PDO;

class UsuarioRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function criar(array $dados): int
    {
        $sql = <<<SQL
            INSERT INTO usuarios (nome, email, senha_hash, papel, setor)
            VALUES (:nome, :email, :senha_hash, :papel, :setor)
            RETURNING id
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha_hash' => password_hash($dados['senha'], PASSWORD_BCRYPT),
            'papel' => $dados['papel'],
            'setor' => $dados['setor'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function listar(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, nome, email, papel, setor, criado_em FROM usuarios ORDER BY criado_em DESC'
        );

        return $stmt->fetchAll();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nome, email, papel, setor, criado_em FROM usuarios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarHashSenhaPorId(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $hash = $stmt->fetchColumn();

        return $hash === false ? null : $hash;
    }

    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $stmt->execute([
            'senha_hash' => password_hash($novaSenha, PASSWORD_BCRYPT),
            'id' => $id,
        ]);
    }

    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->buscarPorEmail($email);

        if ($usuario === null || !password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        unset($usuario['senha_hash']);

        return $usuario;
    }
}
