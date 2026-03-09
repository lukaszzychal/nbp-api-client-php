<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model\ValueObject;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */
class ExchangeRateValue
{
    /**
     * @var numeric-string
     */
    private string $rate;

    /**
     * @param numeric-string|float|int $rate
     */
    public function __construct(string|float|int $rate)
    {
        $stringRate = (string) $rate;

        // Use bccomp to safely compare string float numbers. Returns 1 if left-operand is larger.
        // Third param is scale - NBP usually returns 4 decimal places, providing 8 is safe.
        if (bccomp($stringRate, '0', 8) <= 0) {
            throw new \InvalidArgumentException(sprintf('Exchange rate must be positive, got "%s".', $stringRate));
        }

        $this->rate = $stringRate;
    }

    /**
     * Returns the rate as a strict numeric string representation for calculating
     * operations without precision loss (e.g., ext-bcmath or databases).
     *
     * @return numeric-string
     */
    public function getValue(): string
    {
        return $this->rate;
    }
}
