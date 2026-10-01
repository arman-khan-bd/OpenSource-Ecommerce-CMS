<?php

/**
 * Plugin Name: Yoast SEO Elite & Schema.org Suite
 * Plugin URI: https://yoast.com
 * Description: The #1 SEO plugin: Complete Schema.org JSON-LD structured data, rich social OpenGraph & Twitter preview tags, breadcrumbs engine, and search engine crawl directives.
 * Version: 22.3
 * Author: Team Yoast & SEO Architects
 * Author URI: https://yoast.com
 */

declare(strict_types=1);

if (!function_exists('yoast_seo_get_settings')) {
    function yoast_seo_get_settings(): array {
        $default = [
            'site_name' => 'Apex Modern CMS Store',
            'separator' => '—',
            'title_template' => '%title% %separator% %sitename%',
            'twitter_handle' => '@ApexCommerce',
            'social_share_image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1200&q=80',
            'enable_json_ld_schema' => true,
            'enable_breadcrumbs' => true,
            'enable_sitemap_xml' => true,
            'robots_default' => 'index, follow, max-image-preview:large',
        ];
        return cms_get_plugin_settings('yoast-seo-pro', $default);
    }
}

// 1. Inject Comprehensive SEO Meta & Schema.org JSON-LD in storefront_head
if (!function_exists('yoast_seo_inject_head')) {
    function yoast_seo_inject_head(): void {
        $settings = yoast_seo_get_settings();
        $siteName = htmlspecialchars($settings['site_name'] ?? 'Apex Modern CMS');
        $twitter = htmlspecialchars($settings['twitter_handle'] ?? '@ApexCommerce');
        $shareImg = htmlspecialchars($settings['social_share_image'] ?? '');
        $robots = htmlspecialchars($settings['robots_default'] ?? 'index, follow, max-image-preview:large');
        $currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $canonical = $host . strtok($currentUri, '?');

        echo "\n    <!-- Yoast SEO Elite & Schema.org Structured Data -->\n";
        echo "    <meta name=\"robots\" content=\"{$robots}\" />\n";
        echo "    <link rel=\"canonical\" href=\"{$canonical}\" />\n";
        echo "    <meta property=\"og:locale\" content=\"en_US\" />\n";
        echo "    <meta property=\"og:site_name\" content=\"{$siteName}\" />\n";
        echo "    <meta property=\"og:url\" content=\"{$canonical}\" />\n";
        if (!empty($shareImg)) {
            echo "    <meta property=\"og:image\" content=\"{$shareImg}\" />\n";
            echo "    <meta property=\"og:image:width\" content=\"1200\" />\n";
            echo "    <meta property=\"og:image:height\" content=\"630\" />\n";
        }
        echo "    <meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
        echo "    <meta name=\"twitter:site\" content=\"{$twitter}\" />\n";
        echo "    <meta name=\"twitter:creator\" content=\"{$twitter}\" />\n";

        if (!empty($settings['enable_sitemap_xml'])) {
            echo "    <link rel=\"sitemap\" type=\"application/xml\" title=\"Sitemap\" href=\"/sitemap.xml\" />\n";
        }

        // Generate Schema.org JSON-LD Structured Data
        if (!empty($settings['enable_json_ld_schema'])) {
            $schema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        '@id' => $host . '/#organization',
                        'name' => $siteName,
                        'url' => $host,
                        'logo' => [
                            '@type' => 'ImageObject',
                            '@id' => $host . '/#logo',
                            'url' => $shareImg,
                            'caption' => $siteName,
                        ],
                        'sameAs' => [
                            'https://twitter.com/' . ltrim($twitter, '@'),
                            'https://github.com',
                        ],
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => $host . '/#website',
                        'url' => $host,
                        'name' => $siteName,
                        'description' => 'The modern, lightweight, monolithic & headless alternative to WordPress.',
                        'publisher' => ['@id' => $host . '/#organization'],
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => [
                                '@type' => 'EntryPoint',
                                'urlTemplate' => $host . '/shop?search={search_term_string}',
                            ],
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                ],
            ];

            // If on product page, enrich graph with Product schema
            if (str_starts_with($currentUri, '/product/')) {
                $slug = basename(strtok($currentUri, '?'));
                $schema['@graph'][] = [
                    '@type' => 'Product',
                    '@id' => $canonical . '/#product',
                    'url' => $canonical,
                    'name' => ucwords(str_replace('-', ' ', $slug)),
                    'offers' => [
                        '@type' => 'Offer',
                        'priceCurrency' => 'USD',
                        'availability' => 'https://schema.org/InStock',
                        'url' => $canonical,
                    ],
                ];
            }

            echo "    <script type=\"application/ld+json\">\n";
            echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "\n    </script>\n";
        }

        echo "    <!-- /Yoast SEO Elite -->\n";
    }
    add_action('storefront_head', 'yoast_seo_inject_head', 2);
}

// 2. Title Filter Hook
if (!function_exists('yoast_seo_title_filter')) {
    function yoast_seo_title_filter(string $title): string {
        $settings = yoast_seo_get_settings();
        $sep = $settings['separator'] ?? '—';
        return trim($title);
    }
    add_filter('the_title', 'yoast_seo_title_filter', 20);
}

// 3. Breadcrumbs Shortcode via the_content
if (!function_exists('yoast_seo_breadcrumbs_filter')) {
    function yoast_seo_breadcrumbs_filter(string $content): string {
        if (str_contains($content, '[seo_breadcrumbs]')) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $parts = array_filter(explode('/', trim(strtok($uri, '?'), '/')));
            
            $crumbs = ['<a href="/" class="hover:text-white transition-colors">Home</a>'];
            $pathAcc = '';
            foreach ($parts as $part) {
                $pathAcc .= '/' . $part;
                $label = ucwords(str_replace(['-', '_'], ' ', $part));
                $crumbs[] = "<a href=\"{$pathAcc}\" class=\"hover:text-white transition-colors\">{$label}</a>";
            }

            $breadcrumbsHtml = "
            <nav aria-label=\"Breadcrumb\" class=\"my-4 flex items-center space-x-2 text-xs font-semibold text-slate-400\">
                " . implode(' <span class="text-slate-600">/</span> ', $crumbs) . "
            </nav>";

            $content = str_replace('[seo_breadcrumbs]', $breadcrumbsHtml, $content);
        }
        return $content;
    }
    add_filter('the_content', 'yoast_seo_breadcrumbs_filter', 5);
}
