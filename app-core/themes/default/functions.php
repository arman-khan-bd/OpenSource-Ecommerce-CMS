<?php

/**
 * Theme Name: Default Canvas Theme
 * Description: WordPress-style theme functions, custom filters, and storefront hooks.
 */

declare(strict_types=1);

// Hook into theme initialization
if (function_exists('add_action')) {
    add_action('after_setup_theme', function () {
        // Theme supports: featured images, html5 markup, custom logo
    });
}
