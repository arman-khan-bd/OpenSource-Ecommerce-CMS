<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import {
  Bold, Italic, Underline, Strikethrough, Heading1, Heading2, Heading3,
  List, ListOrdered, Quote, Code, Link as LinkIcon, Image as ImageIcon,
  Minus, Undo, Redo, RemoveFormatting, AlignLeft, AlignCenter, AlignRight,
  Maximize2, Minimize2, Eye, Code2, Sparkles, UploadCloud, X, Check
} from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Write your story here... format headings, insert rich media, or switch to code view.',
  },
  uploadUrl: {
    type: String,
    default: '/admin/posts/upload-image',
  },
});

const emit = defineEmits(['update:modelValue', 'statsChange']);

const editorRef = ref(null);
const activeMode = ref('visual'); // 'visual', 'code', 'preview'
const isFullscreen = ref(false);
const rawHtml = ref(props.modelValue || '');

// Modals
const showLinkModal = ref(false);
const linkForm = ref({ url: '', text: '', openInNewTab: true });

const showImageModal = ref(false);
const imageTab = ref('upload'); // 'upload' or 'url'
const imageForm = ref({ url: '', alt: '', caption: '' });
const isUploadingImage = ref(false);
const uploadError = ref('');
const fileInputRef = ref(null);

// Saved selection range for restoring after modal opens
let savedRange = null;

function saveSelection() {
  const sel = window.getSelection();
  if (sel && sel.rangeCount > 0) {
    savedRange = sel.getRangeAt(0).cloneRange();
  }
}

function restoreSelection() {
  if (savedRange && editorRef.value) {
    editorRef.value.focus();
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
}

// Stats live computation
const stats = computed(() => {
  const content = activeMode.value === 'code' ? rawHtml.value : (editorRef.value?.innerHTML || rawHtml.value);
  const plainText = content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  const words = plainText ? plainText.split(/\s+/).length : 0;
  const chars = plainText.length;
  const readingTime = Math.max(1, Math.ceil(words / 200));

  return { words, chars, readingTime };
});

watch(stats, (val) => {
  emit('statsChange', val);
}, { immediate: true });

// Sync modelValue changes from outside
watch(() => props.modelValue, (newVal) => {
  if (newVal !== rawHtml.value) {
    rawHtml.value = newVal || '';
    if (editorRef.value && activeMode.value === 'visual' && editorRef.value.innerHTML !== newVal) {
      editorRef.value.innerHTML = newVal || '';
    }
  }
});

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerHTML = props.modelValue || '';
  }
});

function onVisualInput() {
  if (!editorRef.value) return;
  const html = editorRef.value.innerHTML;
  rawHtml.value = html;
  emit('update:modelValue', html);
}

function onCodeInput(e) {
  const val = e.target.value;
  rawHtml.value = val;
  emit('update:modelValue', val);
}

function setMode(mode) {
  if (activeMode.value === 'visual' && editorRef.value) {
    rawHtml.value = editorRef.value.innerHTML;
  }
  activeMode.value = mode;

  nextTick(() => {
    if (mode === 'visual' && editorRef.value) {
      editorRef.value.innerHTML = rawHtml.value;
    }
  });
}

function execCmd(command, value = null) {
  if (activeMode.value !== 'visual') {
    setMode('visual');
  }
  nextTick(() => {
    editorRef.value?.focus();
    document.execCommand(command, false, value);
    onVisualInput();
  });
}

function formatHeading(tag) {
  execCmd('formatBlock', `<${tag}>`);
}

function insertHorizontalRule() {
  execCmd('insertHorizontalRule');
}

function insertCodeBlock() {
  saveSelection();
  const sel = window.getSelection();
  const selectedText = sel ? sel.toString() : '';
  const codeSnippet = selectedText ? selectedText : 'console.log("Hello, OpenSource CMS!");';
  const html = `<pre class="my-4 p-4 rounded-xl bg-slate-950 border border-slate-800 text-sky-400 font-mono text-xs overflow-x-auto"><code>${escapeHtml(codeSnippet)}</code></pre><p><br></p>`;
  insertHtmlAtCursor(html);
}

