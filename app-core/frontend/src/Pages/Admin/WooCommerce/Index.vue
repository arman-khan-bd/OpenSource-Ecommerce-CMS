<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  ShoppingBag,
  Tag,
  DollarSign,
  TrendingUp,
  Percent,
  Plus,
  Trash2,
  Settings,
  Package,
  ShieldCheck,
  CheckCircle,
  Truck,
  ExternalLink,
  X
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
  recentOrders: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ totalOrders: 0, totalRevenue: 0, activeCouponsCount: 0, freeShippingThreshold: 100 }),
  },
});

const activeTab = ref('coupons'); // 'coupons' | 'settings' | 'orders'
const showCouponModal = ref(false);
const newCoupon = ref({
  code: '',
  type: 'percent',
  amount: 15,
  min_spend: 0,
  description: '',
});

const storeForm = ref({
  currency_symbol: props.settings.currency_symbol || '$',
  currency_code: props.settings.currency_code || 'USD',
  free_shipping_threshold: props.settings.free_shipping_threshold || 100,
  stock_urgency_threshold: props.settings.stock_urgency_threshold || 5,
  enable_coupons: props.settings.enable_coupons !== false,
  enable_free_shipping_meter: props.settings.enable_free_shipping_meter !== false,
  enable_trust_badges: props.settings.enable_trust_badges !== false,
});

function openAddCouponModal() {
  newCoupon.value = { code: '', type: 'percent', amount: 15, min_spend: 0, description: '' };
  showCouponModal.value = true;
}

function saveCoupon() {
  if (!newCoupon.value.code) return;
  router.post('/admin/woocommerce/coupons', newCoupon.value, {
    preserveScroll: true,
    onSuccess: () => {
      showCouponModal.value = false;
    },
  });
}

function deleteCoupon(code) {
  if (confirm(`Are you sure you want to delete coupon "${code}"?`)) {
    router.delete(`/admin/woocommerce/coupons/${code}`, {
      preserveScroll: true,
    });
  }
}

