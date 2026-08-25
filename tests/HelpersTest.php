<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/helpers.php';

class HelpersTest extends TestCase
{
    public function testFormatarDataHoraRemoveMicrossegundosEFormataPtBr(): void
    {
        $this->assertSame('25/08/2026 às 12:46', formatar_data_hora('2026-08-25 12:46:03.399149'));
    }

    public function testFormatarDataHoraFuncionaSemMicrossegundos(): void
    {
        $this->assertSame('01/01/2026 às 09:05', formatar_data_hora('2026-01-01 09:05:00'));
    }
}
