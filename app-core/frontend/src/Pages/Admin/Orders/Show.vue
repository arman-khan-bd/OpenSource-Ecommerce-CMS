<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeft, CheckCircle, Package, User, Clock, MapPin } from 'lucide-vue-next';

const props = defineProps({
  order: Object,
});

const form = useForm({
  status: props.order.status || 'pending',
  notes: props.order.notes || '',
});

function updateStatus() {
  form.post(`/admin/orders/${props.order.id}/status`);
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6 max-w-4xl mx-auto">
      <div class="flex items-center justify-between pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <Link href="/admin/orders" class="p-2 rounded-xl bg-slate-900 text-slate-400 hover:text-white border border-slate-800 transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <h1 class="text-xl font-bold text-white font-mono">Invoice {{ order.order_number }}</h1>
            <span class="text-xs text-slate-400">Placed on {{ order.created_at }}</span>
          </div>
        </div>

        <!-- Status Selector -->
        <div class="flex items-center space-x-2">
          <select v-model="form.status" @change="updateStatus"
                  class="px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs font-semibold text-white focus:outline-none focus:border-indigo-500">
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer & Shipping -->
        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2">
            <User class="w-4 h-4 text-indigo-400" />
            <span>Customer Details</span>
          </h3>
          <div class="text-xs space-y-1">
            <p class="font-bold text-white">{{ order.customer_name }}</p>
            <p class="text-slate-400">{{ order.customer_email }}</p>
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2">
            <MapPin class="w-4 h-4 text-indigo-400" />
            <span>Shipping Address</span>
          </h3>
          <div class="text-xs text-slate-300 space-y-1">
            <p>{{ order.shipping_address?.street || 'Standard Delivery' }}</p>
            <p>{{ order.shipping_address?.city || 'Default City' }}, {{ order.shipping_address?.country || 'USA' }}</p>
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2">
            <Package class="w-4 h-4 text-indigo-400" />
            <span>Order Summary</span>
          </h3>
          <div class="text-xs space-y-1">
            <div class="flex justify-between text-slate-400">
              <span>Status:</span>
              <span class="font-bold uppercase text-indigo-300">{{ order.status }}</span>
            </div>
            <div class="flex justify-between text-base font-bold text-white pt-2 border-t border-slate-800">
              <span>Total:</span>
              <span>${{ (order.total_amount || 0).toFixed(2) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Line Items -->
      <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white">Line Items ({{ order.items ? order.items.length : 0 }})</h3>
        <div class="divide-y divide-slate-800/80">
          <div v-for="(item, idx) in order.items" :key="idx" class="py-3 flex items-center justify-between text-xs">
            <div>
              <p class="font-bold text-white">{{ item.title || 'Item #' + (idx+1) }}</p>
              <span class="text-slate-500 font-mono text-[11px]">Qty: {{ item.quantity || 1 }}</span>
            </div>
            <span class="font-bold text-white">${{ ((item.price || 0) * (item.quantity || 1)).toFixed(2) }}</span>
          </div>

          <div v-if="!order.items || !order.items.length" class="py-4 text-slate-500 text-xs">
            Standard order package.
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
