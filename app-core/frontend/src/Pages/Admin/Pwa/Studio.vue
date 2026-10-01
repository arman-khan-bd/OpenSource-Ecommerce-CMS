<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Download,
  Bell,
  Smartphone,
  ShieldCheck,
  Send,
  Save,
  RefreshCw,
  Copy,
  Check,
  CheckCircle2,
  AlertTriangle,
  Sliders,
  ExternalLink,
  Zap,
  Globe,
  Monitor,
  Laptop
} from 'lucide-vue-next';

const props = defineProps({
  manifest: {
    type: Object,
    default: () => ({})
  },
  vapid: {
    type: Object,
    default: () => ({})
  },
  subscribersCount: {
    type: Number,
    default: 0
  },
  latestAlert: {
    type: Object,
    default: null
  }
});

const activeTab = ref('install'); // 'install' | 'manifest' | 'vapid' | 'broadcast'
const pwaIcon192 = '/icon-192.png';
const isCopied = ref(false);
const isVapidCopied = ref(false);

// Manifest form
const manifestForm = useForm({
  manifest: {
    name: props.manifest.name || 'Admin Dashboard — OpenSource eCommerce CMS',
    short_name: props.manifest.short_name || 'Admin Dashboard',
    description: props.manifest.description || 'Dedicated Progressive Web App for OpenSource eCommerce CMS Admin Dashboard',
    start_url: props.manifest.start_url || '/admin/dashboard',
    scope: props.manifest.scope || '/admin',
    display: props.manifest.display || 'standalone',
    theme_color: props.manifest.theme_color || '#4f46e5',
    background_color: props.manifest.background_color || '#020617',
  },
  vapid_subject: props.vapid.subject || 'mailto:admin@opensource-cms.local'
});

function saveManifest() {
  manifestForm.post('/admin/pwa/save');
}

// VAPID state
const currentVapid = ref({
  publicKey: props.vapid.publicKey || '',
  subject: props.vapid.subject || ''
});
const isRegeneratingVapid = ref(false);
const vapidSuccessMsg = ref('');

async function regenerateVapid() {
  if (!confirm('Regenerating VAPID keys will invalidate existing push subscriptions. Proceed?')) {
    return;
  }
  isRegeneratingVapid.value = true;
  try {
    const res = await fetch('/admin/pwa/vapid/generate', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const data = await res.json();
    if (data.success) {
      currentVapid.value.publicKey = data.publicKey;
      currentVapid.value.subject = data.subject;
      vapidSuccessMsg.value = '✓ RFC 8292 VAPID keypair successfully generated!';
      setTimeout(() => { vapidSuccessMsg.value = ''; }, 4000);
    }
  } catch (err) {
    alert('Error regenerating VAPID keys: ' + err.message);
  } finally {
    isRegeneratingVapid.value = false;
  }
}

// Icon regeneration
const isRegeneratingIcons = ref(false);
const iconSuccessMsg = ref('');

async function regenerateIcons() {
  isRegeneratingIcons.value = true;
  try {
    const res = await fetch('/admin/pwa/icons/generate', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const data = await res.json();
    if (data.success) {
      iconSuccessMsg.value = '✓ PWA icons generated in public_html/ (192px, 512px, badge-72px)';
      setTimeout(() => { iconSuccessMsg.value = ''; }, 4000);
    }
  } catch (err) {
    alert('Error generating icons: ' + err.message);
  } finally {
    isRegeneratingIcons.value = false;
  }
}

// PWA Installation & Push Testing
const deferredPrompt = ref(null);
const canInstall = ref(false);
const isInstalled = ref(false);
const swRegistered = ref(false);
const pushPermission = ref('default');

// Broadcast test form
const testPayload = ref({
  title: 'Admin Dashboard Alert',
  body: 'New e-commerce order received! Tap to review order details.',
  url: '/admin/orders'
});
const isBroadcasting = ref(false);
const broadcastResult = ref('');

onMounted(() => {
  // Check standalone mode
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    isInstalled.value = true;
  }

  // Intercept PWA prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    canInstall.value = true;
  });

  window.addEventListener('appinstalled', () => {
    isInstalled.value = true;
    canInstall.value = false;
    deferredPrompt.value = null;
  });

  // Check SW status
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistration().then(reg => {
      swRegistered.value = !!reg;
    });
  }

  // Check Notification permission
  if ('Notification' in window) {
    pushPermission.value = Notification.permission;
  }
});

