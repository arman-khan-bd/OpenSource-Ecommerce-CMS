<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import {
  Layout,
  Plus,
  Trash2,
  Copy,
  MoveUp,
  MoveDown,
  Monitor,
  Tablet,
  Smartphone,
  Eye,
  Save,
  ArrowLeft,
  Settings,
  Sparkles,
  ShoppingBag,
  Send,
  Globe,
  Tag,
  MessageSquare,
  HelpCircle,
  Clock,
  Layers,
  CheckCircle,
  ExternalLink,
  Edit2,
  Code,
  Search,
  Image as ImageIcon,
  AlertTriangle,
  RotateCcw,
  ShieldCheck
} from 'lucide-vue-next';

const pageState = usePage();
const activePlugins = computed(() => pageState.props.activePlugins || []);
const isElementorActive = computed(() => activePlugins.value.includes('elementor-pro'));
const isWooCommerceActive = computed(() => activePlugins.value.includes('woocommerce-core'));
const isWpFormsActive = computed(() => activePlugins.value.includes('wpforms-builder'));
const isYoastSeoActive = computed(() => activePlugins.value.includes('yoast-seo-pro') || activePlugins.value.includes('seo-toolkit'));

const props = defineProps({
  page: {
    type: Object,
    default: () => ({
      id: null,
      title: '',
      slug: '',
      content: '',
      excerpt: '',
      status: 'published',
      featured_image: null,
      meta: {},
    }),
  },
  isNew: {
    type: Boolean,
    default: true,
  },
  initialBuilder: {
    type: String,
    default: null,
  },
});

const viewport = ref('desktop'); // 'desktop' | 'tablet' | 'mobile'
const activeSidebarTab = ref('widgets'); // 'widgets' | 'inspector'
const selectedBlockIndex = ref(null);

// Determine initial editorMode: strictly classic if Elementor is inactive, or check initialBuilder/URL
function getInitialEditorMode() {
  if (!isElementorActive.value) return 'classic';

  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    const builderParam = urlParams.get('builder');
    if (builderParam === 'classic') return 'classic';
    if (builderParam === 'elementor') return 'elementor';
  }

  if (props.initialBuilder === 'classic') return 'classic';
  if (props.initialBuilder === 'elementor') return 'elementor';

  return props.page.meta?.builder === 'elementor' ? 'elementor' : 'classic';
}

const editorMode = ref(getInitialEditorMode());

// If Elementor gets deactivated, automatically fallback to classic editor
watch(isElementorActive, (active) => {
  if (!active && editorMode.value === 'elementor') {
    editorMode.value = 'classic';
  }
});

const form = useForm({
  id: props.page.id || null,
  title: props.page.title || (props.isNew ? 'New Custom Page' : ''),
  slug: props.page.slug || '',
  content: props.page.content || '',
  excerpt: props.page.excerpt || '',
  type: 'page',
  status: props.page.status || 'published',
  featured_image: props.page.featured_image || null,
  meta: {
    builder: props.page.meta?.builder || (isElementorActive.value ? 'elementor' : 'classic'),
    seo_title: props.page.meta?.seo_title || '',
    seo_description: props.page.meta?.seo_description || '',
    ...(props.page.meta || {}),
  },
});

