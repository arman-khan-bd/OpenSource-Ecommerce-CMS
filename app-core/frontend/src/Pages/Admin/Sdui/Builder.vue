<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Smartphone,
  Plus,
  Trash2,
  ChevronUp,
  ChevronDown,
  Save,
  Code2,
  Eye,
  Sliders,
  ExternalLink,
  Layers,
  Sparkles,
  Wifi,
  Battery,
  Search,
  ShoppingCart,
  Copy,
  Check,
  Package,
  Zap,
  Gift,
  BookOpen,
  ZoomIn,
  ZoomOut,
  RotateCcw,
  Maximize2,
  Bell,
  Download,
  Palette,
  Settings2,
  RefreshCw,
  CopyCheck
} from 'lucide-vue-next';

const props = defineProps({
  screens: {
    type: Array,
    default: () => []
  },
  activeScreen: {
    type: Object,
    default: null
  },
  parsedPreview: {
    type: Object,
    default: null
  },
  palette: {
    type: Array,
    default: () => []
  },
  apiEndpoint: {
    type: String,
    default: '/api/v1/sdui/screen/home'
  },
});

// Setup current screen state safely
const currentScreen = ref(props.activeScreen ? JSON.parse(JSON.stringify(props.activeScreen)) : {
  slug: 'home',
  title: 'Mobile Home Screen',
  description: 'Primary Android app home screen rendered dynamically via SDUI.',
  is_active: true,
  layout: {
    status_bar_color: '#0f172a',
    background_color: '#020617',
    is_dark_mode: true,
    primary_color: '#6366f1',
    app_bar: {
      type: 'TopAppBar',
      title: 'OpenSource Mobile Store',
      show_back_button: false,
    },
    components: [],
  }
});

// Ensure layout and components exist
if (!currentScreen.value.layout) {
  currentScreen.value.layout = {
    status_bar_color: '#0f172a',
    background_color: '#020617',
    is_dark_mode: true,
    primary_color: '#6366f1',
    app_bar: {
      type: 'TopAppBar',
      title: currentScreen.value.title || 'Mobile Store',
      show_back_button: false
    },
    components: []
  };
}
if (!Array.isArray(currentScreen.value.layout.components)) {
  currentScreen.value.layout.components = [];
}

const selectedIndex = ref(0);
const activeTab = ref('inspector'); // 'inspector' | 'theme' | 'json'
const isApiModalOpen = ref(false);
const isCopied = ref(false);

// Canvas Zoom & Scale controls
const zoomLevel = ref(0.85); // 0.85 default fits comfortably on all screen resolutions
const zoomLevels = [0.65, 0.75, 0.85, 1.0, 1.15];

function setZoom(val) {
  zoomLevel.value = Math.min(1.25, Math.max(0.5, Math.round(val * 100) / 100));
}

function zoomIn() {
  setZoom(zoomLevel.value + 0.1);
}

function zoomOut() {
  setZoom(zoomLevel.value - 0.1);
}

function resetZoom() {
  zoomLevel.value = 1.0;
}

function fitZoom() {
  zoomLevel.value = 0.85;
}

// PWA Installation & Push Notification handling
const deferredPrompt = ref(null);
const canInstallPwa = ref(false);
const isPwaInstalled = ref(false);
const pushTestStatus = ref('');

onMounted(() => {
  // Check standalone mode
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    isPwaInstalled.value = true;
  }

  // Intercept PWA prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    canInstallPwa.value = true;
  });

  window.addEventListener('appinstalled', () => {
    isPwaInstalled.value = true;
    canInstallPwa.value = false;
    deferredPrompt.value = null;
  });
});

async function installAdminPwa() {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
      isPwaInstalled.value = true;
      canInstallPwa.value = false;
    }
    deferredPrompt.value = null;
  } else {
    // If not directly promptable in this browser window, provide guidance
    alert('To install the Admin Dashboard App:\n• Chrome/Edge: Click the Install icon in the browser address bar (top right).\n• Mobile: Tap Share -> Add to Home Screen.');
  }
}

