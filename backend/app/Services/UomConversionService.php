<?php

namespace App\Services;

use App\Models\Uom;
use App\Models\UomConversion;
use Illuminate\Support\Facades\Log;

class UomConversionService
{
    /**
     * Convert quantity from one UOM to another.
     * Priority: product-specific conversion (UomConversion table) → global UOM conversion via base_uom.
     *
     * @param float $quantity
     * @param int $fromUomId
     * @param int $toUomId
     * @param int|null $productId Optional product-specific conversion
     * @return float
     * @throws \Exception
     */
    public function convert(float $quantity, int $fromUomId, int $toUomId, ?int $productId = null): float
    {
        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        if ($quantity == 0) {
            return 0;
        }

        // 1. Try product-specific conversion first
        if ($productId !== null) {
            $productConv = UomConversion::where('product_id', $productId)
                ->where('from_uom_id', $fromUomId)
                ->where('to_uom_id', $toUomId)
                ->first();

            if ($productConv) {
                return round($quantity * $productConv->conversion_factor, 4);
            }

            // Try reverse conversion
            $reverseConv = UomConversion::where('product_id', $productId)
                ->where('from_uom_id', $toUomId)
                ->where('to_uom_id', $fromUomId)
                ->first();

            if ($reverseConv) {
                return round($quantity / $reverseConv->conversion_factor, 4);
            }
        }

        // 2. Try global UOM conversion via base_uom tree
        $fromUom = Uom::find($fromUomId);
        $toUom = Uom::find($toUomId);

        if (!$fromUom || !$toUom) {
            throw new \Exception("UOM not found: from_uom_id={$fromUomId}, to_uom_id={$toUomId}");
        }

        // Convert both to their base quantity, then divide
        $baseFrom = $this->toBaseQuantity($quantity, $fromUom);
        $baseToFactor = $this->toBaseQuantity(1.0, $toUom);

        if ($baseToFactor == 0) {
            throw new \Exception("Invalid conversion factor for UOM ID {$toUomId}");
        }

        return round($baseFrom / $baseToFactor, 4);
    }

    /**
     * Convert quantity to base UOM using UOM ID, with optional product-specific conversion.
     *
     * @param float $quantity
     * @param int $uomId
     * @param int|null $productId Optional product-specific conversion
     * @return float
     * @throws \Exception
     */
    public function getBaseQuantity(float $quantity, int $uomId, ?int $productId = null): float
    {
        if ($quantity == 0) {
            return 0;
        }

        // Check product-specific conversion first
        if ($productId !== null) {
            $productConv = UomConversion::where('product_id', $productId)
                ->where('from_uom_id', $uomId)
                ->first();

            if ($productConv) {
                return round($quantity * $productConv->conversion_factor, 4);
            }
        }

        // Fall back to global UOM tree
        $uom = Uom::find($uomId);
        if (!$uom) {
            throw new \Exception("UOM not found: ID {$uomId}");
        }

        return $this->toBaseQuantity($quantity, $uom);
    }

    /**
     * Convert quantity to base UOM and return both the value and base UOM ID.
     *
     * @param float $quantity
     * @param int $uomId
     * @param int|null $productId
     * @return array{quantity: float, base_uom_id: int}
     * @throws \Exception
     */
    public function getBaseUomWithConversion(float $quantity, int $uomId, ?int $productId = null): array
    {
        return [
            'quantity' => $this->getBaseQuantity($quantity, $uomId, $productId),
            'base_uom_id' => $this->getBaseUomId($uomId),
        ];
    }

    /**
     * Convert quantity to the base UOM quantity by traversing the base_uom tree.
     *
     * @param float $quantity
     * @param Uom $uom
     * @return float
     * @throws \Exception
     */
    private function toBaseQuantity(float $quantity, Uom $uom): float
    {
        $currentQty = $quantity;
        $currentUom = $uom;
        $visited = [];
        $maxDepth = 10;

        while ($currentUom->base_uom_id !== null) {
            if (in_array($currentUom->id, $visited)) {
                throw new \Exception("Circular UOM reference detected for UOM ID {$currentUom->id}");
            }
            $visited[] = $currentUom->id;

            if (count($visited) > $maxDepth) {
                throw new \Exception("UOM reference depth exceeded max {$maxDepth} for UOM ID {$currentUom->id}");
            }

            $currentQty *= $currentUom->conversion_factor;

            $parent = Uom::find($currentUom->base_uom_id);
            if (!$parent) {
                throw new \Exception("Parent UOM not found: base_uom_id={$currentUom->base_uom_id}");
            }
            $currentUom = $parent;
        }

        return $currentQty;
    }

    /**
     * Convert quantity from base UOM to a target UOM.
     *
     * @param float $baseQuantity
     * @param int $targetUomId
     * @return float
     * @throws \Exception
     */
    public function convertFromBase(float $baseQuantity, int $targetUomId): float
    {
        $targetUom = Uom::find($targetUomId);

        if (!$targetUom) {
            throw new \Exception("Target UOM not found: ID {$targetUomId}");
        }

        if ($targetUom->base_uom_id === null) {
            // This is already a base UOM
            return $baseQuantity;
        }

        // Traverse down: baseQuantity / conversion_factor chain
        $result = $baseQuantity;
        $currentUom = $targetUom;
        $visited = [];
        $maxDepth = 10;

        while ($currentUom->base_uom_id !== null) {
            if (in_array($currentUom->id, $visited)) {
                throw new \Exception("Circular UOM reference detected for UOM ID {$currentUom->id}");
            }
            $visited[] = $currentUom->id;

            if (count($visited) > $maxDepth) {
                throw new \Exception("UOM reference depth exceeded max {$maxDepth}");
            }

            $result /= $currentUom->conversion_factor;

            $parent = Uom::find($currentUom->base_uom_id);
            if (!$parent) {
                throw new \Exception("Parent UOM not found: base_uom_id={$currentUom->base_uom_id}");
            }
            $currentUom = $parent;
        }

        return round($result, 4);
    }

    /**
     * Get the base UOM ID for a given UOM.
     *
     * @param int $uomId
     * @return int
     * @throws \Exception
     */
    public function getBaseUomId(int $uomId): int
    {
        $uom = Uom::find($uomId);
        if (!$uom) {
            throw new \Exception("UOM not found: ID {$uomId}");
        }

        $current = $uom;
        $visited = [];
        $maxDepth = 10;

        while ($current->base_uom_id !== null) {
            if (in_array($current->id, $visited)) {
                throw new \Exception("Circular UOM reference for UOM ID {$current->id}");
            }
            $visited[] = $current->id;

            if (count($visited) > $maxDepth) {
                throw new \Exception("UOM depth exceeded max {$maxDepth}");
            }

            $parent = Uom::find($current->base_uom_id);
            if (!$parent) {
                throw new \Exception("Parent UOM not found: base_uom_id={$current->base_uom_id}");
            }
            $current = $parent;
        }

        return $current->id;
    }
}
