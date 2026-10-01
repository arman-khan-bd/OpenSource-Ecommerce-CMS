<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  Layers,
  Plus,
  Search,
  ExternalLink,
  Edit,
  Trash2,
  Sparkles,
  Layout,
  CheckCircle,
  Clock,
  Eye
} from 'lucide-vue-next';

const pageState = usePage();
const activePlugins = computed(() => pageState.props.activePlugins || []);
const isElementorActive = computed(() => activePlugins.value.includes('elementor-pro'));

const props = defineProps({
  pages: {
    type: Object,
    default: () => ({ items: [], total: 0, page: 1 }),
  },
  filters: {
    type: Object,
    default: () => ({ status: 'all', search: '' }),
  },
});

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');

function handleSearch() {
  router.get(
    '/admin/pages',
    {
      search: searchQuery.value,
      status: selectedStatus.value,
    },
    { preserveState: true, replace: true }
  );
}

function handleStatusFilter(status) {
  selectedStatus.value = status;
  handleSearch();
}

function deletePage(id, title) {
  if (confirm(`Are you sure you want to delete page "${title}"?`)) {
    router.delete(`/admin/posts/${id}/delete`, {
      onSuccess: () => {
        router.get('/admin/pages');
      }
    });
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2">
            <h1 class="text-2xl font-bold text-white tracking-tight">Static Pages</h1>
            <span class="px-2.5 py-0.5 rounded-full bg-pink-500/20 text-pink-300 font-mono text-xs font-bold">
              {{ pages.total }} Pages
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            {{ isElementorActive ? 'Manage marketing landing pages, brand stories, and Elementor Pro layouts' : 'Manage your site pages, content, and templates' }}
          </p>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Normal Add New Page button -->
          <Link
            href="/admin/pages/new?builder=classic"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center space-x-1.5 shadow-lg shadow-indigo-600/30 transition-all active:scale-95"
            title="Create page with standard WordPress editor"
          >
            <Plus class="w-4 h-4" />
            <span>Add New Page</span>
          </Link>

          <!-- Elementor Pro Quick Creator (if Elementor is active) -->
          <Link
            v-if="isElementorActive"
            href="/admin/pages/new?builder=elementor"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-600 via-indigo-600 to-purple-600 hover:from-pink-500 hover:to-indigo-500 text-white font-bold text-xs flex items-center space-x-1.5 shadow-lg shadow-pink-600/30 transition-all active:scale-95"
            title="Create page directly with Elementor Pro Visual Builder"
          >
            <Sparkles class="w-4 h-4 text-amber-300" />
            <span>Add with Elementor</span>
          </Link>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
        <!-- Status Tabs -->
        <div class="flex items-center space-x-1 bg-slate-950 p-1 rounded-xl border border-slate-800 self-start">
          <button
            v-for="s in [
              { id: 'all', label: 'All Pages' },
              { id: 'published', label: 'Published' },
              { id: 'draft', label: 'Drafts' },
            ]"
            :key="s.id"
            @click="handleStatusFilter(s.id)"
            :class="[
              selectedStatus === s.id ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all'
            ]"
          >
            {{ s.label }}
          </button>
        </div>

        <!-- Search -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Search pages by title..."
            class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
          />
        </div>
      </div>

      <!-- Pages Table -->
      <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
            <tr>
              <th class="py-3 px-4">Title & Permalinks</th>
              <th class="py-3 px-4">Editor Type</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Published / Updated</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
            <tr v-for="item in pages.items" :key="item.id" class="hover:bg-slate-800/30">
              <td class="py-4 px-4 font-sans">
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-white text-sm hover:text-indigo-300 transition-colors">{{ item.title }}</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono mt-0.5">
                  <span>/page/{{ item.slug }}</span>
                  <a :href="`/page/${item.slug}`" target="_blank" class="text-indigo-400 hover:text-indigo-300 inline-flex items-center space-x-1" title="Preview Live">
                    <ExternalLink class="w-3 h-3" />
                  </a>
                </div>
              </td>

              <td class="py-4 px-4 font-sans">
                <span v-if="isElementorActive && ((item.content || '').includes('[elementor') || (item.meta && item.meta.builder === 'elementor'))"
                      class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-pink-500/20 text-pink-300 border border-pink-500/30 inline-flex items-center space-x-1">
                  <Layout class="w-3 h-3 text-pink-400" />
                  <span>Elementor Pro</span>
                </span>
                <span v-else-if="!isElementorActive && ((item.content || '').includes('[elementor') || (item.meta && item.meta.builder === 'elementor'))"
                      class="px-2.5 py-1 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">
                  Standard (Elementor Inactive)
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">
                  Classic WYSIWYG
                </span>
              </td>

              <td class="py-4 px-4 font-sans">
                <span :class="[
                  item.status === 'published' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                  'px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize'
                ]">
                  {{ item.status }}
                </span>
              </td>

              <td class="py-4 px-4 text-right text-slate-400 font-sans">
                {{ item.published_at ? item.published_at.substring(0, 10) : item.created_at ? item.created_at.substring(0, 10) : 'Recent' }}
              </td>

              <td class="py-4 px-4 text-right font-sans space-x-1.5 whitespace-nowrap">
                <a :href="`/page/${item.slug}`" target="_blank" class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold inline-flex items-center space-x-1 transition-colors" title="Preview Live">
                  <Eye class="w-3.5 h-3.5" />
                  <span>View</span>
                </a>

                <!-- Normal / Classic Page Edit (ALWAYS visible) -->
                <Link
                  :href="`/admin/pages/${item.id}/edit?builder=classic`"
                  class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs inline-flex items-center space-x-1.5 shadow-md shadow-indigo-600/30 transition-all"
                  title="Normal page edit with standard editor"
                >
                  <Edit class="w-3.5 h-3.5" />
                  <span>Edit</span>
                </Link>

                <!-- Elementor Edit (Visible whenever Elementor Pro is active) -->
                <Link
                  v-if="isElementorActive"
                  :href="`/admin/pages/${item.id}/edit?builder=elementor`"
                  class="px-3 py-1.5 rounded-lg bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 text-white font-bold text-xs inline-flex items-center space-x-1.5 shadow-md shadow-pink-600/30 transition-all"
                  title="Edit visually with Elementor Pro"
                >
                  <Layout class="w-3.5 h-3.5" />
                  <span>Edit with Elementor</span>
                </Link>

                <button @click="deletePage(item.id, item.title)" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-950/30 transition-colors" title="Delete Page">
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>

            <tr v-if="!pages.items.length">
              <td colspan="5" class="text-center py-12 text-slate-400 space-y-2 font-sans">
                <Layers class="w-8 h-8 mx-auto text-slate-600" />
                <p class="text-sm font-semibold">No static pages found</p>
                <Link href="/admin/pages/new" class="text-xs text-indigo-400 font-bold hover:underline">
                  Create your first page &rarr;
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