async function testWebPush() {
  pushTestStatus.value = 'Triggering native dropdown alert...';
  try {
    if ('Notification' in window) {
      const perm = await Notification.requestPermission();
      if (perm === 'granted') {
        if ('serviceWorker' in navigator) {
          const reg = await navigator.serviceWorker.ready;
          // Trigger the exact notification options configured in sw.js
          reg.showNotification('Admin SDUI Studio Alert', {
            body: 'Jetpack Compose native component tree synchronized successfully!',
            icon: '/icon-192.png',
            badge: '/badge-72.png',
            vibrate: [100, 50, 100],
            data: { url: '/admin/sdui' }
          });
          pushTestStatus.value = '✓ Native dropdown notification displayed!';
        } else {
          new Notification('Admin SDUI Studio Alert', {
            body: 'Jetpack Compose native component tree synchronized successfully!',
            icon: '/icon-192.png'
          });
          pushTestStatus.value = '✓ Notification displayed!';
        }
      } else {
        pushTestStatus.value = 'Notification permission denied in browser.';
      }
    } else {
      pushTestStatus.value = 'Web Push API not supported in this browser.';
    }
  } catch (err) {
    pushTestStatus.value = 'Push test: ' + err.message;
  }

  setTimeout(() => {
    pushTestStatus.value = '';
  }, 4000);
}

// Components reactive accessor
const components = computed({
  get: () => currentScreen.value.layout.components || [],
  set: (val) => { currentScreen.value.layout.components = val; }
});

const selectedComponent = computed(() => {
  return components.value[selectedIndex.value] || null;
});

function addComponent(preset) {
  const clone = JSON.parse(JSON.stringify(preset.default));
  clone.id = preset.type.toLowerCase() + '_' + Math.random().toString(36).substring(2, 7);
  components.value.push(clone);
  selectedIndex.value = components.value.length - 1;
}

function removeComponent(idx) {
  components.value.splice(idx, 1);
  if (selectedIndex.value >= components.value.length) {
    selectedIndex.value = Math.max(0, components.value.length - 1);
  }
}

function moveUp(idx) {
  if (idx <= 0) return;
  const temp = components.value[idx];
  components.value[idx] = components.value[idx - 1];
  components.value[idx - 1] = temp;
  selectedIndex.value = idx - 1;
}

function moveDown(idx) {
  if (idx >= components.value.length - 1) return;
  const temp = components.value[idx];
  components.value[idx] = components.value[idx + 1];
  components.value[idx + 1] = temp;
  selectedIndex.value = idx + 1;
}

function switchScreen(e) {
  const slug = e.target.value;
  if (slug) {
    router.get('/admin/sdui', { screen: slug });
  }
}

const form = useForm({
  slug: currentScreen.value.slug,
  title: currentScreen.value.title,
  description: currentScreen.value.description,
  is_active: currentScreen.value.is_active,
  layout: currentScreen.value.layout,
});

function saveScreen() {
  form.slug = currentScreen.value.slug;
  form.title = currentScreen.value.title;
  form.layout = currentScreen.value.layout;
  form.is_active = currentScreen.value.is_active;

  form.post('/admin/sdui/save');
}

