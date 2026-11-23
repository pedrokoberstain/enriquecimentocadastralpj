<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cnpj' => $this->cnpj,
            'razao_social' => $this->razao_social,
            'nome_fantasia' => $this->nome_fantasia,
            'situacao_cadastral' => $this->situacao_cadastral,
            'municipio' => $this->municipio,
            'uf' => $this->uf,
            'dados_cadastrais' => $this->dados_cadastrais,
            'atualizado_em' => $this->last_synced_at,
        ];
    }
}
