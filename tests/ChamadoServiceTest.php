<?php

namespace Tests;

use App\Integrations\AnthropicClient;
use App\Integrations\EmailClient;
use App\Integrations\FirecrawlClient;
use App\Repositories\ChamadoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\ChamadoService;
use App\Validation\ValidacaoException;
use PHPUnit\Framework\TestCase;

class ChamadoServiceTest extends TestCase
{
    private function criarService(
        ?ChamadoRepository $repository = null,
        ?AnthropicClient $anthropicClient = null,
        ?FirecrawlClient $firecrawlClient = null,
        ?UsuarioRepository $usuarioRepository = null,
        ?EmailClient $emailClient = null
    ): ChamadoService {
        return new ChamadoService(
            $repository ?? $this->createMock(ChamadoRepository::class),
            $anthropicClient ?? $this->createMock(AnthropicClient::class),
            $firecrawlClient ?? $this->createMock(FirecrawlClient::class),
            $usuarioRepository ?? $this->createMock(UsuarioRepository::class),
            $emailClient ?? $this->createMock(EmailClient::class)
        );
    }

    public function testValidacaoRecusaChamadoSemTitulo(): void
    {
        $service = $this->criarService();

        $this->expectException(ValidacaoException::class);

        $service->abrirChamado([
            'solicitante' => 'Hugo',
            'setor' => 'TI',
            'titulo' => '',
            'descricao' => 'Descrição válida com mais de dez caracteres',
        ]);
    }

    public function testValidacaoRecusaDescricaoMenorQueDezCaracteres(): void
    {
        $service = $this->criarService();

        $this->expectException(ValidacaoException::class);

        $service->abrirChamado([
            'solicitante' => 'Hugo',
            'setor' => 'TI',
            'titulo' => 'Chamado teste',
            'descricao' => 'curto',
        ]);
    }

    public function testAbrirChamadoUsaValoresPadraoQuandoAnthropicFalha(): void
    {
        $anthropicClient = $this->createMock(AnthropicClient::class);
        $anthropicClient->method('analisar')->willReturn([
            'categoria' => 'Outro',
            'prioridade' => 'Media',
            'sugestao' => '',
        ]);

        $firecrawlClient = $this->createMock(FirecrawlClient::class);
        $firecrawlClient->method('buscarArtigos')->willReturn([]);

        $repository = $this->createMock(ChamadoRepository::class);
        $repository->expects($this->once())
            ->method('criar')
            ->with($this->callback(fn (array $dados) => $dados['categoria'] === 'Outro' && $dados['prioridade'] === 'Media'))
            ->willReturn(1);
        $repository->method('buscarPorId')->willReturn(['id' => 1]);

        $service = $this->criarService($repository, $anthropicClient, $firecrawlClient);

        $chamado = $service->abrirChamado([
            'solicitante' => 'Hugo',
            'setor' => 'TI',
            'titulo' => 'Chamado teste',
            'descricao' => 'Descrição válida com mais de dez caracteres',
        ]);

        $this->assertSame(1, $chamado['id']);
    }

    public function testAtualizarStatusAceitaApenasValoresPermitidos(): void
    {
        $repository = $this->createMock(ChamadoRepository::class);
        $repository->expects($this->once())
            ->method('atualizarStatus')
            ->with(1, 'Resolvido');

        $service = $this->criarService($repository);
        $service->atualizarStatus(1, 'Resolvido');

        $this->expectException(\InvalidArgumentException::class);
        $service->atualizarStatus(1, 'Cancelado');
    }
}