function escapeHtml(str) {
  return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function insertHtmlAtCursor(html) {
  restoreSelection();
  if (!editorRef.value) return;
  editorRef.value.focus();
  document.execCommand('insertHTML', false, html);
  onVisualInput();
}

// Link Modal Functions
function openLinkModal() {
  saveSelection();
  const sel = window.getSelection();
  linkForm.value = {
    url: 'https://',
    text: sel ? sel.toString() : '',
    openInNewTab: true,
  };
  showLinkModal.value = true;
}

function applyLink() {
  if (!linkForm.value.url) return;
  showLinkModal.value = false;
  restoreSelection();

  const url = linkForm.value.url.trim();
  const text = linkForm.value.text.trim() || url;
  const targetAttr = linkForm.value.openInNewTab ? ' target="_blank" rel="noopener noreferrer"' : '';
  const html = `<a href="${url}" class="text-indigo-400 hover:text-indigo-300 underline font-medium transition-colors"${targetAttr}>${escapeHtml(text)}</a>`;

  insertHtmlAtCursor(html);
}

// Image Modal Functions
function openImageModal() {
  saveSelection();
  imageForm.value = { url: '', alt: '', caption: '' };
  uploadError.value = '';
  showImageModal.value = true;
}

async function handleFileUpload(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  isUploadingImage.value = true;
  uploadError.value = '';

  const formData = new FormData();
  formData.append('image', file);
  formData.append('alt_text', imageForm.value.alt || file.name);

  try {
    const res = await fetch(props.uploadUrl, {
      method: 'POST',
      body: formData,
    });
    const data = await res.json();
    if (data.success && data.url) {
      imageForm.value.url = data.url;
      if (!imageForm.value.alt) {
        imageForm.value.alt = file.name.replace(/\.[^/.]+$/, "");
      }
    } else {
      uploadError.value = data.message || 'Image upload failed.';
    }
  } catch (err) {
    uploadError.value = 'Failed to upload image to server.';
  } finally {
    isUploadingImage.value = false;
  }
}

function applyImage() {
  if (!imageForm.value.url) return;
  showImageModal.value = false;
  restoreSelection();

  const url = imageForm.value.url.trim();
  const alt = escapeHtml(imageForm.value.alt.trim() || 'Post image');
  const caption = imageForm.value.caption.trim();

  let html = '';
  if (caption) {
    html = `<figure class="my-6 block text-center"><img src="${url}" alt="${alt}" class="rounded-xl border border-slate-800 shadow-xl max-w-full mx-auto" /><figcaption class="text-xs text-slate-400 mt-2 italic">${escapeHtml(caption)}</figcaption></figure><p><br></p>`;
  } else {
    html = `<p class="my-4"><img src="${url}" alt="${alt}" class="rounded-xl border border-slate-800 shadow-xl max-w-full" /></p><p><br></p>`;
  }

  insertHtmlAtCursor(html);
}

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value;
}
</script>