async function triggerInstall() {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
      isInstalled.value = true;
      canInstall.value = false;
    }
    deferredPrompt.value = null;
  } else {
    alert('To install the Admin Dashboard PWA:\n• Desktop: Click the install icon at the right end of the browser address bar.\n• Mobile: Open browser menu and select "Add to Home screen".');
  }
}

async function requestPushPermission() {
  if (!('Notification' in window)) {
    alert('This browser does not support notifications.');
    return;
  }
  const perm = await Notification.requestPermission();
  pushPermission.value = perm;

  if (perm === 'granted' && 'serviceWorker' in navigator) {
    try {
      const reg = await navigator.serviceWorker.ready;
      // Get VAPID public key from backend
      const res = await fetch('/api/v1/pwa/vapid-public-key');
      const data = await res.json();
      if (data.publicKey) {
        // Subscribe to pushManager
        const convertedKey = urlBase64ToUint8Array(data.publicKey);
        const sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: convertedKey
        });

        // Send subscription to server
        await fetch('/api/v1/pwa/subscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(sub)
        });

        broadcastResult.value = '✓ Subscribed to native Web Push successfully!';
        setTimeout(() => { broadcastResult.value = ''; }, 4000);
      }
    } catch (err) {
      console.warn('Subscription error:', err);
    }
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

async function sendTestNotification() {
  isBroadcasting.value = true;
  broadcastResult.value = '';

  try {
    // 1. Direct Service Worker notification display (exact user specified pattern)
    if ('serviceWorker' in navigator && Notification.permission === 'granted') {
      const reg = await navigator.serviceWorker.ready;
      reg.showNotification(testPayload.value.title, {
        body: testPayload.value.body,
        icon: '/icon-192.png',
        badge: '/badge-72.png',
        vibrate: [100, 50, 100],
        data: {
          url: testPayload.value.url || '/admin/dashboard'
        }
      });
    }

    // 2. Broadcast via backend Web Push VAPID RFC 8292 to all registered subscribers
    const res = await fetch('/api/v1/pwa/broadcast', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(testPayload.value)
    });
    const result = await res.json();
    broadcastResult.value = result.message || '✓ Native dropdown notification sent!';
  } catch (err) {
    broadcastResult.value = 'Notification test: ' + err.message;
  } finally {
    isBroadcasting.value = false;
  }
}

