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

if (!function_exists('cms_get_plugin_settings')) {
    function cms_get_plugin_settings(string $slug, array $default = []): array
    {
        $pm = \App\Service\PluginManager::getInstance();
        if ($pm) {
            $settings = $pm->getPluginSettings($slug);
            return !empty($settings) ? $settings : $default;
        }
        return $default;
    }
}

if (!function_exists('cms_parse_shortcode_atts')) {
    function cms_parse_shortcode_atts(string $text): array
    {
        $atts = [];
        $pattern = '/([a-zA-Z0-9_\-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $val = $match[2] !== '' ? $match[2] : ($match[3] !== '' ? $match[3] : $match[4]);
                $atts[strtolower($match[1])] = trim($val);
            }
        }
        return $atts;
    }
}

if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes(string $content): string
    {
        return preg_replace('/\[([a-zA-Z0-9_\-]+)[^\]]*\](?:.*?\[\/\1\])?/is', '', $content);
    }
}

// Automatically strip unhandled shortcodes from deactivated plugins (priority 99)
if (function_exists('add_filter')) {
    add_filter('the_content', function(string $content) {
        // Strip shortcodes from plugins that are inactive/unhandled
        return preg_replace('/\[(elementor_[a-z0-9_-]+|woocommerce_[a-z0-9_-]+|wpforms)[^\]]*\](?:.*?\[\/\1\])?/is', '', $content);
    }, 99);
}

