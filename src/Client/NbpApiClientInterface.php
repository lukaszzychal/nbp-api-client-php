<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Client;

use LukaszZychal\NbpApiClient\Model\CurrencyTable;
use LukaszZychal\NbpApiClient\Model\GoldPrice;
use Psr\Cache\CacheItemPoolInterface;

interface NbpApiClientInterface
{
    /**
     * Setter do wstrzykiwania opcjonalnej pamięci podręcznej (PSR-6)
     */
    public function setCache(CacheItemPoolInterface $cache, int $ttlSeconds = 3600): void;

    /**
     * Pobiera aktualną tabelę kursów.
     * @return CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTable(string $tableType): array;

    /**
     * @return CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTableForDate(string $tableType, string $date): array;

    /**
     * @return \LukaszZychal\NbpApiClient\Model\CurrencyTable[]
     * @throws \RuntimeException
     */
    public function getCurrencyTableForDateRange(string $tableType, string $startDate, string $endDate): array;

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPrices(): array;

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPricesForDate(string $date): array;

    /**
     * @return \LukaszZychal\NbpApiClient\Model\GoldPrice[]
     * @throws \RuntimeException
     */
    public function getGoldPricesForDateRange(string $startDate, string $endDate): array;
}
