<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeft, Save, Eye, Sparkles, Image as ImageIcon } from 'lucide-vue-next';

const props = defineProps({
  post: Object,
  isNew: Boolean,
});

const form = useForm({
  id: props.post.id,
  title: props.post.title || '',
  slug: props.post.slug || '',
  content: props.post.content || '',
  excerpt: props.post.excerpt || '',
  type: props.post.type || 'post',
  status: props.post.status || 'published',
  featured_image: props.post.featured_image || '',
  meta: props.post.meta || { tags: [], seo_title: '', seo_description: '' },
});

const isPreviewActive = ref(false);

function autoGenerateSlug() {
  if (!form.id || !form.slug) {
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }
}

function submit() {
  form.post('/admin/posts/save');
}
</script>

<template>
  <AdminLayout>
    <form @submit.prevent="submit" class="space-y-6">
      <!-- Action Bar -->
      <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <Link href="/admin/posts" class="p-2 rounded-xl bg-slate-900 text-slate-400 hover:text-white border border-slate-800 transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <h1 class="text-xl font-bold text-white tracking-tight">
              {{ isNew ? 'Create New ' + (form.type === 'page' ? 'Page' : 'Post') : 'Edit ' + form.title }}
            </h1>
            <span class="text-xs text-slate-400">Wordpress-style action and filter hooks applied on save</span>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button type="button" @click="isPreviewActive = !isPreviewActive"
                  class="px-3.5 py-2 rounded-xl bg-slate-900 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all">
            <Eye class="w-3.5 h-3.5" />
            <span>{{ isPreviewActive ? 'Edit Mode' : 'Live Preview' }}</span>
          </button>
          <button type="submit" :disabled="form.processing"
                  class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Saving...' : 'Save & Publish' }}</span>
          </button>
        </div>
      </div>

      <!-- Editor Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Area (2 Cols) -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Title & Slug -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">Article Title</label>
              <input v-model="form.title" @input="autoGenerateSlug" type="text" required placeholder="Enter compelling title..."
                     class="w-full px-4 py-3 rounded-xl bg-slate-950/80 border border-slate-800 text-base font-bold text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>

            <div class="flex items-center space-x-2 text-xs text-slate-400">
              <span class="shrink-0 font-medium">Permalink: /{{ form.type === 'page' ? 'page' : 'blog' }}/</span>
              <input v-model="form.slug" type="text" required
                     class="flex-1 px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <!-- Body Content / Rich Markdown -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-300">Content (HTML / Markdown Supported)</label>
              <span class="text-[11px] text-indigo-400 font-medium">Filtered by 'the_content' hook</span>
            </div>

            <!-- Preview or Edit Mode -->
            <div v-if="isPreviewActive" class="p-6 rounded-xl bg-slate-950/90 border border-slate-800 text-sm text-slate-300 leading-relaxed min-h-[350px] prose prose-invert max-w-none">
              <div v-html="form.content"></div>
            </div>
            <textarea v-else v-model="form.content" rows="16" placeholder="Write your post content here... Supports standard HTML formatting, headings, images, and embeds."
                      class="w-full p-4 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 font-mono focus:outline-none focus:border-indigo-500 leading-relaxed"></textarea>
          </div>

          <!-- Excerpt -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-2">
            <label class="block text-xs font-semibold text-slate-300">Excerpt / Short Summary</label>
            <textarea v-model="form.excerpt" rows="3" placeholder="Brief 1-2 sentence overview for social shares and cards..."
                      class="w-full p-3 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500"></textarea>
          </div>
        </div>

        <!-- Sidebar Options (1 Col) -->
        <div class="space-y-6">
          <!-- Publishing Status -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
            <h3 class="text-sm font-bold text-white">Publishing</h3>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Visibility Status</label>
              <select v-model="form.status"
                      class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="published">Published (Public)</option>
                <option value="draft">Draft (Private)</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Content Type</label>
              <select v-model="form.type"
                      class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="post">Blog Post / Article</option>
                <option value="page">Static Storefront Page</option>
              </select>
            </div>
          </div>

          <!-- Featured Image -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-3 shadow-xl">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
              <ImageIcon class="w-4 h-4 text-indigo-400" />
              <span>Featured Cover Image</span>
            </h3>
            <input v-model="form.featured_image" type="text" placeholder="https://... or /uploads/..."
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            <div v-if="form.featured_image" class="aspect-video w-full rounded-xl overflow-hidden bg-slate-950 border border-slate-800 mt-2">
              <img :src="form.featured_image" alt="Preview" class="w-full h-full object-cover" />
            </div>
          </div>

          <!-- Dynamic JSON Metadata & SEO -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-3 shadow-xl">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <Sparkles class="w-4 h-4 text-purple-400" />
                <span>SEO Metadata (JSON)</span>
              </h3>
              <span class="text-[10px] text-slate-500 font-mono">Native JSON Col</span>
            </div>
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">SEO Title Override</label>
              <input v-model="form.meta.seo_title" type="text" placeholder="Custom Google title..."
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-[11px] text-slate-400 mb-1">Meta Description</label>
              <textarea v-model="form.meta.seo_description" rows="2" placeholder="Search engine snippet..."
                        class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>
          </div>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>
