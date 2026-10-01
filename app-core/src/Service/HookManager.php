<?php

declare(strict_types=1);

namespace App\Service;

use App\Event\FilterEvent;
use App\Event\HookEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Extensible WordPress-style Hook and Filter Engine with Symfony EventDispatcher integration.
 */
class HookManager
{
    private static ?self $instance = null;
    private array $actions = [];
    private array $filters = [];
    private array $executionLogs = [];
    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(?EventDispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        if (self::$instance !== null) {
            $this->actions = self::$instance->actions;
            $this->filters = self::$instance->filters;
        }
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register an action callback.
     */
    public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $id = $this->buildCallbackId($callback);
        $this->actions[$hook][$priority][$id] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
    }

    /**
     * Execute registered callbacks for an action.
     */
    public function doAction(string $hook, ...$args): void
    {
        $startTime = microtime(true);
        $callCount = 0;

        if (isset($this->actions[$hook])) {
            ksort($this->actions[$hook]);
            foreach ($this->actions[$hook] as $priority => $callbacks) {
                foreach ($callbacks as $item) {
                    $callback = $item['callback'];
                    $acceptedArgs = $item['accepted_args'];
                    if ($acceptedArgs < count($args)) {
                        try {
                            if (is_string($callback) && function_exists($callback)) {
                                $ref = new \ReflectionFunction($callback);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            } elseif ($callback instanceof \Closure) {
                                $ref = new \ReflectionFunction($callback);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            } elseif (is_array($callback) && method_exists($callback[0], $callback[1])) {
                                $ref = new \ReflectionMethod($callback[0], $callback[1]);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            }
                        } catch (\Throwable) {}
                    }
                    $passedArgs = array_slice($args, 0, $acceptedArgs);
                    call_user_func_array($callback, $passedArgs);
                    $callCount++;
                }
            }
        }

        // Also dispatch to Symfony EventDispatcher if available
        if ($this->dispatcher !== null) {
            $event = new HookEvent($hook, $args);
            $this->dispatcher->dispatch($event, 'cms.action.' . $hook);
        }

        $duration = microtime(true) - $startTime;
        $this->logExecution('action', $hook, $callCount, $duration);
    }

    /**
     * Register a filter callback.
     */
    public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $id = $this->buildCallbackId($callback);
        $this->filters[$hook][$priority][$id] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
    }

    /**
     * Apply registered filters to modify a value.
     */
    public function applyFilter(string $hook, mixed $value, ...$args): mixed
    {
        $startTime = microtime(true);
        $callCount = 0;
        $currentValue = $value;

        if (isset($this->filters[$hook])) {
            ksort($this->filters[$hook]);
            foreach ($this->filters[$hook] as $priority => $callbacks) {
                foreach ($callbacks as $item) {
                    $callback = $item['callback'];
                    $acceptedArgs = $item['accepted_args'];
                    $allArgs = array_merge([$currentValue], $args);
                    if ($acceptedArgs < count($allArgs)) {
                        try {
                            if (is_string($callback) && function_exists($callback)) {
                                $ref = new \ReflectionFunction($callback);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            } elseif ($callback instanceof \Closure) {
                                $ref = new \ReflectionFunction($callback);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            } elseif (is_array($callback) && method_exists($callback[0], $callback[1])) {
                                $ref = new \ReflectionMethod($callback[0], $callback[1]);
                                $acceptedArgs = max($acceptedArgs, $ref->getNumberOfRequiredParameters());
                            }
                        } catch (\Throwable) {}
                    }
                    $passedArgs = array_slice($allArgs, 0, $acceptedArgs);
                    $currentValue = call_user_func_array($callback, $passedArgs);
                    $callCount++;
                }
            }
        }

        // Also dispatch to Symfony EventDispatcher if available
        if ($this->dispatcher !== null) {
            $event = new FilterEvent($hook, $currentValue, $args);
            $this->dispatcher->dispatch($event, 'cms.filter.' . $hook);
            $currentValue = $event->getValue();
        }

        $duration = microtime(true) - $startTime;
        $this->logExecution('filter', $hook, $callCount, $duration);

        return $currentValue;
    }

    public function hasAction(string $hook): bool
    {
        return !empty($this->actions[$hook]);
    }

    public function hasFilter(string $hook): bool
    {
        return !empty($this->filters[$hook]);
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        $id = $this->buildCallbackId($callback);
        if (isset($this->actions[$hook][$priority][$id])) {
            unset($this->actions[$hook][$priority][$id]);
            return true;
        }
        return false;
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): bool
    {
        $id = $this->buildCallbackId($callback);
        if (isset($this->filters[$hook][$priority][$id])) {
            unset($this->filters[$hook][$priority][$id]);
            return true;
        }
        return false;
    }

    /**
     * Summary of all registered hooks for debugging and admin inspector.
     */
    public function getInspectorSummary(): array
    {
        $result = [
            'actions' => [],
            'filters' => [],
            'total_actions' => 0,
            'total_filters' => 0,
            'execution_history' => array_slice(array_reverse($this->executionLogs), 0, 50),
        ];

        foreach ($this->actions as $hook => $priorities) {
            $count = 0;
            $items = [];
            foreach ($priorities as $p => $cbs) {
                foreach ($cbs as $id => $cb) {
                    $count++;
                    $items[] = ['priority' => $p, 'id' => $id, 'accepted_args' => $cb['accepted_args']];
                }
            }
            $result['actions'][$hook] = [
                'name' => $hook,
                'count' => $count,
                'callbacks' => $items,
            ];
            $result['total_actions'] += $count;
        }

        foreach ($this->filters as $hook => $priorities) {
            $count = 0;
            $items = [];
            foreach ($priorities as $p => $cbs) {
                foreach ($cbs as $id => $cb) {
                    $count++;
                    $items[] = ['priority' => $p, 'id' => $id, 'accepted_args' => $cb['accepted_args']];
                }
            }
            $result['filters'][$hook] = [
                'name' => $hook,
                'count' => $count,
                'callbacks' => $items,
            ];
            $result['total_filters'] += $count;
        }

        return $result;
    }

    private function logExecution(string $type, string $hook, int $callCount, float $duration): void
    {
        $this->executionLogs[] = [
            'type' => $type,
            'hook' => $hook,
            'callbacks_run' => $callCount,
            'duration_ms' => round($duration * 1000, 3),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (count($this->executionLogs) > 200) {
            array_shift($this->executionLogs);
        }
    }

    private function buildCallbackId(callable $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }
        if (is_array($callback)) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            return $class . '::' . $callback[1];
        }
        if ($callback instanceof \Closure) {
            return 'Closure_' . spl_object_hash($callback);
        }
        return 'callable_' . spl_object_hash((object) $callback);
    }
}
