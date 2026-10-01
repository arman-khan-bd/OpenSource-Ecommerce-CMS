<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Globe,
  Search,
  Share2,
  CheckCircle,
  FileCode,
  Sliders,
  ExternalLink,
  Shield,
  Layers,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  pluginSlug: {
    type: String,
    default: 'yoast-seo-pro',
  },
  siteDefaults: {
    type: Object,
    default: () => ({ title: 'Apex Modern CMS Store', description: '', url: 'http://localhost' }),
  },
});

const form = ref({
  plugin_slug: props.pluginSlug,
  site_name: props.settings.site_name || props.siteDefaults.title,
  separator: props.settings.separator || '—',
  title_template: props.settings.title_template || '%title% %separator% %sitename%',
  twitter_handle: props.settings.twitter_handle || '@ApexCommerce',
  social_share_image: props.settings.social_share_image || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=1200&q=80',
  enable_json_ld_schema: props.settings.enable_json_ld_schema !== false,
  enable_breadcrumbs: props.settings.enable_breadcrumbs !== false,
  enable_sitemap_xml: props.settings.enable_sitemap_xml !== false,
  robots_default: props.settings.robots_default || 'index, follow, max-image-preview:large',
});

// Live SERP Preview computation
const serpTitle = computed(() => {
  return `Wireless Noise-Canceling Headphones ${form.value.separator} ${form.value.site_name}`;
});

const serpUrl = computed(() => {
  return `${props.siteDefaults.url}/product/headphones`;
});

const serpDescription = computed(() => {
  return 'Experience premium acoustics with 40-hour battery life, hybrid active noise cancellation, and high-res audio drivers. In stock with complimentary express shipping.';
});

function saveSettings() {
  router.post('/admin/seo/settings', form.value, {
    preserveScroll: true,
  });
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-500/10">
            <Globe class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-2xl font-bold text-white tracking-tight">Yoast SEO Elite Suite</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-mono text-xs font-bold">SEO PRO</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Schema.org JSON-LD structured data, Google SERP simulator, OpenGraph & XML sitemaps</p>
          </div>
        </div>

        <a href="/sitemap.xml" target="_blank" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all self-start">
          <FileCode class="w-4 h-4 text-emerald-400" />
          <span>View XML Sitemap</span>
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>

      <!-- Google SERP Live Preview Simulator -->
      <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-slate-800/80">
          <div class="flex items-center space-x-2">
            <Search class="w-4 h-4 text-indigo-400" />
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">Live Google SERP Snippet Simulator</h3>
          </div>
          <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">Schema Rich Snippet</span>
        </div>

        <!-- Google Result Card Simulation -->
        <div class="p-4 rounded-2xl bg-white text-slate-900 space-y-1 shadow-md max-w-2xl font-sans">
          <div class="flex items-center space-x-2 text-xs text-slate-600">
            <span class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-700">G</span>
            <span class="truncate font-medium">{{ serpUrl }}</span>
          </div>
          <h4 class="text-lg font-medium text-blue-800 hover:underline cursor-pointer leading-snug">
            {{ serpTitle }}
          </h4>
          <p class="text-xs text-slate-700 leading-relaxed">
            {{ serpDescription }}
          </p>
          <div class="pt-1 flex items-center space-x-3 text-[11px] text-slate-500 font-medium">
            <span class="text-emerald-700 font-bold">★ 4.9 (128 reviews)</span>
            <span>•</span>
            <span class="text-slate-700 font-bold">$249.00</span>
            <span>•</span>
            <span class="text-emerald-700">In stock</span>
          </div>
        </div>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="saveSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Col 1: Titles & Search Indexing -->
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-5 shadow-xl">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2 pb-2 border-b border-slate-800">
            <Sliders class="w-4 h-4 text-emerald-400" />
            <span>Title Templates & Robots Directives</span>
          </h3>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Brand / Site Name</label>
              <input v-model="form.site_name" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block font-semibold text-slate-300 mb-1">Title Separator</label>
                <select v-model="form.separator" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono">
                  <option value="—">— (Em Dash)</option>
                  <option value="-">- (Hyphen)</option>
                  <option value="|">| (Pipe)</option>
                  <option value="•">• (Bullet)</option>
                </select>
              </div>
              <div>
                <label class="block font-semibold text-slate-300 mb-1">Robots Directives</label>
                <select v-model="form.robots_default" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono">
                  <option value="index, follow, max-image-preview:large">index, follow</option>
                  <option value="noindex, follow">noindex, follow</option>
                  <option value="noindex, nofollow">noindex, nofollow</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-300 mb-1">Title Template Format</label>
              <input v-model="form.title_template" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
              <p class="text-[11px] text-slate-500 mt-1">Tags supported: <code class="text-indigo-400">%title%</code>, <code class="text-indigo-400">%separator%</code>, <code class="text-indigo-400">%sitename%</code></p>
            </div>
          </div>
        </div>

        <!-- Col 2: Social OpenGraph & Schema.org -->
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-5 shadow-xl">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2 pb-2 border-b border-slate-800">
            <Share2 class="w-4 h-4 text-purple-400" />
            <span>Social Sharing & Schema.org Structured Data</span>
          </h3>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Twitter / X Publisher Handle</label>
              <input v-model="form.twitter_handle" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>

            <div>
              <label class="block font-semibold text-slate-300 mb-1">Default Social Sharing Image URL</label>
              <input v-model="form.social_share_image" type="url" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>

            <div class="pt-2 space-y-2.5 border-t border-slate-800">
              <label class="flex items-center space-x-3 cursor-pointer">
                <input v-model="form.enable_json_ld_schema" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0" />
                <span class="text-slate-200 font-semibold">Enable Schema.org JSON-LD (Product, Organization, WebSite)</span>
              </label>

              <label class="flex items-center space-x-3 cursor-pointer">
                <input v-model="form.enable_breadcrumbs" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0" />
                <span class="text-slate-200 font-semibold">Enable Google Breadcrumbs Snippets [seo_breadcrumbs]</span>
              </label>

              <label class="flex items-center space-x-3 cursor-pointer">
                <input v-model="form.enable_sitemap_xml" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-emerald-600 focus:ring-0" />
                <span class="text-slate-200 font-semibold">Inject XML Sitemap reference in HTML head</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Full Width Save Button -->
        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="px-8 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition-all">
            Save SEO Settings
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
