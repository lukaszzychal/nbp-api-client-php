# NBP API Client for PHP (>= 8.1)

🌍 **[English](#english-version) | [Polski](#wersja-polska)**

---

<a name="english-version"></a>
## 🇬🇧 English Version

A PHP library executing fast HTTP requests to the open API of the National Bank of Poland (NBP). Built with full compatibility with PHP-FIG standards (PSR-3, PSR-6, PSR-7, PSR-17, PSR-18) using modern Data Transfer Objects (DTOs) instead of raw JSON arrays.

Designed using a strict TDD environment, annotated for integration tests (Classicist Approach), maximum static analysis level (PHPStan: MAX), and compliant with PER-CS 2.0. It features a defensive fallback logic with a Retry Strategy (up to 3 retries on NBP server drops / 500+ status codes) and supports processing via injected PSR logger queues.

### Installation
For proper operation, it requires a set of virtual clients implementing PSR-17 and PSR-18 standards. You can use Guzzle or the Symfony variant with Nyholm.

```bash
composer require lukaszzychal/nbp-api-client-php
composer require symfony/http-client nyholm/psr7 # Example client dependency using Symfony mechanics
```

### Basic Client Usage
The library exposes a powerful classifier for operations with the main instance object `NbpApiClient`:

```php
use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$httpClient = new Psr18Client(HttpClient::create());
$requestFactory = new Psr17Factory();

$client = new NbpApiClient($httpClient, $requestFactory);

// Injecting Cache and Logger
// $client->setLogger($myPsr3Logger);           // PSR-3: info, network errors, warnings
// $client->setCache($myPsr6CacheItemPool, 21600); // Enable PSR-6 cache with 6h TTL
// $client->disableCache();                     // Disable cache at runtime
// See docs/developer_usage_guide.md#5-psr-6-caching for full details
```

### Features
Currently, the client supports:
1. Currency Exchange Tables (`getCurrencyTable`, `getCurrencyTableForDate`, `getCurrencyTableForDateRange`). Argument 1 in tables must indicate the table letter ("A", "B", "C").
2. Gold Prices (`getGoldPrices`, `getGoldPricesForDate`, `getGoldPricesForDateRange`).

All procedures returning a result set, e.g., `$client->getGoldPrices()`, are structured as native models rather than nested arrays:
* The returned record is `LukaszZychal\NbpApiClient\Model\CurrencyTable` or `GoldPrice`.
* Inside the Table, there are promoted attributes and an array holding repeated `CurrencyRate` objects.

### Currency Calculator ("Cross-Rates")
The tool comes with a default pre-built currency calculator using the Polish Zloty (PLN) as the fixed point for NBP standard derivations.
It requires an objective table generated from the classic client instantiated above.

```php
use LukaszZychal\NbpApiClient\Calculator\CurrencyCalculator;

// Assuming `$tables` was fetched by NbpApiClient from Table A...
$calculator = new CurrencyCalculator();
$amount = 1000.0;
$usdAmount = $calculator->convert($amount, 'PLN', 'USD', $tables[0]); 
# Calculates the exchange for 1000 PLN to USD using the NBP reference rate
```
### Framework Integration
For detailed instructions on how to integrate this library with **Laravel** or **Symfony**, please refer to the [Developer Usage Guide](docs/developer_usage_guide.md#6-framework-integration).

For complete information on **PSR-6 caching** (enabling, disabling, TTL, available adapters) see the [Caching section](docs/developer_usage_guide.md#5-psr-6-caching).

### Dev Mode (Integration Tests and Static Analysis)
The system is protected with a full integration suite using PHPUnit. Unit tests (for the calculator) and rigorous integration logic tested via injected Mock `FakeHttpClient` do not hit the external interface outside the designated Contract Tests area, operating mostly on simulated static files.
1. `composer run test` (Unit and integration DTO behavior testing)
2. `composer run test:contract` (Real HTTP call against live NBP framework from Github Actions pipeline to verify contract compliance)
3. `composer run analyze` (PHPStan 8/8)
4. `composer run cs` and `cs:fix` (PER CS-2.0 Control).

---

<a name="wersja-polska"></a>
## 🇵🇱 Wersja Polska

Biblioteka PHP realizująca szybkie zapytania protokołem HTTP do otwartego interfejsu Narodowego Banku Polskiego. Skonstruowania z myślą o zachowaniu pełni kompatybilności ze standardami (PSR-3, PSR-6, PSR-7, PSR-17, PSR-18) z nowoczesnym podejściem wykorzystującym modele DTO bez surowych wyników JSON.

Zaprojektowana w rygorystycznym środowisku TDD i zaanotowana pod kątem testów integracyjnych (Classicist Approach), statycznej analizy typu (PHPStan: MAX) oraz sprowadzona do zgodności z PER-CS 2.0. Posiada obronny mechanizm logiki awaryjnej z Retry Strategy (3 ponowienia w obliczu spadku serwerów NBP API / 500+ status code) jak i obrabianie za pomocą logowania z wstrzykniętymi kolejkami PSR.

### Instalacja
Do poprawnego działania wymaga zestawu wirtualnych klientów w standardzie PSR-17 i PSR-18. Możesz użyć Guzzle lub wariantu Symfony z Nyholm.

```bash
composer require lukaszzychal/nbp-api-client-php
composer require symfony/http-client nyholm/psr7 # Przykład zależności klienckiej z użyciem mechaniki Symfony
```

### Podstawowe Wywołania Klienta
Biblioteka eksponuje potężny klasyfikator dla operacji z głównym obiektem instancyjnym `NbpApiClient`:

```php
use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$httpClient = new Psr18Client(HttpClient::create());
$requestFactory = new Psr17Factory();

$client = new NbpApiClient($httpClient, $requestFactory);

// Wstrzykiwanie Cache'a i Loggera
// $client->setLogger($myPsr3Logger);           // PSR-3: info, błędy sieciowe, ostrzeżenia
// $client->setCache($myPsr6CacheItemPool, 21600); // Cache PSR-6 z TTL 6 godzin
// $client->disableCache();                     // Wyłączenie cache w trakcie działania
// Pełna dokumentacja: docs/developer_usage_guide.md#5-psr-6-caching
```

### Funkcjonalność
Klient na moment dzisiejszy implementuje wsparcie:
1. Tabele kursów Walut (`getCurrencyTable`, `getCurrencyTableForDate`, `getCurrencyTableForDateRange`). Argument 1 w tabelach musi oznaczać literę notowania ("A", "B", "C").
2. Kruszce mrożone / Złoto (`getGoldPrices`, `getGoldPricesForDate`, `getGoldPricesForDateRange`).

Wszystkie procedury oddające zbiór wyników, np `$client->getGoldPrices()`, ustrukturyzowane są jako instancje natywnych modelów bez tablic po tablicach:
* Rekord ze zwrotki to `LukaszZychal\NbpApiClient\Model\CurrencyTable` bądź `GoldPrice`.
* W środku Tabeli występują promowane atrybuty oraz tablica na powielone obiekty `CurrencyRate`.

### Kalkulator Przeliczeń ("Krzyżak")
Narzędzie przychodzi z domyślnym pre-buildowanym kalkulatorem walutowym z podłożem waluty Polskiego Złotego jako punktu stałego dla NBP.
Wymaga wprowadzenia obiektywnej tabli wygenerowanej z klasyka klienta powołanego wyżej.

```php
use LukaszZychal\NbpApiClient\Calculator\CurrencyCalculator;

// Zakładając że zmienną `$tables` wyznaczono przez NbpApiClient z tabeli A...
$calculator = new CurrencyCalculator();
$amount = 1000.0;
$usdAmount = $calculator->convert($amount, 'PLN', 'USD', $tables[0]); 
# Oblicza kurs 1000 PLN do dolarów korzystając z notowania wyjściowego NBP
```
### Integracja z Frameworkami
Szczegółowe instrukcje dotyczące integracji z **Laravel** oraz **Symfony** znajdziesz w dokumencie [Developer Usage Guide](docs/developer_usage_guide.md#6-framework-integration).

Pełna dokumentacja **cache PSR-6** (włączanie, wyłączanie, TTL, dostępne adaptery) dostępna w sekcji [Caching](docs/developer_usage_guide.md#5-psr-6-caching).

### Tryb Dev (Testy integracji i analiza Statyczna)
System chroniony pełną szatą integracji pod PHPUnit. Testy jednostkowe (kalkulator), jak i rygorystyczne integracje logiki bazy testowane przez wstrzyknięty Mock FakeHttpClient nie uderzają do interfejsu zewnętrznego w testach poza wyznaczonym rejonem kontrolnym Contract Testów, a operują na statycznych plikach symulowanych.
1. `composer run test` (Jednostkowe i integracyjne testowanie zachowania DTO)
2. `composer run test:contract` (Prawdziwe wywołanie HTTP z żywym żebrowaniem NBP z pętli Github Actions dla sprawdzania zgodności z umową)
3. `composer run analyze` (PHPStan 8/8)
4. `composer run cs` oraz `cs:fix` (Kontrola PER CS-2.0).

---
License / Licencja: MIT. Autor: *NbpApiClient Developer*.
