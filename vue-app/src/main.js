import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import VueTippy from 'vue-tippy';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';
import '@/assets/style/main.scss';
import App from '@/App.vue';
import i18n from '@/plugins/i18n';
import vuetify from '@/plugins/vuetify';
import { useAppStore } from '@/stores/app';
import { getRuntimeConfig } from '@/utils/runtimeConfig';

const bootstrap = () => {
  const tippyConfig = {
    directive: 'tippy',
    component: 'tippy',
    componentSingleton: 'tippy-singleton',
    defaultProps: {
      theme: 'light',
      touch: 'hold',
      arrow: true,
    },
    onShow: (instance) => !!instance?.props?.content,
  };

  const runtimeConfig = getRuntimeConfig();
  const mountId = runtimeConfig.appId || 'app';
  const mountTarget = mountId ? document.getElementById(mountId) : null;

  if (! mountTarget) {
    return;
  }

  const app = createApp(App);
  const pinia = createPinia();

  pinia.use(piniaPluginPersistedstate);
  app.use(pinia);
  app.use(i18n);
  app.use(vuetify);
  app.use(VueTippy, tippyConfig);

  const appStore = useAppStore(pinia);
  appStore.hydrate(runtimeConfig);

  app.mount(mountTarget);
};

bootstrap();
