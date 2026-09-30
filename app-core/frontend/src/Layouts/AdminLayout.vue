<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
  LayoutDashboard,
  FileText,
  ShoppingBag,
  ShoppingCart,
  Image as ImageIcon,
  Smartphone,
  Palette,
  Puzzle,
  Settings,
  LogOut,
  ExternalLink,
  ChevronDown,
  Bell,
  Search,
  CheckCircle,
  AlertCircle,
  Layers,
  Database,
  Menu,
  X
} from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => page.props.auth || { user: {} });
const site = computed(() => page.props.site || {});
const cacheStatus = computed(() => page.props.cacheStatus || { active_driver: 'filesystem' });
const flash = computed(() => page.props.flash || {});

const isSidebarOpen = ref(true);
const isMobileSidebarOpen = ref(false);
const isUserMenuOpen = ref(false);

const navigation = [
  { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
  { name: 'Posts & Pages', href: '/admin/posts', icon: FileText },
  { name: 'Products', href: '/admin/products', icon: ShoppingBag },
  { name: 'Orders', href: '/admin/orders', icon: ShoppingCart },
  { name: 'Media Library', href: '/admin/media', icon: ImageIcon },
  { name: 'Android SDUI Studio', href: '/admin/sdui', icon: Smartphone, badge: 'NATIVE' },
  { name: 'Themes', href: '/admin/themes', icon: Palette },
  { name: 'Plugins & Hooks', href: '/admin/plugins', icon: Puzzle },
  { name: 'Settings & Cache', href: '/admin/settings', icon: Settings },
];

const currentUrl = computed(() => page.url);

function isActive(href) {
  if (href === '/admin/dashboard') {
    return currentUrl.value === '/admin' || currentUrl.value === '/admin/dashboard';
  }
  return currentUrl.value.startsWith(href);
}

function logout() {
  router.post('/admin/logout');
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 flex flex-col antialiased font-sans text-slate-100">
    <!-- Top Flash Toast Notifications -->
    <div v-if="flash.success && flash.success.length" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-md w-full pointer-events-none">
      <div v-for="(msg, i) in flash.success" :key="i"
           v-motion
           :initial="{ opacity: 0, y: -20, scale: 0.95 }"
           :enter="{ opacity: 1, y: 0, scale: 1 }"
           :leave="{ opacity: 0, scale: 0.95 }"
           class="pointer-events-auto flex items-center justify-between p-4 rounded-xl bg-emerald-950/90 border border-emerald-500/40 text-emerald-100 shadow-2xl backdrop-blur-md">
        <div class="flex items-center space-x-3">
          <CheckCircle class="w-5 h-5 text-emerald-400 shrink-0" />
          <p class="text-sm font-medium">{{ msg }}</p>
        </div>
      </div>
    </div>

    <div v-if="flash.error && flash.error.length" class="fixed top-5 right-5 z-50 flex flex-col gap-2 max-w-md w-full pointer-events-none">
      <div v-for="(msg, i) in flash.error" :key="i"
           v-motion
           :initial="{ opacity: 0, y: -20, scale: 0.95 }"
           :enter="{ opacity: 1, y: 0, scale: 1 }"
           :leave="{ opacity: 0, scale: 0.95 }"
           class="pointer-events-auto flex items-center justify-between p-4 rounded-xl bg-rose-950/90 border border-rose-500/40 text-rose-100 shadow-2xl backdrop-blur-md">
        <div class="flex items-center space-x-3">
          <AlertCircle class="w-5 h-5 text-rose-400 shrink-0" />
          <p class="text-sm font-medium">{{ msg }}</p>
        </div>
      </div>
    </div>

    <!-- Main Container -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Desktop Sidebar -->
      <aside :class="[
        isSidebarOpen ? 'w-64' : 'w-20',
        'hidden md:flex flex-col bg-slate-900/90 border-r border-slate-800/80 transition-all duration-300 relative z-30'
      ]">
        <!-- Brand / Header -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800/80">
          <Link href="/admin/dashboard" class="flex items-center space-x-3 overflow-hidden">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/20">
              <span class="font-extrabold text-white text-base font-sans">O</span>
            </div>
            <div v-if="isSidebarOpen" class="flex flex-col overflow-hidden">
              <span class="font-bold text-sm tracking-tight text-white truncate">{{ site.title || 'OpenSource eCommerce CMS' }}</span>
              <span class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">WordPress Alternative</span>
            </div>
          </Link>
          <button @click="isSidebarOpen = !isSidebarOpen" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
            <Menu class="w-4 h-4" />
          </button>
        </div>

        <!-- Navigation Links -->
        <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
          <Link v-for="item in navigation" :key="item.name" :href="item.href"
                :class="[
                  isActive(item.href)
                    ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold shadow-md shadow-indigo-600/30'
                    : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200 font-medium',
                  'group flex items-center px-3 py-2.5 rounded-xl text-sm transition-all duration-150'
                ]">
            <component :is="item.icon" :class="[isActive(item.href) ? 'text-white' : 'text-slate-400 group-hover:text-slate-200', 'w-5 h-5 shrink-0 transition-colors']" />
            <span v-if="isSidebarOpen" class="ml-3 truncate flex-1">{{ item.name }}</span>
            <span v-if="isSidebarOpen && item.badge"
                  class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
              {{ item.badge }}
            </span>
          </Link>
        </div>

        <!-- Bottom Status Box -->
        <div v-if="isSidebarOpen" class="p-3 border-t border-slate-800/80">
          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-400 font-medium">Cache Driver</span>
              <span :class="[
                cacheStatus.active_driver === 'redis' ? 'text-emerald-400 bg-emerald-500/10' : 'text-sky-400 bg-sky-500/10',
                'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider'
              ]">
                {{ cacheStatus.active_driver }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-400 font-medium">Active Theme</span>
              <span class="text-indigo-300 font-medium text-[11px] truncate max-w-[100px]">{{ site.active_theme || 'default' }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Header Bar -->
        <header class="h-16 bg-slate-900/60 border-b border-slate-800/80 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between shrink-0 z-20">
          <div class="flex items-center space-x-3">
            <button @click="isMobileSidebarOpen = true" class="md:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
              <Menu class="w-5 h-5" />
            </button>
            <div class="relative hidden sm:block w-64 lg:w-80">
              <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
              <input type="text" placeholder="Search posts, products, settings..."
                     class="w-full pl-9 pr-4 py-1.5 rounded-lg bg-slate-950/70 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors" />
            </div>
          </div>

          <div class="flex items-center space-x-3">
            <!-- Visit Storefront Button -->
            <a href="/" target="_blank"
               class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700/60 text-xs font-semibold transition-all">
              <span>View Storefront</span>
              <ExternalLink class="w-3.5 h-3.5 text-slate-400" />
            </a>

            <!-- Android SDUI Direct Link -->
            <Link href="/admin/sdui"
                  class="hidden sm:flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-indigo-950/70 hover:bg-indigo-900/60 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition-all">
              <Smartphone class="w-3.5 h-3.5 text-indigo-400" />
              <span>SDUI Builder</span>
            </Link>

            <!-- User Menu -->
            <div class="relative">
              <button @click="isUserMenuOpen = !isUserMenuOpen"
                      class="flex items-center space-x-2.5 p-1.5 rounded-lg hover:bg-slate-800/80 transition-colors">
                <img :src="auth.user?.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&h=100&q=80'"
                     alt="Avatar" class="w-8 h-8 rounded-lg object-cover border border-slate-700" />
                <div class="hidden lg:flex flex-col text-left">
                  <span class="text-xs font-semibold text-white leading-tight">{{ auth.user?.full_name || auth.user?.username || 'Admin' }}</span>
                  <span class="text-[10px] text-slate-400 capitalize">{{ auth.user?.role || 'Administrator' }}</span>
                </div>
                <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
              </button>

              <!-- Dropdown Menu -->
              <div v-if="isUserMenuOpen"
                   @click.outside="isUserMenuOpen = false"
                   v-motion
                   :initial="{ opacity: 0, y: 10, scale: 0.95 }"
                   :enter="{ opacity: 1, y: 0, scale: 1 }"
                   class="absolute right-0 mt-2 w-48 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl p-1.5 z-50 text-xs">
                <div class="px-3 py-2 border-b border-slate-800 mb-1">
                  <p class="font-semibold text-white truncate">{{ auth.user?.username }}</p>
                  <p class="text-slate-400 truncate">{{ auth.user?.email }}</p>
                </div>
                <Link href="/admin/settings" class="flex items-center space-x-2 px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                  <Settings class="w-4 h-4" />
                  <span>Site Settings</span>
                </Link>
                <button @click="logout" class="w-full flex items-center space-x-2 px-3 py-2 rounded-lg text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 transition-colors mt-1">
                  <LogOut class="w-4 h-4" />
                  <span>Log Out</span>
                </button>
              </div>
            </div>
          </div>
        </header>

        <!-- Page Content Scroll Container -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
