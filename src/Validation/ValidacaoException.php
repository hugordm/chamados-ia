<?php

namespace App\Validation;

class ValidacaoException extends \RuntimeException
{
    public function __construct(
        private readonly array $erros
    ) {
        parent::__construct(implode(' ', $erros));
    }

    public function getErros(): array
    {
        return $this->erros;
    }
}
