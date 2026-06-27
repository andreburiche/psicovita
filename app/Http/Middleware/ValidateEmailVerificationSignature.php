<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida assinatura da ligação de confirmação de e-mail.
 *
 * Aceita assinatura relativa ou absoluta (ligações antigas). Reconstrói a query
 * de forma canónica quando {@see Request::$server} `QUERY_STRING` está vazio ou
 * incompatível (comum em alguns ambientes Apache/XAMPP), alinhando com o HMAC do Laravel.
 */
final class ValidateEmailVerificationSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->passesSignature($request)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }

    private function passesSignature(Request $request): bool
    {
        foreach ([false, true] as $absolute) {
            if (URL::hasValidSignature($request, $absolute)) {
                return true;
            }
        }

        foreach ([false, true] as $absolute) {
            if ($this->passesWithCanonicalQueryString($request, $absolute)) {
                return true;
            }
        }

        return false;
    }

    private function passesWithCanonicalQueryString(Request $request, bool $absolute): bool
    {
        $original = $request->server->get('QUERY_STRING');

        $canonical = $this->canonicalQueryStringWithoutSignature($request);
        $request->server->set('QUERY_STRING', $canonical);

        try {
            return URL::hasValidSignature($request, $absolute);
        } finally {
            if ($original === null) {
                $request->server->remove('QUERY_STRING');
            } else {
                $request->server->set('QUERY_STRING', $original);
            }
        }
    }

    /**
     * Mesma ideia que o UrlGenerator ao assinar: parâmetros de query sem `signature`,
     * ordenados por chave (equivalente ao ksort usado em {@see UrlGenerator::signedRoute}).
     */
    private function canonicalQueryStringWithoutSignature(Request $request): string
    {
        return collect($request->query())
            ->except('signature')
            ->sortKeys()
            ->map(function ($value, string $key) {
                if (is_scalar($value) || $value === null) {
                    return $key.'='.(string) $value;
                }

                return $key.'='.json_encode($value);
            })
            ->implode('&');
    }
}
