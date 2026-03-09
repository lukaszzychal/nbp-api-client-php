<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model;

use LukaszZychal\NbpApiClient\Model\ValueObject\DateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\TableNumber;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */

class CurrencyTable
{
    /**
     * @param CurrencyRate[] $rates
     */
    public function __construct(
        public readonly string $table,
        public readonly TableNumber $tableNumber,
        public readonly DateValue $effectiveDate,
        public readonly array $rates,
    ) {}
}
