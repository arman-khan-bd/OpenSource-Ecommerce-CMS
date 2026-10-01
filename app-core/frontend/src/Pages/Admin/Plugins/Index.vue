<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Puzzle,
  CheckCircle,
  Activity,
  Radio,
  Clock,
  Code,
  Search,
  Settings,
  ShoppingBag,
  Layout,
  Globe,
  Zap,
  Send,
  Sliders,
  Copy,
  Check,
  X,
  Sparkles,
  ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
  plugins: {
    type: Array,
    default: () => [],
  },
  hookInspector: {
    type: Object,
    default: () => ({ actions: {}, filters: {}, total_actions: 0, total_filters: 0, execution_history: [] }),
  },
});

const activeTab = ref('plugins'); // 'plugins' | 'hooks'
const searchQuery = ref('');
const selectedCategory = ref('all'); // 'all' | 'ecommerce' | 'builders' | 'seo' | 'performance'
const selectedPluginForSettings = ref(null);
const showSettingsModal = ref(false);
const settingsJson = ref('');
const settingsError = ref('');
const isSavingSettings = ref(false);
const showShortcodesModal = ref(false);
const copiedCode = ref(null);

function getPluginIcon(slug) {
  if (slug.includes('woo') || slug.includes('commerce')) return ShoppingBag;
  if (slug.includes('elementor') || slug.includes('builder')) return Layout;
  if (slug.includes('seo') || slug.includes('yoast')) return Globe;
  if (slug.includes('rocket') || slug.includes('speed')) return Zap;
  if (slug.includes('form')) return Send;
  return Puzzle;
}

function getPluginBadgeColor(slug) {
  if (slug.includes('woo') || slug.includes('commerce')) return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
  if (slug.includes('elementor') || slug.includes('builder')) return 'bg-pink-500/10 text-pink-400 border-pink-500/20';
  if (slug.includes('seo') || slug.includes('yoast')) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
  if (slug.includes('rocket') || slug.includes('speed')) return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
  if (slug.includes('form')) return 'bg-sky-500/10 text-sky-400 border-sky-500/20';
  return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
}

function getPluginCategory(slug) {
  if (slug.includes('woo') || slug.includes('commerce')) return 'ecommerce';
  if (slug.includes('elementor')) return 'builders';
  if (slug.includes('seo') || slug.includes('yoast')) return 'seo';
  if (slug.includes('rocket') || slug.includes('speed')) return 'performance';
  if (slug.includes('form')) return 'builders';
  return 'tools';
}

const filteredPlugins = computed(() => {
  return (props.plugins || []).filter((p) => {
    const matchesSearch =
      p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.slug.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (p.description || '').toLowerCase().includes(searchQuery.value.toLowerCase());

    if (!matchesSearch) return false;

    if (selectedCategory.value === 'all') return true;
    if (selectedCategory.value === 'active') return p.is_active;
    if (selectedCategory.value === 'inactive') return !p.is_active;

    return getPluginCategory(p.slug) === selectedCategory.value;
  });
});

function togglePlugin(slug) {
  router.post('/admin/plugins/toggle', { slug }, { preserveScroll: true });
}

function openSettings(plugin) {
  selectedPluginForSettings.value = plugin;
  settingsJson.value = JSON.stringify(plugin.settings || {}, null, 2);
  settingsError.value = '';
  showSettingsModal.value = true;
}

function closeSettings() {
  showSettingsModal.value = false;
  selectedPluginForSettings.value = null;
}

function saveSettings() {
  try {
    const parsed = JSON.parse(settingsJson.value);
    isSavingSettings.value = true;
    settingsError.value = '';

    router.post(
      '/admin/plugins/settings',
      {
        slug: selectedPluginForSettings.value.slug,
        settings: parsed,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          isSavingSettings.value = false;
          closeSettings();
        },
        onError: (errors) => {
          isSavingSettings.value = false;
          settingsError.value = errors.message || 'Failed to save settings.';
        },
      }
    );
  } catch (err) {
    settingsError.value = 'Invalid JSON: ' + err.message;
  }
}

