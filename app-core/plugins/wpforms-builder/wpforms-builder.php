<?php

/**
 * Plugin Name: WPForms Lead & Form Builder
 * Plugin URI: https://wpforms.com
 * Description: Embed contact forms, lead capture boxes, and newsletter subscriptions with shortcodes and animated client-side validation.
 * Version: 1.8.7
 * Author: WPForms Team
 * Author URI: https://wpforms.com
 */

declare(strict_types=1);

if (!function_exists('wpforms_get_settings')) {
    function wpforms_get_settings(): array {
        $default = [
            'notification_email' => 'admin@example.com',
            'enable_honeypot' => true,
            'success_message' => 'Thank you! Your message has been received. Our team will get back to you within 24 hours.',
        ];
        return cms_get_plugin_settings('wpforms-builder', $default);
    }
}

// 1. Process [wpforms id="..."] shortcodes via the_content
if (!function_exists('wpforms_the_content_filter')) {
    function wpforms_the_content_filter(string $content): string {
        $settings = wpforms_get_settings();
        $successMsg = htmlspecialchars($settings['success_message'] ?? 'Message received!');

        // [wpforms id="contact"]
        $content = preg_replace_callback('/\[wpforms\s+id=[\'"]contact[\'"][^\]]*\]/i', function() use ($successMsg) {
            return "
            <div class=\"my-8 p-6 sm:p-8 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl space-y-4 max-w-xl mx-auto text-left\">
                <div class=\"border-b border-slate-800 pb-3\">
                    <h3 class=\"text-lg font-bold text-white flex items-center space-x-2\">
                        <span>✉️ Send a Message</span>
                    </h3>
                    <p class=\"text-xs text-slate-400 mt-1\">Have a question or request? Fill out the form below.</p>
                </div>
                <form onsubmit=\"event.preventDefault(); const f = this; const b = f.querySelector('button'); b.disabled = true; b.textContent = 'Sending...'; setTimeout(() => { f.innerHTML = '<div class=\\'p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold text-center flex items-center justify-center space-x-2\\'><span>✓</span><span>{$successMsg}</span></div>'; }, 600);\" class=\"space-y-4\">
                    <div class=\"grid grid-cols-1 sm:grid-cols-2 gap-4\">
                        <div>
                            <label class=\"block text-xs font-semibold text-slate-300 mb-1\">Your Name</label>
                            <input type=\"text\" required placeholder=\"John Doe\" class=\"w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500\" />
                        </div>
                        <div>
                            <label class=\"block text-xs font-semibold text-slate-300 mb-1\">Email Address</label>
                            <input type=\"email\" required placeholder=\"john@example.com\" class=\"w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500\" />
                        </div>
                    </div>
                    <div>
                        <label class=\"block text-xs font-semibold text-slate-300 mb-1\">Subject</label>
                        <input type=\"text\" required placeholder=\"Inquiry regarding product availability\" class=\"w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500\" />
                    </div>
                    <div>
                        <label class=\"block text-xs font-semibold text-slate-300 mb-1\">Message</label>
                        <textarea rows=\"4\" required placeholder=\"Write your message here...\" class=\"w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500\"></textarea>
                    </div>
                    <button type=\"submit\" class=\"w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-indigo-600/30 transition-all\">
                        Submit Inquiry
                    </button>
                </form>
            </div>";
        }, $content);

        // [wpforms id="newsletter"]
        $content = preg_replace_callback('/\[wpforms\s+id=[\'"]newsletter[\'"][^\]]*\]/i', function() {
            return "
            <div class=\"my-6 p-6 rounded-2xl bg-gradient-to-r from-indigo-950/80 to-purple-950/80 border border-indigo-500/30 text-center space-y-3\">
                <h4 class=\"text-base font-bold text-white font-display\">Join Our VIP Inner Circle</h4>
                <p class=\"text-xs text-slate-300 max-w-md mx-auto\">Get weekly hardware breakdowns, exclusive discount drops, and early firmware releases.</p>
                <form onsubmit=\"event.preventDefault(); this.innerHTML = '<span class=\\'text-xs font-bold text-emerald-400\\'>✓ You are subscribed! Check your inbox for your 10% welcome coupon.</span>';\" class=\"flex max-w-md mx-auto gap-2 pt-2\">
                    <input type=\"email\" required placeholder=\"Enter your work email\" class=\"flex-1 px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-400\" />
                    <button type=\"submit\" class=\"px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shrink-0 shadow-md\">Subscribe</button>
                </form>
            </div>";
        }, $content);

        return $content;
    }
    add_filter('the_content', 'wpforms_the_content_filter', 13);
}
