<?php

namespace Tests\Feature\Companies;

use App\Enums\TeamRole;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    private const CNPJ = '11222333000181';

    public function test_empresa_pode_ser_consultada_e_salva_pela_brasilapi(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response($this->dadosDaEmpresa(), 200),
        ]);

        $response = $this->actingAs($user)->postJson(
            "/api/teams/{$team->slug}/companies",
            ['cnpj' => '11.222.333/0001-81'],
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'cnpj',
                    'razao_social',
                    'nome_fantasia',
                    'situacao_cadastral',
                    'municipio',
                    'uf',
                    'dados_cadastrais' => ['cnpj', 'razao_social', 'uf'],
                    'atualizado_em',
                ],
            ])
            ->assertJsonPath('data.cnpj', self::CNPJ)
            ->assertJsonPath('data.razao_social', 'Empresa Exemplo Ltda.')
            ->assertJsonPath('data.dados_cadastrais.uf', 'SP');

        $this->assertDatabaseHas('companies', [
            'team_id' => $team->id,
            'cnpj' => self::CNPJ,
            'razao_social' => 'Empresa Exemplo Ltda.',
        ]);
        $this->assertSame(
            $this->dadosDaEmpresa(),
            Company::query()->where('team_id', $team->id)->firstOrFail()->dados_cadastrais,
        );

        Http::assertSent(fn ($request) => $request->url() ===
            'https://brasilapi.com.br/api/cnpj/v1/'.self::CNPJ);
    }

    public function test_atualizacao_renova_os_dados_salvos(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $company = $team->companies()->create([
            'cnpj' => self::CNPJ,
            'razao_social' => 'Nome Antigo Ltda.',
            'dados_cadastrais' => ['cnpj' => self::CNPJ, 'razao_social' => 'Nome Antigo Ltda.'],
            'last_synced_at' => now()->subDay(),
        ]);
        Http::fake([
            'https://brasilapi.com.br/api/cnpj/v1/*' => Http::response(
                $this->dadosDaEmpresa(['razao_social' => 'Nome Atualizado Ltda.']),
                200,
            ),
        ]);

        $this->actingAs($user)
            ->postJson("/api/teams/{$team->slug}/companies/".self::CNPJ.'/refresh')
            ->assertOk()
            ->assertJsonPath('data.razao_social', 'Nome Atualizado Ltda.');

        $this->assertSame('Nome Atualizado Ltda.', $company->fresh()->razao_social);
        $this->assertTrue($company->fresh()->last_synced_at->isToday());
    }

    public function test_cnpj_invalido_e_rejeitado_sem_consultar_brasilapi(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        Http::fake();

        $this->actingAs($user)
            ->postJson("/api/teams/{$team->slug}/companies", ['cnpj' => '11.111.111/1111-11'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnpj');

        Http::assertNothingSent();
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_empresas_ficam_restritas_a_equipe_solicitada(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $otherTeam = Team::factory()->create();
        $otherTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);
        $otherTeam->companies()->create([
            'cnpj' => self::CNPJ,
            'razao_social' => 'Outra Empresa Ltda.',
            'dados_cadastrais' => ['cnpj' => self::CNPJ],
            'last_synced_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson("/api/teams/{$team->slug}/companies/".self::CNPJ)
            ->assertNotFound();
    }

    public function test_usuario_nao_autenticado_nao_acessa_a_api_de_empresas(): void
    {
        $team = Team::factory()->create();

        $this->getJson("/api/teams/{$team->slug}/companies")
            ->assertUnauthorized();
    }

    public function test_listagem_filtra_e_pagina_empresas(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;
        $companies = [
            [
                'cnpj' => self::CNPJ,
                'razao_social' => 'São Paulo Ativa 1 Ltda.',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
                'situacao_cadastral' => 'ATIVA',
            ],
            [
                'cnpj' => '22333444000190',
                'razao_social' => 'São Paulo Ativa 2 Ltda.',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
                'situacao_cadastral' => 'ATIVA',
            ],
            [
                'cnpj' => '33444555000180',
                'razao_social' => 'São Paulo Inativa Ltda.',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
                'situacao_cadastral' => 'INATIVA',
            ],
            [
                'cnpj' => '44555666000170',
                'razao_social' => 'Rio Ativa Ltda.',
                'municipio' => 'Rio de Janeiro',
                'uf' => 'RJ',
                'situacao_cadastral' => 'ATIVA',
            ],
        ];

        foreach ($companies as $index => $attributes) {
            $team->companies()->create([
                ...$attributes,
                'dados_cadastrais' => ['cnpj' => $attributes['cnpj']],
                'last_synced_at' => now()->subMinutes($index),
            ]);
        }

        $query = http_build_query([
            'uf' => 'sp',
            'municipio' => 'são paulo',
            'situacao_cadastral' => 'ativa',
            'per_page' => 1,
        ]);
        $url = "/api/teams/{$team->slug}/companies?{$query}";

        $this->actingAs($user)
            ->getJson($url)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['cnpj', 'razao_social', 'municipio', 'uf', 'situacao_cadastral']],
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.cnpj', self::CNPJ)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2);

        $this->getJson($url.'&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.cnpj', '22333444000190')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_listagem_rejeita_parametros_de_paginacao_invalidos(): void
    {
        $user = User::factory()->create();
        $team = $user->currentTeam;

        $this->actingAs($user)
            ->getJson("/api/teams/{$team->slug}/companies?per_page=101")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    /**
     * @param  array<string, mixed>  $sobrescritos
     * @return array<string, mixed>
     */
    private function dadosDaEmpresa(array $sobrescritos = []): array
    {
        return array_merge([
            'cnpj' => self::CNPJ,
            'razao_social' => 'Empresa Exemplo Ltda.',
            'nome_fantasia' => 'Exemplo',
            'descricao_situacao_cadastral' => 'ATIVA',
            'municipio' => 'São Paulo',
            'uf' => 'SP',
        ], $sobrescritos);
    }
}
