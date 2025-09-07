<?php

namespace App\Services\Orders\DTO;

class OrderDetailDTO
{
    /** @param array<int, array<string,mixed>> $items */
    public function __construct(
        public readonly ListingOrderRowDTO $row,
        public readonly array $items,
        public readonly string $rawHash,
    ) {}
}
