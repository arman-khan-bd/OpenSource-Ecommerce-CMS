<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { UploadCloud, Image as ImageIcon, Trash2, Copy, Check, ExternalLink } from 'lucide-vue-next';

const props = defineProps({
  media: Object,
  filters: Object,
});

const isUploading = ref(false);
const copiedId = ref(null);
const fileInput = ref(null);

async function handleFileUpload(e) {
  const file = e.target.files[0];
  if (!file) return;

  isUploading.value = true;
  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await fetch('/admin/media/upload', {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();
    if (data.success) {
      router.reload();
    } else {
      alert(data.message || 'Upload failed');
    }
  } catch (err) {
    alert('Failed to upload file');
  } finally {
    isUploading.value = false;
  }
}

function copyUrl(id, url) {
  navigator.clipboard.writeText(window.location.origin + url);
  copiedId.value = id;
  setTimeout(() => { copiedId.value = null; }, 2000);
}

function deleteMedia(id) {
  if (confirm('Permanently delete this file from storage?')) {
    router.delete(`/admin/media/${id}/delete`);
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Media Storage</h1>
          <p class="text-xs text-slate-400 mt-1">Uploaded assets saved directly into public_html/uploads/ for rapid static delivery</p>
        </div>
        <div>
          <input ref="fileInput" @change="handleFileUpload" type="file" accept="image/*" class="hidden" />
          <button @click="$refs.fileInput.click()" :disabled="isUploading"
                  class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all">
            <UploadCloud class="w-4 h-4" />
            <span>{{ isUploading ? 'Uploading File...' : 'Upload Media' }}</span>
          </button>
        </div>
      </div>

      <!-- Drag & Drop Zone -->
      <div @click="$refs.fileInput.click()"
           class="border-2 border-dashed border-slate-800 hover:border-indigo-500/50 rounded-2xl p-8 text-center cursor-pointer bg-slate-900/40 hover:bg-slate-900/60 transition-all">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600/10 text-indigo-400 mb-3">
          <UploadCloud class="w-6 h-6" />
        </div>
        <p class="text-sm font-semibold text-white">Click or drop image to upload</p>
        <p class="text-xs text-slate-500 mt-1">PNG, JPG, WEBP, SVG, GIF up to 50MB</p>
      </div>

      <!-- Media Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div v-for="item in media.items" :key="item.id"
             class="group relative bg-slate-900/70 rounded-2xl border border-slate-800 overflow-hidden shadow-lg flex flex-col justify-between">
          <div class="aspect-square bg-slate-950 overflow-hidden relative">
            <img :src="item.url" :alt="item.alt_text || item.filename" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
            
            <div class="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
              <button @click.stop="copyUrl(item.id, item.url)"
                      class="p-2 rounded-lg bg-slate-800 text-slate-200 hover:bg-indigo-600 hover:text-white transition-colors" title="Copy URL">
                <Check v-if="copiedId === item.id" class="w-3.5 h-3.5 text-emerald-400" />
                <Copy v-else class="w-3.5 h-3.5" />
              </button>
              <a :href="item.url" target="_blank"
                 class="p-2 rounded-lg bg-slate-800 text-slate-200 hover:bg-indigo-600 hover:text-white transition-colors" title="View Full">
                <ExternalLink class="w-3.5 h-3.5" />
              </a>
              <button @click.stop="deleteMedia(item.id)"
                      class="p-2 rounded-lg bg-slate-800 text-rose-400 hover:bg-rose-900 transition-colors" title="Delete">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div class="p-2.5 text-[11px] border-t border-slate-800/80">
            <p class="font-semibold text-slate-200 truncate">{{ item.original_name }}</p>
            <p class="text-slate-500 font-mono text-[10px]">{{ (item.file_size / 1024).toFixed(0) }} KB</p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
