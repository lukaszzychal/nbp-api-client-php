<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Integration\Client;

use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use LukaszZychal\NbpApiClient\Model\CurrencyTable;
use LukaszZychal\NbpApiClient\Tests\Doubles\FakeHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class NbpApiClientIntegrationTest extends TestCase
{
    private FakeHttpClient $httpClient;
    private Psr17Factory $psr17Factory;
    private NbpApiClient $client;

    protected function setUp(): void
    {
        $this->httpClient = new FakeHttpClient();
        $this->psr17Factory = new Psr17Factory();

        // PSR-17 request factory for standardised requests
        $this->client = new NbpApiClient($this->httpClient, $this->psr17Factory);
    }

    public function testGetCurrencyTableReturnsParsedDtoArray(): void
    {
        $jsonResponse = '[
            {
                "table": "A",
                "no": "046/A/NBP/2026",
                "effectiveDate": "2026-03-08",
                "rates": [
                    {
                        "currency": "dolar amerykański",
                        "code": "USD",
                        "mid": 3.9512
                    },
                    {
                        "currency": "euro",
                        "code": "EUR",
                        "mid": 4.3121
                    }
                ]
            }
        ]';

        $this->httpClient->addResponse(
            'http://api.nbp.pl/api/exchangerates/tables/A/?format=json',
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        );

        $result = $this->client->getCurrencyTable('A');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(CurrencyTable::class, $result[0]);

        $table = $result[0];
        $this->assertSame('A', $table->table);
        $this->assertSame('046/A/NBP/2026', $table->tableNumber->getValue());
        $this->assertSame('2026-03-08', $table->effectiveDate->getValue());

        $this->assertCount(2, $table->rates);
        $this->assertSame('USD', $table->rates[0]->code->getValue());
        $this->assertSame('3.9512', $table->rates[0]->averageRate->getValue());
    }

    public function testGetsCurrencyTableForSpecificDate(): void
    {
        $jsonResponse = '[{"table":"A","no":"111/A/NBP/2026","effectiveDate":"2026-05-15","rates":[{"currency":"euro","code":"EUR","mid":4.30}]}]';

        $this->httpClient->addResponse(
            'http://api.nbp.pl/api/exchangerates/tables/A/2026-05-15/?format=json',
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        );

        $result = $this->client->getCurrencyTableForDate('A', '2026-05-15');

        $this->assertCount(1, $result);
        $this->assertSame('111/A/NBP/2026', $result[0]->tableNumber->getValue());
        $this->assertSame('EUR', $result[0]->rates[0]->code->getValue());
    }

    public function testGetsCurrencyTableForDateRange(): void
    {
        $jsonResponse = '[{"table":"C","no":"001/C/NBP/2026","effectiveDate":"2026-01-02","rates":[{"currency":"dolar","code":"USD","mid":4.0}]}]';

        $this->httpClient->addResponse(
            'http://api.nbp.pl/api/exchangerates/tables/C/2026-01-01/2026-01-31/?format=json',
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        );

        $result = $this->client->getCurrencyTableForDateRange('C', '2026-01-01', '2026-01-31');

        $this->assertCount(1, $result);
        $this->assertSame('C', $result[0]->table);
        $this->assertCount(1, $result[0]->rates);
    }

    public function testGetGoldPricesForDate(): void
    {
        $jsonResponse = '[{"data":"2026-06-01","cena":312.45}]';

        $this->httpClient->addResponse(
            'http://api.nbp.pl/api/cenyzlota/2026-06-01/?format=json',
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        );

        $result = $this->client->getGoldPricesForDate('2026-06-01');

        $this->assertCount(1, $result);
        $this->assertSame('2026-06-01', $result[0]->date->getValue());
        $this->assertSame('312.45', $result[0]->price->getValue());
    }

    public function testGetGoldPricesForDateRange(): void
    {
        $jsonResponse = '[{"data":"2026-06-01","cena":310.0},{"data":"2026-06-02","cena":315.0}]';

        $this->httpClient->addResponse(
            'http://api.nbp.pl/api/cenyzlota/2026-06-01/2026-06-02/?format=json',
            new Response(200, ['Content-Type' => 'application/json'], $jsonResponse),
        );

        $result = $this->client->getGoldPricesForDateRange('2026-06-01', '2026-06-02');

        $this->assertCount(2, $result);
        $this->assertSame('2026-06-02', $result[1]->date->getValue());
        $this->assertSame('315', $result[1]->price->getValue());
    }
}