function copyApiUrl() {
  navigator.clipboard.writeText(window.location.origin + props.apiEndpoint);
  isCopied.value = true;
  setTimeout(() => { isCopied.value = false; }, 2000);
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-4 pb-12">
      <!-- Top Action Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl backdrop-blur-md">
        <!-- Title & Screen Switcher -->
        <div class="flex items-center space-x-3.5">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-600/30 shrink-0">
            <Smartphone class="w-5 h-5 text-white" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-base sm:text-lg font-bold text-white tracking-tight">Android SDUI Studio</h1>
              <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase border border-indigo-500/30">Jetpack Compose</span>
            </div>
            <p class="text-xs text-slate-400 hidden sm:block">Visual Server-Driven UI builder delivering dynamic Android component trees</p>
          </div>

          <!-- Screen Selector Dropdown -->
          <div v-if="screens && screens.length" class="ml-2 pl-3 border-l border-slate-800">
            <select :value="currentScreen.slug" @change="switchScreen"
                    class="bg-slate-950 border border-slate-700 text-xs font-semibold text-white rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-indigo-500">
              <option v-for="s in screens" :key="s.slug" :value="s.slug">
                {{ s.title }} ({{ s.slug }})
              </option>
            </select>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Install Admin PWA Button -->
          <button @click="installAdminPwa"
                  class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600/20 to-teal-600/20 hover:from-emerald-600/30 hover:to-teal-600/30 border border-emerald-500/40 text-emerald-300 hover:text-white text-xs font-bold flex items-center space-x-1.5 transition-all shadow-sm">
            <Download class="w-3.5 h-3.5 text-emerald-400" />
            <span>{{ isPwaInstalled ? 'Admin App Installed' : 'Install Admin App (PWA)' }}</span>
          </button>

          <!-- Push Notification Test -->
          <button @click="testWebPush"
                  class="px-3 py-1.5 rounded-xl bg-purple-950/70 hover:bg-purple-900/60 border border-purple-500/40 text-purple-300 hover:text-white text-xs font-bold flex items-center space-x-1.5 transition-all">
            <Bell class="w-3.5 h-3.5 text-purple-400" />
            <span>Test Web Push</span>
          </button>

          <!-- REST API Endpoint Modal Button -->
          <button @click="isApiModalOpen = true"
                  class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white flex items-center space-x-1.5 transition-all">
            <Code2 class="w-3.5 h-3.5 text-indigo-400" />
            <span>Android REST API</span>
          </button>

          <!-- Deploy / Save Button -->
          <button @click="saveScreen" :disabled="form.processing"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all disabled:opacity-50">
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Deploying...' : 'Deploy to Android App' }}</span>
          </button>
        </div>
      </div>

      <!-- Flash Toast for Push Test -->
      <div v-if="pushTestStatus"
           class="p-2.5 rounded-xl bg-indigo-950/90 border border-indigo-500/40 text-indigo-200 text-xs flex items-center space-x-2 animate-pulse">
        <Zap class="w-4 h-4 text-amber-400 shrink-0" />
        <span>{{ pushTestStatus }}</span>
      </div>

      <!-- 3-Panel Visual Studio Workspace -->
      <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">
        <!-- Left Panel: Component Palette (3 cols) -->
        <div class="xl:col-span-3 bg-slate-900/80 rounded-2xl border border-slate-800 p-4 flex flex-col shadow-xl xl:sticky xl:top-4 max-h-[85vh]">
          <div class="pb-3 border-b border-slate-800 mb-3 flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
              <Layers class="w-4 h-4 text-indigo-400" />
              <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Android Palette</h3>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-400">{{ palette.length }} Widgets</span>
          </div>

          <p class="text-[11px] text-slate-400 mb-3">Click any widget below to append it to the Android Compose tree:</p>

          <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
            <button v-for="item in palette" :key="item.type"
                    @click="addComponent(item)"
                    class="w-full text-left p-3 rounded-xl bg-slate-950/70 hover:bg-slate-800/80 border border-slate-800 hover:border-indigo-500/50 transition-all flex items-center space-x-3 group">
              <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-400 flex items-center justify-center group-hover:scale-105 group-hover:bg-indigo-600/20 transition-all shrink-0">
                <Plus class="w-4 h-4" />
              </div>
              <div class="overflow-hidden">
                <span class="block text-xs font-bold text-white group-hover:text-indigo-300 transition-colors">{{ item.name }}</span>
                <span class="block text-[10px] text-slate-400 truncate">{{ item.description }}</span>
              </div>
            </button>
          </div>
        </div>

        <!-- Center Panel: Android Device Frame Simulator (5 cols) -->
        <div class="xl:col-span-5 bg-slate-950/70 rounded-2xl border border-slate-800/80 p-4 flex flex-col shadow-2xl relative min-h-[640px]">
          <!-- Canvas Top Toolbar: Device info & Zoom Controls -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 mb-3">
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
              <span class="text-xs font-bold text-slate-300">Android Canvas Simulator</span>
              <span class="text-[10px] text-slate-500 font-mono hidden sm:inline">1080×2400 dp</span>
            </div>

            <!-- Zoom Controller -->
            <div class="flex items-center space-x-1 bg-slate-900 border border-slate-800 rounded-lg p-1">
              <button @click="zoomOut" title="Zoom Out"
                      class="p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-white transition-colors">
                <ZoomOut class="w-3.5 h-3.5" />
              </button>
              <span class="text-[11px] font-mono font-bold text-indigo-300 px-1.5 min-w-[42px] text-center">
                {{ Math.round(zoomLevel * 100) }}%
              </span>
              <button @click="zoomIn" title="Zoom In"
                      class="p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-white transition-colors">
                <ZoomIn class="w-3.5 h-3.5" />
              </button>
              <div class="h-3 w-[1px] bg-slate-800 mx-0.5"></div>
              <button @click="fitZoom" title="Fit to Canvas (85%)"
                      class="px-1.5 py-0.5 text-[10px] rounded hover:bg-slate-800 text-slate-400 hover:text-white transition-colors">
                Fit
              </button>
              <button @click="resetZoom" title="Reset (100%)"
                      class="px-1.5 py-0.5 text-[10px] rounded hover:bg-slate-800 text-slate-400 hover:text-white transition-colors">
                100%
              </button>
            </div>
          </div>

          <!-- Scrollable Canvas Workspace (Phone Centered, Cleanly Scaled) -->
          <div class="flex-1 w-full flex justify-center items-start overflow-x-auto py-2">
            <!-- Transform Wrapper with dynamic zoom -->
            <div :style="{
                   transform: `scale(${zoomLevel})`,
                   transformOrigin: 'top center',
                   transition: 'transform 0.2s cubic-bezier(0.16, 1, 0.3, 1)'
                 }"
                 class="shrink-0 mb-6">

              <!-- Realistic Android Mockup Frame -->
              <div class="w-[330px] h-[670px] bg-slate-950 rounded-[44px] border-[9px] border-slate-800 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.9)] flex flex-col overflow-hidden relative ring-1 ring-slate-700/80">
                <!-- Front Camera Cutout -->
                <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-4 h-4 bg-black rounded-full z-40 border border-slate-800 flex items-center justify-center pointer-events-none">
                  <div class="w-1.5 h-1.5 rounded-full bg-slate-900"></div>
                </div>

                <!-- Android Status Bar -->
                <div :style="{ backgroundColor: currentScreen.layout.status_bar_color || '#0f172a' }"
                     class="h-8 text-[10px] text-slate-300 font-semibold px-6 flex items-center justify-between z-30 shrink-0 transition-colors">
                  <span>9:41</span>
                  <div class="flex items-center space-x-1.5">
                    <span class="text-[9px] font-bold">5G</span>
                    <Wifi class="w-3 h-3 text-slate-300" />
                    <Battery class="w-3.5 h-3.5 text-slate-300" />
                  </div>
                </div>

                <!-- Top App Bar -->
                <div class="h-12 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between z-30 shrink-0">
                  <span class="font-bold text-xs text-white truncate max-w-[190px]">
                    {{ currentScreen.layout.app_bar?.title || currentScreen.title || 'Mobile Store' }}
                  </span>
                  <div class="flex items-center space-x-2.5">
                    <Search class="w-4 h-4 text-slate-400" />
                    <ShoppingCart class="w-4 h-4 text-indigo-400" />
                  </div>
                </div>

                <!-- Mobile Screen Component Render Canvas -->
                <div :style="{ backgroundColor: currentScreen.layout.background_color || '#020617' }"
                     class="flex-1 overflow-y-auto p-3 space-y-3 relative custom-scrollbar">
                  <div v-for="(comp, index) in components" :key="comp.id || index"
                       @click="selectedIndex = index"
                       :class="[
                         selectedIndex === index ? 'ring-2 ring-indigo-500 shadow-lg shadow-indigo-500/20' : 'hover:ring-1 hover:ring-slate-700',
                         'rounded-xl transition-all relative group cursor-pointer'
                       ]">
                    <!-- Floating Reorder & Delete Controls -->
                    <div class="absolute -top-2.5 right-2 hidden group-hover:flex items-center space-x-1 bg-slate-900 border border-slate-700 rounded-lg p-0.5 z-40 shadow-xl">
                      <button @click.stop="moveUp(index)" title="Move Up" class="p-1 hover:text-indigo-400 text-slate-300">
                        <ChevronUp class="w-3 h-3" />
                      </button>
                      <button @click.stop="moveDown(index)" title="Move Down" class="p-1 hover:text-indigo-400 text-slate-300">
                        <ChevronDown class="w-3 h-3" />
                      </button>
                      <button @click.stop="removeComponent(index)" title="Delete" class="p-1 hover:text-rose-400 text-slate-300">
                        <Trash2 class="w-3 h-3" />
                      </button>
                    </div>

                    <!-- Component Type: Banner Slider -->
                    <div v-if="comp.type === 'BannerSlider'" class="rounded-xl overflow-hidden bg-slate-900 border border-slate-800 shadow relative">
                      <div class="aspect-video relative overflow-hidden bg-slate-800">
                        <img :src="comp.banners && comp.banners[0] ? comp.banners[0].image_url : 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=600&q=80'"
                             alt="Banner" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>
                        <div class="absolute bottom-2.5 left-2.5 right-2.5">
                          <span class="px-1.5 py-0.5 rounded bg-indigo-600 text-white text-[9px] font-bold uppercase tracking-wider">
                            {{ comp.banners && comp.banners[0] ? (comp.banners[0].badge || 'PROMO') : 'PROMO' }}
                          </span>
                          <p class="text-xs font-bold text-white leading-tight mt-1 truncate">
                            {{ comp.banners && comp.banners[0] ? comp.banners[0].title : 'Headline' }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Component Type: Quick Actions Grid -->
                    <div v-else-if="comp.type === 'QuickActions'" class="grid grid-cols-4 gap-2 py-1">
                      <div v-for="act in (comp.actions || [])" :key="act.label" class="flex flex-col items-center text-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mb-1 text-indigo-400 shadow-sm">
                          <Package class="w-4 h-4" />
                        </div>
                        <span class="text-[9px] text-slate-300 font-medium truncate w-full">{{ act.label }}</span>
                      </div>
                    </div>

                    <!-- Component Type: Category Row -->
                    <div v-else-if="comp.type === 'CategoryRow'" class="space-y-1.5">
                      <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-white">{{ comp.title || 'Categories' }}</span>
                        <span class="text-[9px] text-slate-400">See All</span>
                      </div>
                      <div class="flex items-center space-x-2 overflow-x-hidden">
                        <div v-for="cat in (comp.items || [])" :key="cat.id"
                             class="px-2.5 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-300 whitespace-nowrap flex items-center space-x-1">
                          <span>{{ cat.label }}</span>
                          <span v-if="cat.badge" class="px-1 py-0.2 rounded-full bg-indigo-500/20 text-indigo-300 text-[8px] font-bold">{{ cat.badge }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Component Type: Product Grid -->
                    <div v-else-if="comp.type === 'ProductGrid'" class="space-y-2">
                      <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-white">{{ comp.title || 'Products' }}</span>
                        <span class="text-[9px] text-indigo-400 font-semibold">Live CMS Items</span>
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div v-for="i in 2" :key="i" class="p-2 rounded-xl bg-slate-900 border border-slate-800 shadow-sm">
                          <div class="aspect-square rounded-lg bg-slate-800 overflow-hidden mb-1.5">
                            <img :src="i === 1 ? 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=200&q=80' : 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=200&q=80'"
                                 class="w-full h-full object-cover" />
                          </div>
                          <p class="text-[10px] font-bold text-white truncate">{{ i === 1 ? 'Mechanical Keyboard' : 'Wireless Audio Pro' }}</p>
                          <div class="flex items-center justify-between mt-1">
                            <p class="text-[10px] font-bold text-indigo-400">{{ i === 1 ? '$149.00' : '$89.00' }}</p>
                            <span class="px-1 py-0.5 rounded bg-indigo-600/30 text-indigo-300 text-[8px] font-bold">Add</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Component Type: Announcement Card -->
                    <div v-else-if="comp.type === 'AnnouncementCard'"
                         class="p-3 rounded-xl bg-gradient-to-r from-indigo-950 via-purple-950 to-slate-900 border border-indigo-500/30 text-white space-y-1 shadow-md">
                      <p class="text-[11px] font-bold flex items-center space-x-1.5 text-white">
                        <Zap class="w-3.5 h-3.5 text-amber-400" />
                        <span>{{ comp.title || 'Exclusive Flash Deal' }}</span>
                      </p>
                      <p class="text-[9px] text-indigo-200 leading-snug">{{ comp.message || 'Use voucher code FLASH20 at checkout.' }}</p>
                      <button v-if="comp.button_text" class="mt-1 px-2 py-0.5 rounded bg-indigo-600 text-white text-[9px] font-bold">
                        {{ comp.button_text }}
                      </button>
                    </div>

                    <!-- Component Type: Featured Article -->
                    <div v-else-if="comp.type === 'FeaturedArticle'" class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1.5">
                      <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-purple-400">Featured Story</span>
                        <BookOpen class="w-3 h-3 text-slate-500" />
                      </div>
                      <p class="text-[10px] font-bold text-white line-clamp-1">{{ comp.title || 'Engineering The Monolithic & SDUI Bridge' }}</p>
                      <div class="aspect-video rounded-lg bg-slate-800 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover" />
                      </div>
                    </div>

                    <!-- Component Type: Spacer -->
                    <div v-else-if="comp.type === 'Spacer'" class="py-2 flex items-center justify-center border border-dashed border-slate-800 rounded-lg text-[9px] text-slate-500">
                      Spacer ({{ comp.height || 16 }}px)
                    </div>

                    <!-- Fallback Generic Component Render -->
                    <div v-else class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-white space-y-1">
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-indigo-300">{{ comp.type }}</span>
                        <span class="text-[8px] text-slate-500 font-mono">{{ comp.id }}</span>
                      </div>
                      <p class="text-[9px] text-slate-400">{{ comp.title || 'Custom Native Component' }}</p>
                    </div>
                  </div>

                  <div v-if="!components.length" class="text-center py-24 text-slate-500 text-xs">
                    Canvas is empty. Click any widget in the left palette to add it.
                  </div>
                </div>

                <!-- Bottom Navigation Bar -->
                <div class="h-12 bg-slate-900/95 border-t border-slate-800 px-4 flex items-center justify-around z-30 shrink-0 text-slate-400">
                  <div class="flex flex-col items-center text-indigo-400 font-bold">
                    <Smartphone class="w-3.5 h-3.5 mb-0.5" />
                    <span class="text-[8px]">Home</span>
                  </div>
                  <div class="flex flex-col items-center hover:text-slate-200">
                    <Package class="w-3.5 h-3.5 mb-0.5 text-slate-500" />
                    <span class="text-[8px]">Shop</span>
                  </div>
                  <div class="flex flex-col items-center hover:text-slate-200">
                    <BookOpen class="w-3.5 h-3.5 mb-0.5 text-slate-500" />
                    <span class="text-[8px]">Blog</span>
                  </div>
                  <div class="flex flex-col items-center hover:text-slate-200">
                    <Zap class="w-3.5 h-3.5 mb-0.5 text-slate-500" />
                    <span class="text-[8px]">Account</span>
                  </div>
                </div>

                <!-- Android Gesture Navigation Pill -->
                <div class="h-4 bg-slate-950 flex items-center justify-center z-30 shrink-0">
                  <div class="w-24 h-1 rounded-full bg-slate-700/60"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Panel: Property Inspector, Theme Settings & JSON (4 cols) -->
        <div class="xl:col-span-4 bg-slate-900/80 rounded-2xl border border-slate-800 p-4 flex flex-col shadow-xl xl:sticky xl:top-4 max-h-[85vh]">
          <!-- Mode Tabs -->
          <div class="flex items-center space-x-1 pb-3 border-b border-slate-800 mb-4">
            <button @click="activeTab = 'inspector'"
                    :class="[
                      activeTab === 'inspector' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white',
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all'
                    ]">
              Inspector
            </button>
            <button @click="activeTab = 'theme'"
                    :class="[
                      activeTab === 'theme' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white',
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all'
                    ]">
              Theme & Screen
            </button>
            <button @click="activeTab = 'json'"
                    :class="[
                      activeTab === 'json' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white',
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all'
                    ]">
              SDUI JSON Tree
            </button>
          </div>

          <!-- Tab 1: Component Inspector -->
          <div v-if="activeTab === 'inspector'" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs custom-scrollbar">
            <div v-if="selectedComponent" class="space-y-4">
              <!-- Component Identifier Header -->
              <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                <div>
                  <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Active Widget</span>
                  <p class="font-bold text-white text-sm">{{ selectedComponent.type }}</p>
                </div>
                <span class="font-mono text-[10px] text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
                  {{ selectedComponent.id }}
                </span>
              </div>

              <!-- General Title Property -->
              <div v-if="selectedComponent.title !== undefined">
                <label class="block text-slate-400 font-semibold mb-1">Section Title</label>
                <input v-model="selectedComponent.title" type="text"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
              </div>

              <!-- Banner Slider Inspector -->
              <div v-if="selectedComponent.type === 'BannerSlider'" class="space-y-3">
                <label class="block text-slate-400 font-semibold">Banners ({{ selectedComponent.banners?.length || 0 }})</label>
                <div v-for="(banner, bIdx) in (selectedComponent.banners || [])" :key="bIdx" class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                  <span class="font-bold text-white text-[11px]">Slide #{{ bIdx+1 }}</span>
                  <div>
                    <label class="block text-[10px] text-slate-400 mb-0.5">Headline</label>
                    <input v-model="banner.title" type="text" class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white text-xs" />
                  </div>
                  <div>
                    <label class="block text-[10px] text-slate-400 mb-0.5">Badge Text</label>
                    <input v-model="banner.badge" type="text" class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white text-xs" />
                  </div>
                  <div>
                    <label class="block text-[10px] text-slate-400 mb-0.5">Image URL</label>
                    <input v-model="banner.image_url" type="text" class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white font-mono text-[10px]" />
                  </div>
                </div>
              </div>

              <!-- Product Grid Inspector -->
              <div v-if="selectedComponent.type === 'ProductGrid'" class="space-y-3">
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Data Query Source</label>
                  <select v-model="selectedComponent.data_source" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white">
                    <option value="database">Live Storefront Catalog (Dynamic)</option>
                    <option value="manual">Manual Products</option>
                  </select>
                </div>
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Columns</label>
                  <select v-model.number="selectedComponent.columns" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white">
                    <option :value="1">1 Column (Vertical List)</option>
                    <option :value="2">2 Columns (Grid)</option>
                  </select>
                </div>
              </div>

              <!-- Announcement Card Inspector -->
              <div v-if="selectedComponent.type === 'AnnouncementCard'" class="space-y-3">
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Message</label>
                  <textarea v-model="selectedComponent.message" rows="2" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white"></textarea>
                </div>
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Button Call To Action</label>
                  <input v-model="selectedComponent.button_text" type="text" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white" />
                </div>
              </div>

              <!-- Spacer Inspector -->
              <div v-if="selectedComponent.type === 'Spacer'" class="space-y-3">
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Height (Pixels)</label>
                  <input v-model.number="selectedComponent.height" type="number" min="4" max="100" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white" />
                </div>
              </div>

              <!-- Delete Action -->
              <div class="pt-2 border-t border-slate-800">
                <button @click="removeComponent(selectedIndex)"
                        class="w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center justify-center space-x-1.5 transition-all">
                  <Trash2 class="w-3.5 h-3.5" />
                  <span>Remove Widget from Android Tree</span>
                </button>
              </div>
            </div>

            <div v-else class="text-center py-20 text-slate-500">
              Select a component on the Android phone frame to edit its properties.
            </div>
          </div>

          <!-- Tab 2: Screen & Theme Settings -->
          <div v-else-if="activeTab === 'theme'" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs custom-scrollbar">
            <div class="space-y-3">
              <div>
                <label class="block text-slate-400 font-semibold mb-1">Screen Title</label>
                <input v-model="currentScreen.title" type="text"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
              </div>

              <div>
                <label class="block text-slate-400 font-semibold mb-1">Screen Slug (REST Identifier)</label>
                <input v-model="currentScreen.slug" type="text"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-indigo-400 font-mono focus:outline-none focus:border-indigo-500" />
              </div>

              <div>
                <label class="block text-slate-400 font-semibold mb-1">Top App Bar Title</label>
                <input v-model="currentScreen.layout.app_bar.title" type="text"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Status Bar Color</label>
                  <div class="flex items-center space-x-2">
                    <input v-model="currentScreen.layout.status_bar_color" type="color" class="w-8 h-8 rounded border border-slate-700 bg-transparent cursor-pointer" />
                    <input v-model="currentScreen.layout.status_bar_color" type="text" class="flex-1 px-2 py-1.5 rounded bg-slate-950 border border-slate-800 text-white font-mono text-[11px]" />
                  </div>
                </div>

                <div>
                  <label class="block text-slate-400 font-semibold mb-1">Background Color</label>
                  <div class="flex items-center space-x-2">
                    <input v-model="currentScreen.layout.background_color" type="color" class="w-8 h-8 rounded border border-slate-700 bg-transparent cursor-pointer" />
                    <input v-model="currentScreen.layout.background_color" type="text" class="flex-1 px-2 py-1.5 rounded bg-slate-950 border border-slate-800 text-white font-mono text-[11px]" />
                  </div>
                </div>
              </div>

              <div class="pt-2">
                <label class="flex items-center space-x-2 text-slate-300 cursor-pointer">
                  <input v-model="currentScreen.is_active" type="checkbox" class="w-4 h-4 rounded text-indigo-600 bg-slate-950 border-slate-700 focus:ring-0" />
                  <span class="font-semibold">Screen Published & Active for Android Clients</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Tab 3: SDUI JSON Tree -->
          <div v-else-if="activeTab === 'json'" class="flex-1 overflow-hidden flex flex-col space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Dynamic Layout JSON</span>
              <button @click="copyApiUrl" class="text-indigo-400 hover:text-indigo-300 text-[10px] flex items-center space-x-1 font-semibold">
                <CopyCheck v-if="isCopied" class="w-3 h-3 text-emerald-400" />
                <Copy v-else class="w-3 h-3" />
                <span>{{ isCopied ? 'Copied' : 'Copy Spec' }}</span>
              </button>
            </div>
            <textarea :value="JSON.stringify(currentScreen.layout, null, 2)" readonly
                      class="flex-1 p-3 rounded-xl bg-slate-950 border border-slate-800 text-indigo-300 font-mono text-[10px] leading-relaxed resize-none focus:outline-none custom-scrollbar"></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Android REST API Modal -->
    <div v-if="isApiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 rounded-2xl border border-slate-800 max-w-2xl w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center space-x-2">
            <Smartphone class="w-5 h-5 text-indigo-400" />
            <h3 class="font-bold text-white text-base">Android SDUI Client Endpoint</h3>
          </div>
          <button @click="isApiModalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="space-y-2 text-xs">
          <p class="text-slate-400">Native Android Jetpack Compose apps query this endpoint over HTTPS to render dynamic mobile experiences:</p>
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between font-mono text-emerald-400 text-[11px]">
            <span>GET {{ apiEndpoint }}</span>
            <button @click="copyApiUrl" class="px-2.5 py-1 rounded bg-slate-800 text-slate-300 hover:text-white flex items-center space-x-1">
              <Check v-if="isCopied" class="w-3 h-3 text-emerald-400" />
              <Copy v-else class="w-3 h-3" />
              <span>{{ isCopied ? 'Copied' : 'Copy URL' }}</span>
            </button>
          </div>
        </div>

        <div class="space-y-1">
          <span class="text-[11px] font-bold text-slate-300">Native Android Kotlin Compose Client Code:</span>
          <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-indigo-300 font-mono text-[10px] overflow-x-auto leading-relaxed">
// Kotlin Jetpack Compose client integration
val response = sduiClient.getScreen("{{ currentScreen.slug }}")
SduiScreenRenderer(
    screenSpec = response,
    onAction = { action -> handleMobileNavigation(action) }
)</pre>
        </div>

        <div class="flex justify-end pt-2">
          <button @click="isApiModalOpen = false" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs">
            Close
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
