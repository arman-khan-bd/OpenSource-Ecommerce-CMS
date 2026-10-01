<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import {
  ArrowLeft, Save, Eye, Sparkles, Image as ImageIcon,
  Tag as TagIcon, Hash, Plus, X, Check, Lock, Unlock,
  RefreshCw, Globe, Search, Share2, Smartphone, Monitor,
  AlertCircle, CheckCircle2, Copy, Calendar, Clock,
  FolderPlus, Folder, BookOpen, Layers, CheckSquare,
  ExternalLink, UploadCloud, ChevronDown, ChevronUp
} from 'lucide-vue-next';

const props = defineProps({
  post: Object,
  categories: {
    type: Array,
    default: () => ['Technology', 'E-Commerce', 'Guides', 'Updates', 'Announcements'],
  },
  isNew: Boolean,
});

const defaultMeta = {
  categories: [props.categories[0] || 'Technology'],
  tags: [],
  focus_keyword: '',
  seo_title: '',
  seo_description: '',
  canonical_url: '',
  og_title: '',
  og_description: '',
  og_image: '',
  robots_index: 'index',
  robots_follow: 'follow',
  reading_time: 1,
  word_count: 0,
  is_featured: false,
};

const form = useForm({
  id: props.post?.id || null,
  title: props.post?.title || '',
  slug: props.post?.slug || '',
  content: props.post?.content || '',
  excerpt: props.post?.excerpt || '',
  type: props.post?.type || 'post',
  status: props.post?.status || 'published',
  featured_image: props.post?.featured_image || '',
  published_at: props.post?.published_at ? props.post.published_at.substring(0, 16) : new Date().toISOString().substring(0, 16),
  meta: {
    ...defaultMeta,
    ...(props.post?.meta || {}),
    categories: Array.isArray(props.post?.meta?.categories) ? props.post.meta.categories : (props.post?.meta?.category ? [props.post.meta.category] : [props.categories[0] || 'Technology']),
    tags: Array.isArray(props.post?.meta?.tags) ? props.post.meta.tags : [],
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
    const res = await fetch('/admin/posts/categories', {
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
      const res = await fetch(`/admin/posts/check-slug?slug=${encodeURIComponent(slug)}&id=${form.id || ''}&type=${form.type}`);
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

// Tag Input
const tagInput = ref('');
const suggestedTags = ['ecommerce', 'tutorial', 'guides', 'wordpress', 'cms', 'symfony', 'vuejs', 'updates'];

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

// Featured Image Upload Box
const featuredImageInputRef = ref(null);
const isUploadingCover = ref(false);
const coverUploadError = ref('');
const coverInputMode = ref('file'); // 'file' or 'url'

async function handleCoverUpload(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  isUploadingCover.value = true;
  coverUploadError.value = '';

  const formData = new FormData();
  formData.append('image', file);
  formData.append('alt_text', form.title || file.name);

  try {
    const res = await fetch('/admin/posts/upload-image', {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();
    if (data.success && data.url) {
      form.featured_image = data.url;
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

function removeCoverImage() {
  form.featured_image = '';
}

// Auto Excerpt Generator
function autoGenerateExcerpt() {
  if (!form.content) return;
  const plainText = form.content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  form.excerpt = plainText.length > 160 ? plainText.substring(0, 157) + '...' : plainText;
}

// Stats & Table of Contents
const editorStats = ref({ words: 0, chars: 0, readingTime: 1 });

function onEditorStatsChange(stats) {
  editorStats.value = stats;
  form.meta.reading_time = stats.readingTime;
  form.meta.word_count = stats.words;
}

const tableOfContents = computed(() => {
  if (!form.content) return [];
  const regex = /<h([2-3])[^>]*>(.*?)<\/h\1>/gi;
  const headings = [];
  let match;
  while ((match = regex.exec(form.content)) !== null) {
    const text = match[2].replace(/<[^>]*>/g, '').trim();
    if (text) {
      headings.push({ level: parseInt(match[1]), text });
    }
  }
  return headings;
});

// SEO Analysis & SERP Preview
const activeSeoTab = ref('general'); // 'general', 'social', 'robots', 'audit'
const serpDevice = ref('desktop'); // 'desktop', 'mobile'

const seoTitle = computed(() => form.meta.seo_title || form.title || 'Untitled Post');
const seoDescription = computed(() => form.meta.seo_description || form.excerpt || 'Discover in-depth insights, tutorials, and practical strategies on OpenSource eCommerce CMS.');
const postPermalink = computed(() => `/${form.type === 'page' ? 'page' : 'blog'}/${form.slug || 'your-slug'}`);

// Real-time SEO Audit Checklist
const seoAudit = computed(() => {
  const kw = (form.meta.focus_keyword || '').toLowerCase().trim();
  const title = (form.title || '').toLowerCase();
  const sTitle = (form.meta.seo_title || '').toLowerCase();
  const slug = (form.slug || '').toLowerCase();
  const desc = (form.meta.seo_description || '').toLowerCase();
  const plainContent = form.content.replace(/<[^>]*>/g, ' ').toLowerCase();

  const checks = [
    {
      id: 'kw_title',
      label: 'Focus keyword in SEO/Article Title',
      passed: Boolean(kw && (sTitle.includes(kw) || title.includes(kw))),
      importance: 'high',
    },
    {
      id: 'kw_slug',
      label: 'Focus keyword in URL Slug',
      passed: Boolean(kw && slug.includes(slugify(kw))),
      importance: 'high',
    },
    {
      id: 'kw_desc',
      label: 'Focus keyword in Meta Description',
      passed: Boolean(kw && desc.includes(kw)),
      importance: 'medium',
    },
    {
      id: 'kw_content',
      label: 'Focus keyword mentioned in content',
      passed: Boolean(kw && plainContent.includes(kw)),
      importance: 'high',
    },
    {
      id: 'content_length',
      label: 'Content length (min 300 words)',
      passed: editorStats.value.words >= 300,
      importance: 'medium',
    },
    {
      id: 'has_headings',
      label: 'Subheadings (H2/H3) used in article',
      passed: tableOfContents.value.length > 0,
      importance: 'medium',
    },
    {
      id: 'has_cover',
      label: 'Featured Cover Image set',
      passed: Boolean(form.featured_image),
      importance: 'medium',
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

// Copy link
const copiedLink = ref(false);
function copyPublicLink() {
  const fullUrl = window.location.origin + postPermalink.value;
  navigator.clipboard.writeText(fullUrl);
  copiedLink.value = true;
  setTimeout(() => copiedLink.value = false, 2500);
}

function submit(statusOverride = null) {
  if (statusOverride) {
    form.status = statusOverride;
  }
  form.post('/admin/posts/save');
}
</script>

<template>
  <AdminLayout>
    <form @submit.prevent="submit()" class="space-y-6">
      <!-- Top Sticky Action Bar -->
      <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div class="flex items-center space-x-3">
          <Link href="/admin/posts" class="p-2.5 rounded-xl bg-slate-900 text-slate-400 hover:text-white border border-slate-800 transition-colors shadow-sm">
            <ArrowLeft class="w-4 h-4" />
          </Link>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-xl font-extrabold text-white tracking-tight">
                {{ isNew ? 'Create New ' + (form.type === 'page' ? 'Page' : 'Post') : 'Edit Post: ' + form.title }}
              </h1>
              <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-bold border uppercase tracking-wider',
                             form.status === 'published' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20']">
                {{ form.status }}
              </span>
            </div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mt-0.5">
              <span>Alternative to WordPress</span>
              <span>•</span>
              <span class="text-indigo-400 font-mono">{{ editorStats.words }} words (~{{ editorStats.readingTime }} min read)</span>
            </div>
          </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center space-x-2.5">
          <button v-if="!isNew" type="button" @click="copyPublicLink"
                  class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all">
            <Copy class="w-3.5 h-3.5 text-indigo-400" />
            <span>{{ copiedLink ? 'Copied URL!' : 'Share Link' }}</span>
          </button>

          <button type="button" @click="submit('draft')" :disabled="form.processing"
                  class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold transition-all">
            Save as Draft
          </button>

          <button type="submit" :disabled="form.processing"
                  class="px-6 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Publishing...' : (isNew ? 'Publish Post' : 'Update & Publish') }}</span>
          </button>
        </div>
      </div>

      <!-- Main Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Title, Slug, Rich Text Editor, Excerpt, SEO Suite -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Title & Permalink Card -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                Article Title <span class="text-rose-500">*</span>
              </label>
              <input v-model="form.title" @input="handleTitleInput" type="text" required
                     placeholder="Enter a compelling title..."
                     class="w-full px-4 py-3.5 rounded-xl bg-slate-950 border border-slate-800 text-lg font-bold text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors shadow-inner" />
            </div>

            <!-- Auto Slug Generator with Collision Resolution (e.g. old_1) -->
            <div class="pt-2 border-t border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400 font-medium">Permalink URL Slug:</span>
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
                  /{{ form.type === 'page' ? 'page' : 'blog' }}/
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

              <!-- Slug collision message -->
              <div v-if="slugCollisionNotice" class="text-[11px] text-amber-400 font-mono flex items-center space-x-1 mt-1">
                <AlertCircle class="w-3.5 h-3.5 shrink-0" />
                <span>{{ slugCollisionNotice }}</span>
              </div>
              <p class="text-[11px] text-slate-500">
                Slugs are automatically checked against the database. If taken, a numerical suffix like <span class="font-mono text-slate-400">old_1</span> or <span class="font-mono text-slate-400">old_2</span> is automatically generated.
              </p>
            </div>
          </div>

          <!-- Rich Text Content Editor -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Article Content (Rich Text / WYSIWYG & HTML)
              </label>
              <span class="text-[11px] text-indigo-400 font-medium">Filtered by 'the_content' hook</span>
            </div>

            <!-- Custom Rich Text Editor with Upload to public_html/uploads -->
            <RichTextEditor v-model="form.content"
                            upload-url="/admin/posts/upload-image"
                            placeholder="Write your story... format headings, bold, lists, quote blocks, code snippets, or upload images directly."
                            @stats-change="onEditorStatsChange" />
          </div>

          <!-- Excerpt Card with Auto-Generate -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Excerpt / Short Summary
              </label>
              <button type="button" @click="autoGenerateExcerpt"
                      class="px-2.5 py-1 text-[11px] font-semibold text-purple-300 bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/20 rounded-lg flex items-center space-x-1.5 transition-colors">
                <Sparkles class="w-3 h-3 text-purple-400" />
                <span>Auto Generate from Content</span>
              </button>
            </div>
            <textarea v-model="form.excerpt" rows="3"
                      placeholder="Brief 1-2 sentence overview for social shares, storefront cards, and search engines..."
                      class="w-full p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 leading-relaxed"></textarea>
          </div>

          <!-- Advanced SEO & Social Suite -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-4">
              <div class="flex items-center space-x-2">
                <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                  <Search class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                    <span>Search Engine Optimization (SEO) & Social Meta</span>
                  </h3>
                  <span class="text-xs text-slate-400">Fine-tune Google search rankings, OpenGraph cards, and crawler robots</span>
                </div>
              </div>

              <!-- SEO Score Indicator -->
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
                <span>Social Cards (OG)</span>
              </button>
              <button type="button" @click="activeSeoTab = 'audit'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'audit' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <CheckSquare class="w-3.5 h-3.5" />
                <span>Live SEO Audit ({{ seoAudit.passedCount }}/{{ seoAudit.total }})</span>
              </button>
              <button type="button" @click="activeSeoTab = 'robots'"
                      :class="['pb-2.5 transition-all flex items-center space-x-1.5',
                               activeSeoTab === 'robots' ? 'text-indigo-400 border-b-2 border-indigo-500' : 'text-slate-400 hover:text-white']">
                <Globe class="w-3.5 h-3.5" />
                <span>Robots & Canonical</span>
              </button>
            </div>

            <!-- TAB 1: General & SERP -->
            <div v-if="activeSeoTab === 'general'" class="space-y-5">
              <!-- Focus Keyphrase -->
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Focus Keyphrase</label>
                <input v-model="form.meta.focus_keyword" type="text"
                       placeholder="e.g. headless cms, wordpress alternative, online shop tutorial..."
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-medium" />
                <p class="text-[11px] text-slate-500 mt-1">The primary keyword you want this article to rank for in Google searches.</p>
              </div>

              <!-- Google SERP Snippet Preview Box -->
              <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-300 flex items-center space-x-1.5">
                    <GoogleIcon class="w-3.5 h-3.5 text-blue-400" />
                    <span>Google Search Result Snippet</span>
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

                <!-- Realistic SERP Card -->
                <div :class="['p-4 rounded-xl bg-white text-slate-800 shadow-sm space-y-1.5 font-sans',
                             serpDevice === 'mobile' ? 'max-w-sm mx-auto' : 'w-full']">
                  <!-- Breadcrumb -->
                  <div class="flex items-center space-x-1.5 text-xs text-slate-600">
                    <span class="w-4 h-4 rounded-full bg-slate-200 flex items-center justify-center font-bold text-[9px] text-slate-700">G</span>
                    <span class="font-medium text-slate-700">OpenSource CMS</span>
                    <span>›</span>
                    <span class="text-slate-500 font-mono text-[11px] truncate">{{ postPermalink }}</span>
                  </div>
                  <!-- Clickable Blue Title -->
                  <div class="text-blue-700 hover:underline text-base font-medium leading-snug cursor-pointer truncate">
                    {{ seoTitle }} — OpenSource eCommerce CMS
                  </div>
                  <!-- Snippet Text -->
                  <div class="text-xs text-slate-600 leading-relaxed line-clamp-2">
                    <span class="text-slate-500 font-medium">Oct 1, 2026 — </span>{{ seoDescription }}
                  </div>
                </div>
              </div>

              <!-- SEO Meta Title -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-medium text-slate-300">SEO Title Tag</label>
                  <span :class="['text-[11px] font-mono',
                                 (form.meta.seo_title || '').length > 60 ? 'text-amber-400' : 'text-slate-400']">
                    {{ (form.meta.seo_title || '').length }}/60 chars
                  </span>
                </div>
                <input v-model="form.meta.seo_title" type="text"
                       :placeholder="form.title || 'Custom Google search title...'"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                <div class="w-full bg-slate-800 h-1 rounded-full mt-1.5 overflow-hidden">
                  <div class="h-full bg-emerald-500 transition-all duration-300"
                       :style="{ width: Math.min(100, ((form.meta.seo_title || '').length / 60) * 100) + '%' }"></div>
                </div>
              </div>

              <!-- SEO Meta Description -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-medium text-slate-300">Meta Description</label>
                  <span :class="['text-[11px] font-mono',
                                 (form.meta.seo_description || '').length > 160 ? 'text-amber-400' : 'text-slate-400']">
                    {{ (form.meta.seo_description || '').length }}/160 chars
                  </span>
                </div>
                <textarea v-model="form.meta.seo_description" rows="2"
                          :placeholder="form.excerpt || 'Write a compelling summary to increase click-through rate in search results...'"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
                <div class="w-full bg-slate-800 h-1 rounded-full mt-1.5 overflow-hidden">
                  <div class="h-full bg-emerald-500 transition-all duration-300"
                       :style="{ width: Math.min(100, ((form.meta.seo_description || '').length / 160) * 100) + '%' }"></div>
                </div>
              </div>
            </div>

            <!-- TAB 2: Social Cards (Open Graph / Twitter) -->
            <div v-if="activeSeoTab === 'social'" class="space-y-4">
              <p class="text-xs text-slate-400">Controls how this article appears when shared on Facebook, X (Twitter), LinkedIn, and WhatsApp.</p>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Title</label>
                  <input v-model="form.meta.og_title" type="text"
                         :placeholder="form.meta.seo_title || form.title || 'Social headline...'"
                         class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Image URL</label>
                  <input v-model="form.meta.og_image" type="text"
                         :placeholder="form.featured_image || 'https://... or /uploads/...'"
                         class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">OG Social Description</label>
                <textarea v-model="form.meta.og_description" rows="2"
                          :placeholder="form.meta.seo_description || form.excerpt || 'Social card summary...'"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
              </div>

              <!-- Social Preview Card -->
              <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                <span class="text-xs font-bold text-slate-400">Facebook / LinkedIn Feed Card Preview</span>
                <div class="rounded-xl overflow-hidden border border-slate-800 bg-slate-900 shadow-lg max-w-md">
                  <div class="aspect-video w-full bg-slate-950 flex items-center justify-center overflow-hidden">
                    <img v-if="form.meta.og_image || form.featured_image"
                         :src="form.meta.og_image || form.featured_image"
                         alt="Social Preview" class="w-full h-full object-cover" />
                    <span v-else class="text-xs text-slate-500 font-medium">Featured cover image will be used</span>
                  </div>
                  <div class="p-3 space-y-1">
                    <div class="text-[10px] text-slate-400 uppercase tracking-wider font-mono">YOURDOMAIN.COM</div>
                    <div class="text-xs font-bold text-white truncate">{{ form.meta.og_title || form.meta.seo_title || form.title || 'Article Headline' }}</div>
                    <div class="text-[11px] text-slate-400 line-clamp-2">{{ form.meta.og_description || form.meta.seo_description || form.excerpt || 'Article summary description.' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- TAB 3: Live SEO Audit Checklist -->
            <div v-if="activeSeoTab === 'audit'" class="space-y-3">
              <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-xs text-slate-400">
                <span>Metric / SEO Rule</span>
                <span>Status</span>
              </div>
              <div v-for="check in seoAudit.checks" :key="check.id"
                   class="flex items-center justify-between p-3 rounded-xl bg-slate-950/70 border border-slate-800 text-xs">
                <div class="flex items-center space-x-2.5">
                  <CheckCircle2 v-if="check.passed" class="w-4 h-4 text-emerald-400" />
                  <AlertCircle v-else class="w-4 h-4 text-rose-400" />
                  <span :class="check.passed ? 'text-slate-200' : 'text-slate-400'">{{ check.label }}</span>
                </div>
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                               check.passed ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400']">
                  {{ check.passed ? 'Passed' : 'Needs Action' }}
                </span>
              </div>
            </div>

            <!-- TAB 4: Robots & Canonical -->
            <div v-if="activeSeoTab === 'robots'" class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Canonical URL</label>
                <input v-model="form.meta.canonical_url" type="url"
                       :placeholder="'Auto: ' + postPermalink"
                       class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" />
                <p class="text-[11px] text-slate-500 mt-1">Leave empty to auto-set canonical tag to this post's permanent link.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1.5">Robots Indexing</label>
                  <select v-model="form.meta.robots_index"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="index">index (Allow search engines to index)</option>
                    <option value="noindex">noindex (Prevent search engines from indexing)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-medium text-slate-300 mb-1.5">Robots Following</label>
                  <select v-model="form.meta.robots_follow"
                          class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="follow">follow (Follow all links on this page)</option>
                    <option value="nofollow">nofollow (Do not follow links on this page)</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right 1 Col: Publishing Status, Categories with Create Button, Featured Image, Tags with Comma -->
        <div class="space-y-6">
          <!-- 1. Publishing Status & Schedule -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white flex items-center justify-between">
              <span>Publishing Status</span>
              <span class="text-[10px] text-slate-500 font-mono">ID: {{ form.id || 'New' }}</span>
            </h3>

            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Visibility Status</label>
              <select v-model="form.status"
                      class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-medium focus:outline-none focus:border-indigo-500">
                <option value="published">Published (Live to Public)</option>
                <option value="draft">Draft (Work in Progress)</option>
                <option value="archived">Archived (Unpublished)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5">Content Type</label>
              <select v-model="form.type"
                      class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-medium focus:outline-none focus:border-indigo-500">
                <option value="post">Blog Post / Editorial Article</option>
                <option value="page">Storefront Static Page</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1.5 flex items-center space-x-1">
                <Calendar class="w-3.5 h-3.5 text-indigo-400" />
                <span>Publish Date / Schedule</span>
              </label>
              <input v-model="form.published_at" type="datetime-local"
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono" />
            </div>

            <div class="pt-2 border-t border-slate-800">
              <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input v-model="form.meta.is_featured" type="checkbox"
                       class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0" />
                <span class="font-medium">Pin to Top / Mark as Featured Post</span>
              </label>
            </div>
          </div>

          <!-- 2. Categories with Inline Create Button -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <Folder class="w-4 h-4 text-amber-400" />
                <span>Categories</span>
              </h3>
              <span class="text-xs text-slate-400">({{ form.meta.categories.length }} selected)</span>
            </div>

            <!-- Inline Success/Error Message -->
            <div v-if="categoryMessage" class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center space-x-1.5">
              <Check class="w-3.5 h-3.5 shrink-0" />
              <span>{{ categoryMessage }}</span>
            </div>

            <!-- Category Search Filter -->
            <div v-if="categoryList.length > 5">
              <input v-model="categorySearch" type="text" placeholder="Search categories..."
                     class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500" />
            </div>

            <!-- Category Checkbox List -->
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

            <!-- Create New Category Button & Inline Form -->
            <div class="pt-2 border-t border-slate-800 space-y-2">
              <div v-if="!showNewCategoryModal">
                <button type="button" @click="showNewCategoryModal = true"
                        class="w-full py-2 px-3 rounded-xl bg-slate-950 hover:bg-slate-800 border border-dashed border-slate-700 hover:border-indigo-500 text-xs font-semibold text-indigo-400 flex items-center justify-center space-x-1.5 transition-colors">
                  <Plus class="w-3.5 h-3.5" />
                  <span>+ Create Category</span>
                </button>
              </div>

              <!-- New Category Creation Form -->
              <div v-else class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                <label class="block text-[11px] font-semibold text-slate-300">New Category Name</label>
                <div class="flex items-center space-x-1.5">
                  <input v-model="newCategoryInput" @keydown.enter.prevent="createNewCategory" type="text"
                         placeholder="e.g. Design Systems"
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

          <!-- 3. Featured Image Upload to public_html/uploads -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <ImageIcon class="w-4 h-4 text-emerald-400" />
                <span>Featured Cover Image</span>
              </h3>
              <!-- Mode switch: file vs url -->
              <div class="flex items-center space-x-1 text-[11px] bg-slate-950 rounded-lg p-0.5 border border-slate-800">
                <button type="button" @click="coverInputMode = 'file'"
                        :class="['px-2 py-0.5 rounded font-medium', coverInputMode === 'file' ? 'bg-indigo-600 text-white' : 'text-slate-400']">
                  Upload
                </button>
                <button type="button" @click="coverInputMode = 'url'"
                        :class="['px-2 py-0.5 rounded font-medium', coverInputMode === 'url' ? 'bg-indigo-600 text-white' : 'text-slate-400']">
                  URL
                </button>
              </div>
            </div>

            <!-- Preview Card -->
            <div v-if="form.featured_image" class="space-y-2">
              <div class="relative aspect-video w-full rounded-xl overflow-hidden bg-slate-950 border border-slate-800 group shadow-md">
                <img :src="form.featured_image" alt="Cover Preview" class="w-full h-full object-cover" />
                <button type="button" @click="removeCoverImage" title="Remove cover image"
                        class="absolute top-2 right-2 p-1.5 rounded-lg bg-black/70 hover:bg-rose-600 text-white transition-colors">
                  <X class="w-3.5 h-3.5" />
                </button>
              </div>
              <div class="text-[11px] text-slate-400 font-mono truncate">
                {{ form.featured_image }}
              </div>
            </div>

            <!-- File Upload Zone -->
            <div v-if="coverInputMode === 'file'" class="space-y-2">
              <div @click="$refs.featuredImageInputRef.click()"
                   class="border-2 border-dashed border-slate-700 hover:border-emerald-500/70 rounded-xl p-5 text-center cursor-pointer transition-colors bg-slate-950/60">
                <input ref="featuredImageInputRef" type="file" accept="image/*" class="hidden" @change="handleCoverUpload" />
                <div class="flex flex-col items-center space-y-1.5">
                  <UploadCloud class="w-6 h-6 text-emerald-400" />
                  <span class="text-xs font-semibold text-white">Click or drag image to upload</span>
                  <span class="text-[10px] text-slate-500">Stored in public_html/uploads</span>
                </div>
              </div>

              <div v-if="isUploadingCover" class="text-xs text-center text-emerald-400 font-medium animate-pulse">
                Uploading to public_html/uploads...
              </div>
              <div v-if="coverUploadError" class="text-xs text-center text-rose-400 font-medium">
                {{ coverUploadError }}
              </div>
            </div>

            <!-- Direct URL Input -->
            <div v-else>
              <input v-model="form.featured_image" type="url" placeholder="https://... or /uploads/..."
                     class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
            </div>
          </div>

          <!-- 4. Tags Input with Comma Auto-Select -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-white flex items-center space-x-2">
                <TagIcon class="w-4 h-4 text-indigo-400" />
                <span>Tags</span>
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

              <!-- Tag typing input -->
              <input v-model="tagInput"
                     @keydown="handleTagKeydown"
                     @paste="handleTagPaste"
                     type="text"
                     placeholder="Add tag and press comma (,)..."
                     class="flex-1 min-w-[140px] bg-transparent text-xs text-white placeholder-slate-500 focus:outline-none px-1 py-0.5" />
            </div>

            <!-- Suggested Popular Tags -->
            <div class="space-y-1.5">
              <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider">Suggested Tags:</span>
              <div class="flex flex-wrap gap-1">
                <button v-for="st in suggestedTags" :key="st" type="button"
                        @click="addTag(st)"
                        class="px-2 py-0.5 rounded-md bg-slate-950 hover:bg-indigo-950/60 border border-slate-800 text-[11px] text-slate-400 hover:text-indigo-300 transition-colors">
                  + #{{ st }}
                </button>
              </div>
            </div>
          </div>

          <!-- 5. Table of Contents Outline Preview -->
          <div v-if="tableOfContents.length > 0" class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <h3 class="text-sm font-bold text-white flex items-center space-x-2">
              <Layers class="w-4 h-4 text-sky-400" />
              <span>Article Outline (Headings)</span>
            </h3>
            <div class="space-y-1.5 text-xs text-slate-400 max-h-44 overflow-y-auto">
              <div v-for="(heading, idx) in tableOfContents" :key="idx"
                   :class="['truncate', heading.level === 3 ? 'pl-4 text-slate-400' : 'font-medium text-slate-300']">
                • {{ heading.text }}
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
