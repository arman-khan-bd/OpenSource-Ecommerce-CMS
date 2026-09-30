<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Settings, Zap, Trash2, CheckCircle2, XCircle, RefreshCw, Server, Shield } from 'lucide-vue-next';

const props = defineProps({
  settings: Object,
  cacheInfo: Object,
  diagnostics: Object,
});

const form = useForm({
  site_title: props.settings.site_title || '',
  site_tagline: props.settings.site_tagline || '',
  site_description: props.settings.site_description || '',
  site_url: props.settings.site_url || '',
  admin_email: props.settings.admin_email || '',
  redis_host: props.settings.redis_host || 'none',
  redis_port: props.settings.redis_port || 6379,
  currency: props.settings.currency || 'USD',
});

const isTestingRedis = ref(false);
const redisTestResult = ref(null);
const isFlushing = ref(false);

async function testRedis() {
  isTestingRedis.value = true;
  redisTestResult.value = null;

  try {
    const res = await fetch('/admin/settings/test-redis', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        host: form.redis_host,
        port: form.redis_port,
      }),
    });
    const data = await res.json();
    redisTestResult.value = data;
  } catch (err) {
    redisTestResult.value = { success: false, message: 'Redis check failed: ' + err.message };
  } finally {
    isTestingRedis.value = false;
  }
}

function flushCache() {
  if (confirm('Clear all cache entries across Redis and Filesystem?')) {
    isFlushing.value = true;
    router.post('/admin/settings/cache-flush', {}, {
      onFinish: () => { isFlushing.value = false; }
    });
  }
}

function saveSettings() {
  form.post('/admin/settings');
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-8 max-w-5xl mx-auto">
      <div class="flex items-center justify-between pb-4 border-b border-slate-800">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">System Settings & Cache</h1>
          <p class="text-xs text-slate-400 mt-1">Configure site parameters, dynamic Redis caching, and review hosting diagnostics</p>
        </div>
      </div>

      <!-- Settings Form -->
      <form @submit.prevent="saveSettings" class="space-y-6">
        <!-- Site Configuration -->
        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
          <h2 class="text-base font-bold text-white flex items-center space-x-2">
            <Settings class="w-4 h-4 text-indigo-400" />
            <span>General Site Configuration</span>
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Site Title</label>
              <input v-model="form.site_title" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Tagline</label>
              <input v-model="form.site_tagline" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-300 mb-1">Description (Alternative to WordPress)</label>
              <input v-model="form.site_description" type="text" placeholder="The modern, lightweight, monolithic & headless alternative to WordPress."
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Public Site URL</label>
              <input v-model="form.site_url" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Administrative Email</label>
              <input v-model="form.admin_email" type="email"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
          </div>
        </div>

        <!-- Cache Configuration -->
        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-5 shadow-xl">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
              <Zap class="w-4 h-4 text-indigo-400" />
              <span>Cache & Runtime Fallback Engine</span>
            </h2>
            <button type="button" @click="flushCache" :disabled="isFlushing"
                    class="px-3.5 py-1.5 rounded-xl bg-rose-950/60 hover:bg-rose-900/60 border border-rose-500/40 text-rose-300 text-xs font-semibold flex items-center space-x-1.5 transition-all">
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ isFlushing ? 'Purging Cache...' : 'Flush Entire Cache' }}</span>
            </button>
          </div>

          <!-- Active Cache Status Box -->
          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
              <div class="flex items-center space-x-2">
                <span class="text-slate-400 font-semibold">Active Cache Engine:</span>
                <span :class="cacheInfo.active_driver === 'redis' ? 'text-emerald-400' : 'text-sky-400'" class="font-bold uppercase tracking-wider">
                  {{ cacheInfo.active_driver }}
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300">{{ cacheInfo.status }}</span>
              </div>
              <p class="text-[11px] text-slate-500 mt-1">
                {{ cacheInfo.active_driver === 'redis' ? 'Operating in-memory for maximum throughput.' : 'Filesystem storage active at ' + cacheInfo.directory }}
              </p>
            </div>
          </div>

          <!-- Redis Configuration Fields -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-2">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Redis Host (use 'none' for Filesystem)</label>
              <input v-model="form.redis_host" type="text"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Redis Port</label>
              <input v-model.number="form.redis_port" type="number"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <div class="flex items-center justify-between pt-2">
            <button type="button" @click="testRedis" :disabled="isTestingRedis"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center space-x-1.5 transition-all">
              <RefreshCw :class="{ 'animate-spin': isTestingRedis }" class="w-3.5 h-3.5" />
              <span>Test Redis Connectivity</span>
            </button>

            <span v-if="redisTestResult" :class="redisTestResult.success ? 'text-emerald-400' : 'text-rose-400'" class="text-xs font-semibold">
              {{ redisTestResult.message || redisTestResult.error }}
            </span>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="form.processing"
                  class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all">
            <span>{{ form.processing ? 'Saving Changes...' : 'Save Settings' }}</span>
          </button>
        </div>
      </form>

      <!-- System Diagnostics & Environment -->
      <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
        <h2 class="text-base font-bold text-white flex items-center space-x-2">
          <Server class="w-4 h-4 text-emerald-400" />
          <span>Server Diagnostics (cPanel Host Environment)</span>
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
            <span class="text-slate-400 font-semibold">PHP Version</span>
            <p class="font-bold text-white font-mono mt-1">{{ diagnostics.php_version }}</p>
          </div>
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
            <span class="text-slate-400 font-semibold">Memory Limit</span>
            <p class="font-bold text-white font-mono mt-1">{{ diagnostics.memory_limit }}</p>
          </div>
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
            <span class="text-slate-400 font-semibold">Max Execution Time</span>
            <p class="font-bold text-white font-mono mt-1">{{ diagnostics.max_execution_time }}</p>
          </div>
          <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
            <span class="text-slate-400 font-semibold">Upload Max Filesize</span>
            <p class="font-bold text-white font-mono mt-1">{{ diagnostics.upload_max_filesize }}</p>
          </div>
        </div>

        <div class="pt-2">
          <span class="block text-xs font-semibold text-slate-300 mb-2">PHP Extensions Audit:</span>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
            <div v-for="(enabled, name) in diagnostics.extensions" :key="name"
                 class="p-2.5 rounded-lg bg-slate-950 border border-slate-800/80 flex items-center justify-between">
              <span class="font-mono text-[11px] text-slate-300">{{ name }}</span>
              <CheckCircle2 v-if="enabled" class="w-4 h-4 text-emerald-400" />
              <XCircle v-else class="w-4 h-4 text-slate-600" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
