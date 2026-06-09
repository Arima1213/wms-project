<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    protected ?int $productId;
    protected ?int $warehouseId;
    protected ?float $requestedQty;
    protected ?float $availableQty;

    public function __construct(
        string $message = '',
        ?int $productId = null,
        ?int $warehouseId = null,
        ?float $requestedQty = null,
        ?float $availableQty = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->productId = $productId;
        $this->warehouseId = $warehouseId;
        $this->requestedQty = $requestedQty;
        $this->availableQty = $availableQty;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getWarehouseId(): ?int
    {
        return $this->warehouseId;
    }

    public function getRequestedQty(): ?float
    {
        return $this->requestedQty;
    }

    public function getAvailableQty(): ?float
    {
        return $this->availableQty;
    }
}
