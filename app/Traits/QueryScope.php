<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait QueryScope
{
    /**
     * paginate the records when the paginate parameter
     * is present in the request
     */
    public function scopeWithPagination(Builder $query, Request $request): Collection|LengthAwarePaginator
    {
        return $query->paginate($request->integer('per_page', 15))->withQueryString();
    }

    /**
     * limit the number of records to be returned
     * when the limit parameter is present in the request
     */
    public function scopeWithLimit(Builder $query, Request $request): Builder
    {
        return $query->when(
            $request->boolean('limit'),
            fn (Builder $query) => $query->limit($request->integer('limit', 15)),
        );
    }
}
