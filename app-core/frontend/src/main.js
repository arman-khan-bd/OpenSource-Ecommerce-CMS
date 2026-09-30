import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { MotionPlugin } from '@vueuse/motion';
import './css/app.css';

// Ensure initialPage is always properly parsed across Inertia 1.x and 2.x formats
let initialPage = undefined;
const scriptEl = document.querySelector('script[data-page="app"][type="application/json"]');
if (scriptEl && scriptEl.textContent) {
  try {
    initialPage = JSON.parse(scriptEl.textContent);
  } catch (e) {
    console.error('Failed to parse script data-page JSON:', e);
  }
}

if (!initialPage) {
  const el = document.getElementById('app');
  if (el && el.dataset && el.dataset.page) {
    try {
      initialPage = JSON.parse(el.dataset.page);
    } catch (e) {
      console.error('Failed to parse div data-page attribute:', e);
    }
  }
}

createInertiaApp({
  page: initialPage,
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    const normalizedName = name.replace(/\\/g, '/');
    const page = pages[`./Pages/${normalizedName}.vue`];
    if (!page) {
      throw new Error(`Page component "${normalizedName}" not found in Pages directory.`);
    }
    return page.default;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(MotionPlugin)
      .mount(el);
  },
});

