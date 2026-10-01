<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Layout,
  Sparkles,
  Settings,
  MessageCircle,
  Phone,
  Mail,
  Copy,
  Check,
  Plus,
  Layers,
  Palette,
  ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  pages: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ totalPages: 0, primaryAccent: '#6366f1', supportWidgetActive: true }),
  },
});

const activeTab = ref('settings'); // 'settings' | 'widgets' | 'pages'
const copiedCode = ref(null);

const form = ref({
  primary_accent: props.settings.primary_accent || '#6366f1',
  enable_floating_support: props.settings.enable_floating_support !== false,
  support_phone: props.settings.support_phone || '+1 (800) 555-0199',
  support_email: props.settings.support_email || 'support@example.com',
  whatsapp_number: props.settings.whatsapp_number || '18005550199',
  enable_micro_animations: props.settings.enable_micro_animations !== false,
});

function saveSettings() {
  router.post('/admin/elementor/settings', form.value, {
    preserveScroll: true,
  });
}

function copyShortcode(text, id) {
  navigator.clipboard.writeText(text);
  copiedCode.value = id;
  setTimeout(() => {
    if (copiedCode.value === id) copiedCode.value = null;
  }, 2000);
}

const elementorWidgets = [
  {
    id: 'hero',
    name: 'Hero Banner Section',
    tag: '[elementor_hero title="Next-Gen Architecture" subtitle="Lightning-fast monolithic & headless experience." button_text="Shop Hardware" button_url="/shop" badge="Featured"]',
    description: 'High-impact glassmorphic hero banner with glowing backdrop light, badge pill, and animated CTA button.',
  },
  {
    id: 'pricing',
    name: 'Pricing Plan Box',
    tag: '[elementor_pricing plan="Pro Plan" price="$49" period="/mo" features="Unlimited Products,24/7 Priority Support,Real-Time Telemetry" badge="Most Popular" button_text="Get Started" button_url="/checkout"]',
    description: 'Conversion-optimized pricing card with checkmark lists and call-to-action link.',
  },
  {
    id: 'testimonial',
    name: 'Customer Testimonial',
    tag: '[elementor_testimonial author="Elena Rostova" role="Lead Architect, CloudSync" quote="The fastest CMS storefront we have ever deployed." stars="5"]',
    description: 'Glassmorphic customer review card with star rating and author avatar.',
  },
  {
    id: 'faq',
    name: 'Accordion FAQ Box',
    tag: '[elementor_faq q="Is worldwide express shipping supported?" a="Yes! Orders ship worldwide with express courier tracking within 24 hours."]',
    description: 'Interactive collapsible accordion question and answer item.',
  },
  {
    id: 'button',
    name: 'Animated Button',
    tag: '[elementor_button text="Explore New Arrivals" url="/shop" style="glow"]',
    description: 'Sleek button with keyframe hover glow effect (styles: glow, primary, outline).',
  },
];
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-2xl bg-pink-500/10 border border-pink-500/20 text-pink-400 flex items-center justify-center shadow-lg shadow-pink-500/10">
            <Layout class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-2xl font-bold text-white tracking-tight">Elementor Pro Visual Hub</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-pink-500/20 text-pink-300 font-mono text-xs font-bold">PAGE BUILDER</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Design system styling, floating support concierge, and shortcode widget library</p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <Link
            href="/admin/pages/new"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold text-xs flex items-center space-x-1.5 shadow-lg shadow-pink-600/30 transition-all"
          >
            <Plus class="w-4 h-4" />
            <span>New Elementor Page</span>
          </Link>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Pages Built</span>
          <div class="text-2xl font-extrabold text-pink-400 font-display">
            {{ stats.totalPages }} Pages
          </div>
          <span class="text-[11px] text-slate-400">Rendered with Elementor blocks</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Primary Accent Color</span>
          <div class="flex items-center space-x-2 mt-1">
            <span class="w-5 h-5 rounded-full border border-slate-700" :style="{ backgroundColor: form.primary_accent }"></span>
            <span class="text-xl font-extrabold text-white font-mono">{{ form.primary_accent }}</span>
          </div>
          <span class="text-[11px] text-slate-400">Design system CSS variable</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Live Concierge Widget</span>
          <div class="text-2xl font-extrabold text-emerald-400 font-display">
            {{ form.enable_floating_support ? 'ACTIVE' : 'DISABLED' }}
          </div>
          <span class="text-[11px] text-emerald-300">WhatsApp & Email direct chat</span>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex items-center space-x-2 border-b border-slate-800 pb-2">
        <button
          @click="activeTab = 'settings'"
          :class="[
            activeTab === 'settings' ? 'text-pink-400 border-b-2 border-pink-500 font-bold' : 'text-slate-400 hover:text-white',
            'px-4 py-2 text-xs transition-colors'
          ]"
        >
          Builder & Support Settings
        </button>
        <button
          @click="activeTab = 'widgets'"
          :class="[
            activeTab === 'widgets' ? 'text-pink-400 border-b-2 border-pink-500 font-bold' : 'text-slate-400 hover:text-white',
            'px-4 py-2 text-xs transition-colors'
          ]"
        >
          Widget Library & Shortcodes
        </button>
        <button
          @click="activeTab = 'pages'"
          :class="[
            activeTab === 'pages' ? 'text-pink-400 border-b-2 border-pink-500 font-bold' : 'text-slate-400 hover:text-white',
            'px-4 py-2 text-xs transition-colors'
          ]"
        >
          Elementor Pages ({{ pages.length }})
        </button>
      </div>

      <!-- Tab 1: Builder Settings -->
      <div v-if="activeTab === 'settings'" class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 shadow-xl max-w-2xl space-y-6">
        <div class="border-b border-slate-800 pb-3">
          <h2 class="text-base font-bold text-white">Global Elementor Builder & Support Settings</h2>
          <p class="text-xs text-slate-400">Configure storefront theme accents and floating customer concierge desk</p>
        </div>

        <form @submit.prevent="saveSettings" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Primary Brand Accent Color</label>
            <div class="flex items-center space-x-3">
              <input v-model="form.primary_accent" type="color" class="w-10 h-10 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer p-1" />
              <input v-model="form.primary_accent" type="text" class="flex-1 px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>
          </div>

          <div class="pt-4 border-t border-slate-800 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-pink-400 flex items-center space-x-1.5">
              <MessageCircle class="w-4 h-4" />
              <span>Floating Live Support Concierge Widget</span>
            </h3>

            <label class="flex items-center space-x-3 cursor-pointer">
              <input v-model="form.enable_floating_support" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-pink-600 focus:ring-0" />
              <span class="text-slate-200 font-semibold">Enable floating support icon on storefront footer</span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-slate-300 mb-1">Support Telephone Line</label>
                <input v-model="form.support_phone" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
              </div>
              <div>
                <label class="block font-semibold text-slate-300 mb-1">Support Email Desk</label>
                <input v-model="form.support_email" type="email" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-300 mb-1">WhatsApp Direct Number (No '+' or spaces)</label>
              <input v-model="form.whatsapp_number" type="text" placeholder="e.g. 18005550199" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>
          </div>

          <div class="pt-4 border-t border-slate-800 space-y-3">
            <label class="flex items-center space-x-3 cursor-pointer">
              <input v-model="form.enable_micro_animations" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-pink-600 focus:ring-0" />
              <span class="text-slate-200 font-semibold">Enable Elementor micro-animations and button glow keyframes</span>
            </label>
          </div>

          <div class="pt-4 border-t border-slate-800">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-pink-600/30 transition-all">
              Save Builder Settings
            </button>
          </div>
        </form>
      </div>

      <!-- Tab 2: Widgets Showcase -->
      <div v-else-if="activeTab === 'widgets'" class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
          <div
            v-for="w in elementorWidgets"
            :key="w.id"
            class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-2 text-xs"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                <span class="font-bold text-white text-sm">{{ w.name }}</span>
              </div>
              <button
                @click="copyShortcode(w.tag, w.id)"
                class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center space-x-1"
              >
                <Check v-if="copiedCode === w.id" class="w-3.5 h-3.5 text-emerald-400" />
                <Copy v-else class="w-3.5 h-3.5" />
                <span>{{ copiedCode === w.id ? 'Copied Tag' : 'Copy Shortcode' }}</span>
              </button>
            </div>
            <p class="text-slate-400">{{ w.description }}</p>
            <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-pink-300 overflow-x-auto whitespace-pre-wrap">{{ w.tag }}</pre>
          </div>
        </div>
      </div>

      <!-- Tab 3: Pages Built with Elementor -->
      <div v-else class="space-y-4">
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
              <tr>
                <th class="py-3 px-4">Page Title</th>
                <th class="py-3 px-4">URL Slug</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr v-for="page in pages" :key="page.id" class="hover:bg-slate-800/30">
                <td class="py-3.5 px-4 font-sans">
                  <span class="font-bold text-white">{{ page.title }}</span>
                </td>
                <td class="py-3.5 px-4 text-indigo-400">/page/{{ page.slug }}</td>
                <td class="py-3.5 px-4 font-sans">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 uppercase">
                    {{ page.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right font-sans space-x-2 whitespace-nowrap">
                  <a :href="`/page/${page.slug}`" target="_blank" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs">
                    View
                  </a>
                  <Link :href="`/admin/pages/${page.id}/edit?builder=classic`" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs" title="Normal page edit">
                    Edit
                  </Link>
                  <Link :href="`/admin/pages/${page.id}/edit?builder=elementor`" class="px-3 py-1 rounded-lg bg-pink-600 hover:bg-pink-500 text-white font-semibold text-xs" title="Edit visually with Elementor">
                    Edit with Elementor
                  </Link>
                </td>
              </tr>
              <tr v-if="!pages.length">
                <td colspan="4" class="text-center py-8 text-slate-500">No static pages found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
