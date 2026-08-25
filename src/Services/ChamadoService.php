<?php

namespace App\Services;

use App\Integrations\AnthropicClient;
use App\Integrations\EmailClient;
use App\Integrations\FirecrawlClient;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Validation\ChamadoValidator;
use App\Validation\ValidacaoException;

class ChamadoService
{
    private const STATUS_VALIDOS = ['Aberto', 'Em Andamento', 'Resolvido'];

    private const MENSAGENS_MUDANCA_STATUS = [
        'Em Andamento' => [
            'assunto' => 'Chamado #%d está em andamento',
            'corpo' => '<h2>Chamado em andamento</h2><p>O time de TI já está atuando no chamado <strong>%s</strong>.</p>',
        ],
        'Resolvido' => [
            'assunto' => 'Chamado #%d foi resolvido',
            'corpo' => '<h2>Chamado resolvido</h2><p>O chamado <strong>%s</strong> foi marcado como resolvido.</p>'
                . '<p>Se o problema voltar a acontecer, abra um novo chamado.</p>',
        ],
    ];

    public function __construct(
        private readonly ChamadoRepository $repository,
        private readonly AnthropicClient $anthropicClient,
        private readonly FirecrawlClient $firecrawlClient,
        private readonly UsuarioRepository $usuarioRepository,
        private readonly EmailClient $emailClient
    ) {
    }

    public function abrirChamado(array $dadosFormulario): array
    {
        $erros = ChamadoValidator::validar($dadosFormulario);
        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }

        $analise = $this->anthropicClient->analisar(
            $dadosFormulario['titulo'],
            $dadosFormulario['descricao']
        );

        $id = $this->repository->criar([
            'solicitante' => $dadosFormulario['solicitante'],
            'setor' => $dadosFormulario['setor'],
            'titulo' => $dadosFormulario['titulo'],
            'descricao' => $dadosFormulario['descricao'],
            'categoria' => $analise['categoria'],
            'prioridade' => $analise['prioridade'],
            'sugestao_ia' => $analise['sugestao'],
            'usuario_id' => $dadosFormulario['usuario_id'] ?? null,
        ]);

        $artigos = $this->firecrawlClient->buscarArtigos($dadosFormulario['titulo']);
        $this->repository->salvarArtigos($id, $artigos);

        $chamado = $this->repository->buscarPorId($id);

        $this->notificarAbertura($chamado);

        return $chamado;
    }

    public function listarChamados(?string $status = null): array
    {
        return $this->repository->listar($status);
    }

    public function listarChamadosDoUsuario(int $usuarioId): array
    {
        return $this->repository->listarPorUsuario($usuarioId);
    }

    public function buscarChamado(int $id): ?array
    {
        return $this->repository->buscarPorId($id);
    }

    public function atualizarStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUS_VALIDOS, true)) {
            throw new \InvalidArgumentException("Status inválido: {$status}");
        }

        $chamado = $this->repository->buscarPorId($id) ?? [];
        $statusAnterior = $chamado['status'] ?? null;

        $this->repository->atualizarStatus($id, $status);

        if ($statusAnterior !== null && $statusAnterior !== $status) {
            $chamado['status'] = $status;
            $this->notificarMudancaStatus($chamado);
        }
    }

    public function contarPorStatus(): array
    {
        return $this->repository->contarPorStatus();
    }

    private function notificarAbertura(array $chamado): void
    {
        $usuario = $this->buscarUsuarioDoChamado($chamado);
        if ($usuario === null) {
            return;
        }

        $corpo = '<h2>Chamado #' . $chamado['id'] . ' aberto</h2>'
            . '<p>Recebemos seu chamado <strong>' . htmlspecialchars($chamado['titulo']) . '</strong>.</p>'
            . '<p>Categoria sugerida: ' . htmlspecialchars($chamado['categoria'] ?? 'Outro') . '<br>'
            . 'Prioridade sugerida: ' . htmlspecialchars($chamado['prioridade']) . '</p>'
            . (!empty($chamado['sugestao_ia'])
                ? '<p><strong>Sugestão inicial:</strong> ' . htmlspecialchars($chamado['sugestao_ia']) . '</p>'
                : '')
            . '<p>O time de TI vai acompanhar o andamento.</p>';

        $enviado = $this->emailClient->enviar(
            $usuario['email'],
            $usuario['nome'],
            "Chamado #{$chamado['id']} aberto — {$chamado['titulo']}",
            $corpo
        );

        if (!$enviado) {
            error_log("Falha ao enviar e-mail de abertura do chamado #{$chamado['id']}");
        }
    }

    private function notificarMudancaStatus(array $chamado): void
    {
        $mensagem = self::MENSAGENS_MUDANCA_STATUS[$chamado['status']] ?? null;
        if ($mensagem === null) {
            return;
        }

        $usuario = $this->buscarUsuarioDoChamado($chamado);
        if ($usuario === null) {
            return;
        }

        $enviado = $this->emailClient->enviar(
            $usuario['email'],
            $usuario['nome'],
            sprintf($mensagem['assunto'], $chamado['id']),
            sprintf($mensagem['corpo'], htmlspecialchars($chamado['titulo']))
        );

        if (!$enviado) {
            error_log("Falha ao enviar e-mail de mudança de status do chamado #{$chamado['id']}");
        }
    }

    private function buscarUsuarioDoChamado(array $chamado): ?array
    {
        if (empty($chamado['usuario_id'])) {
            return null;
        }

        return $this->usuarioRepository->buscarPorId((int) $chamado['usuario_id']);
    }
}