// Parse existing content into builder blocks for Elementor
function parseInitialBlocks() {
  const content = form.content || props.page.content || '';
  const blocks = [];

  // Parse hero
  if (content.includes('[elementor_hero')) {
    const match = content.match(/\[elementor_hero([^\]]*)\]/i);
    blocks.push({
      type: 'hero',
      title: getAtt(match?.[1], 'title') || 'Modern Monolithic Architecture',
      subtitle: getAtt(match?.[1], 'subtitle') || 'High speed, monolithic simplicity, and limitless extensibility.',
      button_text: getAtt(match?.[1], 'button_text') || 'Explore Store',
      button_url: getAtt(match?.[1], 'button_url') || '/shop',
      badge: getAtt(match?.[1], 'badge') || '★ Featured Drop',
    });
  }

  // Parse pricing
  if (content.includes('[elementor_pricing')) {
    const match = content.match(/\[elementor_pricing([^\]]*)\]/i);
    blocks.push({
      type: 'pricing',
      plan: getAtt(match?.[1], 'plan') || 'Pro Elite Plan',
      price: getAtt(match?.[1], 'price') || '$49',
      period: getAtt(match?.[1], 'period') || '/mo',
      features: getAtt(match?.[1], 'features') || 'Unlimited Hardware Drops, 24/7 Concierge Support, Real-Time Telemetry',
      badge: getAtt(match?.[1], 'badge') || 'Most Popular',
      button_text: getAtt(match?.[1], 'button_text') || 'Get Started',
      button_url: getAtt(match?.[1], 'button_url') || '/checkout',
    });
  }

  // Parse coupon box
  if (content.includes('[woocommerce_coupon_box]')) {
    blocks.push({ type: 'woo_coupons' });
  }

  // Parse trust badges
  if (content.includes('[woocommerce_trust_badges]')) {
    blocks.push({ type: 'woo_trust' });
  }

  // Parse testimonials
  if (content.includes('[elementor_testimonial')) {
    const match = content.match(/\[elementor_testimonial([^\]]*)\]/i);
    blocks.push({
      type: 'testimonial',
      author: getAtt(match?.[1], 'author') || 'Sarah Chen',
      role: getAtt(match?.[1], 'role') || 'Creative Director',
      quote: getAtt(match?.[1], 'quote') || 'Replaced WordPress, WooCommerce, and multiple slow plugins with one blazing fast CMS engine.',
      stars: parseInt(getAtt(match?.[1], 'stars') || '5', 10),
    });
  }

  // Parse FAQ
  if (content.includes('[elementor_faq')) {
    const match = content.match(/\[elementor_faq([^\]]*)\]/i);
    blocks.push({
      type: 'faq',
      question: getAtt(match?.[1], 'q') || 'Can I migrate my existing WordPress data?',
      answer: getAtt(match?.[1], 'a') || 'Yes! Our unified SQLite/MySQL schema and Twig templating allows instant migration.',
    });
  }

  // Parse contact
  if (content.includes('[wpforms id="contact"]')) {
    blocks.push({ type: 'wpforms_contact' });
  }

  // Parse newsletter
  if (content.includes('[wpforms id="newsletter"]')) {
    blocks.push({ type: 'wpforms_newsletter' });
  }

  // Fallback default blocks if empty
  if (!blocks.length && isElementorActive.value) {
    blocks.push(
      {
        type: 'hero',
        title: props.page.title || 'Apex High-Performance Store',
        subtitle: 'Engineered for maximum conversion rates and ultra-low server latency.',
        button_text: 'Explore Hardware',
        button_url: '/shop',
        badge: '★ Enterprise Drop',
      },
      {
        type: 'pricing',
        plan: 'Commercial Fleet',
        price: '$99',
        period: '/mo',
        features: 'Dedicated SDUI Sync, PWA Web Push Broadcasts, Zero Cloud Vendor Lock-in',
        badge: 'Enterprise',
        button_text: 'Deploy Now',
        button_url: '/checkout',
      }
    );
  }

  return blocks;
}

function getAtt(str, key) {
  if (!str) return null;
  const match = str.match(new RegExp(`${key}=["']([^"']*)["']`, 'i'));
  return match ? match[1] : null;
}

const blocks = ref(parseInitialBlocks());

// Sync blocks to form.content ONLY when in elementor editor mode
watch(
  blocks,
  () => {
    if (editorMode.value !== 'elementor') return;
    let out = '';
    blocks.value.forEach((b) => {
      if (b.type === 'hero') {
        out += `[elementor_hero title="${b.title}" subtitle="${b.subtitle}" button_text="${b.button_text}" button_url="${b.button_url}" badge="${b.badge}"]\n\n`;
      } else if (b.type === 'pricing') {
        out += `[elementor_pricing plan="${b.plan}" price="${b.price}" period="${b.period}" features="${b.features}" badge="${b.badge}" button_text="${b.button_text}" button_url="${b.button_url}"]\n\n`;
      } else if (b.type === 'testimonial') {
        out += `[elementor_testimonial author="${b.author}" role="${b.role}" quote="${b.quote}" stars="${b.stars || 5}"]\n\n`;
      } else if (b.type === 'faq') {
        out += `[elementor_faq q="${b.question}" a="${b.answer}"]\n\n`;
      } else if (b.type === 'woo_coupons') {
        out += `[woocommerce_coupon_box]\n\n`;
      } else if (b.type === 'woo_trust') {
        out += `[woocommerce_trust_badges]\n\n`;
      } else if (b.type === 'wpforms_contact') {
        out += `[wpforms id="contact"]\n\n`;
      } else if (b.type === 'wpforms_newsletter') {
        out += `[wpforms id="newsletter"]\n\n`;
      } else if (b.type === 'text') {
        out += `<div class="my-6 p-6 rounded-2xl bg-slate-900/60 border border-slate-800 text-slate-200">${b.content}</div>\n\n`;
      }
    });
    form.content = out.trim();
  },
  { deep: true }
);

function addBlock(type) {
  if (type === 'hero') {
    blocks.value.push({
      type: 'hero',
      title: 'New Showcase Section',
      subtitle: 'Write a compelling subheadline that captures attention.',
      button_text: 'Learn More',
      button_url: '/shop',
      badge: '★ Featured',
    });
  } else if (type === 'pricing') {
    blocks.value.push({
      type: 'pricing',
      plan: 'Growth Plan',
      price: '$39',
      period: '/mo',
      features: 'Fast Delivery, Priority Support, Lifetime Updates',
      badge: 'Popular',
      button_text: 'Get Started',
      button_url: '/checkout',
    });
  } else if (type === 'testimonial') {
    blocks.value.push({
      type: 'testimonial',
      author: 'Jordan Bell',
      role: 'Verified Customer',
      quote: 'Exceptional build quality and lightning-fast checkout experience!',
      stars: 5,
    });
  } else if (type === 'faq') {
    blocks.value.push({
      type: 'faq',
      question: 'How fast is express delivery?',
      answer: 'All orders placed before 3 PM EST ship the same business day.',
    });
  } else if (type === 'woo_coupons') {
    blocks.value.push({ type: 'woo_coupons' });
  } else if (type === 'woo_trust') {
    blocks.value.push({ type: 'woo_trust' });
  } else if (type === 'wpforms_contact') {
    blocks.value.push({ type: 'wpforms_contact' });
  } else if (type === 'wpforms_newsletter') {
    blocks.value.push({ type: 'wpforms_newsletter' });
  } else if (type === 'text') {
    blocks.value.push({
      type: 'text',
      content: 'Add your custom editorial text or HTML paragraph here.',
    });
  }

  selectedBlockIndex.value = blocks.value.length - 1;
  activeSidebarTab.value = 'inspector';
}

