<?php

namespace App\Repositories;

use PDO;

class ChamadoRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function criar(array $dados): int
    {
        $sql = <<<SQL
            INSERT INTO chamados (solicitante, setor, titulo, descricao, categoria, prioridade, sugestao_ia, usuario_id)
            VALUES (:solicitante, :setor, :titulo, :descricao, :categoria, :prioridade, :sugestao_ia, :usuario_id)
            RETURNING id
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'solicitante' => $dados['solicitante'],
            'setor' => $dados['setor'],
            'titulo' => $dados['titulo'],
            'descricao' => $dados['descricao'],
            'categoria' => $dados['categoria'] ?? null,
            'prioridade' => $dados['prioridade'] ?? 'Media',
            'sugestao_ia' => $dados['sugestao_ia'] ?? null,
            'usuario_id' => $dados['usuario_id'] ?? null,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function salvarArtigos(int $chamadoId, array $artigos): void
    {
        if ($artigos === []) {
            return;
        }

        $sql = <<<SQL
            INSERT INTO artigos_sugeridos (chamado_id, titulo, url, resumo)
            VALUES (:chamado_id, :titulo, :url, :resumo)
            SQL;

        $stmt = $this->pdo->prepare($sql);
        foreach ($artigos as $artigo) {
            $stmt->execute([
                'chamado_id' => $chamadoId,
                'titulo' => $artigo['titulo'],
                'url' => $artigo['url'],
                'resumo' => $artigo['resumo'] ?? null,
            ]);
        }
    }

    public function listar(?string $status = null): array
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare('SELECT * FROM chamados WHERE status = :status ORDER BY criado_em DESC');
            $stmt->execute(['status' => $status]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM chamados ORDER BY criado_em DESC');
        }

        return $stmt->fetchAll();
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chamados WHERE usuario_id = :usuario_id ORDER BY criado_em DESC');
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM chamados WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $chamado = $stmt->fetch();

        if ($chamado === false) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM artigos_sugeridos WHERE chamado_id = :id');
        $stmt->execute(['id' => $id]);
        $chamado['artigos'] = $stmt->fetchAll();

        return $chamado;
    }

    public function atualizarStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE chamados SET status = :status, atualizado_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function contarPorStatus(): array
    {
        $contagem = ['Aberto' => 0, 'Em Andamento' => 0, 'Resolvido' => 0];

        $stmt = $this->pdo->query('SELECT status, COUNT(*) AS total FROM chamados GROUP BY status');
        foreach ($stmt->fetchAll() as $linha) {
            $contagem[$linha['status']] = (int) $linha['total'];
        }

        return $contagem;
    }
}
