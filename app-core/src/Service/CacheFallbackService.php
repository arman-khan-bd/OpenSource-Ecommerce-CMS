<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * High-performance Cache service with automatic Redis to Filesystem fallback for cPanel compatibility.
 */
class CacheFallbackService
{
    private CacheItemPoolInterface $adapter;
    private string $activeDriver = 'filesystem';
    private string $cacheDirectory;
    private array $driverDetails = [];
    private ?string $redisDsn = null;

    public function __construct(string $projectDir)
    {
        $this->cacheDirectory = $projectDir . '/var/cache/app_cache';
        $this->redisDsn = $_ENV['REDIS_URL'] ?? null;
        $this->initializeCache();
    }

    /**
     * Initializes Redis if accessible, otherwise falls back gracefully to Filesystem.
     */
    private function initializeCache(): void
    {
        $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $redisPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
        $redisPass = $_ENV['REDIS_PASSWORD'] ?? null;

        // Try Redis if DSN or Host is configured
        if (!empty($this->redisDsn) || (!empty($_ENV['REDIS_HOST']) && $_ENV['REDIS_HOST'] !== 'none')) {
            try {
                $test = $this->testRedisConnection($redisHost, $redisPort, $redisPass);
                if ($test['success']) {
                    if (!empty($this->redisDsn)) {
                        $client = RedisAdapter::createConnection($this->redisDsn);
                    } else {
                        $dsn = 'redis://' . ($redisPass ? ':' . urlencode($redisPass) . '@' : '') . $redisHost . ':' . $redisPort;
                        $client = RedisAdapter::createConnection($dsn);
                    }
                    $this->adapter = new RedisAdapter($client, 'cms_', 3600);
                    $this->activeDriver = 'redis';
                    $this->driverDetails = [
                        'driver' => 'redis',
                        'host' => $redisHost,
                        'port' => $redisPort,
                        'latency_ms' => $test['latency_ms'],
                        'status' => 'online',
                    ];
                    return;
                }
            } catch (\Throwable $e) {
                // Ignore exception and fall back to filesystem
            }
        }

        // Graceful fallback to FilesystemAdapter
        $this->adapter = new FilesystemAdapter('cms', 3600, $this->cacheDirectory);
        $this->activeDriver = 'filesystem';
        $this->driverDetails = [
            'driver' => 'filesystem',
            'directory' => $this->cacheDirectory,
            'status' => 'active (Redis fallback)',
            'writable' => is_writable(dirname($this->cacheDirectory)),
        ];
    }

    /**
     * Retrieve an item from cache, or compute and store it if missing.
     */
    public function get(string $key, ?callable $callback = null, int $ttl = 3600): mixed
    {
        $item = $this->adapter->getItem($this->sanitizeKey($key));

        if (!$item->isHit()) {
            if ($callback === null) {
                return null;
            }
            $value = $callback($item);
            $item->set($value);
            $item->expiresAfter($ttl);
            $this->adapter->save($item);
            return $value;
        }

        return $item->get();
    }

    /**
     * Directly store an item in cache.
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $item = $this->adapter->getItem($this->sanitizeKey($key));
        $item->set($value);
        $item->expiresAfter($ttl);
        return $this->adapter->save($item);
    }

    /**
     * Delete an item from cache.
     */
    public function delete(string $key): bool
    {
        return $this->adapter->deleteItem($this->sanitizeKey($key));
    }

    /**
     * Flush all cache items.
     */
    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    /**
     * Return information about the current cache driver.
     */
    public function getDriverInfo(): array
    {
        return array_merge($this->driverDetails, [
            'active_driver' => $this->activeDriver,
            'is_redis' => $this->activeDriver === 'redis',
            'timestamp' => time(),
        ]);
    }

    /**
     * Test connection to a Redis server with low timeout.
     */
    public function testRedisConnection(string $host, int $port = 6379, ?string $password = null): array
    {
        $start = microtime(true);
        $timeout = 0.5; // 500ms timeout max to prevent page delays

        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$connection) {
            return [
                'success' => false,
                'error' => "Cannot connect to $host:$port ($errstr)",
                'latency_ms' => null,
            ];
        }

        fclose($connection);
        $latency = round((microtime(true) - $start) * 1000, 2);

        return [
            'success' => true,
            'message' => "Successfully connected to Redis at $host:$port",
            'latency_ms' => $latency,
        ];
    }

    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_\.]/', '_', $key);
    }
}
