<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model\ValueObject;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */
class TableNumber
{
    private string $number;

    public function __construct(string $number)
    {
        $this->number = trim($number);
    }

    public function getValue(): string
    {
        return $this->number;
    }

    public function __toString(): string
    {
        return $this->number;
    }
}
