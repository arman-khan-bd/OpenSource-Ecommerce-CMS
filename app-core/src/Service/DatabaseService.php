<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Robust Database Service managing Doctrine DBAL connections, migrations, and queries
 * for MySQL 8.0+, MariaDB 10.4+, and SQLite.
 */
class DatabaseService
{
    private ?Connection $connection = null;
    private string $projectDir;
    private string $tablePrefix = 'cms_';
    private bool $isInstalled = false;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->isInstalled = file_exists($projectDir . '/var/installed.lock');
        $this->initialize();
    }

    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function tableName(string $name): string
    {
        return $this->tablePrefix . $name;
    }

    /**
     * Initializes DBAL Connection from config file or environment.
     */
    private function initialize(): void
    {
        $configFile = $this->projectDir . '/var/database.php';

        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->tablePrefix = $config['prefix'] ?? 'cms_';
            try {
                $this->connection = DriverManager::getConnection($config['connection']);
            } catch (\Throwable $e) {
                // Connection failed (e.g. database offline)
                $this->connection = null;
            }
        } elseif (isset($_ENV['DATABASE_URL'])) {
            try {
                $this->connection = DriverManager::getConnection([
                    'url' => $_ENV['DATABASE_URL'],
                ]);
            } catch (\Throwable $e) {
                $this->connection = null;
            }
        }
    }

    /**
     * Test connection to a database with given parameters.
     */
    public function testConnection(array $params): array
    {
        try {
            $driver = $params['db_driver'] ?? $params['driver'] ?? 'pdo_mysql';
            $connectionParams = [];

            if ($driver === 'pdo_sqlite' || $driver === 'sqlite') {
                $connectionParams = [
                    'driver' => 'pdo_sqlite',
                    'path' => $params['path'] ?? ($this->projectDir . '/var/data.sqlite'),
                ];
            } else {
                $connectionParams = [
                    'driver' => 'pdo_mysql',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => (int)($params['port'] ?? 3306),
                    'dbname' => $params['dbname'] ?? 'cms_db',
                    'user' => $params['user'] ?? 'root',
                    'password' => $params['password'] ?? '',
                    'charset' => 'utf8mb4',
                ];
            }

            $conn = DriverManager::getConnection($connectionParams);
            $conn->getDatabasePlatform();
            $conn->fetchOne('SELECT 1');
            $serverVersion = 'Active';
            try {
                if (method_exists($conn, 'getServerVersion')) {
                    $serverVersion = $conn->getServerVersion();
                }
            } catch (\Throwable $e) {}

            return [
                'success' => true,
                'message' => 'Connection established successfully.',
                'server_version' => $serverVersion,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save database configuration to var/database.php.
     */
    public function saveConfiguration(array $config): void
    {
        $varDir = $this->projectDir . '/var';
        if (!is_dir($varDir)) {
            mkdir($varDir, 0775, true);
        }

        $code = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($varDir . '/database.php', $code);

        // Reconnect with new configuration
        $this->initialize();
    }

    /**
     * Run all schema migration DDL statements to set up tables.
     */
    public function createTables(string $prefix = 'cms_'): void
    {
        if (!$this->connection) {
            throw new \RuntimeException('Database connection is not initialized.');
        }

        $this->tablePrefix = $prefix;
        $platformClass = get_class($this->connection->getDatabasePlatform());
        $isSqlite = str_contains(strtolower($platformClass), 'sqlite');

        $statements = $this->getSchemaDdl($prefix, $isSqlite);

        foreach ($statements as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    /**
     * DDL definitions for MySQL and SQLite.
     */
    private function getSchemaDdl(string $p, bool $isSqlite): array
    {
        if ($isSqlite) {
            return [
                "CREATE TABLE IF NOT EXISTS `{$p}users` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `username` VARCHAR(100) NOT NULL UNIQUE,
                    `email` VARCHAR(255) NOT NULL UNIQUE,
                    `password_hash` VARCHAR(255) NOT NULL,
                    `role` VARCHAR(50) NOT NULL DEFAULT 'editor',
                    `full_name` VARCHAR(255) NULL,
                    `avatar` VARCHAR(500) NULL,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                    `meta` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `{$p}posts` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL UNIQUE,
                    `content` TEXT,
                    `excerpt` TEXT,
                    `type` VARCHAR(50) NOT NULL DEFAULT 'post',
                    `status` VARCHAR(50) NOT NULL DEFAULT 'published',
                    `author_id` INTEGER NULL,
                    `featured_image` VARCHAR(500) NULL,
                    `meta` TEXT NULL,
                    `published_at` DATETIME NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}post_type_status` ON `{$p}posts` (`type`, `status`)",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}post_slug` ON `{$p}posts` (`slug`)",
                "CREATE TABLE IF NOT EXISTS `{$p}options` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `option_name` VARCHAR(191) NOT NULL UNIQUE,
                    `option_value` TEXT NULL,
                    `autoload` INTEGER NOT NULL DEFAULT 1
                )",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}option_name` ON `{$p}options` (`option_name`)",
                "CREATE TABLE IF NOT EXISTS `{$p}media` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `filename` VARCHAR(255) NOT NULL,
                    `original_name` VARCHAR(255) NOT NULL,
                    `mime_type` VARCHAR(100) NOT NULL,
                    `file_size` INTEGER NOT NULL,
                    `width` INTEGER NULL,
                    `height` INTEGER NULL,
                    `path` VARCHAR(500) NOT NULL,
                    `url` VARCHAR(500) NOT NULL,
                    `alt_text` VARCHAR(255) NULL,
                    `meta` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `{$p}plugins` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
                    `author` VARCHAR(255) NULL,
                    `description` TEXT NULL,
                    `is_active` INTEGER NOT NULL DEFAULT 0,
                    `settings` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `{$p}products` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(255) NOT NULL UNIQUE,
                    `description` TEXT,
                    `price` REAL NOT NULL DEFAULT 0.00,
                    `sale_price` REAL NULL,
                    `sku` VARCHAR(100) NULL,
                    `stock` INTEGER NOT NULL DEFAULT 0,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                    `images` TEXT NULL,
                    `meta` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}prod_status` ON `{$p}products` (`status`)",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}prod_slug` ON `{$p}products` (`slug`)",
                "CREATE TABLE IF NOT EXISTS `{$p}orders` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `order_number` VARCHAR(100) NOT NULL UNIQUE,
                    `customer_name` VARCHAR(255) NOT NULL,
                    `customer_email` VARCHAR(255) NOT NULL,
                    `total_amount` REAL NOT NULL DEFAULT 0.00,
                    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                    `items` TEXT NOT NULL,
                    `shipping_address` TEXT NULL,
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}order_status` ON `{$p}orders` (`status`)",
                "CREATE TABLE IF NOT EXISTS `{$p}sdui_screens` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `title` VARCHAR(255) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` VARCHAR(500) NULL,
                    `layout` TEXT NOT NULL,
                    `is_active` INTEGER NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE INDEX IF NOT EXISTS `idx_{$p}sdui_slug` ON `{$p}sdui_screens` (`slug`)",
            ];
        }

        // MySQL 8.0+ / MariaDB 10.4+ schema with native JSON columns and foreign keys/indexes
        return [
            "CREATE TABLE IF NOT EXISTS `{$p}users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) NOT NULL DEFAULT 'editor',
                `full_name` VARCHAR(255) NULL,
                `avatar` VARCHAR(500) NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                `meta` JSON NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_user_email` (`email`),
                INDEX `idx_user_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}posts` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `content` LONGTEXT NULL,
                `excerpt` TEXT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'post',
                `status` VARCHAR(50) NOT NULL DEFAULT 'published',
                `author_id` INT UNSIGNED NULL,
                `featured_image` VARCHAR(500) NULL,
                `meta` JSON NULL,
                `published_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_post_type_status` (`type`, `status`),
                INDEX `idx_post_slug` (`slug`),
                INDEX `idx_post_published` (`published_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}options` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `option_name` VARCHAR(191) NOT NULL UNIQUE,
                `option_value` LONGTEXT NULL,
                `autoload` TINYINT(1) NOT NULL DEFAULT 1,
                INDEX `idx_option_name` (`option_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}media` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `filename` VARCHAR(255) NOT NULL,
                `original_name` VARCHAR(255) NOT NULL,
                `mime_type` VARCHAR(100) NOT NULL,
                `file_size` BIGINT UNSIGNED NOT NULL,
                `width` INT UNSIGNED NULL,
                `height` INT UNSIGNED NULL,
                `path` VARCHAR(500) NOT NULL,
                `url` VARCHAR(500) NOT NULL,
                `alt_text` VARCHAR(255) NULL,
                `meta` JSON NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_media_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}plugins` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
                `author` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                `settings` JSON NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_plugin_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}products` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `description` LONGTEXT NULL,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `sale_price` DECIMAL(10,2) NULL,
                `sku` VARCHAR(100) NULL UNIQUE,
                `stock` INT NOT NULL DEFAULT 0,
                `status` VARCHAR(50) NOT NULL DEFAULT 'active',
                `images` JSON NULL,
                `meta` JSON NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_product_status` (`status`),
                INDEX `idx_product_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}orders` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_number` VARCHAR(100) NOT NULL UNIQUE,
                `customer_name` VARCHAR(255) NOT NULL,
                `customer_email` VARCHAR(255) NOT NULL,
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
                `items` JSON NOT NULL,
                `shipping_address` JSON NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_order_status` (`status`),
                INDEX `idx_order_email` (`customer_email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `{$p}sdui_screens` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` VARCHAR(500) NULL,
                `layout` JSON NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_sdui_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }
}
