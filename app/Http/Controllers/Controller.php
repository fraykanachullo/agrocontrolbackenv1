<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function paginated(string $resourceClass, $paginator): array
    {
        return [
            'success' => true,
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