function copyVapidKey() {
  navigator.clipboard.writeText(currentVapid.value.publicKey);
  isVapidCopied.value = true;
  setTimeout(() => { isVapidCopied.value = false; }, 2000);
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6 pb-12">
      <!-- Top Welcome Banner -->
      <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 shadow-2xl relative overflow-hidden backdrop-blur-md">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center space-x-2">
              <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Admin Dashboard PWA Studio</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold uppercase border border-emerald-500/30">Admin Only</span>
              <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase border border-indigo-500/30">RFC 8292 Web Push</span>
            </div>
            <p class="text-xs text-indigo-200 max-w-2xl leading-relaxed">
              Progressive Web App configured strictly for the Admin Dashboard. Push notifications run on open web standards: the Web Push API and the VAPID protocol (RFC 8292) without Firebase or third-party wrappers.
            </p>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-3">
            <button @click="triggerInstall"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition-all">
              <Download class="w-4 h-4" />
              <span>{{ isInstalled ? 'Admin App Installed ✓' : 'Install Admin App' }}</span>
            </button>

            <button @click="sendTestNotification" :disabled="isBroadcasting"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-2 transition-all disabled:opacity-50">
              <Bell class="w-4 h-4" />
              <span>{{ isBroadcasting ? 'Sending...' : 'Test Web Push' }}</span>
            </button>
          </div>
        </div>

        <!-- Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Quick Metrics Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- PWA Scope (Strictly Admin) -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-3.5 shadow-lg">
          <div class="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Application Target</span>
            <p class="text-xs font-bold text-white">Admin Dashboard Only</p>
            <span class="text-[10px] text-emerald-400 font-mono">scope: /admin</span>
          </div>
        </div>

        <!-- App Display Mode -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-3.5 shadow-lg">
          <div class="w-10 h-10 rounded-lg bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
            <Monitor class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Display Mode</span>
            <p class="text-xs font-bold text-white capitalize">{{ manifest.display || 'Standalone' }}</p>
            <span class="text-[10px] text-slate-400">Desktop & Mobile App</span>
          </div>
        </div>

        <!-- Service Worker Status -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-3.5 shadow-lg">
          <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
            <CheckCircle2 class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Service Worker</span>
            <p class="text-xs font-bold text-emerald-300">sw.js Active</p>
            <span class="text-[10px] text-slate-400">Cache & Push Worker</span>
          </div>
        </div>

        <!-- Web Push Subscribers -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center space-x-3.5 shadow-lg">
          <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
            <Bell class="w-5 h-5" />
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Push Subscribers</span>
            <p class="text-xs font-bold text-white">{{ subscribersCount }} Device(s)</p>
            <span class="text-[10px] text-amber-400 font-mono">RFC 8292 VAPID</span>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
        <button @click="activeTab = 'install'"
                :class="[
                  activeTab === 'install' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60',
                  'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2'
                ]">
          <Download class="w-3.5 h-3.5" />
          <span>Admin App Install</span>
        </button>

        <button @click="activeTab = 'manifest'"
                :class="[
                  activeTab === 'manifest' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60',
                  'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2'
                ]">
          <Sliders class="w-3.5 h-3.5" />
          <span>Manifest Settings</span>
        </button>

        <button @click="activeTab = 'vapid'"
                :class="[
                  activeTab === 'vapid' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60',
                  'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2'
                ]">
          <ShieldCheck class="w-3.5 h-3.5" />
          <span>RFC 8292 VAPID Protocol</span>
        </button>

        <button @click="activeTab = 'broadcast'"
                :class="[
                  activeTab === 'broadcast' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60',
                  'px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center space-x-2'
                ]">
          <Send class="w-3.5 h-3.5" />
          <span>Web Push Dispatcher</span>
        </button>
      </div>

      <!-- Tab 1: Admin App Installation & Device Preview -->
      <div v-if="activeTab === 'install'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Installation Action Card (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-6 shadow-xl">
          <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
              <Download class="w-4 h-4 text-emerald-400" />
              <span>Install Admin Dashboard App</span>
            </h3>
            <p class="text-xs text-slate-400 mt-1">
              Installing the Admin Dashboard PWA provides a dedicated desktop window and full-screen mobile app experience with zero browser address bar clutter and instant access.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <img :src="pwaIcon192" alt="App Icon" class="w-12 h-12 rounded-xl object-cover border border-slate-700 shadow-md" />
                <div>
                  <h4 class="text-sm font-bold text-white">{{ manifest.name || 'Admin Dashboard' }}</h4>
                  <p class="text-[11px] text-slate-400">Standalone PWA for Admin Panel</p>
                </div>
              </div>
              <button @click="triggerInstall"
                      class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 flex items-center space-x-1.5 transition-all">
                <Download class="w-3.5 h-3.5" />
                <span>{{ isInstalled ? 'Installed' : 'Install Now' }}</span>
              </button>
            </div>

            <div class="pt-2 border-t border-slate-800/80 grid grid-cols-2 gap-2 text-xs">
              <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">Start URL</span>
                <span class="font-mono text-indigo-300 text-[11px]">/admin/dashboard</span>
              </div>
              <div class="p-2 rounded-lg bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] text-slate-500 uppercase block">Permission Scope</span>
                <span class="font-mono text-emerald-400 text-[11px]">/admin (Admin Only)</span>
              </div>
            </div>
          </div>

          <!-- Notification Permission Card -->
          <div class="p-4 rounded-xl bg-indigo-950/40 border border-indigo-500/30 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <Bell class="w-4 h-4 text-indigo-400" />
                <span class="text-xs font-bold text-white">Browser Push Notification Permission</span>
              </div>
              <span :class="[
                pushPermission === 'granted' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                'px-2 py-0.5 rounded text-[10px] font-bold uppercase border'
              ]">
                {{ pushPermission }}
              </span>
            </div>
            <p class="text-xs text-indigo-200">
              Enable native system dropdown alerts to receive instant notifications for new store orders, customer inquiries, and SDUI mobile deployments.
            </p>
            <div class="flex items-center space-x-2">
              <button @click="requestPushPermission"
                      class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow transition-all">
                {{ pushPermission === 'granted' ? 'Re-sync Subscription' : 'Allow Notifications' }}
              </button>
              <button @click="sendTestNotification"
                      class="px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all">
                Send Test Dropdown Alert
              </button>
            </div>
          </div>

          <!-- Instruction Steps -->
          <div class="space-y-2">
            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">How to Install on Any Device:</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                <div class="flex items-center space-x-2 text-indigo-400 font-bold">
                  <Monitor class="w-4 h-4" />
                  <span>Desktop (Chrome, Edge)</span>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Click the install icon in the URL bar (top right) or click the "Install Now" button above to launch as a standalone desktop app.
                </p>
              </div>

              <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                <div class="flex items-center space-x-2 text-purple-400 font-bold">
                  <Smartphone class="w-4 h-4" />
                  <span>Mobile (Android, iOS)</span>
                </div>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                  Open in Chrome or Safari, tap menu or Share button, and select <strong>"Add to Home Screen"</strong> for full-screen admin control.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Standalone Window Mockup Preview (5 cols) -->
        <div class="lg:col-span-5 bg-slate-900/80 rounded-2xl border border-slate-800 p-6 flex flex-col shadow-xl">
          <div class="pb-3 border-b border-slate-800 mb-4 flex items-center justify-between">
            <span class="text-xs font-bold text-white uppercase tracking-wider">Standalone Window Preview</span>
            <span class="text-[10px] text-slate-500 font-mono">display: standalone</span>
          </div>

          <!-- Desktop App Window Frame Simulator -->
          <div class="flex-1 rounded-xl bg-slate-950 border border-slate-800 overflow-hidden shadow-2xl flex flex-col">
            <!-- Window Title Bar -->
            <div class="h-8 bg-slate-900 border-b border-slate-800 px-3 flex items-center justify-between">
              <div class="flex items-center space-x-1.5">
                <div class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
              </div>
              <div class="flex items-center space-x-1.5 text-[11px] font-semibold text-slate-300">
                <img :src="pwaIcon192" class="w-3.5 h-3.5 rounded" />
                <span>{{ manifest.short_name || 'Admin Dashboard' }}</span>
              </div>
              <div class="w-8"></div>
            </div>

            <!-- App Inner Canvas Mockup -->
            <div class="p-4 space-y-3 bg-slate-950 flex-1">
              <div class="flex items-center justify-between pb-2 border-b border-slate-900">
                <div class="w-24 h-3 bg-slate-800 rounded"></div>
                <div class="w-6 h-6 bg-indigo-600/30 rounded-full"></div>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 space-y-1">
                  <div class="w-12 h-2 bg-slate-700 rounded"></div>
                  <div class="w-16 h-4 bg-emerald-500/40 rounded"></div>
                </div>
                <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 space-y-1">
                  <div class="w-12 h-2 bg-slate-700 rounded"></div>
                  <div class="w-16 h-4 bg-indigo-500/40 rounded"></div>
                </div>
              </div>

              <!-- SDUI Canvas Shortcut inside Mockup -->
              <div class="p-3 rounded-lg bg-gradient-to-r from-indigo-900/40 to-purple-900/40 border border-indigo-500/20 space-y-1">
                <span class="text-[10px] font-bold text-white flex items-center space-x-1">
                  <Smartphone class="w-3 h-3 text-indigo-400" />
                  <span>Android SDUI Studio</span>
                </span>
                <p class="text-[9px] text-slate-400">Native Jetpack Compose engine synced</p>
              </div>

              <!-- Sample Native Dropdown Toast Simulation -->
              <div class="p-2.5 rounded-lg bg-slate-900 border border-indigo-500/50 shadow-lg text-[10px] space-y-0.5">
                <div class="flex items-center justify-between text-indigo-300 font-bold">
                  <span class="flex items-center space-x-1">
                    <Bell class="w-3 h-3 text-indigo-400" />
                    <span>System Dropdown Alert</span>
                  </span>
                  <span class="text-[8px] text-slate-500">now</span>
                </div>
                <p class="text-[9px] text-slate-300">Order #1042 completed via Stripe</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Admin Manifest Customizer -->
      <div v-else-if="activeTab === 'manifest'" class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-6 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Web App Manifest Settings</h3>
            <p class="text-xs text-slate-400 mt-0.5">Customizes /manifest.json for the standalone admin dashboard</p>
          </div>

          <div class="flex items-center space-x-3">
            <button @click="regenerateIcons" :disabled="isRegeneratingIcons"
                    class="px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center space-x-1.5 transition-all">
              <RefreshCw :class="['w-3.5 h-3.5 text-indigo-400', isRegeneratingIcons ? 'animate-spin' : '']" />
              <span>Regenerate PNG Icons</span>
            </button>

            <button @click="saveManifest" :disabled="manifestForm.processing"
                    class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
              <Save class="w-3.5 h-3.5" />
              <span>{{ manifestForm.processing ? 'Saving...' : 'Save Manifest' }}</span>
            </button>
          </div>
        </div>

        <div v-if="iconSuccessMsg" class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 text-xs">
          {{ iconSuccessMsg }}
        </div>

        <form @submit.prevent="saveManifest" class="grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
          <div>
            <label class="block text-slate-400 font-semibold mb-1">Full Application Name</label>
            <input v-model="manifestForm.manifest.name" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            <span class="text-[10px] text-slate-500 mt-1 block">Displayed on installation banners and app launchers</span>
          </div>

          <div>
            <label class="block text-slate-400 font-semibold mb-1">Short Name</label>
            <input v-model="manifestForm.manifest.short_name" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            <span class="text-[10px] text-slate-500 mt-1 block">Displayed beneath the app icon on mobile home screens</span>
          </div>

          <div class="md:col-span-2">
            <label class="block text-slate-400 font-semibold mb-1">App Description</label>
            <textarea v-model="manifestForm.manifest.description" rows="2"
                      class="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500"></textarea>
          </div>

          <div>
            <label class="block text-slate-400 font-semibold mb-1">Start URL</label>
            <input v-model="manifestForm.manifest.start_url" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-indigo-400 font-mono focus:outline-none focus:border-indigo-500" />
            <span class="text-[10px] text-slate-500 mt-1 block">Landing URL when opened from app icon (e.g. /admin/dashboard)</span>
          </div>

          <div>
            <label class="block text-slate-400 font-semibold mb-1">Scope</label>
            <input v-model="manifestForm.manifest.scope" type="text"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 font-mono focus:outline-none focus:border-indigo-500" />
            <span class="text-[10px] text-slate-500 mt-1 block">URL navigation boundaries for standalone app (strictly /admin)</span>
          </div>

          <div>
            <label class="block text-slate-400 font-semibold mb-1">Display Mode</label>
            <select v-model="manifestForm.manifest.display"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500">
              <option value="standalone">Standalone (Native App Feel - No browser chrome)</option>
              <option value="minimal-ui">Minimal UI (Minimal browser navigation)</option>
              <option value="fullscreen">Fullscreen</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-slate-400 font-semibold mb-1">Theme Color</label>
              <div class="flex items-center space-x-2">
                <input v-model="manifestForm.manifest.theme_color" type="color" class="w-8 h-8 rounded border border-slate-700 bg-transparent cursor-pointer" />
                <input v-model="manifestForm.manifest.theme_color" type="text" class="flex-1 px-2 py-1.5 rounded bg-slate-950 border border-slate-800 text-white font-mono text-[11px]" />
              </div>
            </div>

            <div>
              <label class="block text-slate-400 font-semibold mb-1">Background Color</label>
              <div class="flex items-center space-x-2">
                <input v-model="manifestForm.manifest.background_color" type="color" class="w-8 h-8 rounded border border-slate-700 bg-transparent cursor-pointer" />
                <input v-model="manifestForm.manifest.background_color" type="text" class="flex-1 px-2 py-1.5 rounded bg-slate-950 border border-slate-800 text-white font-mono text-[11px]" />
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Tab 3: RFC 8292 VAPID Protocol Manager -->
      <div v-else-if="activeTab === 'vapid'" class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-6 shadow-xl">
        <div>
          <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
            <ShieldCheck class="w-4 h-4 text-indigo-400" />
            <span>RFC 8292 VAPID Protocol & Web Push Architecture</span>
          </h3>
          <p class="text-xs text-slate-400 mt-1">
            Voluntary Application Server Identification (VAPID) allows native browsers to verify push messages directly from your CMS backend without reliance on Google Firebase or closed proprietary gateways.
          </p>
        </div>

        <div v-if="vapidSuccessMsg" class="p-3 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 text-xs">
          {{ vapidSuccessMsg }}
        </div>

        <!-- Educational Explanation Card -->
        <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-950/60 to-purple-950/60 border border-indigo-500/30 text-xs space-y-2">
          <p class="font-bold text-white flex items-center space-x-1.5">
            <Zap class="w-4 h-4 text-amber-400" />
            <span>Native Open Web Standards Principle</span>
          </p>
          <p class="text-indigo-200 leading-relaxed">
            Push notifications in PWAs are built on open web standards: the <strong>Web Push API</strong> and the <strong>VAPID protocol (RFC 8292)</strong>. Firebase is merely a third-party wrapper around these native standards. Modern CMS communicates directly with native browser push services (Mozilla autopush, Google push service, Apple Web Push) using ECDSA P-256 signatures.
          </p>
        </div>

        <!-- Keypair Card -->
        <div class="space-y-4">
          <div>
            <label class="block text-slate-400 font-semibold mb-1 text-xs">VAPID Public Key (ECDSA P-256 Base64URL)</label>
            <div class="flex items-center space-x-2">
              <input :value="currentVapid.publicKey" readonly
                     class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 font-mono text-xs focus:outline-none" />
              <button @click="copyVapidKey"
                      class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1 transition-all">
                <Check v-if="isVapidCopied" class="w-3.5 h-3.5 text-emerald-400" />
                <Copy v-else class="w-3.5 h-3.5" />
                <span>{{ isVapidCopied ? 'Copied' : 'Copy' }}</span>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-slate-400 font-semibold mb-1 text-xs">VAPID Contact Subject</label>
            <input :value="currentVapid.subject" readonly
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs focus:outline-none" />
          </div>

          <div class="pt-3 border-t border-slate-800 flex justify-between items-center">
            <span class="text-[11px] text-slate-500">Need to rotate keys? Regenerate a fresh RFC 8292 pair anytime.</span>
            <button @click="regenerateVapid" :disabled="isRegeneratingVapid"
                    class="px-4 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 border border-rose-500/40 text-rose-300 hover:text-white text-xs font-bold flex items-center space-x-1.5 transition-all">
              <RefreshCw :class="['w-3.5 h-3.5', isRegeneratingVapid ? 'animate-spin' : '']" />
              <span>{{ isRegeneratingVapid ? 'Generating...' : 'Regenerate VAPID Keys' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tab 4: Web Push Dropdown Notification Dispatcher -->
      <div v-else-if="activeTab === 'broadcast'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Notification Dispatch Form (7 cols) -->
        <div class="lg:col-span-7 bg-slate-900/80 rounded-2xl border border-slate-800 p-6 space-y-5 shadow-xl">
          <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
              <Send class="w-4 h-4 text-indigo-400" />
              <span>Dispatch Native Dropdown Alert</span>
            </h3>
            <p class="text-xs text-slate-400 mt-1">
              Triggers the Service Worker (sw.js) push event listener and wakes up subscribed admin devices.
            </p>
          </div>

          <div v-if="broadcastResult"
               class="p-3 rounded-xl bg-indigo-950/80 border border-indigo-500/40 text-indigo-200 text-xs flex items-center space-x-2">
            <Zap class="w-4 h-4 text-amber-400 shrink-0" />
            <span>{{ broadcastResult }}</span>
          </div>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block text-slate-400 font-semibold mb-1">Notification Title</label>
              <input v-model="testPayload.title" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>

            <div>
              <label class="block text-slate-400 font-semibold mb-1">Notification Body</label>
              <textarea v-model="testPayload.body" rows="3"
                        class="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div>
              <label class="block text-slate-400 font-semibold mb-1">Target Click URL</label>
              <input v-model="testPayload.url" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-indigo-400 font-mono focus:outline-none focus:border-indigo-500" />
              <span class="text-[10px] text-slate-500 mt-1 block">Opened by Service Worker when user clicks the notification</span>
            </div>

            <div class="pt-2">
              <button @click="sendTestNotification" :disabled="isBroadcasting"
                      class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center justify-center space-x-2 transition-all disabled:opacity-50">
                <Bell class="w-4 h-4" />
                <span>{{ isBroadcasting ? 'Broadcasting Alert...' : 'Send Native System Dropdown Alert' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Code & Dropdown Simulation (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          <!-- Dropdown Notification Visual Preview -->
          <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-5 shadow-xl space-y-3">
            <span class="text-xs font-bold text-white uppercase tracking-wider block">Native Dropdown Appearance</span>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-indigo-500/40 shadow-2xl flex items-start space-x-3">
              <img :src="pwaIcon192" class="w-10 h-10 rounded-xl object-cover border border-slate-700 shrink-0" />
              <div class="flex-1 overflow-hidden space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-white truncate">{{ testPayload.title || 'New Alert' }}</span>
                  <span class="text-[9px] text-slate-500">now</span>
                </div>
                <p class="text-[11px] text-slate-300 leading-snug line-clamp-2">{{ testPayload.body || 'Default notification body' }}</p>
                <div class="flex items-center space-x-2 pt-1 text-[9px] text-indigo-400">
                  <span class="font-mono">{{ testPayload.url || '/' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Service Worker Listener Code Inspector -->
          <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-5 shadow-xl space-y-2">
            <span class="text-xs font-bold text-white uppercase tracking-wider block">sw.js Event Implementation</span>
            <pre class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-indigo-300 font-mono text-[10px] overflow-x-auto leading-relaxed">
// Native Service Worker (sw.js)
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : { title: 'New Alert', body: 'Default body' };
  const options = {
    body: data.body,
    icon: '/icon-192.png',
    badge: '/badge-72.png',
    vibrate: [100, 50, 100],
    data: { url: data.url || '/' }
  };
  event.waitUntil(self.registration.showNotification(data.title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(clients.openWindow(event.notification.data.url));
});</pre>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
