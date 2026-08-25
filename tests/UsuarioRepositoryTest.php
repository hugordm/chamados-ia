<?php

namespace Tests;

use App\Repositories\UsuarioRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UsuarioRepositoryTest extends TestCase
{
    private function criarRepository(array|false $usuario): UsuarioRepository
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($usuario);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        return new UsuarioRepository($pdo);
    }

    public function testAutenticarRetornaNullParaSenhaErrada(): void
    {
        $repository = $this->criarRepository([
            'id' => 1,
            'nome' => 'Marina Alves Ferreira',
            'email' => 'marina@empresa.com',
            'senha_hash' => password_hash('senha123', PASSWORD_BCRYPT),
            'papel' => 'cliente',
            'setor' => 'Financeiro',
        ]);

        $this->assertNull($repository->autenticar('marina@empresa.com', 'senha-errada'));
    }

    public function testAutenticarRetornaUsuarioParaSenhaCerta(): void
    {
        $repository = $this->criarRepository([
            'id' => 1,
            'nome' => 'Marina Alves Ferreira',
            'email' => 'marina@empresa.com',
            'senha_hash' => password_hash('senha123', PASSWORD_BCRYPT),
            'papel' => 'cliente',
            'setor' => 'Financeiro',
        ]);

        $usuario = $repository->autenticar('marina@empresa.com', 'senha123');

        $this->assertNotNull($usuario);
        $this->assertSame('marina@empresa.com', $usuario['email']);
        $this->assertArrayNotHasKey('senha_hash', $usuario);
    }

    public function testAutenticarRetornaNullParaEmailInexistente(): void
    {
        $repository = $this->criarRepository(false);

        $this->assertNull($repository->autenticar('desconhecido@empresa.com', 'qualquer'));
    }
}
