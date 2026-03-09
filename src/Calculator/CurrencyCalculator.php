<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Calculator;

use LukaszZychal\NbpApiClient\Model\CurrencyTable;
use LukaszZychal\NbpApiClient\Model\ValueObject\CurrencyCode;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */
class CurrencyCalculator
{
    private const BASE_CURRENCY = 'PLN';

    /**
     * Converts an amount of money from one currency to another using the provided exchange rate table.
     * The conversion is always based on the Polish Zloty (PLN) as the reference currency.
     *
     * @param string|float|int $amount The amount to convert. Recommended to use string representation to dodge PHP float bugs.
     * @param string|CurrencyCode $fromCode The currency code of the initial amount.
     * @param string|CurrencyCode $toCode The currency code to convert to.
     * @param CurrencyTable $table The table containing current exchange rates.
     * @param int $scale Decimals round scale for final math formatting. Default is 4 to match NBP output.
     * @return numeric-string The converted amount safe for further database or BCMath usage.
     * @throws \InvalidArgumentException If the requested currency does not exist in the provided table.
     */
    public function convert(string|float|int $amount, string|CurrencyCode $fromCode, string|CurrencyCode $toCode, CurrencyTable $table, int $scale = 4): string
    {
        $fromCodeStr = (string) $fromCode;
        $toCodeStr = (string) $toCode;
        /** @var numeric-string $amountStr */
        $amountStr = (string) $amount;

        if ($fromCodeStr === $toCodeStr) {
            return $amountStr;
        }

        // Return values from resolver are always strictly validated string rates like "4.1232"
        $fromRateStr = $this->resolveExchangeRate($fromCodeStr, $table);
        $toRateStr = $this->resolveExchangeRate($toCodeStr, $table);

        // Calculate amount * fromRate using ext-bcmath. Keep precision extra high initially
        /** @var numeric-string $valueInPlnStr */
        $valueInPlnStr = (string) bcmul($amountStr, $fromRateStr, 8);

        // Finalize valueInPln / toRate keeping user desired scale precision
        /** @var numeric-string $converted */
        $converted = (string) bcdiv($valueInPlnStr, $toRateStr, $scale);

        return $converted;
    }

    /**
     * Resolves the exchange rate for a given currency code.
     * Treats the base currency (PLN) as a 1:1 equivalent.
     *
     * @return numeric-string
     * @throws \InvalidArgumentException
     */
    private function resolveExchangeRate(string $code, CurrencyTable $table): string
    {
        if ($this->isBaseCurrency($code)) {
            return '1.0';
        }

        return $this->findRate($code, $table);
    }

    private function isBaseCurrency(string $code): bool
    {
        return $code === self::BASE_CURRENCY;
    }

    /**
     * @return numeric-string
     * @throws \InvalidArgumentException
     */
    private function findRate(string $code, CurrencyTable $table): string
    {
        foreach ($table->rates as $rate) {
            if ($rate->code->getValue() === $code) {
                if ($rate->averageRate === null) {
                    throw new \InvalidArgumentException(sprintf('Calculator requires Table A/B for average rates. Table C (Bid/Ask) cannot be used for currency: %s', $code));
                }
                // Here getValue() on ExchangeRateValue is already returning a string under new BCmath rules setup
                return $rate->averageRate->getValue();
            }
        }

        throw new \InvalidArgumentException(sprintf('Currency %s not found in the given table.', $code));
    }
}