function saveStoreSettings() {
  router.post('/admin/woocommerce/settings', storeForm.value, {
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
          <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center shadow-lg shadow-purple-500/10">
            <ShoppingBag class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-2xl font-bold text-white tracking-tight">WooCommerce Commerce Pro</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-mono text-xs font-bold">PRO</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Smart coupons, checkout discounts, free shipping rules, and conversion badging</p>
          </div>
        </div>

        <div class="flex items-center space-x-2 bg-slate-900 p-1 rounded-xl border border-slate-800 self-start">
          <button
            @click="activeTab = 'coupons'"
            :class="[
              activeTab === 'coupons' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5'
            ]"
          >
            <Tag class="w-3.5 h-3.5" />
            <span>Coupons ({{ (settings.coupons || []).length }})</span>
          </button>
          <button
            @click="activeTab = 'settings'"
            :class="[
              activeTab === 'settings' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5'
            ]"
          >
            <Settings class="w-3.5 h-3.5" />
            <span>Store Rules</span>
          </button>
          <button
            @click="activeTab = 'orders'"
            :class="[
              activeTab === 'orders' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-400 hover:text-white',
              'px-4 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5'
            ]"
          >
            <TrendingUp class="w-3.5 h-3.5" />
            <span>Orders Telemetry</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Total Sales Volume</span>
          <div class="text-2xl font-extrabold text-white font-display">
            {{ settings.currency_symbol || '$' }}{{ stats.totalRevenue.toFixed(2) }}
          </div>
          <span class="text-[11px] text-emerald-400 font-medium">Real-time storefront checkout</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Total Orders</span>
          <div class="text-2xl font-extrabold text-indigo-400 font-display">
            {{ stats.totalOrders }}
          </div>
          <span class="text-[11px] text-slate-400 font-medium">Processed via WooCommerce</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Active Coupons</span>
          <div class="text-2xl font-extrabold text-purple-400 font-display">
            {{ (settings.coupons || []).length }} Active
          </div>
          <span class="text-[11px] text-purple-300 font-medium">Instant discount rules</span>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1 shadow-lg">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Free Shipping Unlock</span>
          <div class="text-2xl font-extrabold text-emerald-400 font-display">
            {{ settings.currency_symbol || '$' }}{{ stats.freeShippingThreshold }}
          </div>
          <span class="text-[11px] text-emerald-300 font-medium">Interactive cart meter</span>
        </div>
      </div>

      <!-- Tab 1: Coupons Manager -->
      <div v-if="activeTab === 'coupons'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white">Promotional Coupons & Rebate Codes</h2>
            <p class="text-xs text-slate-400">Coupons apply at checkout and inside the storefront slide-out cart drawer</p>
          </div>
          <button
            @click="openAddCouponModal"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center space-x-1.5 transition-all shadow-md shadow-purple-600/30"
          >
            <Plus class="w-4 h-4" />
            <span>Add Coupon</span>
          </button>
        </div>

        <!-- Coupons Table -->
        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
              <tr>
                <th class="py-3 px-4">Coupon Code</th>
                <th class="py-3 px-4">Type</th>
                <th class="py-3 px-4">Discount</th>
                <th class="py-3 px-4">Min. Spend</th>
                <th class="py-3 px-4">Description</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr v-for="coupon in settings.coupons || []" :key="coupon.code" class="hover:bg-slate-800/30">
                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-1 rounded-lg bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30">
                    {{ coupon.code }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-sans capitalize text-slate-300">
                  {{ coupon.type === 'percent' ? 'Percentage' : 'Fixed Rebate' }}
                </td>
                <td class="py-3.5 px-4 font-bold text-emerald-400">
                  {{ coupon.type === 'percent' ? `${coupon.amount}% OFF` : `${settings.currency_symbol || '$'}${coupon.amount} OFF` }}
                </td>
                <td class="py-3.5 px-4 text-slate-400">
                  {{ coupon.min_spend ? `${settings.currency_symbol || '$'}${coupon.min_spend}` : 'No minimum' }}
                </td>
                <td class="py-3.5 px-4 font-sans text-slate-300 max-w-xs truncate">
                  {{ coupon.description || 'Promotional Discount' }}
                </td>
                <td class="py-3.5 px-4 text-right font-sans">
                  <button
                    @click="deleteCoupon(coupon.code)"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-950/30 transition-colors"
                    title="Delete Coupon"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!(settings.coupons || []).length">
                <td colspan="6" class="text-center py-8 text-slate-500">
                  No coupons configured. Click "Add Coupon" above.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2: Store Rules & Configuration -->
      <div v-else-if="activeTab === 'settings'" class="bg-slate-900/80 rounded-2xl border border-slate-800 p-6 shadow-xl max-w-2xl space-y-6">
        <div class="border-b border-slate-800 pb-3">
          <h2 class="text-base font-bold text-white">eCommerce Store Rules & Conversion Badging</h2>
          <p class="text-xs text-slate-400">Configure global currency, free delivery targets, and checkout trust seals</p>
        </div>

        <form @submit.prevent="saveStoreSettings" class="space-y-5 text-xs">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Currency Symbol</label>
              <input v-model="storeForm.currency_symbol" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Currency Code (ISO)</label>
              <input v-model="storeForm.currency_code" type="text" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Free Shipping Threshold ($)</label>
              <input v-model.number="storeForm.free_shipping_threshold" type="number" step="1" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
              <p class="text-[11px] text-slate-500 mt-1">Orders exceeding this amount unlock free delivery meter in cart.</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Stock Urgency Alert Threshold</label>
              <input v-model.number="storeForm.stock_urgency_threshold" type="number" step="1" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
              <p class="text-[11px] text-slate-500 mt-1">Show "🔥 Only X Left!" when stock is at or below this count.</p>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-800 space-y-3">
            <label class="flex items-center space-x-3 cursor-pointer">
              <input v-model="storeForm.enable_coupons" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-purple-600 focus:ring-0" />
              <span class="text-slate-200 font-semibold">Enable Coupon Code entry on storefront and checkout</span>
            </label>

            <label class="flex items-center space-x-3 cursor-pointer">
              <input v-model="storeForm.enable_free_shipping_meter" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-purple-600 focus:ring-0" />
              <span class="text-slate-200 font-semibold">Enable Dynamic Free Shipping Progress Meter in cart drawer</span>
            </label>

            <label class="flex items-center space-x-3 cursor-pointer">
              <input v-model="storeForm.enable_trust_badges" type="checkbox" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-purple-600 focus:ring-0" />
              <span class="text-slate-200 font-semibold">Enable 256-bit SSL, Money-Back & Warranty trust badges</span>
            </label>
          </div>

          <div class="pt-4 border-t border-slate-800">
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition-all">
              Save Store Rules
            </button>
          </div>
        </form>
      </div>

      <!-- Tab 3: Orders Telemetry -->
      <div v-else class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white">Live Storefront Orders & Telemetry</h2>
            <p class="text-xs text-slate-400">Synchronized across Web Storefront, Android Mobile SDUI, and PWA apps</p>
          </div>
          <Link href="/admin/orders" class="text-xs text-indigo-400 hover:underline flex items-center space-x-1">
            <span>View All Orders in Orders Manager</span>
            <ExternalLink class="w-3.5 h-3.5" />
          </Link>
        </div>

        <div class="bg-slate-900/80 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-slate-400 font-semibold uppercase text-[11px]">
              <tr>
                <th class="py-3 px-4">Order #</th>
                <th class="py-3 px-4">Customer</th>
                <th class="py-3 px-4">Items</th>
                <th class="py-3 px-4">Total Amount</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-[11px]">
              <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-800/30">
                <td class="py-3.5 px-4 font-bold text-indigo-300">{{ order.order_number }}</td>
                <td class="py-3.5 px-4 font-sans">
                  <div class="font-bold text-white">{{ order.customer_name }}</div>
                  <div class="text-[10px] text-slate-400">{{ order.customer_email }}</div>
                </td>
                <td class="py-3.5 px-4 text-slate-300 font-sans">{{ (order.items || []).length }} items</td>
                <td class="py-3.5 px-4 font-bold text-emerald-400">${{ parseFloat(order.total_amount || 0).toFixed(2) }}</td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 uppercase">
                    {{ order.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right text-slate-500 font-sans">{{ order.created_at || 'Just now' }}</td>
              </tr>
              <tr v-if="!recentOrders.length">
                <td colspan="6" class="text-center py-8 text-slate-500">No orders placed yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Coupon Modal -->
    <div v-if="showCouponModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center space-x-2">
            <Tag class="w-5 h-5 text-purple-400" />
            <h3 class="text-base font-bold text-white">Create New Coupon</h3>
          </div>
          <button @click="showCouponModal = false" class="text-slate-400 hover:text-white p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveCoupon" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">Coupon Code (Uppercase)</label>
            <input v-model="newCoupon.code" type="text" required placeholder="e.g. FLASH30" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase tracking-wider" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Discount Type</label>
              <select v-model="newCoupon.type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white">
                <option value="percent">Percentage (% OFF)</option>
                <option value="fixed">Fixed Rebate ($ OFF)</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-300 mb-1">Discount Amount</label>
              <input v-model.number="newCoupon.amount" type="number" step="0.5" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Minimum Spend Requirement ($)</label>
            <input v-model.number="newCoupon.min_spend" type="number" step="1" placeholder="0 for no minimum" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono" />
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">Public Description / Tagline</label>
            <input v-model="newCoupon.description" type="text" placeholder="e.g. Flash 30% OFF Weekend Deal" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white" />
          </div>

          <div class="pt-4 border-t border-slate-800 flex justify-end space-x-2">
            <button @click="showCouponModal = false" type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold">
              Cancel
            </button>
            <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md shadow-purple-600/30">
              Save Coupon
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
