<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'external.api_key' => \App\Http\Middleware\VerifyExternalApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = static fn (Request $r) => $r->is('api/*') || $r->expectsJson();

        $exceptions->render(function (ValidationException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('Validation error.', $e->errors(), 422);
            }
        });

        // App is API-only — every unauthenticated request gets a 401 JSON
        // regardless of Accept header or path. Returning unconditionally
        // avoids edge cases where the default handler tries to redirect to
        // a non-existent login page.
        $exceptions->render(function (AuthenticationException $e, Request $r) {
            return ApiResponse::error('Unauthenticated.', null, 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error($e->getMessage() ?: 'Forbidden.', null, 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('Resource not found.', null, 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                return ApiResponse::error('Endpoint not found.', null, 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $r) use ($isApi) {
            if ($isApi($r)) {
                $status = $e->getStatusCode();
                $msg = $e->getMessage() ?: match ($status) {
                    401 => 'Unauthenticated.',
                    403 => 'Forbidden.',
                    404 => 'Not found.',
                    405 => 'Method not allowed.',
                    default => 'Request failed.',
                };

                return ApiResponse::error($msg, null, $status);
            }
        });
    })->create();
