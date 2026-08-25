<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/auth.php';

class AuthTest extends TestCase
{
    public function testPapelConfereBloqueiaUsuarioComPapelDiferente(): void
    {
        $this->assertFalse(\papel_confere(['papel' => 'cliente'], 'agente'));
    }

    public function testPapelConfereAceitaUsuarioComPapelIgual(): void
    {
        $this->assertTrue(\papel_confere(['papel' => 'agente'], 'agente'));
    }
}
