<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  CheckCircle2,
  XCircle,
  Database,
  Globe,
  Zap,
  ShieldCheck,
  ArrowRight,
  ArrowLeft,
  Server,
  Loader2,
  Sparkles,
  Lock
} from 'lucide-vue-next';

const props = defineProps({
  requirements: Object,
  defaultDbPath: String,
  canProceed: Boolean,
});

const currentStep = ref(1);
const isTestingDb = ref(false);
const dbTestResult = ref(null);
const isSubmitting = ref(false);
const submitError = ref(null);

const form = ref({
  db_driver: 'pdo_mysql',
  db_host: '127.0.0.1',
  db_port: 3306,
  db_name: 'cms_db',
  db_user: 'root',
  db_pass: '',
  db_prefix: 'cms_',
  site_title: 'OpenSource eCommerce CMS',
  site_tagline: 'Alternative to WordPress',
  admin_username: 'admin',
  admin_email: 'admin@example.com',
  admin_password: 'Password123!',
  redis_host: 'none',
  redis_port: 6379,
});

const steps = [
  { id: 1, name: 'Environment', desc: 'System requirements & permissions' },
  { id: 2, name: 'Database', desc: 'MySQL / MariaDB or SQLite' },
  { id: 3, name: 'Site & Admin', desc: 'Identity & credentials' },
  { id: 4, name: 'Cache Engine', desc: 'Redis with File fallback' },
  { id: 5, name: 'Complete', desc: 'Execute zero-terminal build' },
];

async function testDatabase() {
  isTestingDb.value = true;
  dbTestResult.value = null;

  try {
    const res = await fetch('/install/test-db', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        driver: form.value.db_driver,
        host: form.value.db_host,
        port: form.value.db_port,
        dbname: form.value.db_name,
        user: form.value.db_user,
        password: form.value.db_pass,
      }),
    });
    const data = await res.json();
    dbTestResult.value = data;
  } catch (e) {
    dbTestResult.value = { success: false, message: 'Test failed: ' + e.message };
  } finally {
    isTestingDb.value = false;
  }
}

