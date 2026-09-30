<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Search, ShoppingCart, Eye, DollarSign, Clock, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
  orders: Object,
  stats: Object,
  filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

function onFilter() {
  router.get('/admin/orders', {
    search: search.value,
    status: status.value,
  }, { preserveState: true });
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-white tracking-tight">Orders & Invoicing</h1>
          <p class="text-xs text-slate-400 mt-1">Manage customer purchases and fulfillment status</p>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
          <span class="text-slate-400 text-xs font-semibold">Total Revenue</span>
          <p class="text-xl font-bold text-white mt-1">${{ (stats.total_sales || 0).toLocaleString() }}</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
          <span class="text-slate-400 text-xs font-semibold">All Orders</span>
          <p class="text-xl font-bold text-white mt-1">{{ stats.total_orders || 0 }}</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
          <span class="text-slate-400 text-xs font-semibold">Pending Fulfillment</span>
          <p class="text-xl font-bold text-amber-400 mt-1">{{ stats.pending || 0 }}</p>
        </div>
        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
          <span class="text-slate-400 text-xs font-semibold">Completed</span>
          <p class="text-xl font-bold text-emerald-400 mt-1">{{ stats.completed || 0 }}</p>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-800">
        <div class="flex items-center space-x-2">
          <button v-for="st in ['all', 'pending', 'processing', 'completed', 'cancelled']" :key="st"
                  @click="status = st; onFilter()"
                  :class="[
                    status === st ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white',
                    'px-3.5 py-1.5 rounded-xl text-xs font-semibold uppercase tracking-wider transition-all capitalize'
                  ]">
            {{ st }}
          </button>
        </div>

        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
          <input v-model="search" @keyup.enter="onFilter" type="text" placeholder="Search order number or email..."
                 class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-900/80 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
        </div>
      </div>

      <div class="bg-slate-900/70 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
              <tr>
                <th class="py-3.5 px-4">Order #</th>
                <th class="py-3.5 px-4">Customer</th>
                <th class="py-3.5 px-4">Total</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Date</th>
                <th class="py-3.5 px-4 text-right">View</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="order in orders.items" :key="order.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="py-4 px-4 font-mono font-bold text-white">
                  <Link :href="`/admin/orders/${order.id}`" class="hover:text-indigo-400">
                    {{ order.order_number }}
                  </Link>
                </td>
                <td class="py-4 px-4">
                  <p class="font-semibold text-white">{{ order.customer_name }}</p>
                  <span class="text-slate-400 text-[11px]">{{ order.customer_email }}</span>
                </td>
                <td class="py-4 px-4 font-bold text-white">
                  ${{ (order.total_amount || 0).toFixed(2) }}
                </td>
                <td class="py-4 px-4">
                  <span :class="[
                    order.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' :
                    order.status === 'processing' ? 'bg-indigo-500/20 text-indigo-400' :
                    order.status === 'pending' ? 'bg-amber-500/20 text-amber-400' : 'bg-rose-500/20 text-rose-400',
                    'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider'
                  ]">
                    {{ order.status }}
                  </span>
                </td>
                <td class="py-4 px-4 text-slate-400">
                  {{ order.created_at ? order.created_at.slice(0, 10) : 'Recent' }}
                </td>
                <td class="py-4 px-4 text-right">
                  <Link :href="`/admin/orders/${order.id}`" class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white inline-flex items-center">
                    <Eye class="w-3.5 h-3.5" />
                  </Link>
                </td>
              </tr>

              <tr v-if="!orders.items || !orders.items.length">
                <td colspan="6" class="py-12 text-center text-slate-500 text-xs">
                  No orders found. When customers place orders on your storefront or mobile app, they will appear here.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
