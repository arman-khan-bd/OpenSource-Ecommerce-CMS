<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Plugin;
use App\Repository\PluginRepository;

/**
 * Manages plugin lifecycle, discovery, activation, and hook registration.
 */
class PluginManager
{
    private string $pluginsDir;
    private PluginRepository $pluginRepo;
    private HookManager $hookManager;
    private array $loadedPlugins = [];

    public function __construct(string $projectDir, PluginRepository $pluginRepo, HookManager $hookManager)
    {
        $this->pluginsDir = $projectDir . '/plugins';
        $this->pluginRepo = $pluginRepo;
        $this->hookManager = $hookManager;
    }

    public function getPluginsDirectory(): string
    {
        return $this->pluginsDir;
    }

    /**
     * Scan plugin directory and synchronize with database.
     */
    public function discoverPlugins(): array
    {
        if (!is_dir($this->pluginsDir)) {
            mkdir($this->pluginsDir, 0775, true);
        }

        $discovered = [];
        $dirs = scandir($this->pluginsDir);

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($this->pluginsDir . '/' . $dir)) {
                continue;
            }

            $pluginPath = $this->pluginsDir . '/' . $dir;
            $meta = $this->readPluginMetadata($pluginPath, $dir);
            if ($meta) {
                // Ensure record exists in database
                $existing = $this->pluginRepo->findBySlug($meta['slug']);
                if (!$existing) {
                    $pluginEntity = Plugin::fromArray($meta);
                    $this->pluginRepo->save($pluginEntity);
                    $meta['is_active'] = false;
                } else {
                    $meta['is_active'] = $existing->isActive();
                    $meta['settings'] = $existing->getSettings();
                }
                $discovered[] = $meta;
            }
        }

        return $discovered;
    }

    /**
     * Boot all active plugins and allow them to register hooks.
     */
    public function bootActivePlugins(): void
    {
        $this->discoverPlugins();
        $activePlugins = $this->pluginRepo->getActivePlugins();

        foreach ($activePlugins as $plugin) {
            $slug = $plugin->getSlug();
            $mainFile = $this->pluginsDir . '/' . $slug . '/' . $slug . '.php';

            if (!file_exists($mainFile)) {
                $mainFile = $this->pluginsDir . '/' . $slug . '/index.php';
            }

            if (file_exists($mainFile) && !isset($this->loadedPlugins[$slug])) {
                $this->loadPluginFile($mainFile, $plugin);
                $this->loadedPlugins[$slug] = true;
            }
        }

        // Trigger action after plugins loaded
        $this->hookManager->doAction('plugins_loaded', $this->loadedPlugins);
    }

    public function togglePlugin(string $slug): bool
    {
        return $this->pluginRepo->toggleActive($slug);
    }

    private function loadPluginFile(string $file, Plugin $plugin): void
    {
        $hookManager = $this->hookManager;
        $pluginSettings = $plugin->getSettings();

        // Scope execution
        (static function() use ($file, $hookManager, $pluginSettings) {
            require_once $file;
        })();
    }

    private function readPluginMetadata(string $pluginPath, string $slug): ?array
    {
        $jsonFile = $pluginPath . '/plugin.json';
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (is_array($data) && !empty($data['name'])) {
                return [
                    'name' => $data['name'],
                    'slug' => $slug,
                    'version' => $data['version'] ?? '1.0.0',
                    'author' => $data['author'] ?? 'Unknown',
                    'description' => $data['description'] ?? '',
                    'is_active' => false,
                    'settings' => $data['settings'] ?? [],
                ];
            }
        }

        // Read PHP file header comments (WordPress style)
        $phpFile = $pluginPath . '/' . $slug . '.php';
        if (!file_exists($phpFile)) {
            $phpFile = $pluginPath . '/index.php';
        }

        if (file_exists($phpFile)) {
            $content = file_get_contents($phpFile, false, null, 0, 4096);
            $meta = [
                'name' => $this->extractHeader($content, 'Plugin Name') ?: ucfirst($slug),
                'slug' => $slug,
                'version' => $this->extractHeader($content, 'Version') ?: '1.0.0',
                'author' => $this->extractHeader($content, 'Author') ?: 'Community',
                'description' => $this->extractHeader($content, 'Description') ?: '',
                'is_active' => false,
                'settings' => [],
            ];
            return $meta;
        }

        return null;
    }

    private function extractHeader(string $content, string $header): ?string
    {
        if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)$/mi', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
