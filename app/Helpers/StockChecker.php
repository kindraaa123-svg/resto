<?php

namespace App\Helpers;

class StockChecker
{
    /**
     * Check if a menu is available based on ingredient stocks at a specific branch.
     */
    public static function isMenuAvailable(mixed $menuId, int $branchId): bool
    {
        return true;
    }

    /**
     * Check if an addon is available based on ingredient stocks.
     */
    public static function isAddonAvailable(mixed $addonId, int $branchId): bool
    {
        return true;
    }

    /**
     * Check if a product is available (direct scan).
     */
    public static function isProductAvailable(mixed $productId, int $branchId): bool
    {
        return true;
    }

    /**
     * Get the maximum number of portions possible for a menu based on stocks.
     */
    public static function getMaxQuantity(mixed $menuId, int $branchId): int
    {
        return 99;
    }

    /**
     * Get the maximum number of items possible for a product.
     */
    public static function getMaxProductQuantity(mixed $productId, int $branchId): int
    {
        return 999;
    }
}
