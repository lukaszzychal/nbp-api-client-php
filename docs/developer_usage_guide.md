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

### 5. PSR-6 Caching

Caching is **disabled by default**. Every call to the API goes directly to the NBP servers unless you explicitly inject a PSR-6 cache pool.

#### Enabling Cache

Call `setCache()` after creating the client. The second argument is the TTL in seconds (default: `3600` — 1 hour):

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new FilesystemAdapter();

$client->setCache($cache);           // TTL = 3600 s (1 hour, default)
$client->setCache($cache, 1800);     // TTL = 1800 s (30 minutes)
$client->setCache($cache, 86400);    // TTL = 86400 s (24 hours)
```

> [!TIP]
> NBP publishes new exchange rate tables once a day (usually in the early afternoon on working days). A TTL of `21600` (6 hours) or `86400` (24 hours) is a good balance between freshness and performance.

#### How the Cache Works Internally

Each unique API endpoint URL is hashed with `md5()` and used as a cache key:

```
nbp_api_cache_<md5(endpoint)>
```

On every request:
1. If a valid cache item exists (`isHit() === true`) → return cached data immediately, **no HTTP request is made**.
2. If cache is cold or expired → execute HTTP request, store result in cache with the configured TTL, return data.

#### Cache Key Examples

| Method call | Cache key (prefix + md5) |
|---|---|
| `getCurrencyTable('A')` | `nbp_api_cache_<md5('exchangerates/tables/A/?format=json')>` |
| `getCurrencyTableForDate('A', '2026-03-01')` | `nbp_api_cache_<md5('exchangerates/tables/A/2026-03-01/?format=json')>` |
| `getGoldPrices()` | `nbp_api_cache_<md5('cenyzlota/?format=json')>` |

Each combination of table type + date has its own independent cache entry.

#### Disabling Cache at Runtime

Use `disableCache()` to stop using cache for all subsequent requests without destroying the client instance:

```php
$client->setCache($cache, 3600); // cache is now active

// ... some requests hit the cache ...

$client->disableCache(); // cache is now off — next requests go directly to NBP API

// ... fresh data fetched directly ...

$client->setCache($cache, 3600); // re-enable if needed
```

#### Available PSR-6 Adapters

Any library implementing `Psr\Cache\CacheItemPoolInterface` will work:

| Package | Adapter class |
|---|---|
| `symfony/cache` | `FilesystemAdapter`, `RedisAdapter`, `MemcachedAdapter`, `ArrayAdapter` |
| `cache/filesystem-adapter` | `FilesystemCachePool` |
| Laravel | `Illuminate\Cache\Repository` (via bridge) |

### 6. Framework Integration

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

