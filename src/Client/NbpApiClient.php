<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Client;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Cache\CacheItemPoolInterface;
use LukaszZychal\NbpApiClient\Model\CurrencyTable;
use LukaszZychal\NbpApiClient\Model\CurrencyRate;
use LukaszZychal\NbpApiClient\Model\GoldPrice;
use LukaszZychal\NbpApiClient\Model\ValueObject\CurrencyCode;
use LukaszZychal\NbpApiClient\Model\ValueObject\DateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\ExchangeRateValue;
use LukaszZychal\NbpApiClient\Model\ValueObject\TableNumber;

class NbpApiClient implements NbpApiClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const BASE_URL = 'http://api.nbp.pl/api/';
    
    /**
     * Maksymalna liczba powtórzeń po błędzie z serwera 5xx
     */
    private const MAX_RETRIES = 3;

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private ?CacheItemPoolInterface $cache = null;
    private int $cacheTtl = 3600;
    
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Wstrzykuje opcjonalny system cache PSR-6
     */
    public function setCache(CacheItemPoolInterface $cache, int $ttlSeconds = 3600): void
    {
        $this->cache = $cache;
        $this->cacheTtl = $ttlSeconds;
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTable(string $tableType): array
    {
        $endpoint = sprintf('exchangerates/tables/%s/?format=json', $tableType);
        return $this->fetchAndParseTables($endpoint);
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTableForDate(string $tableType, string $date): array
    {
        $endpoint = sprintf('exchangerates/tables/%s/%s/?format=json', $tableType, $date);
        return $this->fetchAndParseTables($endpoint);
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTableForDateRange(string $tableType, string $startDate, string $endDate): array
    {
        $endpoint = sprintf('exchangerates/tables/%s/%s/%s/?format=json', $tableType, $startDate, $endDate);
        return $this->fetchAndParseTables($endpoint);
    }

    /**
     * Wewnętrzna metoda wykonująca żądanie z obsługą ponawiania dla błędów 5xx oraz opcjonalnym Cache PSR-6.
     * Wykorzystuje zaimplementowanegy Loggera z LoggerAwareTrait, o ile wstrzyknięty.
     * 
     * @return array<mixed>
     * @throws \RuntimeException
     */
    private function fetchAndParse(string $endpoint): array
    {
        $cacheKey = 'nbp_api_cache_' . md5($endpoint);

        if ($this->cache !== null) {
            try {
                $cacheItem = $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    if ($this->logger !== null) {
                        $this->logger->info(sprintf('NbpApiClient: Returning info from Cache for %s', $endpoint));
                    }
                    /** @var array<mixed> $cachedData */
                    $cachedData = $cacheItem->get();
                    return $cachedData;
                }
            } catch (\Psr\Cache\InvalidArgumentException $e) {
                if ($this->logger !== null) {
                    $this->logger->warning('NbpApiClient: PSR-6 InvalidArgumentException: ' . $e->getMessage());
                }
            }
        }

        $request = $this->requestFactory->createRequest('GET', self::BASE_URL . $endpoint);

        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            $attempt++;
            
            try {
                if ($this->logger !== null) {
                    $this->logger->info(sprintf('NbpApiClient (try: %d): Executing GET request to %s', $attempt, self::BASE_URL . $endpoint));
                }

                $response = $this->httpClient->sendRequest($request);
                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 300) {
                    $json = (string) $response->getBody();
                    $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

                    if (!is_array($data)) {
                        throw new \RuntimeException('Invalid JSON response format');
                    }

                    if ($this->cache !== null && isset($cacheItem)) {
                        $cacheItem->set($data);
                        $cacheItem->expiresAfter($this->cacheTtl);
                        $this->cache->save($cacheItem);
                    }

                    return $data;
                }

                if ($statusCode >= 500) {
                    if ($this->logger !== null) {
                        $this->logger->warning(sprintf('NbpApiClient (try: %d): Re-trying after status code %d on %s', $attempt, $statusCode, $endpoint));
                    }
                    
                    usleep((int) (100000 * $attempt)); // Backoff 100ms * próba
                    continue;
                }

                throw new \RuntimeException(sprintf('API Error. Status Code: %d. Body: %s', $statusCode, (string) $response->getBody()));
            } catch (ClientExceptionInterface $e) {
                // Wyjątki sieciowe HTTPClient
                $lastException = $e;
                
                if ($this->logger !== null) {
                    $this->logger->error(sprintf('NbpApiClient (try: %d): ClientException -> %s', $attempt, $e->getMessage()));
                }
                
                usleep((int) (100000 * $attempt));
                continue;
            } catch (\JsonException $e) {
                if ($this->logger !== null) {
                    $this->logger->error('NbpApiClient: JSON format error');
                }
                throw new \RuntimeException('JSON Decode Error: ' . $e->getMessage(), 0, $e);
            }
        }

        if ($this->logger !== null) {
            $this->logger->critical(sprintf('NbpApiClient: Abandoning after %d attempts for %s', self::MAX_RETRIES, $endpoint));
        }

        throw new \RuntimeException(
            sprintf('Failed to communicate with NBP API after %d retries', self::MAX_RETRIES),
            0,
            $lastException
        );
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\CurrencyTable[]
     * @throws \RuntimeException
     */
    private function fetchAndParseTables(string $endpoint): array
    {
        $data = $this->fetchAndParse($endpoint);

        $tables = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $rates = [];
            if (isset($item['rates']) && is_array($item['rates'])) {
                foreach ($item['rates'] as $rateItem) {
                    if (is_array($rateItem)) {
                        $currency = $this->getStringValue($rateItem, 'currency');
                        $codeStr = $this->getStringValue($rateItem, 'code');
                        
                        $midStr = $this->getNumericStringValueOrNull($rateItem, 'mid');
                        $bidStr = $this->getNumericStringValueOrNull($rateItem, 'bid');
                        $askStr = $this->getNumericStringValueOrNull($rateItem, 'ask');

                        $midVal = $midStr !== null ? new ExchangeRateValue($midStr) : null;
                        $bidVal = $bidStr !== null ? new ExchangeRateValue($bidStr) : null;
                        $askVal = $askStr !== null ? new ExchangeRateValue($askStr) : null;

                        $rates[] = new CurrencyRate(
                            $currency,
                            new CurrencyCode($codeStr),
                            $midVal,
                            $bidVal,
                            $askVal
                        );
                    }
                }
            }

            $tableStr = $this->getStringValue($item, 'table');
            $noStr = $this->getStringValue($item, 'no');
            $effectiveDateStr = $this->getStringValue($item, 'effectiveDate');

            $tables[] = new CurrencyTable(
                $tableStr,
                new TableNumber($noStr),
                new DateValue($effectiveDateStr),
                $rates
            );
        }

        if (count($tables) === 0) {
            throw new \RuntimeException('No data found in NBP API response');
        }

        return $tables;
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPrices(): array
    {
        $endpoint = 'cenyzlota/?format=json';
        return $this->fetchAndParseGoldPrices($endpoint);
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPricesForDate(string $date): array
    {
        $endpoint = sprintf('cenyzlota/%s/?format=json', $date);
        return $this->fetchAndParseGoldPrices($endpoint);
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPricesForDateRange(string $startDate, string $endDate): array
    {
        $endpoint = sprintf('cenyzlota/%s/%s/?format=json', $startDate, $endDate);
        return $this->fetchAndParseGoldPrices($endpoint);
    }

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    private function fetchAndParseGoldPrices(string $endpoint): array
    {
        $data = $this->fetchAndParse($endpoint);

        $prices = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $dateStr = $this->getStringValue($item, 'data');
            $priceString = $this->getNumericStringValue($item, 'cena');

            $prices[] = new GoldPrice(
                new DateValue($dateStr),
                new ExchangeRateValue($priceString)
            );
        }

        if (count($prices) === 0) {
            throw new \RuntimeException('No data found in NBP API response for gold');
        }

        return $prices;
    }

    /**
     * Zwraca wartość tekstową z tablicy JSON, lub pusty string jako the fallback.
     * Metoda wspierająca koncepcję Intention-Revealing (Self-Documenting Code).
     *
     * @param array<mixed> $data
     */
    private function getStringValue(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : '';
    }

    /**
     * Zwraca wartość rzutowaną prosto na numeryczny the string, chroniąc procedury bcmath.
     *
     * @param array<mixed> $data
     */
    private function getNumericStringValue(array $data, string $key): string
    {
        return $this->getNumericStringValueOrNull($data, $key) ?? '0';
    }

    /**
     * Zwraca wartość rzutowaną bezpiecznie na numeric-string, powstrzymując
     * notację naukową przy rzutowaniu małych floatów. Zwraca null jeśli klucz nie istnieje.
     *
     * @param array<mixed> $data
     */
    private function getNumericStringValueOrNull(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        $val = $data[$key];

        if (is_float($val)) {
            // Wymuś 8 miejsc po przecinku by zapobiec naukowej notacji "4.1E-5" i utnij nadmiar zer.
            $str = sprintf('%.8F', $val);
            if (str_contains($str, '.')) {
                $str = rtrim($str, '0');
                $str = rtrim($str, '.');
            }
            return $str;
        }

        return (string) $val;
    }
}
