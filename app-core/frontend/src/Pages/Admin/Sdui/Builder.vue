<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
  BookOpen
} from 'lucide-vue-next';

const props = defineProps({
  screens: Array,
  activeScreen: Object,
  parsedPreview: Object,
  palette: Array,
  apiEndpoint: String,
});

const currentScreen = ref(props.activeScreen ? JSON.parse(JSON.stringify(props.activeScreen)) : {
  slug: 'home',
  title: 'Mobile Home Screen',
  is_active: true,
  layout: {
    status_bar_color: '#0f172a',
    background_color: '#020617',
    is_dark_mode: true,
    primary_color: '#6366f1',
    app_bar: {
      type: 'TopAppBar',
      title: 'Apex Mobile Store',
      show_back_button: false,
    },
    components: [],
  }
});

const selectedIndex = ref(0);
const activeTab = ref('inspector'); // 'inspector' | 'json' | 'preview'
const isApiModalOpen = ref(false);
const isCopied = ref(false);

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
    <div class="h-[calc(100vh-6.5rem)] flex flex-col gap-4">
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
        <div class="flex items-center space-x-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-600/30">
            <Smartphone class="w-5 h-5 text-white" />
          </div>
          <div>
            <h1 class="text-lg font-bold text-white tracking-tight flex items-center space-x-2">
              <span>Android SDUI Studio</span>
              <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase">Native Engine</span>
            </h1>
            <p class="text-xs text-slate-400">Server-Driven UI builder delivering dynamic Jetpack Compose component trees</p>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button @click="isApiModalOpen = true"
                  class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white flex items-center space-x-1.5 transition-all">
            <Code2 class="w-3.5 h-3.5 text-indigo-400" />
            <span>Test Android REST API</span>
          </button>

          <button @click="saveScreen" :disabled="form.processing"
                  class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Deploying...' : 'Deploy to Android App' }}</span>
          </button>
        </div>
      </div>

      <!-- 3-Panel Visual Studio Workspace -->
      <div class="flex-1 grid grid-cols-12 gap-4 overflow-hidden">
        <!-- Left Panel: Component Palette (3 cols) -->
        <div class="col-span-3 bg-slate-900/70 rounded-2xl border border-slate-800 p-4 flex flex-col overflow-hidden shadow-xl">
          <div class="pb-3 border-b border-slate-800 mb-3">
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-1.5">
              <Layers class="w-3.5 h-3.5 text-indigo-400" />
              <span>Native Android Palette</span>
            </h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Click to append native widget</p>
          </div>

          <div class="flex-1 overflow-y-auto space-y-2 pr-1">
            <button v-for="item in palette" :key="item.type"
                    @click="addComponent(item)"
                    class="w-full text-left p-3 rounded-xl bg-slate-950/70 hover:bg-slate-800/80 border border-slate-800/80 hover:border-indigo-500/50 transition-all flex items-center space-x-3 group">
              <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-400 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
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
        <div class="col-span-5 bg-slate-950/60 rounded-2xl border border-slate-800/80 p-4 flex items-center justify-center overflow-hidden relative">
          <!-- Realistic Android Mockup Frame -->
          <div class="w-[320px] h-[640px] bg-slate-950 rounded-[42px] border-[8px] border-slate-800 shadow-[0_0_50px_rgba(0,0,0,0.8)] flex flex-col overflow-hidden relative ring-1 ring-slate-700/60">
            <!-- Front Camera Cutout -->
            <div class="absolute top-2.5 left-1/2 -translate-x-1/2 w-4 h-4 bg-black rounded-full z-40 border border-slate-800 flex items-center justify-center">
              <div class="w-1.5 h-1.5 rounded-full bg-slate-900"></div>
            </div>

            <!-- Android Status Bar -->
            <div class="h-8 bg-slate-950/90 text-[10px] text-slate-300 font-semibold px-6 flex items-center justify-between z-30 shrink-0">
              <span>9:41</span>
              <div class="flex items-center space-x-2">
                <span class="text-[9px] font-bold">5G</span>
                <Wifi class="w-3 h-3 text-slate-300" />
                <Battery class="w-3.5 h-3.5 text-slate-300" />
              </div>
            </div>

            <!-- App Bar -->
            <div class="h-12 bg-slate-900 border-b border-slate-800 px-4 flex items-center justify-between z-30 shrink-0">
              <span class="font-bold text-xs text-white truncate">{{ currentScreen.layout.app_bar?.title || 'Mobile Store' }}</span>
              <div class="flex items-center space-x-2.5">
                <Search class="w-4 h-4 text-slate-400" />
                <ShoppingCart class="w-4 h-4 text-indigo-400" />
              </div>
            </div>

            <!-- Mobile Screen Component Render Canvas -->
            <div class="flex-1 overflow-y-auto p-3 space-y-3 bg-[#020617] relative">
              <div v-for="(comp, index) in components" :key="comp.id || index"
                   @click="selectedIndex = index"
                   :class="[
                     selectedIndex === index ? 'ring-2 ring-indigo-500 shadow-glow' : 'hover:ring-1 hover:ring-slate-700',
                     'rounded-xl transition-all relative group cursor-pointer'
                   ]">
                <!-- Reorder & Delete Floating Controls -->
                <div class="absolute -top-2.5 right-2 hidden group-hover:flex items-center space-x-1 bg-slate-900 border border-slate-700 rounded-lg p-0.5 z-40 shadow-lg">
                  <button @click.stop="moveUp(index)" class="p-1 hover:text-indigo-400"><ChevronUp class="w-3 h-3" /></button>
                  <button @click.stop="moveDown(index)" class="p-1 hover:text-indigo-400"><ChevronDown class="w-3 h-3" /></button>
                  <button @click.stop="removeComponent(index)" class="p-1 hover:text-rose-400"><Trash2 class="w-3 h-3" /></button>
                </div>

                <!-- Component Type Renders -->
                <!-- Banner Slider -->
                <div v-if="comp.type === 'BannerSlider'" class="rounded-xl overflow-hidden bg-slate-900 border border-slate-800 shadow relative">
                  <div class="aspect-video relative overflow-hidden bg-slate-800">
                    <img :src="comp.banners && comp.banners[0] ? comp.banners[0].image_url : 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=600&q=80'"
                         alt="" class="w-full h-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                    <div class="absolute bottom-2.5 left-2.5 right-2.5">
                      <span class="px-1.5 py-0.5 rounded bg-indigo-600 text-white text-[9px] font-bold uppercase">{{ comp.banners && comp.banners[0] ? comp.banners[0].badge || 'PROMO' : 'PROMO' }}</span>
                      <p class="text-xs font-bold text-white leading-tight mt-1">{{ comp.banners && comp.banners[0] ? comp.banners[0].title : 'Headline' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Quick Actions Grid -->
                <div v-else-if="comp.type === 'QuickActions'" class="grid grid-cols-4 gap-2 py-1">
                  <div v-for="act in (comp.actions || [])" :key="act.label" class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center mb-1 text-indigo-400">
                      <Package class="w-4 h-4" />
                    </div>
                    <span class="text-[9px] text-slate-300 font-medium">{{ act.label }}</span>
                  </div>
                </div>

                <!-- Category Row -->
                <div v-else-if="comp.type === 'CategoryRow'" class="space-y-1.5">
                  <span class="text-[11px] font-bold text-white">{{ comp.title || 'Categories' }}</span>
                  <div class="flex items-center space-x-2 overflow-x-hidden">
                    <div v-for="cat in (comp.items || [])" :key="cat.id" class="px-2.5 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-300 whitespace-nowrap">
                      {{ cat.label }}
                    </div>
                  </div>
                </div>

                <!-- Product Grid (Dynamic) -->
                <div v-else-if="comp.type === 'ProductGrid'" class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold text-white">{{ comp.title || 'Products' }}</span>
                    <span class="text-[9px] text-indigo-400">Dynamic</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div v-for="i in 2" :key="i" class="p-2 rounded-xl bg-slate-900 border border-slate-800">
                      <div class="aspect-square rounded-lg bg-slate-800 overflow-hidden mb-1.5">
                        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=200&q=80" class="w-full h-full object-cover" />
                      </div>
                      <p class="text-[10px] font-bold text-white truncate">Pro Hardware Item</p>
                      <p class="text-[10px] font-bold text-indigo-400">$149.99</p>
                    </div>
                  </div>
                </div>

                <!-- Announcement Card -->
                <div v-else-if="comp.type === 'AnnouncementCard'"
                     class="p-3 rounded-xl bg-gradient-to-r from-indigo-900 to-purple-900 border border-indigo-500/30 text-white space-y-1">
                  <p class="text-[11px] font-bold flex items-center space-x-1">
                    <Zap class="w-3 h-3 text-amber-400" />
                    <span>{{ comp.title || 'Announcement' }}</span>
                  </p>
                  <p class="text-[9px] text-indigo-200">{{ comp.message || 'Notification content' }}</p>
                </div>

                <!-- Featured Article -->
                <div v-else-if="comp.type === 'FeaturedArticle'" class="p-2 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
                  <p class="text-[10px] font-bold text-white">{{ comp.title || 'Journal Story' }}</p>
                  <div class="aspect-video rounded-lg bg-slate-800 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=400&q=80" class="w-full h-full object-cover" />
                  </div>
                </div>

                <!-- Spacer -->
                <div v-else-if="comp.type === 'Spacer'" class="py-2 flex items-center justify-center border border-dashed border-slate-800 text-[9px] text-slate-500">
                  Spacer ({{ comp.height || 16 }}px)
                </div>
              </div>

              <div v-if="!components.length" class="text-center py-20 text-slate-600 text-xs">
                Canvas is empty. Add components from the left palette.
              </div>
            </div>

            <!-- Bottom Navigation Bar -->
            <div class="h-12 bg-slate-900/95 border-t border-slate-800 px-4 flex items-center justify-around z-30 shrink-0 text-slate-400">
              <span class="text-[9px] font-bold text-indigo-400">Home</span>
              <span class="text-[9px]">Shop</span>
              <span class="text-[9px]">Blog</span>
              <span class="text-[9px]">Account</span>
            </div>
          </div>
        </div>

        <!-- Right Panel: Property Inspector & JSON Viewer (4 cols) -->
        <div class="col-span-4 bg-slate-900/70 rounded-2xl border border-slate-800 p-4 flex flex-col overflow-hidden shadow-xl">
          <!-- Mode Tabs -->
          <div class="flex items-center space-x-2 pb-3 border-b border-slate-800 mb-4">
            <button @click="activeTab = 'inspector'"
                    :class="[
                      activeTab === 'inspector' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white',
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all'
                    ]">
              Inspector
            </button>
            <button @click="activeTab = 'json'"
                    :class="[
                      activeTab === 'json' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white',
                      'px-3 py-1.5 rounded-lg text-xs font-bold transition-all'
                    ]">
              SDUI JSON Tree
            </button>
          </div>

          <!-- Inspector Content -->
          <div v-if="activeTab === 'inspector'" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
            <div v-if="selectedComponent" class="space-y-4">
              <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                <div>
                  <span class="text-[10px] text-slate-500 uppercase font-bold">Component</span>
                  <p class="font-bold text-white text-sm">{{ selectedComponent.type }}</p>
                </div>
                <span class="font-mono text-[10px] text-indigo-400">{{ selectedComponent.id }}</span>
              </div>

              <!-- General Title Property -->
              <div v-if="selectedComponent.title !== undefined">
                <label class="block text-slate-400 font-semibold mb-1">Section Title</label>
                <input v-model="selectedComponent.title" type="text"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
              </div>

              <!-- Banner Slider Inspector -->
              <div v-if="selectedComponent.type === 'BannerSlider'" class="space-y-3">
                <div v-for="(banner, bIdx) in (selectedComponent.banners || [])" :key="bIdx" class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                  <span class="font-bold text-white text-[11px]">Slide #{{ bIdx+1 }}</span>
                  <div>
                    <label class="block text-[10px] text-slate-400 mb-0.5">Headline</label>
                    <input v-model="banner.title" type="text" class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white" />
                  </div>
                  <div>
                    <label class="block text-[10px] text-slate-400 mb-0.5">Badge Text</label>
                    <input v-model="banner.badge" type="text" class="w-full px-2.5 py-1.5 rounded bg-slate-900 border border-slate-800 text-white" />
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
                    <option value="database">Live Storefront Database</option>
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
            </div>

            <div v-else class="text-center py-20 text-slate-500">
              Select a component on the phone frame to edit its properties.
            </div>
          </div>

          <!-- JSON Tree Content -->
          <div v-else-if="activeTab === 'json'" class="flex-1 overflow-hidden flex flex-col">
            <textarea :value="JSON.stringify(currentScreen.layout, null, 2)" readonly
                      class="flex-1 p-3 rounded-xl bg-slate-950 border border-slate-800 text-indigo-300 font-mono text-[10px] leading-relaxed resize-none focus:outline-none"></textarea>
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
          <p class="text-slate-400">Native Android apps fetch this endpoint to render Compose screens dynamically without app store releases:</p>
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
          <span class="text-[11px] font-bold text-slate-300">Sample Android Kotlin Jetpack Compose Usage:</span>
          <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-indigo-300 font-mono text-[10px] overflow-x-auto leading-relaxed">
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
