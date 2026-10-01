<?php

/**
 * Plugin Name: WooCommerce Commerce Pro
 * Plugin URI: https://woocommerce.com
 * Description: The comprehensive eCommerce extension suite for modern storefronts: Smart coupon & discount rules, sales badging, inventory scarcity urgency, free shipping progress meter, checkout trust seals, and Android SDUI commerce widgets.
 * Version: 8.5.2
 * Author: Automattic & Commerce Team
 * Author URI: https://woocommerce.com
 */

declare(strict_types=1);

if (!function_exists('woocommerce_get_settings')) {
    function woocommerce_get_settings(): array {
        $default = [
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'free_shipping_threshold' => 100,
            'stock_urgency_threshold' => 5,
            'enable_coupons' => true,
            'enable_free_shipping_meter' => true,
            'enable_trust_badges' => true,
            'coupons' => [
                ['code' => 'APEX20', 'type' => 'percent', 'amount' => 20, 'description' => 'VIP Summer Sale — 20% OFF Everything'],
                ['code' => 'WELCOME10', 'type' => 'percent', 'amount' => 10, 'description' => 'New Customer Welcome — 10% OFF'],
                ['code' => 'FREESHIP', 'type' => 'fixed', 'amount' => 15, 'description' => 'Complimentary Free Express Shipping ($15 OFF)'],
                ['code' => 'VIP50', 'type' => 'fixed', 'amount' => 50, 'min_spend' => 200, 'description' => '$50 Instant Rebate on orders over $200'],
            ],
        ];
        return cms_get_plugin_settings('woocommerce-core', $default);
    }
}

// 1. Validate & Apply Coupon Filter Hook
if (!function_exists('woocommerce_validate_coupon_handler')) {
    function woocommerce_validate_coupon_handler(array $result, string $code, float $subtotal): array {
        $settings = woocommerce_get_settings();
        if (empty($settings['enable_coupons'])) {
            return ['success' => false, 'message' => 'Coupons are currently disabled by store administrator.'];
        }

        $coupons = $settings['coupons'] ?? [];
        $upperCode = strtoupper(trim($code));

        foreach ($coupons as $coupon) {
            if (strtoupper($coupon['code'] ?? '') === $upperCode) {
                $minSpend = (float)($coupon['min_spend'] ?? 0);
                if ($minSpend > 0 && $subtotal < $minSpend) {
                    $sym = $settings['currency_symbol'] ?? '$';
                    return [
                        'success' => false,
                        'message' => "Coupon '{$upperCode}' requires a minimum spend of {$sym}{$minSpend}. Current subtotal is {$sym}" . number_format($subtotal, 2) . '.',
                    ];
                }

                $discount = 0.0;
                $type = $coupon['type'] ?? 'percent';
                $amount = (float)($coupon['amount'] ?? 0);

                if ($type === 'percent') {
                    $discount = round(($subtotal * ($amount / 100)), 2);
                } else {
                    $discount = min($subtotal, $amount);
                }

                return [
                    'success' => true,
                    'code' => $upperCode,
                    'type' => $type,
                    'amount' => $amount,
                    'discount' => $discount,
                    'formatted_discount' => ($settings['currency_symbol'] ?? '$') . number_format($discount, 2),
                    'description' => $coupon['description'] ?? "Applied code {$upperCode}",
                    'new_subtotal' => max(0.0, round($subtotal - $discount, 2)),
                    'message' => "Coupon '{$upperCode}' applied successfully! You saved " . ($settings['currency_symbol'] ?? '$') . number_format($discount, 2) . '.',
                ];
            }
        }

        return [
            'success' => false,
            'message' => "Coupon code '{$upperCode}' is invalid or expired.",
        ];
    }
    add_filter('woocommerce_validate_coupon', 'woocommerce_validate_coupon_handler', 10, 3);
}

