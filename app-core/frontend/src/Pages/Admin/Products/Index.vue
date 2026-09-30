<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Plus, Search, ShoppingBag, Trash2, Edit3, ExternalLink } from 'lucide-vue-next';

const props = defineProps({
  products: Object,
  filters: Object,
});

const search = ref(props.filters.search || '');

function onSearch() {
  router.get('/admin/products', { search: search.value }, { preserveState: true });
}

function deleteProduct(id) {
  if (confirm('Permanently delete this product from catalog?')) {
    router.delete(`/admin/products/${id}/delete`);
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Product Catalog</h1>
          <p class="text-xs text-slate-400 mt-1">Manage e-commerce products for storefront and Android mobile app</p>
        </div>
        <Link href="/admin/products/new"
              class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs flex items-center space-x-2 shadow-lg shadow-indigo-600/30 transition-all self-start">
          <Plus class="w-4 h-4" />
          <span>New Product</span>
        </Link>
      </div>

      <div class="flex items-center justify-between gap-4 pb-2 border-b border-slate-800">
        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">
          Total Products ({{ products.total }})
        </span>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
          <input v-model="search" @keyup.enter="onSearch" type="text" placeholder="Search by name, SKU..."
                 class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
        </div>
      </div>

      <div class="bg-slate-900/70 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
              <tr>
                <th class="py-3.5 px-4">Item & SKU</th>
                <th class="py-3.5 px-4">Price</th>
                <th class="py-3.5 px-4">Stock</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="prod in products.items" :key="prod.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="py-4 px-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-800 shrink-0 overflow-hidden border border-slate-700">
                      <img :src="prod.images && prod.images[0] ? prod.images[0] : 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=100&q=80'"
                           alt="" class="w-full h-full object-cover" />
                    </div>
                    <div>
                      <Link :href="`/admin/products/${prod.id}/edit`" class="font-bold text-white hover:text-indigo-400 text-sm transition-colors">
                        {{ prod.title }}
                      </Link>
                      <span class="block text-slate-500 font-mono text-[11px]">SKU: {{ prod.sku || 'N/A' }}</span>
                    </div>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <div class="font-bold text-white text-sm">
                    ${{ (prod.sale_price || prod.price || 0).toFixed(2) }}
                    <span v-if="prod.sale_price" class="text-xs text-slate-500 line-through font-normal ml-1">
                      ${{ (prod.price || 0).toFixed(2) }}
                    </span>
                  </div>
                </td>
                <td class="py-4 px-4">
                  <span :class="prod.stock > 0 ? 'text-emerald-400' : 'text-rose-400'" class="font-bold">
                    {{ prod.stock }} in stock
                  </span>
                </td>
                <td class="py-4 px-4">
                  <span :class="prod.status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400'"
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    {{ prod.status }}
                  </span>
                </td>
                <td class="py-4 px-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <a :href="`/product/${prod.slug}`" target="_blank"
                       class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" title="View product">
                      <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                    <Link :href="`/admin/products/${prod.id}/edit`"
                          class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition-colors" title="Edit">
                      <Edit3 class="w-3.5 h-3.5" />
                    </Link>
                    <button @click="deleteProduct(prod.id)"
                            class="p-1.5 rounded-lg bg-rose-950/40 text-rose-400 hover:bg-rose-900/50 transition-colors" title="Delete">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!products.items || !products.items.length">
                <td colspan="5" class="py-12 text-center text-slate-500 text-xs">
                  No products in catalog. Click "New Product" to add your first item.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
