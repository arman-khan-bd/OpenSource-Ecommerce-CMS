<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event wrapper for filter hooks.
 */
class FilterEvent extends Event
{
    private string $filter;
    private mixed $value;
    private array $arguments;

    public function __construct(string $filter, mixed $value, array $arguments = [])
    {
        $this->filter = $filter;
        $this->value = $value;
        $this->arguments = $arguments;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
