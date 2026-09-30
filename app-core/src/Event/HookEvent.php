<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event wrapper for action hooks.
 */
class HookEvent extends Event
{
    private string $action;
    private array $arguments;

    public function __construct(string $action, array $arguments = [])
    {
        $this->action = $action;
        $this->arguments = $arguments;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
