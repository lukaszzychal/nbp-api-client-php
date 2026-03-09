<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Unit\Calculator;

use LukaszZychal\NbpApiClient\Calculator\CurrencyCalculator;
use LukaszZychal\NbpApiClient\Model\CurrencyRate;
use LukaszZychal\NbpApiClient\Model\CurrencyTable;
use LukaszZychal\NbpApiClient\Model\ValueObject\CurrencyCode;
use LukaszZychal\NbpApiClient\Model\ValueObject\DateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\ExchangeRateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\TableNumber;
use PHPUnit\Framework\TestCase;

class CurrencyCalculatorTest extends TestCase
{
    private CurrencyTable $table;

    protected function setUp(): void
    {
        // 1 USD = 4.00 PLN, 1 EUR = 4.30 PLN
        $this->table = new CurrencyTable(
            'A',
            new TableNumber('123/A/NBP'),
            new DateValue('2026-01-01'),
            [
                new CurrencyRate('dolar', new CurrencyCode('USD'), new ExchangeRateValue(4.00)),
                new CurrencyRate('euro', new CurrencyCode('EUR'), new ExchangeRateValue(4.30)),
            ],
        );
    }

    public function testCalculateForeignToPln(): void
    {
        $calculator = new CurrencyCalculator();
        $result = $calculator->convert(100.0, 'USD', 'PLN', $this->table);
        $this->assertSame('400.0000', $result);
    }

    public function testCalculatePlnToForeign(): void
    {
        $calculator = new CurrencyCalculator();
        $result = $calculator->convert(430.0, 'PLN', 'EUR', $this->table);
        $this->assertSame('100.0000', $result);
    }

    public function testCalculateCrossForeign(): void
    {
        $calculator = new CurrencyCalculator();
        $result = $calculator->convert(100.0, 'EUR', 'USD', $this->table);
        // (100 * 4.3) / 4.0 = 430 / 4.0 = 107.5
        $this->assertSame('107.5000', $result);
    }

    public function testCalculateSameCurrency(): void
    {
        $calculator = new CurrencyCalculator();
        $result = $calculator->convert(100.0, 'USD', 'USD', $this->table);
        $this->assertSame('100', $result);
    }

    public function testConvertThrowsExceptionForUnknownCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $calculator = new CurrencyCalculator();
        $calculator->convert(100.0, 'GBP', 'PLN', $this->table);
    }
}
