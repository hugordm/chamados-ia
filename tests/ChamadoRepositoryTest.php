<?php

namespace Tests;

use App\Repositories\ChamadoRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class ChamadoRepositoryTest extends TestCase
{
    public function testListarSemFiltroExcluiArquivadosPorPadrao(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('arquivado_em IS NULL'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $repository->listar();
    }

    public function testListarComStatusExcluiArquivados(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('arquivado_em IS NULL'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $repository->listar('Aberto');
    }

    public function testListarPorUsuarioExcluiArquivados(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('arquivado_em IS NULL'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $repository->listarPorUsuario(1);
    }

    public function testArquivarPreencheArquivadoEm(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id' => 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('arquivado_em = NOW()'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $repository->arquivar(5);
    }

    public function testDesarquivarLimpaArquivadoEm(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id' => 5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('arquivado_em = NULL'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $repository->desarquivar(5);
    }

    public function testListarArquivadosRetornaApenasArquivados(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'arquivado_em' => '2026-08-25 10:00:00'],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with($this->stringContains('arquivado_em IS NOT NULL'))
            ->willReturn($stmt);

        $repository = new ChamadoRepository($pdo);
        $resultado = $repository->listarArquivados();

        $this->assertCount(1, $resultado);
        $this->assertSame(1, $resultado[0]['id']);
    }
}