<template>
  <div :class="[
    'rich-text-editor rounded-2xl bg-slate-900/90 border border-slate-800 shadow-2xl transition-all duration-200 flex flex-col',
    isFullscreen ? 'fixed inset-4 z-50 overflow-hidden shadow-2xl ring-1 ring-slate-700 bg-slate-950' : 'relative'
  ]">
    <!-- Top Toolbar Header -->
    <div class="flex flex-wrap items-center justify-between gap-2 p-3 bg-slate-950/70 border-b border-slate-800 rounded-t-2xl">
      <!-- Formatting Buttons Group -->
      <div class="flex flex-wrap items-center gap-1">
        <!-- Heading Selector -->
        <div class="flex items-center space-x-0.5 bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
          <button type="button" @click="formatHeading('p')" title="Paragraph"
                  class="px-2 py-1 text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            P
          </button>
          <button type="button" @click="formatHeading('h1')" title="Heading 1"
                  class="px-2 py-1 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            H1
          </button>
          <button type="button" @click="formatHeading('h2')" title="Heading 2"
                  class="px-2 py-1 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            H2
          </button>
          <button type="button" @click="formatHeading('h3')" title="Heading 3"
                  class="px-2 py-1 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            H3
          </button>
        </div>

        <div class="h-5 w-px bg-slate-800 mx-1"></div>

        <!-- Inline Styles -->
        <div class="flex items-center space-x-0.5 bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
          <button type="button" @click="execCmd('bold')" title="Bold (Ctrl+B)"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Bold class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('italic')" title="Italic (Ctrl+I)"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Italic class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('underline')" title="Underline (Ctrl+U)"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Underline class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('strikeThrough')" title="Strikethrough"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Strikethrough class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="h-5 w-px bg-slate-800 mx-1"></div>

        <!-- Alignment -->
        <div class="flex items-center space-x-0.5 bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
          <button type="button" @click="execCmd('justifyLeft')" title="Align Left"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <AlignLeft class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('justifyCenter')" title="Align Center"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <AlignCenter class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('justifyRight')" title="Align Right"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <AlignRight class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="h-5 w-px bg-slate-800 mx-1"></div>

        <!-- Lists & Quotes -->
        <div class="flex items-center space-x-0.5 bg-slate-900/90 rounded-lg p-0.5 border border-slate-800">
          <button type="button" @click="execCmd('insertUnorderedList')" title="Bulleted List"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <List class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('insertOrderedList')" title="Numbered List"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <ListOrdered class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="formatHeading('blockquote')" title="Blockquote"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Quote class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="insertCodeBlock" title="Code Block"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Code class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="insertHorizontalRule" title="Horizontal Divider"
                  class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Minus class="w-3.5 h-3.5" />
          </button>
        </div>

        <div class="h-5 w-px bg-slate-800 mx-1"></div>

        <!-- Media & Links -->
        <div class="flex items-center space-x-1">
          <button type="button" @click="openLinkModal" title="Insert Link"
                  class="px-2.5 py-1.5 text-xs font-medium text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 rounded-lg flex items-center space-x-1 transition-colors">
            <LinkIcon class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Link</span>
          </button>

          <button type="button" @click="openImageModal" title="Upload or Insert Image"
                  class="px-2.5 py-1.5 text-xs font-semibold text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 rounded-lg flex items-center space-x-1.5 transition-colors">
            <ImageIcon class="w-3.5 h-3.5" />
            <span>Upload Image</span>
          </button>
        </div>

        <div class="h-5 w-px bg-slate-800 mx-1"></div>

        <!-- History & Clean -->
        <div class="flex items-center space-x-0.5">
          <button type="button" @click="execCmd('undo')" title="Undo"
                  class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Undo class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('redo')" title="Redo"
                  class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <Redo class="w-3.5 h-3.5" />
          </button>
          <button type="button" @click="execCmd('removeFormat')" title="Clear Formatting"
                  class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded transition-colors">
            <RemoveFormatting class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Right Controls: View Switcher & Fullscreen -->
      <div class="flex items-center space-x-2">
        <!-- View Mode Switcher -->
        <div class="flex items-center bg-slate-900 rounded-xl p-0.5 border border-slate-800 text-xs">
          <button type="button" @click="setMode('visual')"
                  :class="['px-2.5 py-1 rounded-lg font-medium transition-all flex items-center space-x-1',
                           activeMode === 'visual' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']">
            <span>Visual</span>
          </button>
          <button type="button" @click="setMode('code')"
                  :class="['px-2.5 py-1 rounded-lg font-medium transition-all flex items-center space-x-1',
                           activeMode === 'code' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']">
            <Code2 class="w-3 h-3" />
            <span>HTML</span>
          </button>
          <button type="button" @click="setMode('preview')"
                  :class="['px-2.5 py-1 rounded-lg font-medium transition-all flex items-center space-x-1',
                           activeMode === 'preview' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white']">
            <Eye class="w-3 h-3" />
            <span>Preview</span>
          </button>
        </div>

        <!-- Fullscreen -->
        <button type="button" @click="toggleFullscreen" :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen Distraction Free'"
                class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-colors">
          <Minimize2 v-if="isFullscreen" class="w-3.5 h-3.5 text-indigo-400" />
          <Maximize2 v-else class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Editor Body Canvas -->
    <div class="relative flex-1 min-h-[380px] flex flex-col bg-slate-950/60 overflow-y-auto">
      <!-- 1. Visual WYSIWYG Mode -->
      <div v-show="activeMode === 'visual'"
           ref="editorRef"
           contenteditable="true"
           @input="onVisualInput"
           @blur="onVisualInput"
           :data-placeholder="placeholder"
           class="editor-content flex-1 p-6 text-slate-200 text-sm leading-relaxed focus:outline-none min-h-[380px] prose prose-invert max-w-none">
      </div>

      <!-- 2. Raw HTML / Code Mode -->
      <div v-show="activeMode === 'code'" class="flex-1 p-4 bg-slate-950 flex flex-col min-h-[380px]">
        <textarea :value="rawHtml"
                  @input="onCodeInput"
                  rows="18"
                  placeholder="<h2>Type or paste raw HTML code here...</h2>"
                  class="w-full flex-1 p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-sky-300 font-mono leading-relaxed focus:outline-none focus:border-indigo-500 resize-y"></textarea>
      </div>

      <!-- 3. Live Preview Mode -->
      <div v-if="activeMode === 'preview'" class="flex-1 p-8 bg-slate-950/90 text-sm text-slate-200 leading-relaxed min-h-[380px] prose prose-invert max-w-none">
        <div v-if="rawHtml" v-html="rawHtml"></div>
        <div v-else class="text-slate-500 italic text-center py-12">
          No content written yet. Switch to Visual mode to start composing.
        </div>
      </div>
    </div>

    <!-- Bottom Status / Metrics Footer -->
    <div class="flex items-center justify-between px-4 py-2 bg-slate-950/90 border-t border-slate-800/80 rounded-b-2xl text-[11px] text-slate-400">
      <div class="flex items-center space-x-3">
        <span class="flex items-center space-x-1 text-slate-300 font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>{{ stats.words }} words</span>
        </span>
        <span>•</span>
        <span>{{ stats.chars }} characters</span>
        <span>•</span>
        <span class="text-indigo-400 font-medium">~{{ stats.readingTime }} min read</span>
      </div>

      <div class="flex items-center space-x-2 text-slate-500">
        <span class="hidden sm:inline">Images saved to public_html/uploads</span>
        <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 uppercase font-mono text-[10px] text-slate-400">
          {{ activeMode }}
        </span>
      </div>
    </div>

    <!-- Link Modal Popup -->
    <div v-if="showLinkModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2">
            <LinkIcon class="w-4 h-4 text-indigo-400" />
            <span>Insert Link</span>
          </h3>
          <button type="button" @click="showLinkModal = false" class="text-slate-400 hover:text-white">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Destination URL</label>
            <input v-model="linkForm.url" type="url" placeholder="https://example.com"
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Display Text (Optional)</label>
            <input v-model="linkForm.text" type="text" placeholder="Click here..."
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500" />
          </div>

          <label class="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer pt-1">
            <input v-model="linkForm.openInNewTab" type="checkbox" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0" />
            <span>Open link in new browser tab</span>
          </label>
        </div>

        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
          <button type="button" @click="showLinkModal = false" class="px-3 py-1.5 text-xs text-slate-400 hover:text-white">
            Cancel
          </button>
          <button type="button" @click="applyLink" class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">
            Insert Link
          </button>
        </div>
      </div>
    </div>

    <!-- Image Upload & Insert Modal -->
    <div v-if="showImageModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-sm font-bold text-white flex items-center space-x-2">
            <ImageIcon class="w-4 h-4 text-emerald-400" />
            <span>Insert Image into Content</span>
          </h3>
          <button type="button" @click="showImageModal = false" class="text-slate-400 hover:text-white">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Image Tabs: Upload vs URL -->
        <div class="flex border-b border-slate-800 space-x-4 text-xs font-medium pb-2">
          <button type="button" @click="imageTab = 'upload'"
                  :class="[imageTab === 'upload' ? 'text-emerald-400 border-b-2 border-emerald-500 pb-1' : 'text-slate-400 hover:text-white']">
            Upload from Computer
          </button>
          <button type="button" @click="imageTab = 'url'"
                  :class="[imageTab === 'url' ? 'text-emerald-400 border-b-2 border-emerald-500 pb-1' : 'text-slate-400 hover:text-white']">
            Enter Web URL
          </button>
        </div>

        <div v-if="imageTab === 'upload'" class="space-y-4">
          <!-- Drop Area -->
          <div @click="$refs.fileInputRef.click()"
               class="border-2 border-dashed border-slate-700 hover:border-emerald-500/70 rounded-2xl p-6 text-center cursor-pointer transition-colors bg-slate-950/60">
            <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleFileUpload" />
            <div class="flex flex-col items-center space-y-2">
              <UploadCloud class="w-8 h-8 text-emerald-400" />
              <div class="text-xs font-semibold text-white">Click to select image or drag here</div>
              <div class="text-[11px] text-slate-500">Supports JPG, PNG, WebP, GIF, SVG (Saved to public_html/uploads)</div>
            </div>
          </div>

          <div v-if="isUploadingImage" class="text-xs text-center text-emerald-400 animate-pulse font-medium">
            Uploading image to server...
          </div>
          <div v-if="uploadError" class="text-xs text-rose-400 text-center font-medium">
            {{ uploadError }}
          </div>

          <div v-if="imageForm.url" class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center space-x-3">
            <img :src="imageForm.url" alt="Preview" class="w-12 h-12 rounded-lg object-cover border border-slate-800" />
            <div class="flex-1 truncate">
              <div class="text-xs text-emerald-400 font-semibold flex items-center space-x-1">
                <Check class="w-3.5 h-3.5" />
                <span>Uploaded successfully</span>
              </div>
              <div class="text-[11px] text-slate-400 font-mono truncate">{{ imageForm.url }}</div>
            </div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Image URL</label>
            <input v-model="imageForm.url" type="url" placeholder="https://example.com/photo.jpg or /uploads/..."
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500" />
          </div>
        </div>

        <!-- Alt Text & Caption -->
        <div class="space-y-3 pt-2">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Alt Text (Accessibility & SEO)</label>
            <input v-model="imageForm.alt" type="text" placeholder="Descriptive image alternative text..."
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500" />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Optional Caption</label>
            <input v-model="imageForm.caption" type="text" placeholder="Displays beneath image in an italicized caption..."
                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-emerald-500" />
          </div>
        </div>

        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
          <button type="button" @click="showImageModal = false" class="px-3 py-1.5 text-xs text-slate-400 hover:text-white">
            Cancel
          </button>
          <button type="button" @click="applyImage" :disabled="!imageForm.url || isUploadingImage"
                  class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold transition-all">
            Insert Image
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.editor-content:empty:before {
  content: attr(data-placeholder);
  color: #64748b;
  font-style: italic;
  pointer-events: none;
}

