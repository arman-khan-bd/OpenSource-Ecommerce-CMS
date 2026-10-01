<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';

const pageState = usePage();
const activePlugins = computed(() => pageState.props.activePlugins || []);
const isYoastSeoActive = computed(() => activePlugins.value.includes('yoast-seo-pro') || activePlugins.value.includes('seo-toolkit'));
import {
  ArrowLeft, Save, Eye, Sparkles, Image as ImageIcon,
  Tag as TagIcon, Hash, Plus, X, Check, Lock, Unlock,
  RefreshCw, Globe, Search, Share2, Smartphone, Monitor,
  AlertCircle, CheckCircle2, Copy, Calendar, Clock,
  FolderPlus, Folder, BookOpen, Layers, CheckSquare,
  ExternalLink, UploadCloud, Trash2, DollarSign, Percent,
  Package, Truck, Sliders, Barcode, ShieldCheck
} from 'lucide-vue-next';

const props = defineProps({
  product: Object,
  categories: {
    type: Array,
    default: () => [
      'Headphones & Audio',
      'Keyboards & Peripherals',
      'Laptops & Computers',
      'Accessories',
      'Gaming Gear',
      'Smart Devices'
    ],
  },
  isNew: Boolean,
});

const defaultMeta = {
  categories: [props.categories[0] || 'Headphones & Audio'],
  tags: ['featured'],
  cost_price: null,
  barcode: '',
  stock_status: 'instock',
  track_stock: true,
  low_stock_threshold: 3,
  weight: '',
  dimensions: { length: '', width: '', height: '' },
  is_virtual: false,
  free_shipping: false,
  attributes: [
    { name: 'Brand', value: 'OpenSource Gear' },
    { name: 'Warranty', value: '1 Year Official' },
  ],
  focus_keyword: '',
  seo_title: '',
  seo_description: '',
  canonical_url: '',
  og_title: '',
  og_description: '',
  og_image: '',
  robots_index: 'index',
  robots_follow: 'follow',
};

const form = useForm({
  id: props.product?.id || null,
  title: props.product?.title || '',
  slug: props.product?.slug || '',
  description: props.product?.description || '',
  price: props.product?.price ? parseFloat(props.product.price) : 0.0,
  sale_price: props.product?.sale_price ? parseFloat(props.product.sale_price) : null,
  sku: props.product?.sku || '',
  stock: props.product?.stock !== undefined ? parseInt(props.product.stock) : 15,
  status: props.product?.status || 'active',
  images: Array.isArray(props.product?.images) ? props.product.images : [],
  meta: {
    ...defaultMeta,
    ...(props.product?.meta || {}),
    categories: Array.isArray(props.product?.meta?.categories)
      ? props.product.meta.categories
      : (props.product?.meta?.category ? [props.product.meta.category] : [props.categories[0] || 'Headphones & Audio']),
    tags: Array.isArray(props.product?.meta?.tags) ? props.product.meta.tags : ['featured'],
    attributes: Array.isArray(props.product?.meta?.attributes)
      ? props.product.meta.attributes
      : (typeof props.product?.meta?.attributes === 'object' && props.product?.meta?.attributes !== null
          ? Object.entries(props.product.meta.attributes).map(([name, value]) => ({ name, value }))
          : defaultMeta.attributes),
  },
});

// Category State
const categoryList = ref([...props.categories]);
const categorySearch = ref('');
const showNewCategoryModal = ref(false);
const newCategoryInput = ref('');
const isCreatingCategory = ref(false);
const categoryMessage = ref('');

const filteredCategories = computed(() => {
  if (!categorySearch.value) return categoryList.value;
  return categoryList.value.filter(c => c.toLowerCase().includes(categorySearch.value.toLowerCase()));
});

function toggleCategory(cat) {
  const index = form.meta.categories.indexOf(cat);
  if (index > -1) {
    form.meta.categories.splice(index, 1);
  } else {
    form.meta.categories.push(cat);
  }
}

async function createNewCategory() {
  const name = newCategoryInput.value.trim();
  if (!name) return;

  isCreatingCategory.value = true;
  categoryMessage.value = '';

  try {
    const res = await fetch('/admin/products/categories', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name }),
    });
    const data = await res.json();
    if (data.success) {
      if (!categoryList.value.includes(data.created)) {
        categoryList.value.push(data.created);
      }
      if (!form.meta.categories.includes(data.created)) {
        form.meta.categories.push(data.created);
      }
      newCategoryInput.value = '';
      showNewCategoryModal.value = false;
      categoryMessage.value = `Category "${data.created}" created and selected!`;
      setTimeout(() => categoryMessage.value = '', 4000);
    }
  } catch (e) {
    categoryMessage.value = 'Failed to create category.';
  } finally {
    isCreatingCategory.value = false;
  }
}

// Auto Slug Generation & Collision Detection
const isSlugLocked = ref(props.isNew);
const isCheckingSlug = ref(false);
const slugCollisionNotice = ref('');
let slugCheckTimeout = null;

