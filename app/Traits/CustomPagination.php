<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\Request;

trait CustomPagination
{
    /**
     * Customize the pagination information for the resource.
     *
     * @return array
     */
    public function paginationInformation(Request $request, array $paginated, array $default)
    {
        return [
            'meta' => [
                'pagination' => [
                    'per_page' => $default['meta']['per_page'],
                    'total' => $default['meta']['total'],
                    'current_page' => $default['meta']['current_page'],
                    'last_page' => $default['meta']['last_page'],
                    'from' => $default['meta']['from'],
                    'to' => $default['meta']['to'],
                    'next_page_url' => $default['links']['next'],
                    'prev_page_url' => $default['links']['prev'],
                ],
            ],
        ];
    }
}
