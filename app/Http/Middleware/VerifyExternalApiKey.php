<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyExternalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('education.external_api_key');
        $provided = $request->header('X-External-Api-Key');

        if (empty($expected) || ! is_string($provided) || ! hash_equals($expected, $provided)) {
            return ApiResponse::error(__('errors.invalid_api_key'), null, 401);
        }

        return $next($request);
    }
}
