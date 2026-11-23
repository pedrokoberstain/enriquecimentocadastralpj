<?php

namespace Tests\Unit\Rules;

use App\Rules\Cnpj;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CnpjTest extends TestCase
{
    public function test_aceita_cnpjs_validos_com_e_sem_formatacao(): void
    {
        foreach (['11222333000181', '11.222.333/0001-81'] as $cnpj) {
            $this->assertTrue(Validator::make(
                ['cnpj' => $cnpj],
                ['cnpj' => [new Cnpj]],
            )->passes());
        }
    }

    public function test_rejeita_digitos_verificadores_invalidos_e_digitos_repetidos(): void
    {
        foreach (['11222333000182', '11.111.111/1111-11'] as $cnpj) {
            $this->assertFalse(Validator::make(
                ['cnpj' => $cnpj],
                ['cnpj' => [new Cnpj]],
            )->passes());
        }
    }
}
