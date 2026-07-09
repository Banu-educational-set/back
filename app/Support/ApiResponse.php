<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? __('messages.ok'),
            'data' => self::unwrap($data, app(Request::class)),
        ], $status);
    }

    public static function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Resolve resources / paginators / arrayables into plain payload data.
     */
    private static function unwrap(mixed $value, Request $request): mixed
    {
        if ($value instanceof JsonResource) {
            $resolved = $value->resolve($request);

            if ($value instanceof ResourceCollection && $value->resource instanceof AbstractPaginator) {
                return [
                    'items' => $resolved,
                    'meta' => self::paginatorMeta($value->resource),
                ];
            }

            return $resolved;
        }

        if ($value instanceof AbstractPaginator) {
            return [
                'items' => $value->items(),
                'meta' => self::paginatorMeta($value),
            ];
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }

    private static function paginatorMeta(AbstractPaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
            'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
        ];
    }
}
