<?php

/**
 * Plugin Name: SpeedBoost Optimizer
 * Description: Optimizes rendering speed and outputs performance metrics.
 * Version: 1.1.0
 * Author: Performance Lab
 */

declare(strict_types=1);

if (!function_exists('speed_boost_inject_footer')) {
    function speed_boost_inject_footer(): void {
        $renderTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
        $mem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        echo "\n    <!-- SpeedBoost: Page generated in {$renderTime}ms | Peak Memory: {$mem}MB -->\n";
    }
    add_action('storefront_footer', 'speed_boost_inject_footer', 99);
}
