<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Plus, Search, FileText, Trash2, Edit3, ExternalLink } from 'lucide-vue-next';

const props = defineProps({
  posts: Object,
  filters: Object,
});

const search = ref(props.filters.search || '');
const currentType = ref(props.filters.type || 'post');

function onSearch() {
  router.get('/admin/posts', {
    type: currentType.value,
    search: search.value,
  }, { preserveState: true });
}

function setType(type) {
  currentType.value = type;
  router.get('/admin/posts', {
    type: type,
    search: search.value,
  }, { preserveState: true });
}

function deletePost(id) {
  if (confirm('Are you sure you want to move this item to trash?')) {
    router.delete(`/admin/posts/${id}/delete`);
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Content Management</h1>
          <p class="text-xs text-slate-400 mt-1">Manage articles, editorial guides, and standalone storefront pages</p>
        </div>
        <Link :href="`/admin/posts/new?type=${currentType}`"
              class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all self-start">
          <Plus class="w-4 h-4" />
          <span>New {{ currentType === 'page' ? 'Page' : 'Post' }}</span>
        </Link>
      </div>

      <!-- Filters & Type Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-800">
        <div class="flex items-center space-x-2">
          <button @click="setType('post')"
                  :class="[
                    currentType === 'post' ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white',
                    'px-4 py-2 rounded-xl text-xs font-semibold transition-all'
                  ]">
            Blog Posts ({{ currentType === 'post' ? posts.total : 'Posts' }})
          </button>
          <button @click="setType('page')"
                  :class="[
                    currentType === 'page' ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white',
                    'px-4 py-2 rounded-xl text-xs font-semibold transition-all'
                  ]">
            Pages ({{ currentType === 'page' ? posts.total : 'Pages' }})
          </button>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
          <input v-model="search" @keyup.enter="onSearch" type="text" placeholder="Search by title or content..."
                 class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
        </div>
      </div>

      <!-- Table Container -->
      <div class="bg-slate-900/70 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
              <tr>
                <th class="py-3.5 px-4">Title & Slug</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Date</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="post in posts.items" :key="post.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-slate-800 shrink-0 overflow-hidden">
                      <img :src="post.featured_image || 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=100&q=80'"
                           alt="" class="w-full h-full object-cover" />
                    </div>
                    <div>
                      <Link :href="`/admin/posts/${post.id}/edit`" class="font-bold text-white hover:text-indigo-400 text-sm transition-colors">
                        {{ post.title }}
                      </Link>
                      <span class="block text-slate-500 font-mono text-[11px] truncate max-w-sm">/{{ post.slug }}</span>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <span :class="post.status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400'"
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    {{ post.status }}
                  </span>
                </td>
                <td class="py-4 px-4 text-slate-400">
                  {{ post.published_at ? post.published_at.slice(0, 10) : (post.created_at ? post.created_at.slice(0, 10) : 'Recent') }}
                </td>
                <td class="py-4 px-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <a v-if="post.status === 'published'" :href="`/blog/${post.slug}`" target="_blank"
                       class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" title="View on storefront">
                      <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                    <Link :href="`/admin/posts/${post.id}/edit`"
                          class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" title="Edit">
                      <Edit3 class="w-3.5 h-3.5" />
                    </Link>
                    <button @click="deletePost(post.id)"
                            class="p-1.5 rounded-lg bg-rose-950/40 text-rose-400 hover:bg-rose-900/50 transition-colors" title="Delete">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!posts.items || !posts.items.length">
                <td colspan="4" class="py-12 text-center text-slate-500 text-xs">
                  No content found matching your filters. Click "New Post" to create your first article!
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
