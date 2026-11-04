<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

abstract class ApiController extends Controller
{
    protected function correlationId(): string
    {
        return (string) (request()?->attributes->get('correlation_id') ?? Str::ulid());
    }

    protected function ok(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        $corrId = $this->correlationId();

        return response()->json([
            'data' => $data,
            'meta' => [
                'correlation_id' => $corrId,
            ],
        ], $status)->withHeaders(array_merge([
            'X-Correlation-ID' => $corrId,
        ], $headers));
    }

    protected function paginated(LengthAwarePaginator $paginator, ?callable $mapper = null, array $headers = []): JsonResponse
    {
        $corrId = $this->correlationId();
        $items = collect($paginator->items());
        if ($mapper) {
            $items = $items->map($mapper);
        }

        return response()->json([
            'data' => $items->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'correlation_id' => $corrId,
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'previous' => $paginator->previousPageUrl(),
            ],
        ], 200)->withHeaders(array_merge([
            'X-Correlation-ID' => $corrId,
        ], $headers));
    }

    protected function fail(string $code, string $message, int $status = 400, array $fields = [], array $headers = []): JsonResponse
    {
        $corrId = $this->correlationId();

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => $fields === [] ? (object) [] : $fields,
                'correlation_id' => $corrId,
            ],
        ], $status)->withHeaders(array_merge([
            'X-Correlation-ID' => $corrId,
        ], $headers));
    }
}
