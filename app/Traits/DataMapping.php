<?php

namespace App\Traits;

trait DataMapping {
    public function mapData($dailyBases, $mappedData)
    {
        return [
            'current_page' => $dailyBases->currentPage(),
            'data' => $mappedData,
            'first_page_url' => $dailyBases->url(1),
            'from' => $dailyBases->firstItem(),
            'last_page' => $dailyBases->lastPage(),
            'last_page_url' => $dailyBases->url($dailyBases->lastPage()),
            'links' => [
                [
                    'url' => $dailyBases->previousPageUrl(),
                    'label' => '&laquo; Previous',
                    'active' => false,
                ],
                [
                    'url' => $dailyBases->url($dailyBases->currentPage()),
                    'label' => $dailyBases->currentPage(),
                    'active' => true,
                ],
                [
                    'url' => $dailyBases->nextPageUrl(),
                    'label' => 'Next &raquo;',
                    'active' => false,
                ],
            ],
            'next_page_url' => $dailyBases->nextPageUrl(),
            'path' => $dailyBases->url(1),
            'per_page' => $dailyBases->perPage(),
            'prev_page_url' => $dailyBases->previousPageUrl(),
            'to' => $dailyBases->lastItem(),
            'total' => $dailyBases->total(),
        ];
    }
}