async function runInstallation() {
  isSubmitting.value = true;
  submitError.value = null;

  try {
    const res = await fetch('/install/execute', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form.value),
    });
    const data = await res.json();

    if (data.success) {
      window.location.href = data.redirect || '/admin/login';
    } else {
      submitError.value = data.message || 'Installation encountered an error.';
    }
  } catch (e) {
    submitError.value = 'Failed to execute installer: ' + e.message;
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8 relative overflow-hidden font-sans">
    <!-- Ambient Glow Background -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-3xl w-full mx-auto relative z-10">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-3">
          <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
          <span>Zero-Terminal Web Installer</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white font-sans">
          OpenSource eCommerce CMS Setup
        </h1>
        <p class="text-slate-400 text-sm mt-2">
          Deploying your modern, lightweight WordPress alternative on cPanel / Apache.
        </p>
      </div>

      <!-- Step Indicator Bar -->
      <div class="grid grid-cols-5 gap-2 mb-8">
        <div v-for="s in steps" :key="s.id"
             :class="[
               currentStep === s.id
                 ? 'border-indigo-500 bg-indigo-950/60 text-white'
                 : currentStep > s.id
                   ? 'border-emerald-500/50 bg-emerald-950/30 text-emerald-300'
                   : 'border-slate-800 bg-slate-900/40 text-slate-500',
               'p-3 rounded-xl border transition-all text-left flex flex-col justify-between'
             ]">
          <span class="text-[10px] font-bold uppercase tracking-wider">Step 0{{ s.id }}</span>
          <span class="text-xs font-semibold mt-1 truncate">{{ s.name }}</span>
        </div>
      </div>

      <!-- Main Step Card -->
      <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 sm:p-8 backdrop-blur-xl shadow-2xl relative">
        <!-- Error Banner -->
        <div v-if="submitError" class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-200 text-sm flex items-start space-x-3">
          <XCircle class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
          <div>
            <p class="font-bold">Installation Stopped</p>
            <p class="text-xs mt-1">{{ submitError }}</p>
          </div>
        </div>

        <!-- Step 1: Environment Check -->
        <div v-if="currentStep === 1" class="space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
              <ShieldCheck class="w-5 h-5 text-indigo-400" />
              <span>Server Environment & Permissions</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
              Verifying PHP 8.2+, database drivers, and write permissions for zero-terminal operation.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="(check, key) in requirements.checks" :key="key"
                 class="p-3.5 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
              <div>
                <p class="text-xs font-semibold text-slate-200">{{ check.name }}</p>
                <p class="text-[11px] text-slate-400">{{ check.current }}</p>
              </div>
              <CheckCircle2 v-if="check.passed" class="w-5 h-5 text-emerald-400 shrink-0" />
              <XCircle v-else class="w-5 h-5 text-rose-400 shrink-0" />
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t border-slate-800">
            <button @click="currentStep = 2"
                    :disabled="!canProceed"
                    class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all">
              <span>Continue to Database</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Step 2: Database Setup -->
        <div v-if="currentStep === 2" class="space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
              <Database class="w-5 h-5 text-indigo-400" />
              <span>Database Configuration</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
              Connect to your MySQL / MariaDB database, or use SQLite for instant zero-config setup.
            </p>
          </div>

          <!-- Driver Selector -->
          <div class="grid grid-cols-2 gap-4">
            <button type="button" @click="form.db_driver = 'pdo_mysql'"
                    :class="[
                      form.db_driver === 'pdo_mysql'
                        ? 'border-indigo-500 bg-indigo-950/40 text-white'
                        : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700',
                      'p-4 rounded-xl border text-left transition-all'
                    ]">
              <span class="block font-bold text-sm">MySQL 8.0+ / MariaDB</span>
              <span class="block text-[11px] text-slate-400 mt-1">Standard cPanel hosting database with native JSON indexes</span>
            </button>

            <button type="button" @click="form.db_driver = 'pdo_sqlite'"
                    :class="[
                      form.db_driver === 'pdo_sqlite'
                        ? 'border-indigo-500 bg-indigo-950/40 text-white'
                        : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700',
                      'p-4 rounded-xl border text-left transition-all'
                    ]">
              <span class="block font-bold text-sm">SQLite (Embedded)</span>
              <span class="block text-[11px] text-slate-400 mt-1">Zero configuration required, stored inside app-core/var/</span>
            </button>
          </div>

          <!-- MySQL Fields -->
          <div v-if="form.db_driver === 'pdo_mysql'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Database Host</label>
              <input v-model="form.db_host" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Port</label>
              <input v-model.number="form.db_port" type="number"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Database Name</label>
              <input v-model="form.db_name" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Table Prefix</label>
              <input v-model="form.db_prefix" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
              <input v-model="form.db_user" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
              <input v-model="form.db_pass" type="password" placeholder="Leave empty if none"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <!-- Connection Test Tool -->
          <div class="flex items-center justify-between pt-2">
            <button type="button" @click="testDatabase" :disabled="isTestingDb"
                    class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 flex items-center space-x-2 transition-all">
              <Loader2 v-if="isTestingDb" class="w-3.5 h-3.5 animate-spin" />
              <span>{{ isTestingDb ? 'Testing Connection...' : 'Test Connection' }}</span>
            </button>
            <div v-if="dbTestResult">
              <span :class="dbTestResult.success ? 'text-emerald-400' : 'text-rose-400'" class="text-xs font-medium">
                {{ dbTestResult.message }}
              </span>
            </div>
          </div>

          <div class="flex justify-between pt-4 border-t border-slate-800">
            <button @click="currentStep = 1" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 flex items-center space-x-2">
              <ArrowLeft class="w-4 h-4" />
              <span>Back</span>
            </button>
            <button @click="currentStep = 3" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all">
              <span>Next: Site & Admin</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Step 3: Site & Admin Setup -->
        <div v-if="currentStep === 3" class="space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
              <Globe class="w-5 h-5 text-indigo-400" />
              <span>Site Details & Administrator Setup</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
              Configure your primary site title and create the Super Administrator account.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-300 mb-1">Site Title</label>
              <input v-model="form.site_title" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-300 mb-1">Site Tagline</label>
              <input v-model="form.site_tagline" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Admin Username</label>
              <input v-model="form.admin_username" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Admin Email</label>
              <input v-model="form.admin_email" type="email"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-300 mb-1">Administrator Password</label>
              <input v-model="form.admin_password" type="text"
                     class="w-full px-3 py-2 rounded-lg bg-slate-950/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" />
            </div>
          </div>

          <div class="flex justify-between pt-4 border-t border-slate-800">
            <button @click="currentStep = 2" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 flex items-center space-x-2">
              <ArrowLeft class="w-4 h-4" />
              <span>Back</span>
            </button>
            <button @click="currentStep = 4" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all">
              <span>Next: Cache Engine</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Step 4: Cache & Performance -->
        <div v-if="currentStep === 4" class="space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
              <Zap class="w-5 h-5 text-indigo-400" />
              <span>Cache & Runtime Fallback</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
              Redis provides extreme performance. If unavailable on your cPanel host, the CMS automatically falls back to file caching without crashing.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-bold text-white">Automatic Filesystem Fallback</p>
                <p class="text-[11px] text-slate-400">Default for shared cPanel accounts lacking Redis extensions</p>
              </div>
              <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-400 text-xs font-bold">ACTIVE</span>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Redis Host (Optional)</label>
                <input v-model="form.redis_host" type="text" placeholder="none"
                       class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Redis Port</label>
                <input v-model.number="form.redis_port" type="number"
                       class="w-full px-3 py-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
          </div>

          <div class="flex justify-between pt-4 border-t border-slate-800">
            <button @click="currentStep = 3" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 flex items-center space-x-2">
              <ArrowLeft class="w-4 h-4" />
              <span>Back</span>
            </button>
            <button @click="currentStep = 5" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all">
              <span>Next: Ready to Deploy</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Step 5: Summary & Execution -->
        <div v-if="currentStep === 5" class="space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
              <Sparkles class="w-5 h-5 text-indigo-400" />
              <span>Execute Installation</span>
            </h2>
            <p class="text-xs text-slate-400 mt-1">
              Confirm your deployment parameters. Clicking below creates the database schema, seeds the initial store and blog, and configures the Android SDUI studio.
            </p>
          </div>

          <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-2.5 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-800/80">
              <span class="text-slate-400">Database Driver:</span>
              <span class="font-bold text-white uppercase">{{ form.db_driver }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/80">
              <span class="text-slate-400">Site Title:</span>
              <span class="font-bold text-white">{{ form.site_title }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/80">
              <span class="text-slate-400">Super Administrator:</span>
              <span class="font-bold text-white">{{ form.admin_username }} ({{ form.admin_email }})</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-slate-400">Pre-loaded Assets:</span>
              <span class="font-bold text-indigo-300">Modern Canvas Theme, Sample Store, Android SDUI Layout</span>
            </div>
          </div>

          <div class="flex justify-between pt-4 border-t border-slate-800">
            <button @click="currentStep = 4" class="px-5 py-2.5 rounded-xl bg-slate-800 text-xs font-semibold text-slate-300 flex items-center space-x-2">
              <ArrowLeft class="w-4 h-4" />
              <span>Back</span>
            </button>
            <button @click="runInstallation" :disabled="isSubmitting"
                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 hover:opacity-95 text-white font-bold text-xs flex items-center space-x-2 shadow-xl shadow-indigo-600/40 transition-all">
              <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
              <span>{{ isSubmitting ? 'Building Schema & Seeding Data...' : 'Install Modern CMS Now' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