function selectBlock(idx) {
  selectedBlockIndex.value = idx;
  activeSidebarTab.value = 'inspector';
}

function removeBlock(idx) {
  blocks.value.splice(idx, 1);
  if (selectedBlockIndex.value === idx) {
    selectedBlockIndex.value = null;
    activeSidebarTab.value = 'widgets';
  }
}

function duplicateBlock(idx) {
  const clone = JSON.parse(JSON.stringify(blocks.value[idx]));
  blocks.value.splice(idx + 1, 0, clone);
  selectedBlockIndex.value = idx + 1;
}

function moveUp(idx) {
  if (idx > 0) {
    const item = blocks.value.splice(idx, 1)[0];
    blocks.value.splice(idx - 1, 0, item);
    selectedBlockIndex.value = idx - 1;
  }
}

function moveDown(idx) {
  if (idx < blocks.value.length - 1) {
    const item = blocks.value.splice(idx, 1)[0];
    blocks.value.splice(idx + 1, 0, item);
    selectedBlockIndex.value = idx + 1;
  }
}

function autoSlug() {
  form.slug = form.title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function switchToElementor() {
  if (!isElementorActive.value) return;
  blocks.value = parseInitialBlocks();
  editorMode.value = 'elementor';
  form.meta.builder = 'elementor';
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    url.searchParams.set('builder', 'elementor');
    window.history.replaceState({}, '', url.toString());
  }
}

function switchToClassic() {
  editorMode.value = 'classic';
  form.meta.builder = 'classic';
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    url.searchParams.set('builder', 'classic');
    window.history.replaceState({}, '', url.toString());
  }
}

function savePage(status = 'published') {
  if (!form.title) {
    alert('Please enter a page title.');
    return;
  }
  if (!form.slug) autoSlug();
  form.status = status;
  form.meta.builder = editorMode.value;

  form.post('/admin/posts/save', {
    onSuccess: () => {
      // Handled by flash notification
    },
  });
}
</script>

