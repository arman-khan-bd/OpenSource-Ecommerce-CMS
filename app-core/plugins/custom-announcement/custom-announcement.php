<?php

/**
 * Plugin Name: Live Promo Banner
 * Description: Injects promotional alerts into storefront and Android SDUI models.
 * Version: 1.0.0
 * Author: Marketing Team
 */

declare(strict_types=1);

if (!function_exists('custom_promo_banner_inject')) {
    function custom_promo_banner_inject(): void {
        echo "<div style=\"background: linear-gradient(90deg, #7c3aed, #4f46e5); color: #fff; text-align: center; font-size: 11px; padding: 4px; font-weight: bold; letter-spacing: 0.05em;\">PROMO: Use code APEX20 at checkout for an extra 20% discount!</div>";
    }
    add_action('storefront_head', 'custom_promo_banner_inject', 1);
}

// Also hook into Android SDUI screen response filter to dynamically inject top announcement banner
if (!function_exists('custom_promo_sdui_filter')) {
    function custom_promo_sdui_filter(array $screenSpec, array $rawScreen = []): array {
        // Only inject on home screen
        if (($screenSpec['screen_id'] ?? '') === 'home') {
            $banner = [
                'type' => 'AnnouncementCard',
                'icon' => 'zap',
                'title' => 'Flash Mobile Deal',
                'message' => 'Use coupon APP20 for 20% off your mobile order today.',
                'gradient' => ['from' => '#7c3aed', 'to' => '#4f46e5'],
                'dismissable' => true,
                'button_text' => 'Apply Code',
            ];
            // Insert at beginning of components
            array_unshift($screenSpec['components'], $banner);
        }
        return $screenSpec;
    }
    add_filter('sdui_screen_response', 'custom_promo_sdui_filter', 20, 2);
}
