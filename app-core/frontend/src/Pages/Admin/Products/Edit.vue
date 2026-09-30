<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowLeft, Save, ShoppingBag, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  product: Object,
  isNew: Boolean,
});

const form = useForm({
  id: props.product.id,
  title: props.product.title || '',
  slug: props.product.slug || '',
  description: props.product.description || '',
  price: props.product.price || 0.0,
  sale_price: props.product.sale_price || null,
  sku: props.product.sku || '',
  stock: props.product.stock || 0,
  status: props.product.status || 'active',
  images: props.product.images || [''],
  meta: props.product.meta || { category: 'General', attributes: {} },
});

function autoGenerateSlug() {
  if (!form.id || !form.slug) {
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }
}

function addImage() {
  form.images.push('');
}

function removeImage(index) {
  form.images.splice(index, 1);
}

function submit() {
  form.post('/admin/products/save');
}
</script>

<template>
  <AdminLayout>
    <form @submit.prevent="submit" class="space-y-6">
      <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <Link href="/admin/products" class="p-2 rounded-xl bg-slate-900 text-slate-400 hover:text-white border border-slate-800 transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <h1 class="text-xl font-bold text-white tracking-tight">
              {{ isNew ? 'Add New Product' : 'Edit ' + form.title }}
            </h1>
            <span class="text-xs text-slate-400">Synchronized with mobile Android SDUI catalog</span>
          </div>
        </div>

        <button type="submit" :disabled="form.processing"
                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
          <Save class="w-3.5 h-3.5" />
          <span>{{ form.processing ? 'Saving...' : 'Save Product' }}</span>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">Product Title</label>
              <input v-model="form.title" @input="autoGenerateSlug" type="text" required placeholder="e.g. Wireless Pro Headset"
                     class="w-full px-4 py-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-sm font-bold text-white focus:outline-none focus:border-indigo-500" />
            </div>

            <div class="flex items-center space-x-2 text-xs text-slate-400">
              <span class="shrink-0 font-medium">Slug: /product/</span>
              <input v-model="form.slug" type="text" required
                     class="flex-1 px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-slate-300 font-mono text-xs focus:outline-none focus:border-indigo-500" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description</label>
              <textarea v-model="form.description" rows="6" placeholder="Describe features, specifications, and warranty..."
                        class="w-full p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 leading-relaxed focus:outline-none focus:border-indigo-500"></textarea>
            </div>
          </div>

          <!-- Pricing & Inventory -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white">Pricing & Stock Inventory</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Regular Price ($)</label>
                <input v-model.number="form.price" type="number" step="0.01" required
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Sale Price ($)</label>
                <input v-model.number="form.sale_price" type="number" step="0.01" placeholder="Optional"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">SKU</label>
                <input v-model="form.sku" type="text" placeholder="e.g. AUD-01"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-400 mb-1">Stock Units</label>
                <input v-model.number="form.stock" type="number" required
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white">Status & Category</h3>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Visibility</label>
              <select v-model="form.status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="active">Active (Visible)</option>
                <option value="draft">Draft (Hidden)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Category</label>
              <input v-model="form.meta.category" type="text" placeholder="e.g. Audio, Peripherals"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <!-- Product Media URLs -->
          <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white">Product Images</h3>
              <button type="button" @click="addImage" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center space-x-1">
                <Plus class="w-3.5 h-3.5" />
                <span>Add Image</span>
              </button>
            </div>

            <div class="space-y-2">
              <div v-for="(img, idx) in form.images" :key="idx" class="flex items-center space-x-2">
                <input v-model="form.images[idx]" type="text" placeholder="Image URL or /uploads/..."
                       class="flex-1 px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                <button type="button" @click="removeImage(idx)" class="p-2 rounded-lg text-slate-500 hover:text-rose-400">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div v-if="form.images && form.images[0]" class="aspect-square w-full rounded-xl overflow-hidden bg-slate-950 border border-slate-800 mt-2">
              <img :src="form.images[0]" alt="" class="w-full h-full object-cover" />
            </div>
          </div>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>