<template>
  <!-- 1. ELEMENTOR PRO VISUAL BUILDER MODE (Only available when Elementor Pro is active) -->
  <div v-if="editorMode === 'elementor' && isElementorActive" class="min-h-screen bg-slate-950 flex flex-col antialiased text-slate-100 font-sans selection:bg-pink-500 selection:text-white">
    <!-- Top Elementor Pro Control Bar -->
    <header class="h-16 bg-slate-900/90 border-b border-slate-800 backdrop-blur-md px-4 sm:px-6 flex items-center justify-between shrink-0 z-30">
      <div class="flex items-center space-x-4">
        <Link href="/admin/pages" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors" title="Back to Pages">
          <ArrowLeft class="w-4 h-4" />
        </Link>

        <!-- Elementor Pro Branding -->
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-pink-600 to-purple-600 flex items-center justify-center shadow-lg shadow-pink-600/30 text-white font-extrabold text-xs">
            <Layout class="w-4 h-4" />
          </div>
          <div>
            <span class="font-extrabold text-white text-xs tracking-wider uppercase font-display">Elementor Pro</span>
            <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-pink-500/20 text-pink-300 border border-pink-500/30 uppercase">BUILDER</span>
          </div>
        </div>

        <!-- Inline Page Title Editor -->
        <div class="hidden sm:flex items-center space-x-2 pl-4 border-l border-slate-800">
          <input
            v-model="form.title"
            @blur="!form.slug ? autoSlug() : null"
            type="text"
            placeholder="Page Title..."
            class="bg-transparent border-b border-transparent hover:border-slate-700 focus:border-pink-500 text-sm font-bold text-white px-1.5 py-0.5 focus:outline-none transition-colors w-48 lg:w-64"
          />
          <span class="text-xs text-slate-500 font-mono">/page/{{ form.slug || 'slug' }}</span>
        </div>
      </div>

      <!-- Center: Responsive Viewport Switcher -->
      <div class="hidden md:flex items-center space-x-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
        <button
          @click="viewport = 'desktop'"
          :class="[viewport === 'desktop' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded-lg transition-colors']"
          title="Desktop (100%)"
        >
          <Monitor class="w-4 h-4" />
        </button>
        <button
          @click="viewport = 'tablet'"
          :class="[viewport === 'tablet' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded-lg transition-colors']"
          title="Tablet (768px)"
        >
          <Tablet class="w-4 h-4" />
        </button>
        <button
          @click="viewport = 'mobile'"
          :class="[viewport === 'mobile' ? 'bg-pink-600 text-white' : 'text-slate-400 hover:text-white', 'p-1.5 rounded-lg transition-colors']"
          title="Mobile (375px)"
        >
          <Smartphone class="w-4 h-4" />
        </button>
      </div>

      <!-- Right: Action Buttons -->
      <div class="flex items-center space-x-2.5">
        <!-- Switch to WordPress Editor button -->
        <button
          type="button"
          @click="switchToClassic"
          class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold flex items-center space-x-1.5 border border-slate-700 transition-colors"
          title="Switch to WordPress Classic Editor"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
          <span class="hidden sm:inline">WordPress Editor</span>
        </button>

        <a
          v-if="form.slug"
          :href="`/page/${form.slug}`"
          target="_blank"
          class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
        >
          <Eye class="w-3.5 h-3.5" />
          <span>Preview Live</span>
        </a>

        <button
          @click="savePage('draft')"
          :disabled="form.processing"
          class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
        >
          Draft
        </button>

        <button
          @click="savePage('published')"
          :disabled="form.processing"
          class="px-5 py-2 rounded-xl bg-gradient-to-r from-pink-600 via-indigo-600 to-purple-600 hover:from-pink-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-pink-600/30 transition-all flex items-center space-x-1.5 active:scale-95"
        >
          <Save class="w-3.5 h-3.5" />
          <span>{{ form.processing ? 'Saving...' : 'Publish Page' }}</span>
        </button>
      </div>
    </header>

    <!-- Main Workspace: Left Sidebar + Center Visual Canvas -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Left Panel: Widgets Library & Block Inspector -->
      <aside class="w-80 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 overflow-hidden shadow-2xl">
        <!-- Sidebar Tabs -->
        <div class="flex items-center border-b border-slate-800 bg-slate-950/60 p-1">
          <button
            @click="activeSidebarTab = 'widgets'"
            :class="[
              activeSidebarTab === 'widgets' ? 'bg-pink-600 text-white font-bold' : 'text-slate-400 hover:text-white',
              'flex-1 py-2 text-xs rounded-lg text-center transition-colors flex items-center justify-center space-x-1'
            ]"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Widgets</span>
          </button>
          <button
            @click="activeSidebarTab = 'inspector'"
            :class="[
              activeSidebarTab === 'inspector' ? 'bg-pink-600 text-white font-bold' : 'text-slate-400 hover:text-white',
              'flex-1 py-2 text-xs rounded-lg text-center transition-colors flex items-center justify-center space-x-1'
            ]"
          >
            <Edit2 class="w-3.5 h-3.5" />
            <span>Edit Block</span>
          </button>
        </div>

        <!-- Sidebar Content Slot 1: Widgets Gallery -->
        <div v-if="activeSidebarTab === 'widgets'" class="flex-1 overflow-y-auto p-4 space-y-5">
          <!-- Elementor Pro Blocks -->
          <div class="space-y-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-400">Elementor Pro Widgets</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                @click="addBlock('hero')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-pink-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <Sparkles class="w-4 h-4 text-pink-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Hero Banner</span>
              </button>
              <button
                @click="addBlock('pricing')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-pink-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <Tag class="w-4 h-4 text-purple-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Pricing Box</span>
              </button>
              <button
                @click="addBlock('testimonial')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-pink-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <MessageSquare class="w-4 h-4 text-amber-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Testimonials</span>
              </button>
              <button
                @click="addBlock('faq')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-pink-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <HelpCircle class="w-4 h-4 text-emerald-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">FAQ Accordion</span>
              </button>
            </div>
          </div>

          <!-- WooCommerce Widgets (ONLY IF isWooCommerceActive IS TRUE) -->
          <div v-if="isWooCommerceActive" class="space-y-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-400">WooCommerce Commerce</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                @click="addBlock('woo_coupons')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <ShoppingBag class="w-4 h-4 text-purple-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Coupon Box</span>
              </button>
              <button
                @click="addBlock('woo_trust')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <CheckCircle class="w-4 h-4 text-emerald-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Trust Badges</span>
              </button>
            </div>
          </div>

          <!-- WPForms Widgets (ONLY IF isWpFormsActive IS TRUE) -->
          <div v-if="isWpFormsActive" class="space-y-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-sky-400">WPForms Lead Capture</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                @click="addBlock('wpforms_contact')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-sky-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <Send class="w-4 h-4 text-sky-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Contact Form</span>
              </button>
              <button
                @click="addBlock('wpforms_newsletter')"
                class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-sky-500/60 text-left transition-all group flex flex-col justify-between h-20"
              >
                <Sparkles class="w-4 h-4 text-indigo-400 group-hover:scale-110 transition-transform" />
                <span class="font-bold text-white text-[11px]">Newsletter Box</span>
              </button>
            </div>
          </div>

          <!-- Editorial Paragraph Block -->
          <div class="space-y-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">General Elements</span>
            <button
              @click="addBlock('text')"
              class="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 text-left transition-all flex items-center space-x-3"
            >
              <Code class="w-4 h-4 text-slate-400" />
              <div>
                <p class="font-bold text-white text-xs">Editorial Content Card</p>
                <p class="text-[10px] text-slate-400">Rich HTML formatting and text container</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Sidebar Content Slot 2: Selected Block Inspector -->
        <div v-else class="flex-1 overflow-y-auto p-4 space-y-4">
          <div v-if="selectedBlockIndex === null || !blocks[selectedBlockIndex]" class="text-center py-12 text-slate-500 space-y-2">
            <Edit2 class="w-6 h-6 mx-auto opacity-50" />
            <p class="text-xs">Click any block in the canvas to customize its settings.</p>
          </div>

          <div v-else class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
              <span class="text-xs font-bold uppercase tracking-wider text-pink-400">
                Editing: {{ blocks[selectedBlockIndex].type }}
              </span>
              <div class="flex items-center space-x-1">
                <button @click="duplicateBlock(selectedBlockIndex)" class="p-1 text-slate-400 hover:text-white" title="Duplicate">
                  <Copy class="w-3.5 h-3.5" />
                </button>
                <button @click="removeBlock(selectedBlockIndex)" class="p-1 text-slate-400 hover:text-rose-400" title="Delete">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- Hero Settings Form -->
            <div v-if="blocks[selectedBlockIndex].type === 'hero'" class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Badge Tag</label>
                <input v-model="blocks[selectedBlockIndex].badge" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Headline</label>
                <input v-model="blocks[selectedBlockIndex].title" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Subheadline</label>
                <textarea v-model="blocks[selectedBlockIndex].subtitle" rows="3" class="w-full p-2.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[11px] text-slate-400 mb-1">Button Text</label>
                  <input v-model="blocks[selectedBlockIndex].button_text" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
                </div>
                <div>
                  <label class="block text-[11px] text-slate-400 mb-1">Target URL</label>
                  <input v-model="blocks[selectedBlockIndex].button_url" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
                </div>
              </div>
            </div>

            <!-- Pricing Settings Form -->
            <div v-else-if="blocks[selectedBlockIndex].type === 'pricing'" class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Plan Name</label>
                <input v-model="blocks[selectedBlockIndex].plan" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[11px] text-slate-400 mb-1">Price</label>
                  <input v-model="blocks[selectedBlockIndex].price" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
                </div>
                <div>
                  <label class="block text-[11px] text-slate-400 mb-1">Period</label>
                  <input v-model="blocks[selectedBlockIndex].period" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
                </div>
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Features (comma separated)</label>
                <textarea v-model="blocks[selectedBlockIndex].features" rows="3" class="w-full p-2.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
              </div>
            </div>

            <!-- Testimonial Settings Form -->
            <div v-else-if="blocks[selectedBlockIndex].type === 'testimonial'" class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Author Name</label>
                <input v-model="blocks[selectedBlockIndex].author" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Role / Company</label>
                <input v-model="blocks[selectedBlockIndex].role" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Customer Quote</label>
                <textarea v-model="blocks[selectedBlockIndex].quote" rows="3" class="w-full p-2.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
              </div>
            </div>

            <!-- FAQ Settings Form -->
            <div v-else-if="blocks[selectedBlockIndex].type === 'faq'" class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Question</label>
                <input v-model="blocks[selectedBlockIndex].question" type="text" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white" />
              </div>
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Answer</label>
                <textarea v-model="blocks[selectedBlockIndex].answer" rows="3" class="w-full p-2.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"></textarea>
              </div>
            </div>

            <!-- Text Content Settings -->
            <div v-else-if="blocks[selectedBlockIndex].type === 'text'" class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">HTML / Text Content</label>
                <textarea v-model="blocks[selectedBlockIndex].content" rows="6" class="w-full p-2.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white font-mono"></textarea>
              </div>
            </div>

            <div v-else class="text-xs text-slate-400 p-3 rounded-lg bg-slate-950">
              This widget dynamically renders live data from its parent plugin engine.
            </div>
          </div>
        </div>
      </aside>

      <!-- Center Canvas Workspace -->
      <main class="flex-1 bg-slate-950 overflow-y-auto p-4 sm:p-8 flex justify-center">
        <div
          :class="[
            viewport === 'desktop' ? 'w-full max-w-4xl' : viewport === 'tablet' ? 'w-[768px]' : 'w-[375px]',
            'transition-all duration-300 min-h-full space-y-4'
          ]"
        >
          <!-- Canvas Top Notice -->
          <div class="flex items-center justify-between text-[11px] text-slate-400 px-2">
            <span class="flex items-center space-x-1">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Live Elementor Visual Canvas Preview</span>
            </span>
            <span>{{ blocks.length }} Blocks Mounted</span>
          </div>

          <!-- Blocks Container -->
          <div v-if="blocks.length" class="space-y-4">
            <div
              v-for="(b, idx) in blocks"
              :key="idx"
              @click="selectBlock(idx)"
              :class="[
                selectedBlockIndex === idx ? 'ring-2 ring-pink-500 shadow-2xl' : 'hover:border-slate-700',
                'relative group cursor-pointer transition-all rounded-3xl overflow-hidden'
              ]"
            >
              <!-- Floating Block Controls Bar -->
              <div class="absolute top-3 right-3 z-20 flex items-center space-x-1 bg-slate-900/90 border border-slate-700 rounded-lg p-1 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-md">
                <button @click.stop="moveUp(idx)" class="p-1 text-slate-300 hover:text-white" title="Move Up">
                  <MoveUp class="w-3.5 h-3.5" />
                </button>
                <button @click.stop="moveDown(idx)" class="p-1 text-slate-300 hover:text-white" title="Move Down">
                  <MoveDown class="w-3.5 h-3.5" />
                </button>
                <button @click.stop="duplicateBlock(idx)" class="p-1 text-slate-300 hover:text-white" title="Duplicate">
                  <Copy class="w-3.5 h-3.5" />
                </button>
                <button @click.stop="removeBlock(idx)" class="p-1 text-slate-300 hover:text-rose-400" title="Delete">
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>

              <!-- Block Render Preview: Hero -->
              <div v-if="b.type === 'hero'" class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 p-8 sm:p-12 text-center shadow-xl">
                <span class="inline-flex px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider border border-indigo-500/30 mb-3">
                  {{ b.badge }}
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                  {{ b.title }}
                </h2>
                <p class="text-slate-300 text-sm max-w-xl mx-auto mt-3">
                  {{ b.subtitle }}
                </p>
                <div class="mt-6 flex justify-center">
                  <span class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-xs shadow-lg uppercase tracking-wider">
                    {{ b.button_text }} &rarr;
                  </span>
                </div>
              </div>

              <!-- Block Render Preview: Pricing -->
              <div v-else-if="b.type === 'pricing'" class="p-8 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-extrabold uppercase tracking-wider text-purple-400">{{ b.plan }}</span>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-pink-500/20 text-pink-300 border border-pink-500/30">{{ b.badge }}</span>
                </div>
                <div class="flex items-baseline space-x-1">
                  <span class="text-4xl font-extrabold text-white">{{ b.price }}</span>
                  <span class="text-xs text-slate-400">{{ b.period }}</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">{{ b.features }}</p>
                <div class="pt-2">
                  <span class="block w-full py-2.5 rounded-xl bg-purple-600 text-center font-bold text-xs text-white">
                    {{ b.button_text || 'Get Started' }}
                  </span>
                </div>
              </div>

              <!-- Block Render Preview: Testimonial -->
              <div v-else-if="b.type === 'testimonial'" class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 text-left space-y-3">
                <div class="flex text-amber-400 space-x-1 text-sm">
                  <span v-for="i in (b.stars || 5)" :key="i">&starf;</span>
                </div>
                <p class="text-slate-200 text-sm italic">"{{ b.quote }}"</p>
                <div class="flex items-center space-x-2 pt-2 border-t border-slate-800">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-pink-500 to-indigo-500 flex items-center justify-center font-bold text-xs text-white">
                    {{ (b.author || 'U')[0] }}
                  </div>
                  <div>
                    <p class="text-xs font-bold text-white">{{ b.author }}</p>
                    <p class="text-[10px] text-slate-400">{{ b.role }}</p>
                  </div>
                </div>
              </div>

              <!-- Block Render Preview: FAQ -->
              <div v-else-if="b.type === 'faq'" class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 text-left space-y-2">
                <h4 class="text-sm font-bold text-white flex items-center justify-between">
                  <span>{{ b.question }}</span>
                  <span class="text-emerald-400">&plus;</span>
                </h4>
                <p class="text-xs text-slate-300 leading-relaxed">{{ b.answer }}</p>
              </div>

              <!-- Block Render Preview: WooCommerce Coupons -->
              <div v-else-if="b.type === 'woo_coupons'" class="p-6 rounded-3xl bg-gradient-to-br from-purple-950/40 via-slate-900 to-slate-900 border border-purple-500/30 text-left space-y-3">
                <div class="flex items-center space-x-2">
                  <ShoppingBag class="w-5 h-5 text-purple-400" />
                  <span class="font-bold text-white text-sm">WooCommerce Pro Discount Drawer</span>
                </div>
                <div class="flex items-center space-x-2">
                  <input type="text" placeholder="Promo code (e.g. APEX20)" disabled class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-400 flex-1" />
                  <span class="px-3 py-1.5 rounded-xl bg-purple-600 text-white font-bold text-xs">Apply</span>
                </div>
              </div>

              <!-- Block Render Preview: WooCommerce Trust Badges -->
              <div v-else-if="b.type === 'woo_trust'" class="p-4 rounded-3xl bg-slate-900 border border-slate-800 text-center">
                <div class="flex items-center justify-around text-xs text-slate-300">
                  <span class="flex items-center space-x-1 text-emerald-400 font-bold">
                    <CheckCircle class="w-4 h-4" />
                    <span>Free Global Shipping</span>
                  </span>
                  <span class="flex items-center space-x-1 text-indigo-400 font-bold">
                    <ShieldCheck class="w-4 h-4" />
                    <span>256-Bit SSL Checkout</span>
                  </span>
                  <span class="flex items-center space-x-1 text-pink-400 font-bold">
                    <Clock class="w-4 h-4" />
                    <span>30-Day Money Back</span>
                  </span>
                </div>
              </div>

              <!-- Block Render Preview: WPForms Contact -->
              <div v-else-if="b.type === 'wpforms_contact'" class="p-6 rounded-3xl bg-slate-900 border border-sky-500/30 text-left space-y-3">
                <div class="flex items-center space-x-2">
                  <Send class="w-4 h-4 text-sky-400" />
                  <span class="font-bold text-white text-sm">WPForms Lead Capture</span>
                </div>
                <div class="space-y-2">
                  <div class="grid grid-cols-2 gap-2">
                    <input type="text" placeholder="First Name" disabled class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-400" />
                    <input type="email" placeholder="Email Address" disabled class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-400" />
                  </div>
                  <textarea rows="2" placeholder="How can we assist you?" disabled class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-slate-400"></textarea>
                  <span class="inline-block px-4 py-1.5 rounded-xl bg-sky-600 text-white font-bold text-xs">Send Message</span>
                </div>
              </div>

              <!-- Block Render Preview: WPForms Newsletter -->
              <div v-else-if="b.type === 'wpforms_newsletter'" class="p-6 rounded-3xl bg-gradient-to-r from-indigo-950/60 to-purple-950/60 border border-indigo-500/30 text-center space-y-2">
                <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider">VIP Newsletter</span>
                <h4 class="text-lg font-bold text-white">Join 20,000+ Hardware Creators</h4>
                <div class="flex max-w-sm mx-auto space-x-2 pt-2">
                  <input type="email" placeholder="Enter email..." disabled class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-400" />
                  <span class="px-4 py-1.5 rounded-xl bg-indigo-600 text-white font-bold text-xs">Join</span>
                </div>
              </div>

              <!-- Block Render Preview: Editorial Text -->
              <div v-else-if="b.type === 'text'" class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 text-left text-xs text-slate-200" v-html="b.content"></div>
            </div>
          </div>

          <!-- Empty Canvas State -->
          <div v-else class="text-center py-20 bg-slate-900/40 border-2 border-dashed border-slate-800 rounded-3xl p-8 space-y-3">
            <Layout class="w-8 h-8 mx-auto text-slate-600 mb-2" />
            <p class="text-sm font-bold text-white">Your Elementor Canvas is empty</p>
            <p class="text-xs text-slate-400">Click any widget on the left panel to insert visual elements onto this page.</p>
            <button @click="addBlock('hero')" class="px-4 py-2 rounded-xl bg-pink-600 text-white font-bold text-xs inline-flex items-center space-x-1 shadow-lg shadow-pink-600/30">
              <Plus class="w-4 h-4" />
              <span>Add Hero Banner</span>
            </button>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- 2. WORDPRESS CLASSIC PAGE EDITOR MODE (Standard WordPress Editor with Theme / Plugin Support) -->
  <AdminLayout v-else>
    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Breadcrumbs and Action Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
          <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
            <Link href="/admin/pages" class="hover:text-indigo-400 transition-colors">Pages</Link>
            <span>/</span>
            <span class="text-slate-200">{{ isNew ? 'Add New Page' : (form.title || 'Edit Page') }}</span>
          </div>
          <h1 class="text-2xl font-bold text-white tracking-tight">
            {{ isNew ? 'Add New Page' : 'Edit Page' }}
          </h1>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Button to switch to Elementor if Elementor Pro is active -->
          <button
            v-if="isElementorActive"
            type="button"
            @click="switchToElementor"
            class="px-4 py-2 rounded-xl bg-gradient-to-r from-pink-600 via-indigo-600 to-purple-600 hover:from-pink-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-pink-600/30 flex items-center space-x-1.5 transition-all active:scale-95"
          >
            <Layout class="w-4 h-4 text-pink-200" />
            <span>Edit with Elementor Pro</span>
          </button>

          <a
            v-if="form.slug && !isNew"
            :href="`/page/${form.slug}`"
            target="_blank"
            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold flex items-center space-x-1.5 transition-all"
          >
            <Eye class="w-3.5 h-3.5 text-slate-400" />
            <span>Preview Live</span>
          </a>

          <button
            type="button"
            @click="savePage('draft')"
            :disabled="form.processing"
            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-semibold transition-all"
          >
            Save as Draft
          </button>

          <button
            type="button"
            @click="savePage('published')"
            :disabled="form.processing"
            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all active:scale-95"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Saving...' : (isNew ? 'Publish Page' : 'Update Page') }}</span>
          </button>
        </div>
      </div>

      <!-- Inactive Plugin Notice Banner (if content contains Elementor codes but Elementor is deactivated) -->
      <div v-if="!isElementorActive && (form.content.includes('[elementor') || (page.meta && page.meta.builder === 'elementor'))"
           class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <AlertTriangle class="w-5 h-5 text-amber-400 shrink-0" />
          <div>
            <p class="font-bold">Elementor Pro Plugin is currently inactive</p>
            <p class="text-amber-300/80 text-[11px] mt-0.5">
              This page was created with Elementor Pro. You can edit the content here directly in the standard WordPress editor, or re-activate Elementor Pro in Plugins & Hooks to unlock visual drag-and-drop editing.
            </p>
          </div>
        </div>
        <Link href="/admin/plugins" class="shrink-0 px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 font-bold text-xs border border-amber-500/40 transition-colors">
          Manage Plugins &rarr;
        </Link>
      </div>

      <!-- Classic 2-Column WordPress Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Column (Title, Slug, Rich Text Editor, Excerpt) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Title & Permalink -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                Page Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.title"
                @blur="!form.slug ? autoSlug() : null"
                type="text"
                placeholder="Enter page title..."
                class="w-full px-4 py-3.5 rounded-xl bg-slate-950 border border-slate-800 text-lg font-bold text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors shadow-inner"
              />
            </div>

            <!-- Permalink Slug Bar -->
            <div class="pt-2 border-t border-slate-800/80 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400 font-medium">Permalink URL Slug:</span>
                <button type="button" @click="autoSlug" class="text-[11px] text-slate-400 hover:text-indigo-400 flex items-center space-x-1">
                  <span>Auto Generate</span>
                </button>
              </div>
              <div class="flex items-center space-x-2">
                <span class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400 font-mono select-none">
                  /page/
                </span>
                <input
                  v-model="form.slug"
                  type="text"
                  placeholder="custom-page-slug"
                  class="flex-1 px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:outline-none focus:border-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Rich Text Content Editor -->
          <div class="space-y-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                Page Content (Rich Text / WYSIWYG & HTML)
              </label>
              <div class="flex items-center space-x-3">
                <button
                  v-if="isElementorActive"
                  type="button"
                  @click="switchToElementor"
                  class="px-3 py-1 rounded-lg bg-pink-500/20 hover:bg-pink-500/30 text-pink-300 border border-pink-500/40 text-xs font-bold inline-flex items-center space-x-1.5 transition-all shadow-sm"
                  title="Switch to visual drag & drop builder"
                >
                  <Layout class="w-3.5 h-3.5 text-pink-400" />
                  <span>Edit with Elementor Pro</span>
                </button>
                <span class="text-[11px] text-indigo-400 font-medium">Filtered by 'the_content' hook</span>
              </div>
            </div>
            <RichTextEditor
              v-model="form.content"
              upload-url="/admin/posts/upload-image"
              placeholder="Start typing your page content... format headings, bold, bullet lists, quote blocks, code snippets, or upload images directly."
            />
          </div>

          <!-- Excerpt Box -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-3">
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
              Page Excerpt / Meta Summary
            </label>
            <textarea
              v-model="form.excerpt"
              rows="3"
              placeholder="Short summary of this page for search engines and social cards..."
              class="w-full p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 leading-relaxed"
            ></textarea>
          </div>
        </div>

        <!-- Sidebar Column (Document Settings, Attributes, Yoast SEO) -->
        <div class="space-y-6">
          <!-- Status & Publish Card -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center space-x-2">
              <Clock class="w-4 h-4 text-indigo-400" />
              <span>Publish Status</span>
            </h3>

            <div class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Status</label>
                <select
                  v-model="form.status"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 capitalize"
                >
                  <option value="published">Published (Live to public)</option>
                  <option value="draft">Draft (Private admin only)</option>
                </select>
              </div>

              <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <span>Visibility:</span>
                <span class="text-white font-medium capitalize">{{ form.status === 'published' ? 'Public' : 'Private' }}</span>
              </div>
            </div>
          </div>

          <!-- Featured Image Card -->
          <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center space-x-2">
              <ImageIcon class="w-4 h-4 text-pink-400" />
              <span>Featured Image</span>
            </h3>
            <div class="space-y-2">
              <input
                v-model="form.featured_image"
                type="text"
                placeholder="https://images.unsplash.com/..."
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
              />
              <div v-if="form.featured_image" class="mt-2 rounded-xl overflow-hidden border border-slate-800 max-h-40">
                <img :src="form.featured_image" alt="Featured preview" class="w-full h-full object-cover" />
              </div>
            </div>
          </div>

          <!-- Yoast SEO Pro Suite (ONLY IF isYoastSeoActive IS TRUE) -->
          <div v-if="isYoastSeoActive" class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                <Globe class="w-4 h-4 text-emerald-400" />
                <span>Yoast SEO Pro Suite</span>
              </h3>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase">
                Active
              </span>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-[11px] text-slate-400 mb-1">SEO Title</label>
                <input
                  v-model="form.meta.seo_title"
                  type="text"
                  :placeholder="form.title || 'Custom Page Title'"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div>
                <label class="block text-[11px] text-slate-400 mb-1">Meta Description</label>
                <textarea
                  v-model="form.meta.seo_description"
                  rows="2"
                  placeholder="Enter high-ranking Google snippet description..."
                  class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                ></textarea>
              </div>

              <!-- Google SERP Snippet Preview -->
              <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                <div class="text-[10px] text-slate-500 uppercase font-semibold">Google Preview</div>
                <div class="text-indigo-400 text-xs font-semibold hover:underline truncate">
                  {{ form.meta.seo_title || form.title || 'Page Title' }} — Apex CMS
                </div>
                <div class="text-[11px] text-emerald-400 font-mono truncate">
                  https://example.com/page/{{ form.slug || 'slug' }}
                </div>
                <div class="text-[11px] text-slate-400 line-clamp-2">
                  {{ form.meta.seo_description || form.excerpt || 'Discover page details, media, and features.' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
