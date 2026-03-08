<?php

declare(strict_types=1);

namespace LukaszZychal\NbpApiClient\Model\ValueObject;

/**
 * @author Łukasz Zychal <lukasz.zychal.dev@gmail.com>
 */
class DateValue
{
    private string $date;

    private \DateTimeImmutable $dateObj;

    public function __construct(string $date)
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException(sprintf('Invalid date format or non-existent day "%s". Expected valid YYYY-MM-DD.', $date));
        }

        $this->date = $date;
        $this->dateObj = $parsed;
    }

    public function getValue(): string
    {
        return $this->date;
    }

    public function __toString(): string
    {
        return $this->date;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->dateObj;
    }
}
