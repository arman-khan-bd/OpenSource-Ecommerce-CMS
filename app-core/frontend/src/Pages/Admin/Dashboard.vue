<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  FileText,
  ShoppingBag,
  ShoppingCart,
  DollarSign,
  TrendingUp,
  Clock,
  Smartphone,
  Server,
  Zap,
  CheckCircle2,
  ExternalLink,
  Plus
} from 'lucide-vue-next';

const props = defineProps({
  stats: Object,
  recentPosts: Array,
  recentOrders: Array,
  recentProducts: Array,
  systemInfo: Object,
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Welcome Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-6 rounded-2xl bg-gradient-to-r from-indigo-900/60 via-purple-900/40 to-slate-900 border border-indigo-500/20 shadow-xl backdrop-blur-md">
        <div>
          <h1 class="text-2xl font-bold font-sans text-white tracking-tight">Admin Overview & Metrics</h1>
          <p class="text-xs text-indigo-200 mt-1">Real-time status for storefront, e-commerce, and Android SDUI mobile app.</p>
        </div>
        <div class="flex items-center space-x-3">
          <Link href="/admin/posts/new" class="px-3.5 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition-all">
            <Plus class="w-3.5 h-3.5" />
            <span>New Post</span>
          </Link>
          <Link href="/admin/products/new" class="px-3.5 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-700 text-xs font-semibold flex items-center space-x-1.5 transition-all">
            <Plus class="w-3.5 h-3.5" />
            <span>New Product</span>
          </Link>
          <Link href="/admin/sdui" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
            <Smartphone class="w-3.5 h-3.5" />
            <span>SDUI Studio</span>
          </Link>
        </div>
      </div>

      <!-- Stats Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Sales -->
        <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 flex items-center justify-between shadow-lg">
          <div>
            <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Sales</span>
            <h3 class="text-2xl font-extrabold text-white font-sans mt-1">${{ (stats.total_sales || 0).toLocaleString() }}</h3>
            <span class="text-[11px] text-emerald-400 font-medium flex items-center mt-1">
              <TrendingUp class="w-3 h-3 mr-1" />
              <span>Active Revenue</span>
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <DollarSign class="w-6 h-6" />
          </div>
        </div>

        <!-- Orders -->
        <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 flex items-center justify-between shadow-lg">
          <div>
            <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Total Orders</span>
            <h3 class="text-2xl font-extrabold text-white font-sans mt-1">{{ stats.total_orders || 0 }}</h3>
            <span class="text-[11px] text-amber-400 font-medium flex items-center mt-1">
              <Clock class="w-3 h-3 mr-1" />
              <span>{{ stats.pending_orders || 0 }} Pending</span>
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <ShoppingCart class="w-6 h-6" />
          </div>
        </div>

        <!-- Products -->
        <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 flex items-center justify-between shadow-lg">
          <div>
            <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Live Products</span>
            <h3 class="text-2xl font-extrabold text-white font-sans mt-1">{{ stats.total_products || 0 }}</h3>
            <span class="text-[11px] text-indigo-400 font-medium flex items-center mt-1">
              <ShoppingBag class="w-3 h-3 mr-1" />
              <span>Storefront Catalog</span>
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
            <ShoppingBag class="w-6 h-6" />
          </div>
        </div>

        <!-- Posts -->
        <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 flex items-center justify-between shadow-lg">
          <div>
            <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Journal Posts</span>
            <h3 class="text-2xl font-extrabold text-white font-sans mt-1">{{ stats.total_posts || 0 }}</h3>
            <span class="text-[11px] text-purple-400 font-medium flex items-center mt-1">
              <FileText class="w-3 h-3 mr-1" />
              <span>Published Content</span>
            </span>
          </div>
          <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
            <FileText class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders (2 Columns) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
              <div>
                <h3 class="text-base font-bold text-white">Recent Orders</h3>
                <p class="text-xs text-slate-400">Real-time incoming orders from storefront and mobile app</p>
              </div>
              <Link href="/admin/orders" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                View all orders &rarr;
              </Link>
            </div>

            <div class="divide-y divide-slate-800/80">
              <div v-for="order in recentOrders" :key="order.id" class="py-3.5 flex items-center justify-between text-xs">
                <div>
                  <span class="font-mono font-bold text-white">{{ order.order_number }}</span>
                  <span class="text-slate-400 ml-2">{{ order.customer_name }}</span>
                </div>
                <div class="flex items-center space-x-3">
                  <span class="font-bold text-white">${{ (order.total_amount || 0).toFixed(2) }}</span>
                  <span :class="[
                    order.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' :
                    order.status === 'processing' ? 'bg-indigo-500/20 text-indigo-400' :
                    order.status === 'pending' ? 'bg-amber-500/20 text-amber-400' : 'bg-rose-500/20 text-rose-400',
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider'
                  ]">
                    {{ order.status }}
                  </span>
                  <Link :href="`/admin/orders/${order.id}`" class="p-1 rounded text-slate-400 hover:text-white">
                    <ExternalLink class="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>

              <div v-if="!recentOrders || !recentOrders.length" class="py-8 text-center text-xs text-slate-500">
                No orders placed yet. Orders made via storefront or SDUI mobile app will display here.
              </div>
            </div>
          </div>

          <!-- Recent Posts -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
              <div>
                <h3 class="text-base font-bold text-white">Recent Posts</h3>
                <p class="text-xs text-slate-400">Articles published on the server-rendered Twig storefront</p>
              </div>
              <Link href="/admin/posts" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
                Manage posts &rarr;
              </Link>
            </div>

            <div class="divide-y divide-slate-800/80">
              <div v-for="post in recentPosts" :key="post.id" class="py-3.5 flex items-center justify-between text-xs">
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-lg bg-slate-800 overflow-hidden shrink-0">
                    <img :src="post.featured_image || 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=100&q=80'"
                         alt="" class="w-full h-full object-cover" />
                  </div>
                  <div>
                    <h4 class="font-semibold text-white truncate max-w-xs">{{ post.title }}</h4>
                    <span class="text-[10px] text-slate-400">{{ post.published_at ? post.published_at.slice(0, 10) : 'Draft' }}</span>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <span :class="post.status === 'published' ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-400 bg-slate-800'"
                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    {{ post.status }}
                  </span>
                  <Link :href="`/admin/posts/${post.id}/edit`" class="text-indigo-400 hover:text-indigo-300 font-medium">Edit</Link>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: System Diagnostics & Health -->
        <div class="space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
              <Server class="w-4 h-4 text-indigo-400" />
              <span>System Diagnostics</span>
            </h3>

            <div class="space-y-2.5 text-xs">
              <div class="flex justify-between py-1.5 border-b border-slate-800/80">
                <span class="text-slate-400">PHP Version</span>
                <span class="font-bold text-white font-mono">{{ systemInfo.php_version }}</span>
              </div>
              <div class="flex justify-between py-1.5 border-b border-slate-800/80">
                <span class="text-slate-400">Database Driver</span>
                <span class="font-bold text-white font-mono uppercase">{{ systemInfo.db_driver }}</span>
              </div>
              <div class="flex justify-between py-1.5 border-b border-slate-800/80">
                <span class="text-slate-400">Cache Active Driver</span>
                <span :class="systemInfo.cache_driver === 'redis' ? 'text-emerald-400' : 'text-sky-400'" class="font-bold uppercase">
                  {{ systemInfo.cache_driver }}
                </span>
              </div>
              <div class="flex justify-between py-1.5 border-b border-slate-800/80">
                <span class="text-slate-400">OPcache</span>
                <span :class="systemInfo.opcache_enabled ? 'text-emerald-400' : 'text-slate-400'" class="font-bold">
                  {{ systemInfo.opcache_enabled ? 'Enabled' : 'Disabled' }}
                </span>
              </div>
              <div class="flex justify-between py-1.5 border-b border-slate-800/80">
                <span class="text-slate-400">Memory Limit</span>
                <span class="font-bold text-white font-mono">{{ systemInfo.memory_limit }}</span>
              </div>
              <div class="flex justify-between py-1.5">
                <span class="text-slate-400">Server Platform</span>
                <span class="font-bold text-slate-300 truncate max-w-[150px]">{{ systemInfo.server_software }}</span>
              </div>
            </div>

            <div class="pt-2">
              <Link href="/admin/settings" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center justify-center space-x-2 transition-all">
                <Zap class="w-3.5 h-3.5 text-indigo-400" />
                <span>Configure Cache & Performance</span>
              </Link>
            </div>
          </div>

          <!-- Android SDUI Banner Callout -->
          <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 shadow-xl space-y-3">
            <div class="flex items-center space-x-2 text-indigo-400">
              <Smartphone class="w-5 h-5" />
              <span class="font-bold text-xs uppercase tracking-wider">Mobile App Builder</span>
            </div>
            <h4 class="text-base font-bold text-white leading-tight">Server-Driven UI Studio</h4>
            <p class="text-xs text-indigo-200 leading-relaxed">
              Dynamically design native Android Compose layouts in your browser with real-time phone preview.
            </p>
            <div class="pt-1">
              <Link href="/admin/sdui" class="inline-flex items-center space-x-1.5 text-xs font-bold text-indigo-300 hover:text-white">
                <span>Launch Visual Studio</span>
                <ExternalLink class="w-3.5 h-3.5" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
