<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Palette, CheckCircle, Sliders, ExternalLink, Sparkles } from 'lucide-vue-next';

const props = defineProps({
  themes: Array,
  activeTheme: String,
  customizerSettings: Object,
});

const isCustomizeModalOpen = ref(false);
const customizerForm = ref({ ...props.customizerSettings });

function activateTheme(slug) {
  router.post('/admin/themes/activate', { slug });
}

function openCustomizer() {
  customizerForm.value = { ...props.customizerSettings };
  isCustomizeModalOpen.value = true;
}

function saveCustomizer() {
  router.post('/admin/themes/customize', {
    theme: props.activeTheme,
    settings: customizerForm.value,
  }, {
    onSuccess: () => {
      isCustomizeModalOpen.value = false;
    }
  });
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Storefront Themes</h1>
          <p class="text-xs text-slate-400 mt-1">Switch server-rendered Twig storefront themes with zero downtime</p>
        </div>
        <button @click="openCustomizer"
                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all self-start">
          <Sliders class="w-4 h-4" />
          <span>Customize Active Theme</span>
        </button>
      </div>

      <!-- Themes Gallery Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="theme in themes" :key="theme.slug"
             :class="[
               theme.is_active ? 'ring-2 ring-indigo-500 shadow-glow' : 'hover:border-slate-700',
               'bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl flex flex-col justify-between transition-all'
             ]">
          <div>
            <!-- Preview Image -->
            <div class="aspect-video bg-slate-950 overflow-hidden relative">
              <img :src="theme.screenshot" :alt="theme.name" class="w-full h-full object-cover" />
              <div v-if="theme.is_active" class="absolute top-3 right-3 px-3 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-extrabold uppercase tracking-wider shadow-lg flex items-center space-x-1">
                <CheckCircle class="w-3.5 h-3.5" />
                <span>Active Theme</span>
              </div>
            </div>

            <!-- Details -->
            <div class="p-5 space-y-2">
              <div class="flex items-center justify-between">
                <h3 class="font-bold text-white text-base">{{ theme.name }}</h3>
                <span class="text-slate-500 font-mono text-xs">v{{ theme.version }}</span>
              </div>
              <p class="text-xs text-slate-400 leading-relaxed">{{ theme.description }}</p>
              <div class="pt-2 text-[11px] text-slate-500">
                <span>By {{ theme.author }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="p-5 pt-0 flex items-center justify-between">
            <button v-if="!theme.is_active" @click="activateTheme(theme.slug)"
                    class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-indigo-600 text-white font-semibold text-xs transition-colors">
              Activate Theme
            </button>
            <div v-else class="w-full flex items-center space-x-2">
              <button @click="openCustomizer" class="flex-1 py-2 rounded-xl bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 hover:bg-indigo-600/30 text-xs font-semibold">
                Customize
              </button>
              <a href="/" target="_blank" class="p-2 rounded-xl bg-slate-800 text-slate-400 hover:text-white" title="Preview on storefront">
                <ExternalLink class="w-4 h-4" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Theme Customizer Modal -->
    <div v-if="isCustomizeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 rounded-2xl border border-slate-800 max-w-lg w-full p-6 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center space-x-2">
            <Sliders class="w-5 h-5 text-indigo-400" />
            <h3 class="font-bold text-white text-base">Customize: {{ activeTheme }}</h3>
          </div>
          <button @click="isCustomizeModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Announcement Bar Alert</label>
            <input v-model="customizerForm.announcement_bar" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Hero Pill Badge</label>
            <input v-model="customizerForm.hero_badge" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Hero Main Title</label>
            <input v-model="customizerForm.hero_title" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Hero Subtitle</label>
            <textarea v-model="customizerForm.hero_subtitle" rows="2"
                      class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500"></textarea>
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Footer Tagline</label>
            <input v-model="customizerForm.footer_tagline" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
          </div>
        </div>

        <div class="flex justify-end space-x-3 pt-3 border-t border-slate-800">
          <button @click="isCustomizeModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold">
            Cancel
          </button>
          <button @click="saveCustomizer" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30">
            Apply Changes
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
