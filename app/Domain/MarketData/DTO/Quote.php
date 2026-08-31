<?php

declare(strict_types=1);

namespace App\Domain\MarketData\DTO;

final readonly class Quote
{
    public function __construct(
        public string $symbol,
        public string $bidPrice,
        public string $bidQuantity,
        public string $askPrice,
        public string $askQuantity,
        public ?int $updateId = null,
    ) {
    }

    /**
     * @return array{
     *     symbol: string,
     *     bid_price: string,
     *     bid_quantity: string,
     *     ask_price: string,
     *     ask_quantity: string,
     *     update_id: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'bid_price' => $this->bidPrice,
            'bid_quantity' => $this->bidQuantity,
            'ask_price' => $this->askPrice,
            'ask_quantity' => $this->askQuantity,
            'update_id' => $this->updateId,
        ];
    }
}
