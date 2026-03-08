<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model;

use LukaszZychal\NbpApiClient\Model\ValueObject\CurrencyCode;
use LukaszZychal\NbpApiClient\Model\ValueObject\ExchangeRateValue;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */

class CurrencyRate
{
    public function __construct(
        public readonly string $currency,
        public readonly CurrencyCode $code,
        public readonly ?ExchangeRateValue $averageRate = null,
        public readonly ?ExchangeRateValue $buyRate = null,
        public readonly ?ExchangeRateValue $sellRate = null
    ) {
    }
}