function slugify(text) {
  return text
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9_\-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function handleTitleInput() {
  if (isSlugLocked.value) {
    const candidate = slugify(form.title);
    form.slug = candidate;
    checkSlugCollision(candidate);
  }
}

function unlockSlug() {
  isSlugLocked.value = !isSlugLocked.value;
}

function regenerateSlug() {
  const candidate = slugify(form.title);
  form.slug = candidate;
  checkSlugCollision(candidate);
}

function checkSlugCollision(slug) {
  if (!slug) return;
  clearTimeout(slugCheckTimeout);
  slugCheckTimeout = setTimeout(async () => {
    isCheckingSlug.value = true;
    try {
      const res = await fetch(`/admin/products/check-slug?slug=${encodeURIComponent(slug)}&id=${form.id || ''}`);
      const data = await res.json();
      if (data.success && data.slug) {
        if (data.is_collision) {
          slugCollisionNotice.value = `Slug in use: auto-resolved to "${data.slug}"`;
          form.slug = data.slug;
        } else {
          slugCollisionNotice.value = '';
        }
      }
    } catch (e) {
      // ignore
    } finally {
      isCheckingSlug.value = false;
    }
  }, 400);
}

// Pricing & Profit Margin Computations
const discountPercentage = computed(() => {
  if (!form.sale_price || form.sale_price <= 0 || !form.price || form.price <= 0) return null;
  if (form.sale_price >= form.price) return null;
  const saving = form.price - form.sale_price;
  const pct = Math.round((saving / form.price) * 100);
  return { pct, saving: saving.toFixed(2) };
});

const profitMargin = computed(() => {
  const sellingPrice = (form.sale_price && form.sale_price > 0) ? form.sale_price : form.price;
  const cost = parseFloat(form.meta.cost_price);
  if (!sellingPrice || isNaN(cost) || cost <= 0) return null;

  const profit = sellingPrice - cost;
  const marginPct = ((profit / sellingPrice) * 100).toFixed(1);
  return { profit: profit.toFixed(2), marginPct };
});

function generateSku() {
  const prefix = form.title
    ? form.title.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, 'PRD')
    : 'PRD';
  const num = Math.floor(1000 + Math.random() * 9000);
  form.sku = `${prefix}-${num}`;
}

// Image Gallery System
const fileCoverInputRef = ref(null);
const fileGalleryInputRef = ref(null);
const isUploadingCover = ref(false);
const isUploadingGallery = ref(false);
const coverUploadError = ref('');
const galleryUploadError = ref('');
const addImageUrlInput = ref('');
const showAddUrlModal = ref(false);