// 2. Pre-process Checkout Hook (Verify Discount on Server)
if (!function_exists('woocommerce_pre_process_checkout_handler')) {
    function woocommerce_pre_process_checkout_handler(array $data): array {
        if (!empty($data['coupon_code'])) {
            $items = $data['items'] ?? [];
            $subtotal = 0.0;
            foreach ($items as $item) {
                $subtotal += ((float)($item['price'] ?? 0)) * max(1, (int)($item['quantity'] ?? 1));
            }
            $val = woocommerce_validate_coupon_handler([], (string)$data['coupon_code'], $subtotal);
            if (!empty($val['success'])) {
                $data['discount_amount'] = $val['discount'];
                $data['notes'] = ($data['notes'] ?? '') . " [WooCommerce Coupon: {$val['code']} (-{$val['formatted_discount']})]";
            } else {
                $data['discount_amount'] = 0.0;
                unset($data['coupon_code']);
            }
        }
        return $data;
    }
    add_filter('pre_process_checkout', 'woocommerce_pre_process_checkout_handler', 10, 1);
}

// 3. Post-Order Telemetry Hook
if (!function_exists('woocommerce_order_created_handler')) {
    function woocommerce_order_created_handler($savedOrder): void {
        if (is_object($savedOrder) && method_exists($savedOrder, 'getOrderNumber')) {
            error_log(sprintf(
                "[WooCommerce Pro] Order %s created with Total: $%s for Customer: %s",
                $savedOrder->getOrderNumber(),
                $savedOrder->getTotalAmount(),
                $savedOrder->getCustomerEmail()
            ));
        }
    }
    add_action('order_created', 'woocommerce_order_created_handler', 10, 1);
}

// 4. Product View Data Urgency Hook
if (!function_exists('woocommerce_storefront_product_data_handler')) {
    function woocommerce_storefront_product_data_handler(array $product): array {
        $settings = woocommerce_get_settings();
        $urgencyThreshold = (int)($settings['stock_urgency_threshold'] ?? 5);
        $stock = (int)($product['stock'] ?? 0);

        if ($stock > 0 && $stock <= $urgencyThreshold) {
            $urgencyBadge = "
            <div class=\"mt-3 p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-semibold flex items-center space-x-2 animate-pulse\">
                <svg class=\"w-4 h-4 text-amber-400 shrink-0\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.527.82-1.127 2.18-1.579 3.51a13.9 13.9 0 01-1.39 2.873c-.503.784-1.076 1.442-1.748 1.954a6.002 6.002 0 00-1.077 8.358A6.002 6.002 0 0010 20a6 6 0 005.67-4.04 6.005 6.005 0 00-1.18-6.177 17.72 17.72 0 01-.84-2.18c-.378-1.284-.664-2.484-.875-3.32-.132-.52-.224-.954-.28-1.23a10.87 10.87 0 01-.1-.5zM10 18a4 4 0 01-3.79-2.72 4.004 4.004 0 01.99-4.32c.57-.435 1.05-.98 1.48-1.638.39-.59.76-1.31 1.12-2.13.3-.68.59-1.4.88-2.13.06.31.14.65.23 1.02.23.92.54 2.21.94 3.57.17.58.38 1.19.62 1.77A4.004 4.004 0 0110 18z\" clip-rule=\"evenodd\"/></svg>
                <span><strong>High Demand:</strong> Only {$stock} units left in stock — order soon to secure delivery!</span>
            </div>";
            $product['description'] = ($product['description'] ?? '') . $urgencyBadge;
        }

        return $product;
    }
    add_filter('storefront_product_data', 'woocommerce_storefront_product_data_handler', 15, 1);
}

