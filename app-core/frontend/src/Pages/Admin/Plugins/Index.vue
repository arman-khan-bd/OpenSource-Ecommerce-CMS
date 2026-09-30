<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Puzzle, CheckCircle, Activity, Radio, Clock, Code } from 'lucide-vue-next';

const props = defineProps({
  plugins: Array,
  hookInspector: Object,
});

const activeTab = ref('plugins'); // 'plugins' | 'hooks'

function togglePlugin(slug) {
  router.post('/admin/plugins/toggle', { slug });
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Plugins & Hook Engine</h1>
          <p class="text-xs text-slate-400 mt-1">WordPress-compatible action & filter hooks engine with live execution monitoring</p>
        </div>

        <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 self-start">
          <button @click="activeTab = 'plugins'"
                  :class="[
                    activeTab === 'plugins' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white',
                    'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all'
                  ]">
            Installed Plugins ({{ plugins.length }})
          </button>
          <button @click="activeTab = 'hooks'"
                  :class="[
                    activeTab === 'hooks' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white',
                    'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5'
                  ]">
            <Activity class="w-3.5 h-3.5" />
            <span>Hook Inspector</span>
          </button>
        </div>
      </div>

      <!-- Plugins Tab -->
      <div v-if="activeTab === 'plugins'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="plugin in plugins" :key="plugin.slug"
             class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 flex flex-col justify-between shadow-xl space-y-4">
          <div class="space-y-3">
            <div class="flex items-start justify-between">
              <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center">
                <Puzzle class="w-5 h-5" />
              </div>
              <span class="text-xs font-mono text-slate-500">v{{ plugin.version }}</span>
            </div>

            <div>
              <h3 class="font-bold text-white text-base">{{ plugin.name }}</h3>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">{{ plugin.description }}</p>
            </div>

            <div class="text-[11px] text-slate-500">
              <span>Author: {{ plugin.author }}</span>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
            <span :class="plugin.is_active ? 'text-emerald-400' : 'text-slate-500'" class="text-xs font-semibold flex items-center space-x-1.5">
              <span :class="plugin.is_active ? 'bg-emerald-500' : 'bg-slate-600'" class="w-2 h-2 rounded-full"></span>
              <span>{{ plugin.is_active ? 'Active' : 'Inactive' }}</span>
            </span>

            <button @click="togglePlugin(plugin.slug)"
                    :class="[
                      plugin.is_active ? 'bg-rose-950/60 text-rose-300 hover:bg-rose-900/60 border-rose-500/30' : 'bg-indigo-600 text-white hover:bg-indigo-500',
                      'px-4 py-2 rounded-xl text-xs font-semibold border transition-all'
                    ]">
              {{ plugin.is_active ? 'Deactivate' : 'Activate' }}
            </button>
          </div>
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
              <div v-for="(act, name) in hookInspector.actions" :key="name"
                   class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                  <span class="font-mono font-bold text-indigo-300">{{ name }}</span>
                  <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold text-[10px]">{{ act.count }} Callbacks</span>
                </div>
                <div class="text-[11px] text-slate-400 pl-2 border-l border-slate-800 space-y-0.5">
                  <div v-for="(cb, idx) in act.callbacks" :key="idx" class="flex justify-between font-mono">
                    <span class="truncate max-w-[200px]">{{ cb.id }}</span>
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
              <div v-for="(flt, name) in hookInspector.filters" :key="name"
                   class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                  <span class="font-mono font-bold text-purple-300">{{ name }}</span>
                  <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold text-[10px]">{{ flt.count }} Callbacks</span>
                </div>
                <div class="text-[11px] text-slate-400 pl-2 border-l border-slate-800 space-y-0.5">
                  <div v-for="(cb, idx) in flt.callbacks" :key="idx" class="flex justify-between font-mono">
                    <span class="truncate max-w-[200px]">{{ cb.id }}</span>
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
                <tr v-for="(log, idx) in (hookInspector.execution_history || []).slice(0, 10)" :key="idx" class="hover:bg-slate-800/30">
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
  </AdminLayout>
</template>
