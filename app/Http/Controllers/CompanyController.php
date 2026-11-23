<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\Team;
use App\Rules\Cnpj;
use App\Services\BrasilApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function __construct(private readonly BrasilApiClient $brasilApi) {}

    public function index(Request $request, Team $team): JsonResponse
    {
        $filters = $request->validate([
            'uf' => ['sometimes', 'nullable', 'alpha', 'size:2'],
            'municipio' => ['sometimes', 'nullable', 'string', 'max:255'],
            'situacao_cadastral' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $team->companies();

        if (isset($filters['uf'])) {
            $query->where('uf', strtoupper($filters['uf']));
        }

        if (isset($filters['municipio'])) {
            $query->whereRaw('LOWER(municipio) = ?', [mb_strtolower($filters['municipio'])]);
        }

        if (isset($filters['situacao_cadastral'])) {
            $query->whereRaw('LOWER(situacao_cadastral) = ?', [mb_strtolower($filters['situacao_cadastral'])]);
        }

        $companies = $query
            ->orderByDesc('last_synced_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return CompanyResource::collection($companies)->response();
    }

    public function store(Request $request, Team $team): JsonResponse
    {
        $validated = $request->validate([
            'cnpj' => ['required', 'string', new Cnpj],
        ]);
        $cnpj = Cnpj::normalize($validated['cnpj']);
        $existing = $team->companies()->where('cnpj', $cnpj)->exists();

        $company = $this->syncCompany($team, $cnpj);

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode($existing ? 200 : 201);
    }

    public function show(Team $team, string $cnpj): CompanyResource
    {
        $cnpj = $this->validatedCnpj($cnpj);

        return new CompanyResource(
            $team->companies()->where('cnpj', $cnpj)->firstOrFail(),
        );
    }

    public function refresh(Team $team, string $cnpj): CompanyResource
    {
        $cnpj = $this->validatedCnpj($cnpj);
        $company = $team->companies()->where('cnpj', $cnpj)->firstOrFail();

        return new CompanyResource($this->syncCompany($team, $cnpj, $company));
    }

    public function destroy(Team $team, string $cnpj): JsonResponse
    {
        $cnpj = $this->validatedCnpj($cnpj);
        $team->companies()->where('cnpj', $cnpj)->firstOrFail()->delete();

        return response()->json(status: 204);
    }

    private function validatedCnpj(string $cnpj): string
    {
        Validator::make(['cnpj' => $cnpj], [
            'cnpj' => ['required', 'string', new Cnpj],
        ])->validate();

        return Cnpj::normalize($cnpj);
    }

    private function syncCompany(Team $team, string $cnpj, ?Company $company = null): Company
    {
        $data = $this->brasilApi->consult($cnpj);

        $attributes = [
            'razao_social' => $data['razao_social'],
            'nome_fantasia' => $data['nome_fantasia'] ?? null,
            'situacao_cadastral' => isset($data['descricao_situacao_cadastral'])
                ? (string) $data['descricao_situacao_cadastral']
                : (isset($data['situacao_cadastral']) ? (string) $data['situacao_cadastral'] : null),
            'municipio' => $data['municipio'] ?? null,
            'uf' => $data['uf'] ?? null,
            'dados_cadastrais' => $data,
            'last_synced_at' => now(),
        ];

        if ($company) {
            $company->update($attributes);

            return $company->refresh();
        }

        return $team->companies()->updateOrCreate(['cnpj' => $cnpj], $attributes);
    }
}
