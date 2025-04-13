<?php

namespace App\DataFixtures\Data;

class Amounts
{
    /**
     * Generate a random amount using exponential distribution.
     *
     * This generates amounts that cluster around the base amount but allow for some higher values,
     * creating a more realistic distribution of financial data. The exponential distribution
     * ensures most amounts are close to the base, with decreasing probability of higher amounts.
     *
     * @param int        $baseAmount The expected/average amount (default: 10000)
     * @param float|null $lambda     Rate parameter for exponential distribution. If null, calculated as 1/baseAmount
     * @param int|null   $min        Minimum allowed amount. If null, defaults to 5% of base amount
     * @param int|null   $max        Maximum allowed amount. If null, defaults to 50x base amount
     *
     * @return int The generated amount, rounded to nearest 100
     */
    public static function generate(
        int $baseAmount = 10000,
        ?float $lambda = null,
        ?int $min = null,
        ?int $max = null,
    ): int {
        // Use exponential distribution to generate amounts clustered around base
        $lambda = $lambda ?? 1 / $baseAmount;
        $amount = round(-log(1 - mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax()) / $lambda);

        // Apply min/max bounds and round to nearest 100
        $min = $min ?? ($baseAmount * 0.05); // Default 5% of base
        $max = $max ?? ($baseAmount * 50);   // Default 50x base
        $amount = max($min, min($max, $amount));

        return (int) (round($amount / 100) * 100);
    }
}