function copyToClipboard(text, id) {
  navigator.clipboard.writeText(text);
  copiedCode.value = id;
  setTimeout(() => {
    if (copiedCode.value === id) copiedCode.value = null;
  }, 2000);
}

const shortcodeList = [
  {
    id: 'woo-coupon',
    name: 'WooCommerce Coupon Box',
    plugin: 'WooCommerce Commerce Pro',
    tag: '[woocommerce_coupon_box]',
    desc: 'Interactive promo code validator and coupon copy cards on any post or page.',
  },
  {
    id: 'woo-trust',
    name: 'WooCommerce Trust & Guarantee Badges',
    plugin: 'WooCommerce Commerce Pro',
    tag: '[woocommerce_trust_badges]',
    desc: '4 high-converting trust badges (Free Shipping, Money-Back, SSL 256-Bit, Warranty).',
  },
  {
    id: 'el-hero',
    name: 'Elementor Pro Glowing Hero Banner',
    plugin: 'Elementor Pro Visual Builder',
    tag: '[elementor_hero title="Next-Gen Architecture" subtitle="Lightning-fast monolithic & headless experience." button_text="Shop Hardware" button_url="/shop" badge="Featured"]',
    desc: 'Glowing hero container with ambient backdrop lighting and CTA button.',
  },
  {
    id: 'el-btn',
    name: 'Elementor Pro Animated Button',
    plugin: 'Elementor Pro Visual Builder',
    tag: '[elementor_button text="Get Started" url="/shop" style="glow"]',
    desc: 'Interactive styled button with keyframe hover animations (styles: glow, primary, outline).',
  },
  {
    id: 'el-pricing',
    name: 'Elementor Pro Pricing Card',
    plugin: 'Elementor Pro Visual Builder',
    tag: '[elementor_pricing plan="Pro Plan" price="$49" period="/mo" features="Unlimited Products,24/7 Support,Analytics" badge="Popular"]',
    desc: 'Modern pricing box with checkmark features and custom checkout URL.',
  },
  {
    id: 'el-testimonial',
    name: 'Elementor Pro Testimonial Card',
    plugin: 'Elementor Pro Visual Builder',
    tag: '[elementor_testimonial author="Sarah Connor" role="Lead Architect" quote="The fastest CMS storefront we have ever deployed." stars="5"]',
    desc: 'Glassmorphic customer review card with star rating and author avatar.',
  },
  {
    id: 'el-faq',
    name: 'Elementor Pro FAQ Accordion',
    plugin: 'Elementor Pro Visual Builder',
    tag: '[elementor_faq q="Is worldwide shipping supported?" a="Yes! Orders ship worldwide with express courier tracking within 24 hours."]',
    desc: 'Expandable accordion FAQ box with smooth CSS toggle.',
  },
  {
    id: 'wpforms-contact',
    name: 'WPForms Interactive Contact Form',
    plugin: 'WPForms Lead Builder',
    tag: '[wpforms id="contact"]',
    desc: 'AJAX-enabled contact inquiry form with Name, Email, Subject, and anti-spam protection.',
  },
  {
    id: 'wpforms-newsletter',
    name: 'WPForms Newsletter Subscription',
    plugin: 'WPForms Lead Builder',
    tag: '[wpforms id="newsletter"]',
    desc: 'Inline lead capture newsletter form with instant confirmation.',
  },
  {
    id: 'seo-breadcrumbs',
    name: 'Yoast SEO Breadcrumb Trail',
    plugin: 'Yoast SEO Elite Suite',
    tag: '[seo_breadcrumbs]',
    desc: 'Google-compliant breadcrumb navigation links matching current URL hierarchy.',
  },
];
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Plugins & Hook Engine</h1>
            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-mono text-xs font-bold">
              {{ plugins.length }} Installed
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            WordPress-compatible action & filter hooks engine with live execution telemetry and visual extensibility
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Shortcodes Cheatsheet Button -->
          <button
            @click="showShortcodesModal = true"
            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all shadow-sm"
          >
            <Sparkles class="w-3.5 h-3.5 text-amber-400" />
            <span>Shortcodes Cheatsheet</span>
          </button>

          <!-- Tab Switcher -->
          <div class="flex items-center space-x-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
            <button
              @click="activeTab = 'plugins'"
              :class="[
                activeTab === 'plugins' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all',
              ]"
            >
              Plugins ({{ plugins.length }})
            </button>
            <button
              @click="activeTab = 'hooks'"
              :class="[
                activeTab === 'hooks' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
                'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5',
              ]"
            >
              <Activity class="w-3.5 h-3.5" />
              <span>Hook Inspector</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Plugins Tab -->
      <div v-if="activeTab === 'plugins'" class="space-y-6">
        <!-- Search and Category Filters -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/60 p-4 rounded-2xl border border-slate-800/80">
          <!-- Search -->
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search plugins by name, slug or description..."
              class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
          </div>

          <!-- Category Badges -->
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              v-for="cat in [
                { id: 'all', label: 'All Plugins' },
                { id: 'active', label: 'Active' },
                { id: 'ecommerce', label: 'eCommerce' },
                { id: 'builders', label: 'Builders & Forms' },
                { id: 'seo', label: 'SEO' },
                { id: 'performance', label: 'Speed' },
              ]"
              :key="cat.id"
              @click="selectedCategory = cat.id"
              :class="[
                selectedCategory === cat.id
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'bg-slate-950/80 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800',
                'px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all',
              ]"
            >
              {{ cat.label }}
            </button>
          </div>
        </div>

        <!-- Plugins Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="plugin in filteredPlugins"
            :key="plugin.slug"
            class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 flex flex-col justify-between shadow-xl space-y-4 hover:border-slate-700/80 transition-all group"
          >
            <div class="space-y-3">
              <div class="flex items-start justify-between">
                <div :class="['w-11 h-11 rounded-xl border flex items-center justify-center transition-transform group-hover:scale-105', getPluginBadgeColor(plugin.slug)]">
                  <component :is="getPluginIcon(plugin.slug)" class="w-5 h-5" />
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-[11px] font-mono px-2 py-0.5 rounded-md bg-slate-950 text-slate-400 border border-slate-800">
                    v{{ plugin.version }}
                  </span>
                </div>
              </div>

              <div>
                <h3 class="font-bold text-white text-base flex items-center space-x-1.5">
                  <span>{{ plugin.name }}</span>
                </h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed line-clamp-3">
                  {{ plugin.description }}
                </p>
              </div>

              <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                <span>By {{ plugin.author }}</span>
                <span class="font-mono text-slate-600 text-[10px]">{{ plugin.slug }}</span>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between gap-2">
              <div class="flex items-center space-x-2">
                <span :class="plugin.is_active ? 'text-emerald-400' : 'text-slate-500'" class="text-xs font-semibold flex items-center space-x-1.5">
                  <span :class="plugin.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'" class="w-2 h-2 rounded-full"></span>
                  <span>{{ plugin.is_active ? 'Active' : 'Inactive' }}</span>
                </span>

                <!-- Configure Settings Button -->
                <button
                  v-if="plugin.settings && Object.keys(plugin.settings).length"
                  @click="openSettings(plugin)"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                  title="Configure Plugin Settings"
                >
                  <Settings class="w-4 h-4" />
                </button>
              </div>

              <button
                @click="togglePlugin(plugin.slug)"
                :class="[
                  plugin.is_active
                    ? 'bg-rose-950/60 text-rose-300 hover:bg-rose-900/60 border-rose-500/30'
                    : 'bg-indigo-600 text-white hover:bg-indigo-500 border-indigo-500',
                  'px-4 py-2 rounded-xl text-xs font-semibold border transition-all active:scale-95 shadow-md',
                ]"
              >
                {{ plugin.is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="!filteredPlugins.length" class="text-center py-16 bg-slate-900/40 rounded-2xl border border-slate-800 text-slate-400 space-y-2">
          <Puzzle class="w-10 h-10 mx-auto text-slate-600" />
          <p class="text-sm font-semibold">No plugins found matching "{{ searchQuery }}"</p>
          <button @click="searchQuery = ''; selectedCategory = 'all'" class="text-xs text-indigo-400 hover:underline">
            Reset filters
          </button>
        </div>
      </div>

      <!-- Hook Inspector Tab -->
      <div v-else class="space-y-6">
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <span class="text-xs text-slate-400 font-semibold uppercase">Total Actions</span>
            <p class="text-xl font-bold text-indigo-400 mt-1">{{ hookInspector.total_actions }} Registered</p>
          </div>
          <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <span class="text-xs text-slate-400 font-semibold uppercase">Total Filters</span>
            <p class="text-xl font-bold text-purple-400 mt-1">{{ hookInspector.total_filters }} Registered</p>
          </div>
          <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <span class="text-xs text-slate-400 font-semibold uppercase">Architecture</span>
            <p class="text-xl font-bold text-emerald-400 mt-1">WP + EventDispatcher</p>
          </div>
          <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
            <span class="text-xs text-slate-400 font-semibold uppercase">Live Execution Log</span>
            <p class="text-xl font-bold text-white mt-1">{{ hookInspector.execution_history ? hookInspector.execution_history.length : 0 }} Calls Tracked</p>
          </div>
        </div>

        <!-- Registered Hooks Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Actions Table -->
          <div class="bg-slate-900/70 rounded-2xl border border-slate-800 p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
              <Radio class="w-4 h-4 text-indigo-400" />
              <span>Registered Action Hooks (`do_action`)</span>
            </h3>

            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="(act, name) in hookInspector.actions"
                :key="name"
                class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1.5 text-xs"
              >
                <div class="flex items-center justify-between">
                  <span class="font-mono font-bold text-indigo-300">{{ name }}</span>
                  <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold text-[10px]">
                    {{ act.count }} Callbacks
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 pl-2 border-l border-slate-800 space-y-0.5">
                  <div v-for="(cb, idx) in act.callbacks" :key="idx" class="flex justify-between font-mono">
                    <span class="truncate max-w-[220px]">{{ cb.id }}</span>
                    <span class="text-slate-500">P: {{ cb.priority }}</span>
                  </div>
                </div>
              </div>

              <div v-if="!hookInspector.actions || !Object.keys(hookInspector.actions).length" class="text-center py-8 text-slate-500 text-xs">
                No active actions registered.
              </div>
            </div>
          </div>

          <!-- Filters Table -->
          <div class="bg-slate-900/70 rounded-2xl border border-slate-800 p-6 space-y-4 shadow-xl">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
              <Code class="w-4 h-4 text-purple-400" />
              <span>Registered Filter Hooks (`apply_filters`)</span>
            </h3>

            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="(flt, name) in hookInspector.filters"
                :key="name"
                class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1.5 text-xs"
              >
                <div class="flex items-center justify-between">
                  <span class="font-mono font-bold text-purple-300">{{ name }}</span>
                  <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px]">
                    {{ flt.count }} Callbacks
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 pl-2 border-l border-slate-800 space-y-0.5">
                  <div v-for="(cb, idx) in flt.callbacks" :key="idx" class="flex justify-between font-mono">
                    <span class="truncate max-w-[220px]">{{ cb.id }}</span>
                    <span class="text-slate-500">P: {{ cb.priority }}</span>
                  </div>
                </div>
              </div>

              <div v-if="!hookInspector.filters || !Object.keys(hookInspector.filters).length" class="text-center py-8 text-slate-500 text-xs">
                No active filters registered.
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Execution Log -->
        <div class="bg-slate-900/70 rounded-2xl border border-slate-800 p-6 space-y-4 shadow-xl">
          <h3 class="text-base font-bold text-white flex items-center space-x-2">
            <Clock class="w-4 h-4 text-emerald-400" />
            <span>Real-Time Hook Execution Telemetry</span>
          </h3>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 font-mono text-[11px]">
                <tr>
                  <th class="py-2.5 px-3">Type</th>
                  <th class="py-2.5 px-3">Hook Tag</th>
                  <th class="py-2.5 px-3">Callbacks Run</th>
                  <th class="py-2.5 px-3">Duration (ms)</th>
                  <th class="py-2.5 px-3 text-right">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
                <tr v-for="(log, idx) in (hookInspector.execution_history || []).slice(0, 15)" :key="idx" class="hover:bg-slate-800/30">
                  <td class="py-2.5 px-3">
                    <span :class="log.type === 'action' ? 'text-indigo-400' : 'text-purple-400'" class="uppercase font-bold">
                      {{ log.type }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-white">{{ log.hook }}</td>
                  <td class="py-2.5 px-3 text-slate-300">{{ log.callbacks_run }}</td>
                  <td class="py-2.5 px-3 text-emerald-400">{{ log.duration_ms }}ms</td>
                  <td class="py-2.5 px-3 text-right text-slate-500">{{ log.timestamp }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Plugin Settings Modal -->
    <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center space-x-3">
            <div :class="['w-9 h-9 rounded-xl border flex items-center justify-center', getPluginBadgeColor(selectedPluginForSettings?.slug || '')]">
              <component :is="getPluginIcon(selectedPluginForSettings?.slug || '')" class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-base font-bold text-white">{{ selectedPluginForSettings?.name }}</h3>
              <p class="text-xs text-slate-400">Plugin Configuration Settings</p>
            </div>
          </div>
          <button @click="closeSettings" class="text-slate-400 hover:text-white p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-semibold text-slate-300">
            Edit Settings (JSON Format)
          </label>
          <textarea
            v-model="settingsJson"
            rows="12"
            class="w-full p-3.5 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-400 focus:outline-none focus:border-indigo-500 leading-relaxed"
          ></textarea>
          <p v-if="settingsError" class="text-xs text-rose-400 font-semibold">{{ settingsError }}</p>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
          <span class="text-[11px] text-slate-500">Live saved to database and hook engine</span>
          <div class="flex items-center space-x-2">
            <button
              @click="closeSettings"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
            >
              Cancel
            </button>
            <button
              @click="saveSettings"
              :disabled="isSavingSettings"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center space-x-1.5"
            >
              <span>{{ isSavingSettings ? 'Saving...' : 'Save Settings' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Shortcodes Cheatsheet Modal -->
    <div v-if="showShortcodesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-3xl shadow-2xl p-6 space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
          <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
              <Sparkles class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-base font-bold text-white">WordPress-Style Shortcode Cheatsheet</h3>
              <p class="text-xs text-slate-400">Paste these shortcodes into any Post, Product description, or Twig template</p>
            </div>
          </div>
          <button @click="showShortcodesModal = false" class="text-slate-400 hover:text-white p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-3 overflow-y-auto flex-1 pr-1">
          <div
            v-for="sc in shortcodeList"
            :key="sc.id"
            class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs"
          >
            <div class="flex items-center justify-between">
              <div>
                <span class="font-bold text-white">{{ sc.name }}</span>
                <span class="ml-2 text-[10px] px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-medium">
                  {{ sc.plugin }}
                </span>
              </div>
              <button
                @click="copyToClipboard(sc.tag, sc.id)"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-[11px] flex items-center space-x-1"
              >
                <Check v-if="copiedCode === sc.id" class="w-3.5 h-3.5 text-emerald-400" />
                <Copy v-else class="w-3.5 h-3.5" />
                <span>{{ copiedCode === sc.id ? 'Copied!' : 'Copy' }}</span>
              </button>
            </div>
            <pre class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 font-mono text-[11px] text-amber-300 overflow-x-auto whitespace-pre-wrap">{{ sc.tag }}</pre>
            <p class="text-slate-400 text-[11px]">{{ sc.desc }}</p>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800 text-right shrink-0">
          <button
            @click="showShortcodesModal = false"
            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold"
          >
            Close Cheatsheet
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
