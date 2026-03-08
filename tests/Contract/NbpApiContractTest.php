<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Contract;

use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Testy Kontraktowe. Uruchamiane jako niezależna część procesu wdrażania za pomocą adnotowanej grupy
 * Działają one bezpośrednio z wystawionym na zewnątrz serwami NBP API i weryfikują działanie usługi klienta na żywym orgaznie.
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
        // Sprawdź czy dzisiejsza Tabela A jest poprawnie budowana na zewnątrz, jeśli odpowiedź serwera jest >200
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
        // Okres kilku Dni
        $result = $this->client->getGoldPricesForDateRange('2024-03-01', '2024-03-05');

        $this->assertNotEmpty($result);
        $this->assertObjectHasProperty('date', $result[0]);
        $this->assertObjectHasProperty('price', $result[0]);
    }
}
