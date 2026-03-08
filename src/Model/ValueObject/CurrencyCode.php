<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model\ValueObject;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */
class CurrencyCode
{
    private string $code;

    public function __construct(string $code)
    {
        $code = strtoupper(trim($code));
        
        // Zabezpieczenie na puste ciągi z braku danych lub dziwne kody krótsze niż 3
        if ($code !== '' && !preg_match('/^[A-Z]{3}$/', $code)) {
            throw new \InvalidArgumentException(sprintf('Invalid Currency Code format "%s".', $code));
        }

        $this->code = $code;
    }

    public function getValue(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
