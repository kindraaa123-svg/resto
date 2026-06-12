<?php

namespace App\Helpers;

class UnitConverter
{
    /**
     * Convert value to the smallest unit (Gr, mL, Pcs).
     */
    public static function toBase($value, $unit)
    {
        $unit = strtolower($unit);
        switch ($unit) {
            case 'kg':
                return (float) $value * 1000;
            case 'l':
            case 'lt':
                return (float) $value * 1000;
            default:
                return (float) $value;
        }
    }

    /**
     * Get the standardized base unit string.
     */
    public static function getBaseUnit($unit)
    {
        if (empty($unit)) {
            return 'Pcs';
        }

        $unit = strtolower($unit);
        switch ($unit) {
            case 'kg':
            case 'gr':
                return 'Gr';
            case 'l':
            case 'lt':
            case 'ml':
                return 'mL';
            default:
                return ucfirst($unit); // Pcs, etc.
        }
    }

    /**
     * Format a base value for display (e.g., 1500 Gr -> 1.5 Kg).
     */
    public static function format($value, $unit)
    {
        $unit = self::getBaseUnit($unit);

        if ($unit === 'Gr' && $value >= 1000) {
            return ($value / 1000).' Kg';
        }
        if ($unit === 'mL' && $value >= 1000) {
            return ($value / 1000).' Lt';
        }

        return $value.' '.$unit;
    }
}
