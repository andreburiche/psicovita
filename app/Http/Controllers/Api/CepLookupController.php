<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepLookupController extends Controller
{
    /**
     * ViaCEP — apenas números no path; resposta sanitizada (sem repassar HTML externo).
     */
    public function __invoke(Request $request, string $cep): JsonResponse
    {
        $digits = preg_replace('/\D/', '', $cep) ?? '';

        if (strlen($digits) !== 8) {
            return response()->json(['message' => __('CEP inválido.')], 422);
        }

        $url = 'https://viacep.com.br/ws/'.$digits.'/json/';

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable) {
            return response()->json(['message' => __('Não foi possível consultar o CEP.')], 503);
        }

        if (! $response->successful()) {
            return response()->json(['message' => __('Serviço de CEP indisponível.')], 502);
        }

        $data = $response->json();
        if (! is_array($data) || (($data['erro'] ?? false) === true)) {
            return response()->json(['message' => __('CEP não encontrado.')], 404);
        }

        return response()->json([
            'cep' => only_digits($data['cep'] ?? $digits),
            'street' => isset($data['logradouro']) ? mb_substr((string) $data['logradouro'], 0, 255) : '',
            'district' => isset($data['bairro']) ? mb_substr((string) $data['bairro'], 0, 255) : '',
            'city' => isset($data['localidade']) ? mb_substr((string) $data['localidade'], 0, 255) : '',
            'state' => isset($data['uf']) ? mb_substr((string) $data['uf'], 0, 2) : '',
        ]);
    }
}
