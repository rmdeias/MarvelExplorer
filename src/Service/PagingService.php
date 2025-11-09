<?php

namespace App\Service;

readonly class PagingService
{
    public function paging($totalItems, $page, $itemsPerPage, $pageRange): array
    {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $startPage = max(1, $page - intval($pageRange / 3));
        $endPage = min($totalPages, $startPage + $pageRange - 1);
        return [
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage
        ];

    }

}
