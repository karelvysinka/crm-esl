<?php

namespace App\Services\Orders\DTO;

class ListingOrderRowDTO
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?string $customerName,
        public readonly ?string $shippingMethod,
        public readonly ?string $paymentMethod,
        public readonly int $itemsCount,
        public readonly int $totalVatCents,
        public readonly string $currency,
    public array $stateCodes,
        public readonly bool $isCompleted,
        public readonly ?int $internalId = null,
    ) {}
}
