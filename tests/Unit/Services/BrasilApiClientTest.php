<?php

namespace Tests\Unit\Services;

use App\Services\BrasilApiClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BrasilApiClientTest extends TestCase
{
    public function test_consulta_o_cnpj_na_brasilapi(): void
    {
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response([
                'cnpj' => '11222333000181',
                'razao_social' => 'Empresa Exemplo Ltda.',
            ]),
        ]);

        $dados = app(BrasilApiClient::class)->consult('11222333000181');

        $this->assertSame('Empresa Exemplo Ltda.', $dados['razao_social']);
        Http::assertSent(fn (Request $request) => $request->url() ===
            'https://brasilapi.com.br/api/cnpj/v1/11222333000181');
    }

    public function test_informa_quando_o_cnpj_nao_e_encontrado_na_brasilapi(): void
    {
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response([], 404),
        ]);

        try {
            app(BrasilApiClient::class)->consult('11222333000181');
            $this->fail('Era esperado informar que o CNPJ não foi encontrado.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }
}