// 5. Shortcodes via the_content filter
if (!function_exists('woocommerce_the_content_shortcodes')) {
    function woocommerce_the_content_shortcodes(string $content): string {
        $settings = woocommerce_get_settings();
        $sym = $settings['currency_symbol'] ?? '$';
        $threshold = (float)($settings['free_shipping_threshold'] ?? 100);

        // [woocommerce_trust_badges]
        if (str_contains($content, '[woocommerce_trust_badges]')) {
            $badgesHtml = "
            <div class=\"my-6 p-4 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl grid grid-cols-2 sm:grid-cols-4 gap-4 text-center\">
                <div class=\"space-y-1 p-2\">
                    <div class=\"w-8 h-8 mx-auto rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"/></svg></div>
                    <div class=\"text-xs font-bold text-white\">Free Shipping</div>
                    <div class=\"text-[11px] text-slate-400\">On orders over {$sym}{$threshold}</div>
                </div>
                <div class=\"space-y-1 p-2\">
                    <div class=\"w-8 h-8 mx-auto rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z\"/></svg></div>
                    <div class=\"text-xs font-bold text-white\">30-Day Returns</div>
                    <div class=\"text-[11px] text-slate-400\">100% Money-back</div>
                </div>
                <div class=\"space-y-1 p-2\">
                    <div class=\"w-8 h-8 mx-auto rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z\"/></svg></div>
                    <div class=\"text-xs font-bold text-white\">SSL Secure</div>
                    <div class=\"text-[11px] text-slate-400\">256-bit Encryption</div>
                </div>
                <div class=\"space-y-1 p-2\">
                    <div class=\"w-8 h-8 mx-auto rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center\"><svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg></div>
                    <div class=\"text-xs font-bold text-white\">Official Warranty</div>
                    <div class=\"text-[11px] text-slate-400\">2-Year Guarantee</div>
                </div>
            </div>";
            $content = str_replace('[woocommerce_trust_badges]', $badgesHtml, $content);
        }

        // [woocommerce_coupon_box]
        if (str_contains($content, '[woocommerce_coupon_box]')) {
            $couponBox = "
            <div class=\"my-6 p-5 rounded-2xl bg-gradient-to-br from-indigo-950/80 to-slate-900 border border-indigo-500/30 text-slate-200 shadow-xl space-y-3\">
                <div class=\"flex items-center justify-between\">
                    <div class=\"flex items-center space-x-2 text-indigo-400 font-bold text-sm\">
                        <span>🎟️ WooCommerce Promo Offers</span>
                    </div>
                    <span class=\"text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold\">Verified Active</span>
                </div>
                <div class=\"grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs\">
                    <div class=\"p-2.5 rounded-xl bg-slate-900/90 border border-slate-800 flex justify-between items-center\">
                        <div>
                            <span class=\"font-mono font-bold text-amber-300\">APEX20</span>
                            <p class=\"text-[10px] text-slate-400\">20% OFF sitewide</p>
                        </div>
                        <button onclick=\"navigator.clipboard.writeText('APEX20'); alert('Code APEX20 copied! Apply at checkout.');\" class=\"px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-[11px]\">Copy</button>
                    </div>
                    <div class=\"p-2.5 rounded-xl bg-slate-900/90 border border-slate-800 flex justify-between items-center\">
                        <div>
                            <span class=\"font-mono font-bold text-emerald-300\">FREESHIP</span>
                            <p class=\"text-[10px] text-slate-400\">Free Express Delivery</p>
                        </div>
                        <button onclick=\"navigator.clipboard.writeText('FREESHIP'); alert('Code FREESHIP copied! Apply at checkout.');\" class=\"px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-[11px]\">Copy</button>
                    </div>
                </div>
            </div>";
            $content = str_replace('[woocommerce_coupon_box]', $couponBox, $content);
        }

        return $content;
    }
    add_filter('the_content', 'woocommerce_the_content_shortcodes', 12);
}

// 6. Inject WooCommerce Header Styles
if (!function_exists('woocommerce_inject_head')) {
    function woocommerce_inject_head(): void {
        echo "\n    <!-- WooCommerce Commerce Pro Styles -->\n";
        echo "    <style>
        .woo-badge-sale { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); }
        .woo-progress-bar { transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .woo-cart-coupon-box { border-top: 1px solid rgba(51, 65, 85, 0.8); }
    </style>\n";
        echo "    <!-- /WooCommerce Commerce Pro -->\n";
    }
    add_action('storefront_head', 'woocommerce_inject_head', 15);
}

