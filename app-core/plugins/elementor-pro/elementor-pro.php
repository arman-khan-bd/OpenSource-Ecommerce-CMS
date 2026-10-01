<?php

/**
 * Plugin Name: Elementor Pro Visual Builder
 * Plugin URI: https://elementor.com
 * Description: Premium visual page builder engine: Modern shortcode widgets (Hero Banners, Pricing Cards, Testimonials, Countdowns, Accordions), floating quick-support desk, and design system animations.
 * Version: 3.19.4
 * Author: Elementor Team
 * Author URI: https://elementor.com
 */

declare(strict_types=1);

if (!function_exists('elementor_get_settings')) {
    function elementor_get_settings(): array {
        $default = [
            'primary_accent' => '#6366f1',
            'enable_floating_support' => true,
            'support_phone' => '+1 (800) 555-0199',
            'support_email' => 'support@example.com',
            'whatsapp_number' => '18005550199',
            'enable_micro_animations' => true,
        ];
        return cms_get_plugin_settings('elementor-pro', $default);
    }
}

// 1. Process Elementor Pro Shortcodes via the_content filter
if (!function_exists('elementor_the_content_filter')) {
    function elementor_the_content_filter(string $content): string {
        $settings = elementor_get_settings();
        $accent = $settings['primary_accent'] ?? '#6366f1';

        // 1. [elementor_hero ...]
        $content = preg_replace_callback('/\[elementor_hero([^\]]*)\]/i', function($matches) use ($accent) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $title = htmlspecialchars($atts['title'] ?? 'Build Faster with Elementor Pro');
            $subtitle = htmlspecialchars($atts['subtitle'] ?? 'Empowering creators and brands with modern monolithic speed and headless agility.');
            $badge = htmlspecialchars($atts['badge'] ?? '★ State of the Art');
            $btnText = htmlspecialchars($atts['button_text'] ?? 'Explore Store');
            $btnUrl = htmlspecialchars($atts['button_url'] ?? '/shop');

            return "
            <div class=\"my-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950/80 via-slate-900 to-purple-950/60 border border-indigo-500/30 p-8 sm:p-12 text-center shadow-2xl\">
                <div class=\"absolute -top-24 -left-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none\"></div>
                <div class=\"absolute -bottom-24 -right-24 w-72 h-72 bg-purple-500/20 rounded-full blur-3xl pointer-events-none\"></div>
                <div class=\"relative z-10 max-w-2xl mx-auto space-y-4\">
                    <span class=\"inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold tracking-wide uppercase border border-indigo-500/30\">
                        {$badge}
                    </span>
                    <h2 class=\"text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight font-display\">
                        {$title}
                    </h2>
                    <p class=\"text-slate-300 text-sm sm:text-base leading-relaxed\">
                        {$subtitle}
                    </p>
                    <div class=\"pt-4 flex flex-wrap items-center justify-center gap-3\">
                        <a href=\"{$btnUrl}\" class=\"px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-indigo-600/30 transition-all hover:scale-105 active:scale-95\">
                            {$btnText} →
                        </a>
                    </div>
                </div>
            </div>";
        }, $content);

        // 2. [elementor_button ...]
        $content = preg_replace_callback('/\[elementor_button([^\]]*)\]/i', function($matches) use ($accent) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $text = htmlspecialchars($atts['text'] ?? 'Click Here');
            $url = htmlspecialchars($atts['url'] ?? '#');
            $style = $atts['style'] ?? 'primary';

            $btnClass = match($style) {
                'glow' => 'bg-gradient-to-r from-pink-600 to-purple-600 shadow-lg shadow-pink-600/30 text-white hover:brightness-110',
                'outline' => 'bg-transparent border border-indigo-500 text-indigo-300 hover:bg-indigo-500/10',
                default => 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30',
            };

            return "<a href=\"{$url}\" class=\"inline-flex items-center space-x-2 px-5 py-2.5 rounded-xl font-bold text-xs tracking-wide transition-all {$btnClass}\"><span>{$text}</span><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M14 5l7 7m0 0l-7 7m7-7H3\"/></svg></a>";
        }, $content);

        // 3. [elementor_pricing ...]
        $content = preg_replace_callback('/\[elementor_pricing([^\]]*)\]/i', function($matches) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $plan = htmlspecialchars($atts['plan'] ?? 'Pro Plan');
            $price = htmlspecialchars($atts['price'] ?? '$49');
            $period = htmlspecialchars($atts['period'] ?? '/mo');
            $badge = htmlspecialchars($atts['badge'] ?? 'Recommended');
            $rawFeatures = $atts['features'] ?? 'Instant Delivery,Premium Support,Lifetime Updates';
            $features = array_map('trim', explode(',', $rawFeatures));
            $btnText = htmlspecialchars($atts['button_text'] ?? 'Get Started Now');
            $btnUrl = htmlspecialchars($atts['button_url'] ?? '/shop');

            $featuresListHtml = '';
            foreach ($features as $f) {
                $featuresListHtml .= "<li class=\"flex items-center space-x-2 text-xs text-slate-300\"><svg class=\"w-4 h-4 text-emerald-400 shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg><span>" . htmlspecialchars($f) . "</span></li>";
            }

            return "
            <div class=\"my-8 max-w-sm mx-auto rounded-3xl bg-slate-900 border-2 border-indigo-500/40 p-6 sm:p-8 shadow-2xl relative space-y-6 text-left\">
                <div class=\"flex justify-between items-center\">
                    <span class=\"text-xs font-extrabold uppercase tracking-wider text-indigo-400\">{$plan}</span>
                    <span class=\"px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-bold uppercase\">{$badge}</span>
                </div>
                <div class=\"flex items-baseline space-x-1\">
                    <span class=\"text-4xl font-extrabold font-display text-white\">{$price}</span>
                    <span class=\"text-xs text-slate-400\">{$period}</span>
                </div>
                <ul class=\"space-y-3 pt-2 border-t border-slate-800\">
                    {$featuresListHtml}
                </ul>
                <a href=\"{$btnUrl}\" class=\"w-full block text-center py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all\">
                    {$btnText}
                </a>
            </div>";
        }, $content);

        // 4. [elementor_testimonial ...]
        $content = preg_replace_callback('/\[elementor_testimonial([^\]]*)\]/i', function($matches) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $author = htmlspecialchars($atts['author'] ?? 'Alex Morrison');
            $role = htmlspecialchars($atts['role'] ?? 'Verified Buyer');
            $quote = htmlspecialchars($atts['quote'] ?? 'The build quality and performance exceeded every expectation. Absolutely flawless experience!');
            $stars = min(5, max(1, (int)($atts['stars'] ?? 5)));

            $starIcons = str_repeat('★', $stars);

            return "
            <div class=\"my-6 p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4\">
                <div class=\"text-amber-400 text-sm tracking-widest\">{$starIcons}</div>
                <p class=\"text-slate-300 text-sm italic leading-relaxed\">“{$quote}”</p>
                <div class=\"flex items-center space-x-3 pt-2 border-t border-slate-800/80\">
                    <div class=\"w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs uppercase\">
                        " . substr($author, 0, 1) . "
                    </div>
                    <div>
                        <div class=\"text-xs font-bold text-white\">{$author}</div>
                        <div class=\"text-[11px] text-slate-400\">{$role}</div>
                    </div>
                </div>
            </div>";
        }, $content);

        // 5. [elementor_faq ...]
        $content = preg_replace_callback('/\[elementor_faq([^\]]*)\]/i', function($matches) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $q = htmlspecialchars($atts['q'] ?? 'Frequently Asked Question');
            $a = htmlspecialchars($atts['a'] ?? 'Detailed answer with full support information.');

            return "
            <details class=\"my-3 p-4 rounded-2xl bg-slate-900/90 border border-slate-800/80 group text-left cursor-pointer transition-all hover:border-indigo-500/50\">
                <summary class=\"font-bold text-white text-xs sm:text-sm flex justify-between items-center list-none select-none\">
                    <span>❓ {$q}</span>
                    <span class=\"text-indigo-400 text-sm transition-transform group-open:rotate-180\">▼</span>
                </summary>
                <p class=\"mt-3 pt-3 border-t border-slate-800 text-xs text-slate-300 leading-relaxed\">
                    {$a}
                </p>
            </details>";
        }, $content);

        // 6. [elementor_grid ...]...[/elementor_grid]
        $content = preg_replace_callback('/\[elementor_grid([^\]]*)\](.*?)\[\/elementor_grid\]/is', function($matches) {
            $atts = function_exists('cms_parse_shortcode_atts') ? cms_parse_shortcode_atts($matches[1]) : [];
            $cols = (int)($atts['cols'] ?? 3);
            $gridClass = match($cols) {
                2 => 'grid-cols-1 md:grid-cols-2',
                4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                default => 'grid-cols-1 md:grid-cols-3',
            };
            return "<div class=\"my-6 grid {$gridClass} gap-6\">" . $matches[2] . "</div>";
        }, $content);

        return $content;
    }
    add_filter('the_content', 'elementor_the_content_filter', 11);
}

