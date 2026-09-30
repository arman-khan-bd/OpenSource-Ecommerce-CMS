<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\OptionRepository;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

/**
 * Manages server-rendered Twig storefront themes and visual customizer options.
 */
class ThemeManager
{
    private string $themesDir;
    private OptionRepository $optionRepo;
    private TwigEnvironment $twig;
    private ?string $activeTheme = null;

    public function __construct(string $projectDir, OptionRepository $optionRepo, TwigEnvironment $twig)
    {
        $this->themesDir = $projectDir . '/themes';
        $this->optionRepo = $optionRepo;
        $this->twig = $twig;
        $this->initActiveTheme();
    }

    private function initActiveTheme(): void
    {
        $this->activeTheme = (string)$this->optionRepo->get('active_theme', 'default');
        $this->configureTwigLoader();
    }

    public function getActiveTheme(): string
    {
        return $this->activeTheme ?: 'default';
    }

    public function setActiveTheme(string $themeSlug): bool
    {
        $themePath = $this->themesDir . '/' . $themeSlug;
        if (!is_dir($themePath)) {
            return false;
        }

        $this->activeTheme = $themeSlug;
        $this->optionRepo->set('active_theme', $themeSlug);
        $this->configureTwigLoader();
        return true;
    }

    public function getThemesDirectory(): string
    {
        return $this->themesDir;
    }

    /**
     * Scan available storefront themes.
     */
    public function getInstalledThemes(): array
    {
        if (!is_dir($this->themesDir)) {
            mkdir($this->themesDir, 0775, true);
        }

        $themes = [];
        $dirs = scandir($this->themesDir);
        $active = $this->getActiveTheme();

        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || !is_dir($this->themesDir . '/' . $dir)) {
                continue;
            }

            $themePath = $this->themesDir . '/' . $dir;
            $jsonFile = $themePath . '/theme.json';

            $meta = [
                'slug' => $dir,
                'name' => ucfirst(str_replace('-', ' ', $dir)),
                'version' => '1.0.0',
                'author' => 'CMS Team',
                'description' => 'Clean storefront theme.',
                'screenshot' => '/themes/' . $dir . '/screenshot.jpg',
                'is_active' => ($dir === $active),
                'customizer' => [],
            ];

            if (file_exists($jsonFile)) {
                $customMeta = json_decode(file_get_contents($jsonFile), true);
                if (is_array($customMeta)) {
                    $meta = array_merge($meta, $customMeta);
                    $meta['is_active'] = ($dir === $active);
                }
            }

            $themes[] = $meta;
        }

        return $themes;
    }

    /**
     * Get theme customizer settings.
     */
    public function getCustomizerOptions(?string $themeSlug = null): array
    {
        $theme = $themeSlug ?: $this->getActiveTheme();
        $saved = $this->optionRepo->get('theme_customizer_' . $theme, []);
        return is_array($saved) ? $saved : [];
    }

    public function saveCustomizerOptions(array $settings, ?string $themeSlug = null): void
    {
        $theme = $themeSlug ?: $this->getActiveTheme();
        $this->optionRepo->set('theme_customizer_' . $theme, $settings);
    }

    /**
     * Point Twig loader to active theme directory.
     */
    public function configureTwigLoader(): void
    {
        $loader = $this->twig->getLoader();
        if ($loader instanceof FilesystemLoader) {
            $activePath = $this->themesDir . '/' . $this->getActiveTheme();
            if (is_dir($activePath)) {
                // Add active theme with highest priority
                $loader->prependPath($activePath, 'theme');
            }
        }
    }
}
