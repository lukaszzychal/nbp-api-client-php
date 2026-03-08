<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Tests\Doubles;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

class FakeHttpClient implements ClientInterface
{
    /** @var array<string, ResponseInterface> */
    private array $responses = [];

    private ?Exception $throwException = null;

    public function addResponse(string $url, ResponseInterface $response): void
    {
        $this->responses[$url] = $response;
    }

    public function throwExceptionOnRequest(Exception $exception): void
    {
        $this->throwException = $exception;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->throwException !== null) {
            throw $this->throwException;
        }

        $url = (string) $request->getUri();

        if (isset($this->responses[$url])) {
            return $this->responses[$url];
        }

        throw new \RuntimeException("Unexpected request to URL: " . $url);
    }
}
