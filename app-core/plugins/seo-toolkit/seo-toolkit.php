<?php

/**
 * Plugin Name: SEO & Social Toolkit
 * Description: Injects search engine optimization metadata and OpenGraph social preview tags.
 * Version: 1.0.0
 * Author: Apex Core Team
 */

declare(strict_types=1);

if (!function_exists('seo_toolkit_inject_head')) {
    function seo_toolkit_inject_head(): void {
        echo "\n    <!-- SEO & Social Toolkit Plugin -->\n";
        echo "    <meta name=\"robots\" content=\"index, follow, max-image-preview:large\" />\n";
        echo "    <meta property=\"og:type\" content=\"website\" />\n";
        echo "    <meta property=\"og:site_name\" content=\"Modern CMS Platform\" />\n";
        echo "    <meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
        echo "    <meta name=\"generator\" content=\"Modern Monolithic CMS 1.0\" />\n";
        echo "    <!-- /SEO & Social Toolkit -->\n";
    }
    add_action('storefront_head', 'seo_toolkit_inject_head', 5);
}

if (!function_exists('seo_toolkit_title_filter')) {
    function seo_toolkit_title_filter(string $title): string {
        return trim($title);
    }
    add_filter('the_title', 'seo_toolkit_title_filter', 10);
}
