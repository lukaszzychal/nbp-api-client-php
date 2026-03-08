<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Unit\Client;

use LukaszZychal\NbpApiClient\Client\NbpApiClient;
use LukaszZychal\NbpApiClient\Client\NbpApiClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class NbpApiClientTest extends TestCase
{
    public function testIsInstantiableWithHttpClient(): void
    {
        $httpClientMock = $this->createMock(ClientInterface::class);
        $requestFactoryMock = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        
        $client = new NbpApiClient($httpClientMock, $requestFactoryMock);
        
        $this->assertInstanceOf(NbpApiClient::class, $client);
    }

    public function testRetriesOnServerError(): void
    {
        $httpClientMock = $this->createMock(ClientInterface::class);
        $requestFactoryMock = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);

        $requestMock = $this->createMock(\Psr\Http\Message\RequestInterface::class);

        $requestFactoryMock->expects($this->once())
            ->method('createRequest')
            ->willReturn($requestMock);

        $errorResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(503);

        $successResponse = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $successResponse->method('getStatusCode')->willReturn(200);
        
        $streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $streamMock->method('__toString')->willReturn('[{"table":"A","no":"123","effectiveDate":"2023-01-01","rates":[]}]');
        $successResponse->method('getBody')->willReturn($streamMock);

        $httpClientMock->expects($this->exactly(3))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                $errorResponse,
                $errorResponse,
                $successResponse
            );

        $client = new NbpApiClient($httpClientMock, $requestFactoryMock);
        $result = $client->getCurrencyTable('A');

        $this->assertCount(1, $result);
    }

    public function testCacheHitPreventsHttpRequest(): void
    {
        $httpClientMock = $this->createMock(ClientInterface::class);
        $requestFactoryMock = $this->createMock(\Psr\Http\Message\RequestFactoryInterface::class);
        $cachePoolMock = $this->createMock(\Psr\Cache\CacheItemPoolInterface::class);
        $cacheItemMock = $this->createMock(\Psr\Cache\CacheItemInterface::class);

        $cachedData = [['table' => 'A', 'no' => 'FROM_CACHE', 'effectiveDate' => '2020-01-01', 'rates' => []]];

        $cacheItemMock->method('isHit')->willReturn(true);
        $cacheItemMock->method('get')->willReturn($cachedData);

        $cachePoolMock->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItemMock);

        $httpClientMock->expects($this->never())->method('sendRequest');

        $client = new NbpApiClient($httpClientMock, $requestFactoryMock);
        $client->setCache($cachePoolMock);

        $result = $client->getCurrencyTable('A');

        $this->assertCount(1, $result);
        $this->assertSame('FROM_CACHE', $result[0]->tableNumber->getValue());
    }
}
