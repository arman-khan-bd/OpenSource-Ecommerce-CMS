<?php

declare(strict_types=1);

use App\Service\HookManager;

if (!function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        HookManager::getInstance()->addAction($hook, $callback, $priority, $acceptedArgs);
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hook, ...$args): void
    {
        HookManager::getInstance()->doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        HookManager::getInstance()->addFilter($hook, $callback, $priority, $acceptedArgs);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, ...$args): mixed
    {
        return HookManager::getInstance()->applyFilter($hook, $value, ...$args);
    }
}