async function handleCoverUpload(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  isUploadingCover.value = true;
  coverUploadError.value = '';

  const formData = new FormData();
  formData.append('image', file);
  formData.append('alt_text', form.title || file.name);

  try {
    const res = await fetch('/admin/products/upload-image', {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();
    if (data.success && data.url) {
      if (form.images.length === 0) {
        form.images.push(data.url);
      } else {
        form.images[0] = data.url;
      }
      if (!form.meta.og_image) {
        form.meta.og_image = data.url;
      }
    } else {
      coverUploadError.value = data.message || 'Image upload failed';
    }
  } catch (err) {
    coverUploadError.value = 'Failed to upload cover image.';
  } finally {
    isUploadingCover.value = false;
  }
}

async function handleGalleryUpload(e) {
  const files = e.target.files;
  if (!files || files.length === 0) return;

  isUploadingGallery.value = true;
  galleryUploadError.value = '';

  try {
    for (let i = 0; i < files.length; i++) {
      const formData = new FormData();
      formData.append('image', files[i]);
      formData.append('alt_text', `${form.title || 'Product'} gallery image ${i + 1}`);

      const res = await fetch('/admin/products/upload-image', {
        method: 'POST',
        body: formData,
      });
      const data = await res.json();
      if (data.success && data.url) {
        form.images.push(data.url);
      }
    }
  } catch (err) {
    galleryUploadError.value = 'Failed to upload one or more gallery images.';
  } finally {
    isUploadingGallery.value = false;
  }
}

function removeGalleryImage(idx) {
  form.images.splice(idx, 1);
}

function setAsCoverImage(idx) {
  if (idx === 0) return;
  const img = form.images.splice(idx, 1)[0];
  form.images.unshift(img);
}

function addImageByUrl() {
  const url = addImageUrlInput.value.trim();
  if (url) {
    form.images.push(url);
    addImageUrlInput.value = '';
    showAddUrlModal.value = false;
  }
}

// Attributes System
const newAttr = ref({ name: '', value: '' });
const attributeSuggestions = ['Brand', 'Color', 'Size', 'Material', 'Warranty', 'Weight', 'Connectivity'];

function addAttribute(presetName = null) {
  const name = presetName || newAttr.value.name.trim();
  const value = presetName ? '' : newAttr.value.value.trim();

  if (name) {
    form.meta.attributes.push({ name, value });
    if (!presetName) {
      newAttr.value = { name: '', value: '' };
    }
  }
}

function removeAttribute(idx) {
  form.meta.attributes.splice(idx, 1);
}

// Tags System
const tagInput = ref('');
const suggestedTags = ['bestseller', 'new', 'featured', 'sale', 'trending', 'limited-edition', 'wireless'];

function addTag(tagText) {
  const clean = tagText.replace(/,/g, '').trim().toLowerCase();
  if (clean && !form.meta.tags.includes(clean)) {
    form.meta.tags.push(clean);
  }
}

function handleTagKeydown(e) {
  if (e.key === ',' || e.key === 'Enter') {
    e.preventDefault();
    if (tagInput.value.trim()) {
      addTag(tagInput.value);
      tagInput.value = '';
    }
  } else if (e.key === 'Backspace' && !tagInput.value && form.meta.tags.length > 0) {
    form.meta.tags.pop();
  }
}

function handleTagPaste(e) {
  const paste = e.clipboardData?.getData('text') || '';
  if (paste.includes(',')) {
    e.preventDefault();
    paste.split(',').forEach(t => addTag(t));
    tagInput.value = '';
  }
}

function removeTag(index) {
  form.meta.tags.splice(index, 1);
}

// SEO & SERP Preview
const activeSeoTab = ref('general'); // 'general', 'social', 'audit', 'robots'
const serpDevice = ref('desktop');

const productPermalink = computed(() => `/product/${form.slug || 'product-slug'}`);
const seoTitle = computed(() => form.meta.seo_title || form.title || 'Untitled Product');
const seoDescription = computed(() => form.meta.seo_description || form.description.replace(/<[^>]*>/g, ' ').substring(0, 160) || 'Buy this high-quality product online with fast shipping and warranty.');

const seoAudit = computed(() => {
  const kw = (form.meta.focus_keyword || '').toLowerCase().trim();
  const title = (form.title || '').toLowerCase();
  const sTitle = (form.meta.seo_title || '').toLowerCase();
  const slug = (form.slug || '').toLowerCase();
  const desc = (form.meta.seo_description || '').toLowerCase();
  const plainDesc = form.description.replace(/<[^>]*>/g, ' ').toLowerCase();

  const checks = [
    {
      id: 'kw_title',
      label: 'Focus keyword in SEO/Product Title',
      passed: Boolean(kw && (sTitle.includes(kw) || title.includes(kw))),
    },
    {
      id: 'kw_slug',
      label: 'Focus keyword in Product URL Slug',
      passed: Boolean(kw && slug.includes(slugify(kw))),
    },
    {
      id: 'kw_desc',
      label: 'Focus keyword in Meta Description',
      passed: Boolean(kw && desc.includes(kw)),
    },
    {
      id: 'has_images',
      label: 'Product has at least 1 image',
      passed: form.images.length > 0,
    },
    {
      id: 'has_gallery',
      label: 'Product has multiple gallery photos (>= 2)',
      passed: form.images.length >= 2,
    },
    {
      id: 'has_price',
      label: 'Valid pricing and stock inventory configured',
      passed: form.price > 0 && form.stock >= 0,
    },
    {
      id: 'has_sku',
      label: 'Unique SKU code assigned',
      passed: Boolean(form.sku && form.sku.trim()),
    },
  ];

  const total = checks.length;
  const passedCount = checks.filter(c => c.passed).length;
  const score = Math.round((passedCount / total) * 100);

  return { checks, score, passedCount, total };
});

const seoScoreBadge = computed(() => {
  const score = seoAudit.value.score;
  if (score >= 80) return { label: 'Good SEO', color: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20' };
  if (score >= 50) return { label: 'Needs Improvement', color: 'text-amber-400 bg-amber-500/10 border-amber-500/20' };
  return { label: 'Poor SEO', color: 'text-rose-400 bg-rose-500/10 border-rose-500/20' };
});

function submit(statusOverride = null) {
  if (statusOverride) {
    form.status = statusOverride;
  }
  form.post('/admin/products/save');
}
</script>

<template>
  <AdminLayout>
    <form @submit.prevent="submit()" class="space-y-6">
      <!-- Top Action Bar -->
      <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <Link href="/admin/products" class="p-2.5 rounded-xl bg-slate-900 text-slate-400 hover:text-white border border-slate-800 transition-colors shadow-sm">
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-xl font-extrabold text-white tracking-tight">
                {{ isNew ? 'Create New Product' : 'Edit Product: ' + form.title }}
              </h1>
              <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold border uppercase tracking-wider',
                             form.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20']">
                {{ form.status }}
              </span>
            </div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mt-0.5">
              <span>Catalog & Android SDUI Storefront</span>
              <span>•</span>
              <span class="text-indigo-400 font-mono">${{ (form.sale_price || form.price).toFixed(2) }} ({{ form.stock }} in stock)</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2.5">
          <a v-if="!isNew" :href="productPermalink" target="_blank"
             class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all">
            <ExternalLink class="w-3.5 h-3.5 text-indigo-400" />
            <span>Storefront View</span>
          </a>

          <button type="button" @click="submit('draft')" :disabled="form.processing"
                  class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold transition-all">
            Save as Draft
          </button>

          <button type="submit" :disabled="form.processing"
                  class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Saving...' : (isNew ? 'Publish Product' : 'Update Product') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Details, Pricing, Images, Attributes, SEO -->
        <div class="lg:col-span-2 space-y-6">
          <!-- 1. Title & Permalink Card -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                Product Title <span class="text-rose-500">*</span>
              </label>
              <input v-model="form.title" @input="handleTitleInput" type="text" required
                     placeholder="e.g. Sony WH-1000XM5 Wireless Noise Cancelling Headphones"
                     class="w-full px-4 py-3.5 rounded-xl bg-slate-950 border border-slate-800 text-lg font-bold text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors shadow-inner" />
            </div>

            <!-- Auto Slug Generator with Collision Detection -->
            <div class="pt-2 border-t border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400 font-medium">Product Permalink:</span>
                <div class="flex items-center space-x-2">
                  <span v-if="isCheckingSlug" class="text-[11px] text-indigo-400 animate-pulse flex items-center space-x-1">
                    <RefreshCw class="w-3 h-3 animate-spin" />
                    <span>Checking uniqueness...</span>
                  </span>
                  <button type="button" @click="regenerateSlug" title="Re-sync slug from title"
                          class="text-[11px] text-slate-400 hover:text-indigo-400 flex items-center space-x-1">
                    <RefreshCw class="w-3 h-3" />
                    <span>Auto Slug</span>
                  </button>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <div class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 font-mono select-none">
                  /product/
                </div>
                <div class="relative flex-1">
                  <input v-model="form.slug" @input="checkSlugCollision(form.slug)" type="text" required
                         :readonly="isSlugLocked"
                         :class="['w-full pl-3 pr-10 py-2 rounded-xl bg-slate-950 border text-xs font-mono transition-colors focus:outline-none',
                                  isSlugLocked ? 'text-slate-300 border-slate-800' : 'text-white border-indigo-500/80']" />
                  <button type="button" @click="unlockSlug" :title="isSlugLocked ? 'Click to edit custom slug' : 'Lock slug to auto-generate'"
                          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white p-1">
                    <Lock v-if="isSlugLocked" class="w-3.5 h-3.5 text-slate-400" />
                    <Unlock v-else class="w-3.5 h-3.5 text-indigo-400" />
                  </button>
                </div>
              </div>

              <div v-if="slugCollisionNotice" class="text-[11px] text-amber-400 font-mono flex items-center space-x-1 mt-1">
                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                <span>{{ slugCollisionNotice }}</span>
              </div>
              <p class="text-[11px] text-slate-500">
                Slugs are collision-protected. If taken, a numerical suffix like <span class="font-mono text-slate-400">product_1</span> is automatically assigned.
              </p>
            </div>
          </div>

          <!-- 2. Rich Text Product Description -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Product Description (Rich Text WYSIWYG & HTML)
              </label>
              <span class="text-[11px] text-indigo-400 font-medium">Rendered on storefront product page</span>
            </div>

            <RichTextEditor v-model="form.description"
                            upload-url="/admin/products/upload-image"
                            placeholder="Write comprehensive product description, technical specifications, warranty details, and box contents..." />
          </div>

          <!-- 3. Pricing, Sale & Profit Margin Card -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div class="flex items-center space-x-2">
                <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  <DollarSign class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-white">Pricing & Financials</h3>
                  <span class="text-xs text-slate-400">Set retail, promotional discounts, and internal profit margins</span>
                </div>
              </div>

              <!-- Live Discount Pill -->
              <div v-if="discountPercentage" class="px-3 py-1 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 text-xs font-bold flex items-center space-x-1">
                <Percent class="w-3 h-3" />
                <span>Save {{ discountPercentage.pct }}% (${{ discountPercentage.saving }} off)</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <!-- Regular Price -->
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Regular Price ($) <span class="text-rose-500">*</span></label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">$</span>
                  <input v-model.number="form.price" type="number" step="0.01" min="0" required
                         class="w-full pl-7 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm font-bold text-white focus:outline-none focus:border-indigo-500" />
                </div>
              </div>

              <!-- Sale Price -->
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Sale / Discount Price ($)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">$</span>
                  <input v-model.number="form.sale_price" type="number" step="0.01" min="0" placeholder="Optional promo price"
                         class="w-full pl-7 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm font-bold text-rose-400 placeholder-slate-600 focus:outline-none focus:border-indigo-500" />
                </div>
              </div>

              <!-- Cost per item -->
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Cost per item ($)</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">$</span>
                  <input v-model.number="form.meta.cost_price" type="number" step="0.01" min="0" placeholder="Internal cost"
                         class="w-full pl-7 pr-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs font-medium text-slate-300 placeholder-slate-600 focus:outline-none focus:border-indigo-500" />
                </div>
              </div>
            </div>

            <!-- Profit & Margin Calculation Metric -->
            <div v-if="profitMargin" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs">
              <div class="flex items-center space-x-2 text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Gross Profit: <strong class="text-white">${{ profitMargin.profit }}</strong> per unit</span>
              </div>
              <div class="flex items-center space-x-1.5 font-bold text-emerald-400">
                <span>Profit Margin: {{ profitMargin.marginPct }}%</span>
              </div>
            </div>
          </div>

          <!-- 4. Product Image Gallery System -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div class="flex items-center space-x-2">
                <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                  <ImageIcon class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-white">Product Image Gallery</h3>
                  <span class="text-xs text-slate-400">Main thumbnail and multi-angle photo showcase</span>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <button type="button" @click="showAddUrlModal = !showAddUrlModal"
                        class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 transition-colors">
                  + Add via URL
                </button>
              </div>
            </div>

            <!-- Upload Multiple Images Directly to public_html/uploads -->
            <div @click="$refs.fileGalleryInputRef.click()"
                 class="border-2 border-dashed border-slate-700 hover:border-indigo-500/70 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-950/60">
              <input ref="fileGalleryInputRef" type="file" accept="image/*" multiple class="hidden" @change="handleGalleryUpload" />
              <div class="flex flex-col items-center space-y-2">
                <UploadCloud class="w-8 h-8 text-indigo-400" />
                <div class="text-xs font-semibold text-white">Click or drag multiple photos to upload into Gallery</div>
                <div class="text-[11px] text-slate-500">Files are automatically saved directly to public_html/uploads</div>
              </div>
            </div>

            <div v-if="isUploadingGallery" class="text-xs text-center text-indigo-400 animate-pulse font-medium">
              Uploading images to public folder...
            </div>
            <div v-if="galleryUploadError" class="text-xs text-center text-rose-400 font-medium">
              {{ galleryUploadError }}
            </div>

            <!-- Add URL Inline Box -->
            <div v-if="showAddUrlModal" class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center space-x-2">
              <input v-model="addImageUrlInput" type="url" placeholder="https://... or /uploads/..."
                     class="flex-1 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500" />
              <button type="button" @click="addImageByUrl" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white">
                Add
              </button>
            </div>

            <!-- Gallery Thumbnails Grid -->
            <div v-if="form.images.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
              <div v-for="(img, idx) in form.images" :key="idx"
                   :class="['group relative rounded-xl overflow-hidden bg-slate-950 border transition-all',
                            idx === 0 ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-lg shadow-indigo-500/20' : 'border-slate-800 hover:border-slate-700']">
                <!-- Thumbnail -->
                <div class="aspect-square w-full overflow-hidden bg-slate-900">
                  <img :src="img" alt="Product photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                </div>

                <!-- Main Cover Badge -->
                <div v-if="idx === 0" class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-indigo-600 text-white font-extrabold text-[10px] uppercase tracking-wider shadow">
                  Main Cover
                </div>

                <!-- Overlay Actions -->
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-between p-2">
                  <div class="flex justify-end">
                    <button type="button" @click="removeGalleryImage(idx)" title="Remove image"
                            class="p-1 rounded-md bg-rose-600 text-white hover:bg-rose-500">
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                  <button v-if="idx !== 0" type="button" @click="setAsCoverImage(idx)"
                          class="w-full py-1 rounded bg-slate-900/90 hover:bg-indigo-600 text-[10px] font-bold text-white transition-colors">
                    Set as Cover
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-xs text-slate-500 italic text-center py-2">
              No images uploaded yet. Upload a main photo to showcase your product.
            </div>
          </div>

          <!-- 5. Attributes & Technical Specifications -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div class="flex items-center space-x-2">
                <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                  <Sliders class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-white">Product Attributes & Specs</h3>
                  <span class="text-xs text-slate-400">Specifications like Color, Size, Dimensions, Brand, Warranty</span>
                </div>
              </div>
            </div>

            <!-- Quick Add Attribute Suggestion Chips -->
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
              <span class="text-slate-500 text-[11px] font-medium mr-1">Quick Add:</span>
              <button v-for="preset in attributeSuggestions" :key="preset" type="button"
                      @click="addAttribute(preset)"
                      class="px-2.5 py-1 rounded-lg bg-slate-950 hover:bg-purple-950/60 border border-slate-800 text-[11px] text-slate-300 hover:text-purple-300 transition-colors">
                + {{ preset }}
              </button>
            </div>

            <!-- Attributes Table / List -->
            <div v-if="form.meta.attributes.length > 0" class="space-y-2">
              <div v-for="(attr, idx) in form.meta.attributes" :key="idx"
                   class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                <input v-model="attr.name" type="text" placeholder="Attribute Name (e.g. Color)"
                       class="w-1/3 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white font-semibold focus:outline-none focus:border-indigo-500" />
                <input v-model="attr.value" type="text" placeholder="Value (e.g. Space Gray, Silver)"
                       class="flex-1 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-indigo-500" />
                <button type="button" @click="removeAttribute(idx)" class="p-2 text-slate-500 hover:text-rose-400">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Custom Add Row -->
            <div class="flex items-center space-x-2 pt-1">
              <input v-model="newAttr.name" type="text" placeholder="Custom Attribute Name..."
                     class="w-1/3 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              <input v-model="newAttr.value" type="text" placeholder="Values (comma separated)..."
                     class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
              <button type="button" @click="addAttribute()" :disabled="!newAttr.name.trim()"
                      class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-xs font-bold text-white transition-all">
                Add
              </button>
            </div>
          </div>

          <!-- 6. Comprehensive eCommerce SEO Suite (WordPress Yoast SEO Style) -->
          <div v-if="isYoastSeoActive" class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-4">
              <div class="flex items-center space-x-2">
                <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                  <Globe class="w-4 h-4 text-emerald-400" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                    <span>Yoast SEO Pro Suite</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase">Active</span>
                  </h3>
                  <span class="text-xs text-slate-400">Google Shopping rich snippets, OpenGraph cards, and schema indexing</span>
                </div>
              </div>

              <!-- Live SEO Score -->
              <div :class="['px-3 py-1 rounded-xl border text-xs font-bold flex items-center space-x-1.5', seoScoreBadge.color]">
                <CheckCircle2 class="w-3.5 h-3.5" />
                <span>SEO Score: {{ seoAudit.score }}% ({{ seoScoreBadge.label }})</span>
              </div>
            </div>

            <!-- SEO Tabs Navigation -->
            <div class="flex border-b border-slate-800 space-x-6 text-xs font-semibold">
              <button type="button" @click="activeSeoTab = 'general'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'general' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <Search class="w-3.5 h-3.5" />
                <span>Google SERP Preview</span>
              </button>
              <button type="button" @click="activeSeoTab = 'social'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'social' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <Share2 class="w-3.5 h-3.5" />
                <span>Social Card (OG)</span>
              </button>
              <button type="button" @click="activeSeoTab = 'audit'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'audit' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <CheckSquare class="w-3.5 h-3.5" />
                <span>Product Audit ({{ seoAudit.passedCount }}/{{ seoAudit.total }})</span>
              </button>
              <button type="button" @click="activeSeoTab = 'robots'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'robots' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <Globe class="w-3.5 h-3.5" />
                <span>Robots & Canonical</span>
              </button>
            </div>

            <!-- Tab 1: General & SERP -->
            <div v-if="activeSeoTab === 'general'" class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Focus Keyphrase</label>
                <input v-model="form.meta.focus_keyword" type="text"
                       placeholder="e.g. noise cancelling wireless headphones, ergonomic mechanical keyboard..."
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
              </div>

              <!-- Google Shopping / SERP Snippet Preview -->
              <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-300 flex items-center space-x-1.5">
                    <Search class="w-3.5 h-3.5 text-blue-400" />
                    <span>Google Rich Product Snippet</span>
                  </span>
                  <div class="flex items-center space-x-1 bg-slate-900 rounded-lg p-0.5 border border-slate-800 text-[11px]">
                    <button type="button" @click="serpDevice = 'desktop'"
                            :class="['px-2 py-0.5 rounded flex items-center space-x-1',
                                     serpDevice === 'desktop' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']">
                      <Monitor class="w-3 h-3" />
                      <span>Desktop</span>
                    </button>
                    <button type="button" @click="serpDevice = 'mobile'"
                            :class="['px-2 py-0.5 rounded flex items-center space-x-1',
                                     serpDevice === 'mobile' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white']">
                      <Smartphone class="w-3 h-3" />
                      <span>Mobile</span>
                    </button>
                  </div>
                </div>

                <!-- Rich Snippet Box -->
                <div :class="['p-4 rounded-xl bg-white text-slate-800 shadow-sm space-y-1.5 font-sans',
                             serpDevice === 'mobile' ? 'max-w-sm mx-auto' : 'w-full']">
                  <div class="flex items-center space-x-1.5 text-xs text-slate-600">
                    <span class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center font-bold text-[9px] text-slate-700">G</span>
                    <span class="font-medium text-slate-700">OpenSource Store</span>
                    <span>›</span>
                    <span class="text-slate-500 font-mono text-[11px] truncate">{{ productPermalink }}</span>
                  </div>
                  <div class="text-blue-700 hover:underline text-base font-medium leading-snug cursor-pointer truncate">
                    {{ seoTitle }} — OpenSource Store
                  </div>
                  <div class="flex items-center space-x-2 text-xs font-semibold text-slate-700">
                    <span class="text-emerald-700 font-bold">${{ (form.sale_price || form.price).toFixed(2) }}</span>
                    <span>•</span>
                    <span class="text-emerald-600">In stock</span>
                    <span>•</span>
                    <span class="text-slate-500 font-mono">SKU: {{ form.sku || 'N/A' }}</span>
                  </div>
                  <div class="text-xs text-slate-600 leading-relaxed line-clamp-2">
                    {{ seoDescription }}
                  </div>
                </div>
              </div>

              <!-- SEO Title -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-medium text-slate-300">SEO Title Tag</label>
                  <span :class="['text-[11px] font-mono', (form.meta.seo_title || '').length > 60 ? 'text-amber-400' : 'text-slate-400']">
                    {{ (form.meta.seo_title || '').length }}/60 chars
                  </span>
                </div>
                <input v-model="form.meta.seo_title" type="text"
                       :placeholder="form.title || 'Product search title...'"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                <div class="w-full bg-slate-800 h-1 rounded-full mt-1.5 overflow-hidden">
                  <div class="h-full bg-emerald-500 transition-all duration-300"
                       :style="{ width: Math.min(100, ((form.meta.seo_title || '').length / 60) * 100) + '%' }"></div>
                </div>
              </div>

              <!-- Meta Description -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-medium text-slate-300">Meta Description</label>
                  <span :class="['text-[11px] font-mono', (form.meta.seo_description || '').length > 160 ? 'text-amber-400' : 'text-slate-400']">
                    {{ (form.meta.seo_description || '').length }}/160 chars
                  </span>
                </div>
                <textarea v-model="form.meta.seo_description" rows="2"
                          placeholder="Compelling product snippet highlighting benefits, price, and warranty..."
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
                <div class="w-full bg-slate-800 h-1 rounded-full mt-1.5 overflow-hidden">
                  <div class="h-full bg-emerald-500 transition-all duration-300"
                       :style="{ width: Math.min(100, ((form.meta.seo_description || '').length / 160) * 100) + '%' }"></div>
                </div>
              </div>
            </div>

            <!-- Tab 2: Social Open Graph -->
            <div v-if="activeSeoTab === 'social'" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Title</label>
                  <input v-model="form.meta.og_title" type="text"
                         :placeholder="form.meta.seo_title || form.title || 'Product headline...'"
                         class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Image URL</label>
                  <input v-model="form.meta.og_image" type="text"
                         :placeholder="form.images[0] || 'https://...'"
                         class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Description</label>
                <textarea v-model="form.meta.og_description" rows="2"
                          :placeholder="form.meta.seo_description || 'Product overview...'"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
              </div>
            </div>

            <!-- Tab 3: Live SEO Audit -->
            <div v-if="activeSeoTab === 'audit'" class="space-y-3">
              <div v-for="check in seoAudit.checks" :key="check.id"
                   class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800 text-xs">
                <div class="flex items-center space-x-2.5">
                  <CheckCircle2 v-if="check.passed" class="w-4 h-4 text-emerald-400" />
                  <AlertCircle v-else class="w-4 h-4 text-rose-400" />
                  <span :class="check.passed ? 'text-slate-200' : 'text-slate-400'">{{ check.label }}</span>
                </div>
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                               check.passed ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400']">
                  {{ check.passed ? 'Passed' : 'Action Needed' }}
                </span>
              </div>
            </div>

            <!-- Tab 4: Robots & Canonical -->
            <div v-if="activeSeoTab === 'robots'" class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Canonical URL</label>
                <input v-model="form.meta.canonical_url" type="url"
                       :placeholder="'Auto: ' + productPermalink"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono focus:outline-none focus:border-indigo-500" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1.5">Robots Indexing</label>
                  <select v-model="form.meta.robots_index"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="index">index (Allow in search engines)</option>
                    <option value="noindex">noindex (Exclude from search engines)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1.5">Robots Following</label>
                  <select v-model="form.meta.robots_follow"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="follow">follow (Follow links)</option>
                    <option value="nofollow">nofollow (Do not follow)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right 1 Col: Status, Categories with Create, Inventory, Shipping, Tags -->
        <div class="space-y-6">
          <!-- 1. Status & Visibility -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center justify-between">
              <span>Catalog Status</span>
              <span class="text-[10px] text-slate-500 font-mono">ID: {{ form.id || 'New' }}</span>
            </h3>

            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Visibility Status</label>
              <select v-model="form.status"
                      class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-medium focus:outline-none focus:border-indigo-500">
                <option value="active">Active (Visible in Store & App)</option>
                <option value="draft">Draft (Hidden)</option>
                <option value="archived">Archived (Discontinued)</option>
              </select>
            </div>
          </div>

          <!-- 2. Categories with Create Button -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <Folder class="w-4 h-4 text-amber-400" />
                <span>Categories</span>
              </h3>
              <span class="text-xs text-slate-400">({{ form.meta.categories.length }} selected)</span>
            </div>

            <div v-if="categoryMessage" class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center space-x-1.5">
              <Check class="w-3.5 h-3.5 shrink-0" />
              <span>{{ categoryMessage }}</span>
            </div>

            <!-- Search filter -->
            <div v-if="categoryList.length > 5">
              <input v-model="categorySearch" type="text" placeholder="Search categories..."
                     class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>

            <!-- Category Checklist -->
            <div class="max-h-48 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
              <div v-for="cat in filteredCategories" :key="cat"
                   @click="toggleCategory(cat)"
                   class="flex items-center justify-between p-2 rounded-xl bg-slate-950/70 border border-slate-800 hover:border-slate-700 cursor-pointer transition-colors text-xs">
                <div class="flex items-center space-x-2">
                  <div :class="['w-4 h-4 rounded flex items-center justify-center border transition-colors',
                               form.meta.categories.includes(cat) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-700 bg-slate-900']">
                    <Check v-if="form.meta.categories.includes(cat)" class="w-3 h-3" />
                  </div>
                  <span :class="form.meta.categories.includes(cat) ? 'text-white font-semibold' : 'text-slate-300'">{{ cat }}</span>
                </div>
              </div>
            </div>

            <!-- Inline Create Category Button -->
            <div class="pt-2 border-t border-slate-800 space-y-2">
              <div v-if="!showNewCategoryModal">
                <button type="button" @click="showNewCategoryModal = true"
                        class="w-full py-2 px-3 rounded-xl bg-slate-950 hover:bg-slate-800 border border-dashed border-slate-700 hover:border-indigo-500 text-xs font-semibold text-indigo-400 flex items-center justify-center space-x-1.5 transition-colors">
                  <Plus class="w-3.5 h-3.5" />
                  <span>+ Create Category</span>
                </button>
              </div>

              <div v-else class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                <label class="block text-[11px] font-semibold text-slate-300">New Category Name</label>
                <div class="flex items-center space-x-1.5">
                  <input v-model="newCategoryInput" @keydown.enter.prevent="createNewCategory" type="text"
                         placeholder="e.g. Smart Wearables"
                         class="flex-1 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-indigo-500" />
                  <button type="button" @click="createNewCategory" :disabled="isCreatingCategory || !newCategoryInput.trim()"
                          class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold transition-all">
                    {{ isCreatingCategory ? '...' : 'Add' }}
                  </button>
                  <button type="button" @click="showNewCategoryModal = false" class="p-1.5 text-slate-400 hover:text-white">
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Inventory & SKU Management -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
              <Package class="w-4 h-4 text-sky-400" />
              <span>Inventory & SKU</span>
            </h3>

            <!-- SKU with auto-generate -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-medium text-slate-400">SKU (Stock Keeping Unit)</label>
                <button type="button" @click="generateSku" class="text-[11px] text-indigo-400 hover:text-indigo-300">
                  Auto-generate
                </button>
              </div>
              <input v-model="form.sku" type="text" placeholder="e.g. AUD-9021"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:outline-none focus:border-indigo-500" />
            </div>

            <!-- Barcode / UPC -->
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Barcode / UPC / EAN</label>
              <div class="relative">
                <input v-model="form.meta.barcode" type="text" placeholder="e.g. 012345678905"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:outline-none focus:border-indigo-500" />
                <Barcode class="w-3.5 h-3.5 text-slate-500 absolute right-3 top-1/2 -translate-y-1/2" />
              </div>
            </div>

            <!-- Stock status -->
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Stock Status</label>
              <select v-model="form.meta.stock_status"
                      class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                <option value="instock">In Stock</option>
                <option value="outofstock">Out of Stock</option>
                <option value="onbackorder">On Backorder</option>
              </select>
            </div>

            <!-- Stock quantity -->
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Stock Quantity</label>
              <input v-model.number="form.stock" type="number" min="0" required
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>

            <div class="pt-2 border-t border-slate-800">
              <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input v-model="form.meta.track_stock" type="checkbox"
                       class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0" />
                <span>Track inventory & auto-decrement on order</span>
              </label>
            </div>
          </div>

          <!-- 4. Shipping & Logistics -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
              <Truck class="w-4 h-4 text-emerald-400" />
              <span>Shipping & Fulfillment</span>
            </h3>

            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Weight (kg / lbs)</label>
              <input v-model="form.meta.weight" type="text" placeholder="e.g. 0.45 kg"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>

            <!-- Dimensions -->
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Dimensions (L × W × H cm)</label>
              <div class="grid grid-cols-3 gap-2">
                <input v-model="form.meta.dimensions.length" type="text" placeholder="L"
                       class="px-2 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-center text-white focus:outline-none focus:border-indigo-500" />
                <input v-model="form.meta.dimensions.width" type="text" placeholder="W"
                       class="px-2 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-center text-white focus:outline-none focus:border-indigo-500" />
                <input v-model="form.meta.dimensions.height" type="text" placeholder="H"
                       class="px-2 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-center text-white focus:outline-none focus:border-indigo-500" />
              </div>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-800">
              <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input v-model="form.meta.free_shipping" type="checkbox"
                       class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0" />
                <span>Eligible for Free Express Shipping</span>
              </label>
              <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input v-model="form.meta.is_virtual" type="checkbox"
                       class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0" />
                <span>Digital / Virtual Product (No shipping required)</span>
              </label>
            </div>
          </div>

          <!-- 5. Product Tags / Badges with Comma Trigger -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <TagIcon class="w-4 h-4 text-indigo-400" />
                <span>Badges & Tags</span>
              </h3>
              <span class="text-[11px] text-slate-500 font-mono">Press comma or Enter</span>
            </div>

            <!-- Tag Chips Box -->
            <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex flex-wrap items-center gap-1.5 min-h-[46px] focus-within:border-indigo-500 transition-colors">
              <span v-for="(tag, idx) in form.meta.tags" :key="idx"
                    class="inline-flex items-center space-x-1 pl-2.5 pr-1.5 py-1 rounded-lg bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                <span>#{{ tag }}</span>
                <button type="button" @click="removeTag(idx)" class="hover:text-white p-0.5 rounded">
                  <X class="w-3 h-3" />
                </button>
              </span>

              <input v-model="tagInput"
                     @keydown="handleTagKeydown"
                     @paste="handleTagPaste"
                     type="text"
                     placeholder="Add badge and press comma (,)..."
                     class="flex-1 min-w-[130px] bg-transparent text-xs text-white placeholder-slate-500 focus:outline-none px-1 py-0.5" />
            </div>

            <!-- Suggestions -->
            <div class="space-y-1.5">
              <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Suggested Badges:</span>
              <div class="flex flex-wrap gap-1">
                <button v-for="st in suggestedTags" :key="st" type="button"
                        @click="addTag(st)"
                        class="px-2 py-0.5 rounded-md bg-slate-950 hover:bg-indigo-950/60 border border-slate-800 text-[11px] text-slate-400 hover:text-indigo-300 transition-colors">
                  + #{{ st }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
</style>
