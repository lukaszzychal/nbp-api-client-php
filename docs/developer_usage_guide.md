# Developer Usage Guide - NBP API Client

This guide explains how to effectively use the `NbpApiClient` library in your PHP projects.

## Core Concepts

The library follows PSR standards and uses Value Objects to ensure data integrity and precision. 

### 1. Initialization

You need a PSR-18 HTTP Client and a PSR-17 Request Factory.

```php
use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

$httpClient = new Psr18Client(HttpClient::create());
$requestFactory = new Psr17Factory();
$client = new NbpApiClient($httpClient, $requestFactory);
```

### 2. Working with Value Objects

Instead of raw floats or strings, the library returns specialized Value Objects:

- **ExchangeRateValue**: Holds monetary rates. Use `getValue()` to get the string representation.
- **CurrencyCode**: Represents 3-letter currency codes (e.g., "USD").
- **DateValue**: Represents dates in `Y-m-d` format.

**Why Value Objects?**
- **Safety**: They validate input in the constructor.
- **Precision**: Monetary values are handled as strings to avoid floating-point errors (used with `bcmath` internally).

### 3. Handling Different Tables

- **Table A**: Daily average rates of major currencies.
- **Table B**: Weekly average rates of exotic currencies.
- **Table C**: Daily buy (bid) and sell (ask) rates.

```php
// Table A (Average)
$tableA = $client->getCurrencyTable('A')[0];
$rate = $tableA->rates[0];
echo $rate->averageRate->getValue(); 

// Table C (Buy/Sell)
$tableC = $client->getCurrencyTable('C')[0];
$rateC = $tableC->rates[0];
echo "Buy: " . $rateC->buyRate->getValue();
echo "Sell: " . $rateC->sellRate->getValue();
```

### 4. Currency Calculator

The `CurrencyCalculator` uses Table A or B data to perform cross-currency conversions.

```php
use LukaszZychal\NbpApiClient\Calculator\CurrencyCalculator;

$calculator = new CurrencyCalculator();
// Convert 100 USD to PLN using the provided table
$plnAmount = $calculator->convert(100, 'USD', 'PLN', $tableA);
```

> [!IMPORTANT]
> The calculator only works with tables that provide an `averageRate` (Table A and B). It will throw an `InvalidArgumentException` if you try to use it with Table C.

### 5. Framework Integration

The library can be easily integrated with modern frameworks using Dependency Injection.

#### Symfony

If you are using `symfony/http-client` and `nyholm/psr7`, the PSR interfaces are often already bound. You can register the client in your `config/services.yaml`:

```yaml
services:
    LukaszZychal\NbpApiClient\Client\NbpApiClient:
        arguments:
            $httpClient: '@Psr\Http\Client\ClientInterface'
            $requestFactory: '@Psr\Http\Message\RequestFactoryInterface'
        public: true
```

#### Laravel

In your `app/Providers/AppServiceProvider.php`, you can bind the client to the container:

```php
use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

public function register()
{
    $this->app->singleton(NbpApiClient::class, function ($app) {
        return new NbpApiClient(
            $app->make(ClientInterface::class),
            $app->make(RequestFactoryInterface::class)
        );
    });
}
```

