<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Lock, Mail, Eye, EyeOff, Sparkles, ArrowRight, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
  siteTitle: String,
  error: String,
});

const showPassword = ref(false);

const form = useForm({
  identifier: 'admin',
  password: 'Password123!',
});

function submit() {
  form.post('/admin/login');
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 relative overflow-hidden font-sans">
    <!-- Ambient Blur Background -->
    <div class="absolute -top-32 -left-32 w-80 h-80 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10">
      <!-- Brand Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 shadow-xl shadow-indigo-500/25 mb-4">
          <span class="font-extrabold text-white text-xl">O</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-white">
          {{ siteTitle || 'OpenSource eCommerce CMS' }}
        </h1>
        <p class="text-indigo-400 text-xs font-semibold mt-1">Alternative to WordPress</p>
        <p class="text-slate-400 text-xs mt-0.5">Sign in to access your administrative control panel</p>
      </div>

      <!-- Login Card -->
      <div class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 sm:p-8 backdrop-blur-xl shadow-2xl">
        <!-- Error Message -->
        <div v-if="error" class="mb-5 p-3.5 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-200 text-xs flex items-center space-x-2">
          <ShieldCheck class="w-4 h-4 text-rose-400 shrink-0" />
          <span>{{ error }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email or Username</label>
            <div class="relative">
              <Mail class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input v-model="form.identifier" type="text" required placeholder="admin or you@domain.com"
                     class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-300">Password</label>
            </div>
            <div class="relative">
              <Lock class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••"
                     class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors" />
              <button type="button" @click="showPassword = !showPassword"
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300">
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between pt-1 text-xs">
            <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
              <input type="checkbox" checked class="rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-0" />
              <span>Remember session</span>
            </label>
            <a href="/" class="text-indigo-400 hover:text-indigo-300 font-medium">Visit Storefront</a>
          </div>

          <div class="pt-2">
            <button type="submit" :disabled="form.processing"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center justify-center space-x-2 transition-all">
              <span>{{ form.processing ? 'Authenticating...' : 'Sign In to Dashboard' }}</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </form>
      </div>

      <div class="mt-6 text-center text-[11px] text-slate-500">
        <p>Production Monolithic Standalone CMS • Dual Architecture</p>
      </div>
    </div>
  </div>
</template>
