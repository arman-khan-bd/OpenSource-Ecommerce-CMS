<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Send,
  Mail,
  Copy,
  Check,
  Shield,
  Clock,
  Settings,
  Inbox,
  User,
  ExternalLink
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  forms: {
    type: Array,
    default: () => [],
  },
  recentEntries: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ totalForms: 2, totalEntries: 32, notificationEmail: 'admin@example.com' }),
  },
});

const activeTab = ref('forms'); // 'forms' | 'entries' | 'settings'
const copiedCode = ref(null);

const formSettings = ref({
  notification_email: props.settings.notification_email || 'admin@example.com',
  enable_honeypot: props.settings.enable_honeypot !== false,
  success_message: props.settings.success_message || 'Thank you! Your message has been received.',
});

function copyTag(tag, id) {
  navigator.clipboard.writeText(tag);
  copiedCode.value = id;
  setTimeout(() => {
    if (copiedCode.value === id) copiedCode.value = null;
  }, 2000);
}

function saveSettings() {
  router.post('/admin/wpforms/settings', formSettings.value, {
    preserveScroll: true,
  });
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
          <div class="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/10">
            <Send class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-2xl font-bold text-white tracking-tight">WPForms Lead & Form Builder</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-sky-500/20 text-sky-300 font-mono text-xs font-bold">FORMS</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Embeddable contact widgets, newsletter subscriptions, and lead capture inbox</p>
          </div>
        </div>

        <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 self-start">
          <button
            @click="activeTab = 'forms'"
            :class="[
              activeTab === 'forms' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all'
            ]"
          >
            Forms ({{ forms.length }})
          </button>
          <button
            @click="activeTab = 'entries'"
            :class="[
              activeTab === 'entries' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5'
            ]"
          >
            <Inbox class="w-3.5 h-3.5" />
            <span>Inquiries Inbox ({{ recentEntries.length }})</span>
          </button>
          <button
            @click="activeTab = 'settings'"
            :class="[
              activeTab === 'settings' ? 'bg-sky-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all'
            ]"
          >
            Settings
          </button>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Active Forms</span>
          <div class="text-2xl font-extrabold text-sky-400 font-display">
            {{ forms.length }} Shortcodes
          </div>
          <span class="text-[11px] text-slate-400">Ready for posts and pages</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Total Leads Captured</span>
          <div class="text-2xl font-extrabold text-white font-display">
            {{ stats.totalEntries }} Leads
          </div>
          <span class="text-[11px] text-emerald-400">Zero spam • Honeypot guarded</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Notification Recipient</span>
          <div class="text-base font-extrabold text-indigo-300 font-mono truncate">
            {{ formSettings.notification_email }}
          </div>
          <span class="text-[11px] text-slate-400">Instant email alerts</span>
        </div>
      </div>

      <!-- Tab 1: Forms List -->
      <div v-if="activeTab === 'forms'" class="space-y-4">
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
              <tr>
                <th class="py-3 px-4">Form Name</th>
                <th class="py-3 px-4">Embed Shortcode</th>
                <th class="py-3 px-4">Fields</th>
                <th class="py-3 px-4">Submissions</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr v-for="f in forms" :key="f.id" class="hover:bg-slate-800/30">
                <td class="py-3.5 px-4 font-sans font-bold text-white">{{ f.name }}</td>
                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-amber-300 font-mono">
                    {{ f.tag }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-sans text-slate-300">{{ f.fields_count }} input fields</td>
                <td class="py-3.5 px-4 font-bold text-sky-400">{{ f.entries_count }} entries</td>
                <td class="py-3.5 px-4 font-sans">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 uppercase">
                    Active
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right font-sans">
                  <button
                    @click="copyTag(f.tag, f.id)"
                    class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold inline-flex items-center space-x-1"
                  >
                    <Check v-if="copiedCode === f.id" class="w-3.5 h-3.5 text-emerald-400" />
                    <Copy v-else class="w-3.5 h-3.5" />
                    <span>{{ copiedCode === f.id ? 'Copied' : 'Copy' }}</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2: Inquiries Inbox -->
      <div v-else-if="activeTab === 'entries'" class="space-y-4">
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
              <tr>
                <th class="py-3 px-4">Sender</th>
                <th class="py-3 px-4">Form</th>
                <th class="py-3 px-4">Subject / Note</th>
                <th class="py-3 px-4 text-right">Received</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr v-for="entry in recentEntries" :key="entry.id" class="hover:bg-slate-800/30">
                <td class="py-3.5 px-4 font-sans">
                  <div class="font-bold text-white">{{ entry.name }}</div>
                  <div class="text-[10px] text-slate-400">{{ entry.email }}</div>
                </td>
                <td class="py-3.5 px-4 font-sans text-sky-400 font-semibold">{{ entry.form }}</td>
                <td class="py-3.5 px-4 font-sans text-slate-300">{{ entry.subject }}</td>
                <td class="py-3.5 px-4 text-right text-slate-500 font-sans">{{ entry.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 3: Settings -->
      <div v-else class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 shadow-xl max-w-xl space-y-5">
        <h2 class="text-base font-bold text-white border-b border-slate-800 pb-2">WPForms Notification & Spam Controls</h2>

        <form @submit.prevent="saveSettings" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Notification Email Recipient</label>
            <input v-model="formSettings.notification_email" type="email" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Submission Success Message</label>
            <textarea v-model="formSettings.success_message" rows="3" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white"></textarea>
          </div>

          <label class="flex items-center space-x-3 cursor-pointer pt-2">
            <input v-model="formSettings.enable_honeypot" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-sky-600 focus:ring-0" />
            <span class="text-slate-200 font-semibold">Enable Anti-Spam Hidden Honeypot protection</span>
          </label>

          <div class="pt-4 border-t border-slate-800">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs shadow-lg shadow-sky-600/30 transition-all">
              Save WPForms Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
