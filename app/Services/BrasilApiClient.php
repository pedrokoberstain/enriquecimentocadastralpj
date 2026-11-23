<?php

namespace App\Services;

use App\Rules\Cnpj;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BrasilApiClient
{
    /** @return array<string, mixed> */
    public function consult(string $cnpj): array
    {
        try {
            $response = Http::baseUrl(config('services.brasilapi.url'))
                ->acceptJson()
                ->timeout(10)
                ->get('cnpj/v1/'.$cnpj);
        } catch (ConnectionException $exception) {
            throw new HttpException(503, 'A BrasilAPI está indisponível no momento.', $exception);
        }

        if ($response->notFound()) {
            throw new HttpException(404, 'CNPJ não encontrado na BrasilAPI.');
        }

        if ($response->failed()) {
            throw new HttpException(503, 'Não foi possível consultar a BrasilAPI no momento.');
        }

        $data = $response->json();

        if (! is_array($data) ||
            ! isset($data['cnpj']) ||
            Cnpj::normalize((string) $data['cnpj']) !== $cnpj ||
            ! isset($data['razao_social']) ||
            ! is_string($data['razao_social'])) {
            throw new HttpException(502, 'A BrasilAPI retornou dados cadastrais inválidos.');
        }

        return $data;
    }
}
