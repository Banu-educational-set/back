<?php

namespace App\Http\Middleware;

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
            return response()->json(['message' => 'Invalid external API key.'], 401);
        }

        return $next($request);
    }
}