:deep(.editor-content h1) {
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
  margin-top: 1.25rem;
  margin-bottom: 0.5rem;
}

:deep(.editor-content h2) {
  font-size: 1.35rem;
  font-weight: 700;
  color: #f8fafc;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

:deep(.editor-content h3) {
  font-size: 1.15rem;
  font-weight: 600;
  color: #f1f5f9;
  margin-top: 0.75rem;
  margin-bottom: 0.35rem;
}

:deep(.editor-content p) {
  margin-bottom: 0.75rem;
  line-height: 1.7;
}

:deep(.editor-content blockquote) {
  border-left: 4px solid #6366f1;
  padding-left: 1rem;
  margin: 1rem 0;
  color: #94a3b8;
  font-style: italic;
  background: rgba(99, 102, 241, 0.05);
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border-radius: 0 0.5rem 0.5rem 0;
}

:deep(.editor-content ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}

:deep(.editor-content ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}

:deep(.editor-content hr) {
  border: 0;
  border-top: 1px solid #334155;
  margin: 1.5rem 0;
}

:deep(.editor-content a) {
  color: #818cf8;
  text-decoration: underline;
}

:deep(.editor-content img) {
  max-width: 100%;
  border-radius: 0.75rem;
  margin: 1rem 0;
}
</style>