// 2. Inject Elementor Pro Head Styles
if (!function_exists('elementor_inject_head')) {
    function elementor_inject_head(): void {
        echo "\n    <!-- Elementor Pro Visual Builder Design Engine -->\n";
        echo "    <style>
        .elementor-glow { box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.4); }
        .elementor-float-btn { animation: elementorPulse 3s infinite; }
        @keyframes elementorPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.06); }
        }
    </style>\n";
        echo "    <!-- /Elementor Pro -->\n";
    }
    add_action('storefront_head', 'elementor_inject_head', 16);
}

// 3. Inject Floating Live Support Desk into Storefront Footer
if (!function_exists('elementor_inject_floating_support')) {
    function elementor_inject_floating_support(): void {
        $settings = elementor_get_settings();
        if (empty($settings['enable_floating_support'])) {
            return;
        }

        $phone = htmlspecialchars($settings['support_phone'] ?? '+1 (800) 555-0199');
        $email = htmlspecialchars($settings['support_email'] ?? 'support@example.com');
        $whatsapp = htmlspecialchars($settings['whatsapp_number'] ?? '18005550199');
        ?>
        <!-- Elementor Pro Floating Support Desk -->
        <div id="elementor-floating-support" class="fixed bottom-6 right-6 z-50">
            <!-- Support Panel (Initially Hidden) -->
            <div id="elementor-chat-panel" class="hidden mb-3 w-80 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-5 space-y-4 backdrop-blur-xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs">
                                EP
                            </div>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-slate-900 rounded-full"></span>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-white">Live Support Concierge</h4>
                            <p class="text-[10px] text-emerald-400 font-medium">Online • Typical reply in 2 min</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('elementor-chat-panel').classList.add('hidden')" class="text-slate-400 hover:text-white p-1 text-xs">✕</button>
                </div>

                <div class="text-xs text-slate-300 leading-relaxed bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                    👋 Hello! Need assistance choosing hardware or checking order status? We are ready to help.
                </div>

                <div class="space-y-2">
                    <a href="https://wa.me/<?php echo $whatsapp; ?>?text=Hello%20Support,%20I%20have%20an%20inquiry" target="_blank" 
                       class="w-full flex items-center justify-center space-x-2 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition-colors shadow-md shadow-emerald-600/20">
                        <span>💬 Chat on WhatsApp</span>
                    </a>
                    <a href="mailto:<?php echo $email; ?>?subject=Order%20Inquiry" 
                       class="w-full flex items-center justify-center space-x-2 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-xs border border-slate-700 transition-colors">
                        <span>✉️ Email Support Desk</span>
                    </a>
                </div>

                <div class="text-center text-[10px] text-slate-500 pt-1">
                    Direct line: <span class="text-slate-400 font-mono"><?php echo $phone; ?></span>
                </div>
            </div>

            <!-- Floating Toggle Button -->
            <button id="elementor-chat-toggle" 
                    onclick="document.getElementById('elementor-chat-panel').classList.toggle('hidden')" 
                    class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 hover:scale-105 text-white shadow-xl shadow-indigo-600/40 flex items-center justify-center transition-all elementor-float-btn"
                    title="Live Support">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </button>
        </div>
        <!-- /Elementor Pro Floating Support Desk -->
        <?php
    }
    add_action('storefront_footer', 'elementor_inject_floating_support', 30);
}

// 4. Android SDUI Layout Filter
if (!function_exists('elementor_sdui_filter')) {
    function elementor_sdui_filter(array $screenSpec, array $rawScreen = []): array {
        if (($screenSpec['screen_id'] ?? '') === 'home') {
            $elementorQuickActions = [
                'type' => 'QuickActions',
                'title' => 'Elementor Pro Quick Hub',
                'actions' => [
                    ['icon' => 'zap', 'label' => 'New Deals', 'action' => 'open_shop'],
                    ['icon' => 'headphones', 'label' => 'Audio', 'action' => 'filter_audio'],
                    ['icon' => 'message-circle', 'label' => 'Support', 'action' => 'open_chat'],
                    ['icon' => 'gift', 'label' => 'VIP Club', 'action' => 'open_vip'],
                ],
            ];
            $screenSpec['components'][] = $elementorQuickActions;
        }
        return $screenSpec;
    }
    add_filter('sdui_screen_response', 'elementor_sdui_filter', 35, 2);
}
