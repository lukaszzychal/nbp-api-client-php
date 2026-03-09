<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model;

use LukaszZychal\NbpApiClient\Model\ValueObject\DateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\ExchangeRateValue;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */

class GoldPrice
{
    public function __construct(
        public readonly DateValue $date,
        public readonly ExchangeRateValue $price,
    ) {}
}
