<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Contract;

use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Contract Tests. Run as an independent part of the deployment process using the annotated group.
 * They interact directly with the publicly exposed NBP API servers and verify the client service behaviour on real data.
 * @group contract
 */
class NbpApiContractTest extends TestCase
{
    private NbpApiClient $client;

    protected function setUp(): void
    {
        $httpClient = new Psr18Client(HttpClient::create());
        $requestFactory = new Psr17Factory();
        
        $this->client = new NbpApiClient($httpClient, $requestFactory);
    }

    public function testRealApiRespondsWithValidCurrencyTable(): void
    {
        // Verify that today's Table A is correctly built from the external server if the response is > 200
        $result = $this->client->getCurrencyTable('A');

        $this->assertNotEmpty($result);
        $this->assertObjectHasProperty('table', $result[0]);
        $this->assertEquals('A', $result[0]->table);
        $this->assertObjectHasProperty('rates', $result[0]);
        $this->assertNotEmpty($result[0]->rates);
        $this->assertObjectHasProperty('currency', $result[0]->rates[0]);
    }

    public function testRealApiRespondsWithValidGoldPricesForDateRange(): void
    {
        // A few-day range
        $result = $this->client->getGoldPricesForDateRange('2024-03-01', '2024-03-05');

        $this->assertNotEmpty($result);
        $this->assertObjectHasProperty('date', $result[0]);
        $this->assertObjectHasProperty('price', $result[0]);
    }
}