// 7. Inject WooCommerce Cart Drawer Enhancements & Coupon Engine Script
if (!function_exists('woocommerce_inject_footer_script')) {
    function woocommerce_inject_footer_script(): void {
        $settings = woocommerce_get_settings();
        $freeShippingThreshold = (float)($settings['free_shipping_threshold'] ?? 100);
        $currencySymbol = $settings['currency_symbol'] ?? '$';
        ?>
        <!-- WooCommerce Pro Dynamic Cart Drawer Enhancer -->
        <script>
        (function() {
            const FREE_SHIPPING_THRESHOLD = <?php echo json_encode($freeShippingThreshold); ?>;
            const CURRENCY_SYM = <?php echo json_encode($currencySymbol); ?>;

            function injectWooCommerceCartFeatures() {
                const drawer = document.getElementById('cart-drawer');
                if (!drawer) return;

                // Check if coupon UI already added
                if (document.getElementById('woo-coupon-wrapper')) return;

                const footerSlot = drawer.querySelector('.p-6.border-t');
                if (!footerSlot) return;

                // 1. Create Free Shipping Progress Bar
                const progressDiv = document.createElement('div');
                progressDiv.id = 'woo-shipping-meter';
                progressDiv.className = 'mb-4 p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs space-y-1.5';
                progressDiv.innerHTML = `
                    <div class="flex items-center justify-between text-[11px] font-semibold">
                        <span id="woo-meter-text" class="text-indigo-300">📦 Free Express Shipping Meter</span>
                        <span id="woo-meter-percent" class="text-slate-400 font-mono">0%</span>
                    </div>
                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                        <div id="woo-meter-bar" class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 woo-progress-bar rounded-full" style="width: 0%"></div>
                    </div>
                    <p id="woo-meter-msg" class="text-[10px] text-slate-400 text-center font-medium">Add more items to qualify for FREE Shipping!</p>
                `;
                footerSlot.insertBefore(progressDiv, footerSlot.firstChild);

                // 2. Create Coupon Input Section
                const couponDiv = document.createElement('div');
                couponDiv.id = 'woo-coupon-wrapper';
                couponDiv.className = 'mb-4 pt-3 woo-cart-coupon-box';
                couponDiv.innerHTML = `
                    <div class="flex items-center space-x-2">
                        <div class="relative flex-1">
                            <input id="woo-coupon-input" type="text" placeholder="Promo code (e.g. APEX20)" 
                                   class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 uppercase tracking-wider focus:outline-none focus:border-indigo-500 font-mono" />
                        </div>
                        <button id="woo-apply-coupon-btn" type="button" 
                                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-colors shrink-0 shadow-md">
                            Apply
                        </button>
                    </div>
                    <div id="woo-coupon-feedback" class="mt-2 text-[11px] hidden font-medium"></div>
                `;
                footerSlot.insertBefore(couponDiv, progressDiv.nextSibling);

                // Handle coupon click
                const btn = document.getElementById('woo-apply-coupon-btn');
                const input = document.getElementById('woo-coupon-input');
                const feedback = document.getElementById('woo-coupon-feedback');

                btn.addEventListener('click', async function() {
                    const code = input.value.trim();
                    if (!code) {
                        feedback.className = 'mt-2 text-[11px] text-rose-400 font-semibold block';
                        feedback.textContent = 'Please enter a coupon code.';
                        return;
                    }

                    btn.disabled = true;
                    btn.textContent = 'Checking...';

                    const items = (window.CartManager && window.CartManager.state) ? window.CartManager.state.items : [];
                    let subtotal = 0;
                    items.forEach(i => subtotal += (parseFloat(i.price) * (i.quantity || 1)));

                    try {
                        const res = await fetch('/api/v1/woocommerce/apply-coupon', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ code: code, subtotal: subtotal })
                        });
                        const data = await res.json();
                        if (data.success) {
                            feedback.className = 'mt-2 text-[11px] text-emerald-400 font-semibold block flex items-center space-x-1';
                            feedback.innerHTML = `<span>✓</span> <span>${data.message}</span>`;
                            sessionStorage.setItem('woo_applied_coupon', JSON.stringify(data));
                            updateCartTotalsWithDiscount(data);
                        } else {
                            feedback.className = 'mt-2 text-[11px] text-rose-400 font-semibold block';
                            feedback.textContent = data.message || 'Invalid coupon code.';
                        }
                    } catch (e) {
                        feedback.className = 'mt-2 text-[11px] text-rose-400 font-semibold block';
                        feedback.textContent = 'Unable to validate coupon. Try again.';
                    } finally {
                        btn.disabled = false;
                        btn.textContent = 'Apply';
                    }
                });

                updateMeter();
            }

            function updateMeter() {
                const meterBar = document.getElementById('woo-meter-bar');
                const meterPercent = document.getElementById('woo-meter-percent');
                const meterMsg = document.getElementById('woo-meter-msg');
                if (!meterBar || !window.CartManager) return;

                const items = window.CartManager.state ? window.CartManager.state.items : [];
                let subtotal = 0;
                items.forEach(i => subtotal += (parseFloat(i.price) * (i.quantity || 1)));

                const pct = Math.min(100, Math.round((subtotal / FREE_SHIPPING_THRESHOLD) * 100));
                meterBar.style.width = pct + '%';
                meterPercent.textContent = pct + '%';

                if (subtotal >= FREE_SHIPPING_THRESHOLD) {
                    meterBar.className = 'h-full bg-emerald-500 rounded-full woo-progress-bar';
                    meterMsg.className = 'text-[10px] text-emerald-400 text-center font-bold';
                    meterMsg.innerHTML = '🎉 Congratulations! You have unlocked FREE Express Delivery!';
                } else {
                    meterBar.className = 'h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-full woo-progress-bar';
                    const remaining = (FREE_SHIPPING_THRESHOLD - subtotal).toFixed(2);
                    meterMsg.className = 'text-[10px] text-slate-400 text-center font-medium';
                    meterMsg.innerHTML = `Add <strong class="text-white">${CURRENCY_SYM}${remaining}</strong> more to unlock FREE Express Delivery!`;
                }
            }

            function updateCartTotalsWithDiscount(couponData) {
                const totalEl = document.getElementById('cart-drawer-total');
                if (!totalEl || !window.CartManager) return;

                const items = window.CartManager.state ? window.CartManager.state.items : [];
                let subtotal = 0;
                items.forEach(i => subtotal += (parseFloat(i.price) * (i.quantity || 1)));

                let discount = parseFloat(couponData.discount || 0);
                let finalTotal = Math.max(0, subtotal - discount);

                totalEl.innerHTML = `
                    <span class="text-slate-400 text-xs font-normal line-through mr-1">${CURRENCY_SYM}${subtotal.toFixed(2)}</span>
                    <span class="text-emerald-400 font-extrabold">${CURRENCY_SYM}${finalTotal.toFixed(2)}</span>
                `;
            }

            // Hook into CartManager openDrawer & render
            document.addEventListener('DOMContentLoaded', function() {
                injectWooCommerceCartFeatures();
                if (window.CartManager) {
                    const originalOpen = window.CartManager.openDrawer;
                    window.CartManager.openDrawer = function() {
                        originalOpen.apply(this, arguments);
                        setTimeout(injectWooCommerceCartFeatures, 50);
                    };

                    const originalRender = window.CartManager.render;
                    window.CartManager.render = function() {
                        originalRender.apply(this, arguments);
                        updateMeter();
                    };
                }
            });
        })();
        </script>
        <!-- /WooCommerce Pro Dynamic Cart Drawer Enhancer -->
        <?php
    }
    add_action('storefront_footer', 'woocommerce_inject_footer_script', 20);
}

// 8. Filter Android SDUI to Inject Native Commerce Deal Banner
if (!function_exists('woocommerce_sdui_filter')) {
    function woocommerce_sdui_filter(array $screenSpec, array $rawScreen = []): array {
        if (($screenSpec['screen_id'] ?? '') === 'home' || ($screenSpec['screen_id'] ?? '') === 'shop') {
            $wooDeal = [
                'type' => 'AnnouncementCard',
                'icon' => 'shopping-bag',
                'title' => 'WooCommerce Flash Deal',
                'message' => 'Use code APEX20 at checkout for 20% off all hardware! Free shipping over $100.',
                'gradient' => ['from' => '#4f46e5', 'to' => '#9333ea'],
                'dismissable' => false,
                'button_text' => 'Shop Sale',
                'action_url' => '/shop',
            ];
            // Insert right after top banner
            if (isset($screenSpec['components']) && is_array($screenSpec['components'])) {
                array_splice($screenSpec['components'], 1, 0, [$wooDeal]);
            }
        }
        return $screenSpec;
    }
    add_filter('sdui_screen_response', 'woocommerce_sdui_filter', 25, 2);
}
