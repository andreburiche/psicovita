<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\OpenApi\PsiConectaOpenApiSpec;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(PsiConectaOpenApiSpec::document());
    }
}
