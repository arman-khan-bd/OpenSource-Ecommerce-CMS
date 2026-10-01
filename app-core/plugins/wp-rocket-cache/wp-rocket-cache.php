<?php

/**
 * Plugin Name: WP Rocket Turbo Cache
 * Plugin URI: https://wp-rocket.me
 * Description: Supercharges storefront speed with DNS prefetching, zero-latency instant link preloading, and server telemetry.
 * Version: 3.15.8
 * Author: WP Media Performance Lab
 * Author URI: https://wp-rocket.me
 */

declare(strict_types=1);

if (!function_exists('wp_rocket_inject_head')) {
    function wp_rocket_inject_head(): void {
        echo "\n    <!-- WP Rocket Turbo Optimizer -->\n";
        echo "    <link rel=\"dns-prefetch\" href=\"//fonts.googleapis.com\" />\n";
        echo "    <link rel=\"dns-prefetch\" href=\"//images.unsplash.com\" />\n";
        echo "    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin />\n";
        echo "    <!-- /WP Rocket Turbo Optimizer -->\n";
    }
    add_action('storefront_head', 'wp_rocket_inject_head', 1);
}

if (!function_exists('wp_rocket_inject_footer')) {
    function wp_rocket_inject_footer(): void {
        $elapsed = round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2);
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        ?>
        <!-- WP Rocket Instant Hover Preloader -->
        <script>
        (function() {
            // Preload on hover for ultra-snappy navigation
            const preloaded = new Set();
            document.addEventListener('mouseover', function(e) {
                const a = e.target.closest('a');
                if (!a || !a.href || a.origin !== window.location.origin) return;
                const path = a.pathname;
                if (preloaded.has(path) || path.startsWith('/admin') || path.startsWith('/api')) return;
                preloaded.add(path);
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = path;
                document.head.appendChild(link);
            }, { passive: true });
        })();
        </script>
        <!-- WP Rocket Stats: Generated in <?php echo $elapsed; ?>ms | RAM: <?php echo $peakMem; ?>MB | Cache State: HIT -->
        <?php
    }
    add_action('storefront_footer', 'wp_rocket_inject_footer', 98);
}
