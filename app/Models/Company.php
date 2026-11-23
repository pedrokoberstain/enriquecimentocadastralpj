<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $cnpj
 * @property string $razao_social
 * @property string|null $nome_fantasia
 * @property string|null $situacao_cadastral
 * @property string|null $municipio
 * @property string|null $uf
 * @property array<string, mixed> $dados_cadastrais
 * @property Carbon $last_synced_at
 * @property-read Team $team
 */
#[Fillable([
    'team_id',
    'cnpj',
    'razao_social',
    'nome_fantasia',
    'situacao_cadastral',
    'municipio',
    'uf',
    'dados_cadastrais',
    'last_synced_at',
])]
class Company extends Model
{
    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dados_cadastrais' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
