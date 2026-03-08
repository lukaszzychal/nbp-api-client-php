<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use LukaszZychal\NbpApiClient\Calculator\CurrencyCalculator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

// 1. [PL] Inicjalizacja zależności PSR-18 i PSR-17 / [EN] Initialization of PSR-18 and PSR-17 dependencies
// [PL] Wymagane pakiety: symfony/http-client, nyholm/psr7 (lub inne wspierające te PSR np. guzzlehttp)
// [EN] Required packages: symfony/http-client, nyholm/psr7 (or others supporting these PSRs, e.g., guzzlehttp)
$httpClient = new Psr18Client(HttpClient::create());
$requestFactory = new Psr17Factory();

$client = new NbpApiClient($httpClient, $requestFactory);

// [PL] Opcjonalne: Wstrzykiwanie Cache PSR-6 (CacheItemPoolInterface) i Loggera PSR-3 (LoggerInterface)
// [EN] Optional: Injecting Cache PSR-6 (CacheItemPoolInterface) and Logger PSR-3 (LoggerInterface)
// $client->setLogger($logger);
// $client->setCache($cacheItemPool);

try {
    // 2. [PL] Pobieranie Tabel Walut i Ceny Złota / [EN] Fetching Currency Tables and Gold Prices
    echo "[PL] Pobieranie tabeli A (dzisiejszy kurs średni)...\n";
    echo "[EN] Fetching Table A (today's average exchange rate)...\n";
    $tables = $client->getCurrencyTable('A');
    
    if (!empty($tables)) {
        $recentTable = $tables[0];
        echo sprintf("[PL] Tabela z dnia: %s. Posiada %d walut.\n", $recentTable->effectiveDate, count($recentTable->rates));
        echo sprintf("[EN] Table from date: %s. Contains %d currencies.\n", $recentTable->effectiveDate, count($recentTable->rates));
        
        // 3. [PL] Kalkulator walutowy krzyżowy / [EN] Cross-currency calculator
        $calculator = new CurrencyCalculator();
        $amountPln = 1000.0;
        $usdAmount = $calculator->convert($amountPln, 'PLN', 'USD', $recentTable);
        echo sprintf("[PL/EN] %.2f PLN => ok/approx. %.2f USD\n", $amountPln, $usdAmount);
    }
    echo "\n-------------------------\n";

    echo "[PL] Pobieranie tabeli B (kursy średnie walut egzotycznych)...\n";
    echo "[EN] Fetching Table B (average rates for exotic currencies)...\n";
    $tablesB = $client->getCurrencyTable('B');
    if (!empty($tablesB)) {
        $recentTableB = $tablesB[0];
        echo sprintf("[PL] Tabela B z dnia: %s. Posiada %d walut.\n", $recentTableB->effectiveDate, count($recentTableB->rates));
        echo sprintf("[EN] Table B from date: %s. Contains %d currencies.\n", $recentTableB->effectiveDate, count($recentTableB->rates));
    }

    echo "\n-------------------------\n";

    echo "[PL] Pobieranie tabeli C (kursy kupna i sprzedaży)...\n";
    echo "[EN] Fetching Table C (buy and sell rates)...\n";
    $tablesC = $client->getCurrencyTable('C');
    if (!empty($tablesC)) {
        $recentTableC = $tablesC[0];
        echo sprintf("[PL] Tabela C z dnia: %s. Posiada %d walut.\n", $recentTableC->effectiveDate, count($recentTableC->rates));
        echo sprintf("[EN] Table C from date: %s. Contains %d currencies.\n", $recentTableC->effectiveDate, count($recentTableC->rates));
        
        if (count($recentTableC->rates) > 0) {
            $firstC = $recentTableC->rates[0];
            echo sprintf("[PL] Przykład z C: %s (%s) - Kupno (Bid): %s | Sprzedaż (Ask): %s\n", 
                $firstC->currency, 
                (string) $firstC->code,
                $firstC->buyRate?->getValue() ?? 'brak',
                $firstC->sellRate?->getValue() ?? 'brak'
            );
            echo sprintf("[EN] Example from C: %s (%s) - Buy (Bid): %s | Sell (Ask): %s\n", 
                $firstC->currency, 
                (string) $firstC->code,
                $firstC->buyRate?->getValue() ?? 'N/A',
                $firstC->sellRate?->getValue() ?? 'N/A'
            );
        }
    }

    echo "\n-------------------------\n";

    echo "[PL] Pobieranie notowań złota (historyczne)...\n";
    echo "[EN] Fetching historical gold prices...\n";
    $golds = $client->getGoldPricesForDateRange('2024-01-01', '2024-01-05');
    
    foreach ($golds as $gold) {
        echo sprintf("[PL] Dnia %s za kruszec liczono %s PLN / g\n", (string) $gold->date, $gold->price->getValue());
        echo sprintf("[EN] On %s the bullion price was %s PLN / g\n", (string) $gold->date, $gold->price->getValue());
    }

} catch (\RuntimeException $e) {
    echo "[PL] Błąd komunikacji z API NBP: " . $e->getMessage() . "\n";
    echo "[EN] NBP API communication error: " . $e->getMessage() . "\n";
} catch (\InvalidArgumentException $e) {
    echo "[PL] Błąd kalkulatora: " . $e->getMessage() . "\n";
    echo "[EN] Calculator error: " . $e->getMessage() . "\n";
}
