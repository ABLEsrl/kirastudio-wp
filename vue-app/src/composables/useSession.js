import { useAppStore } from '@/stores/app';

const platform = import.meta.env.VITE_PLATFORM;

export const useSession = () => {
  const appStore = useAppStore();

  const persistSession = (data) => {
    if (! appStore.ajaxUrl) return;

    if (platform === 'drupal') {
      fetch(appStore.ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': appStore.nonce,
        },
        body: JSON.stringify(data),
      }).catch(() => {});
    } else if (platform === 'standalone') {
      fetch(appStore.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      }).catch(() => {});
    } else {
      if (! appStore.nonce) return;
      const body = new FormData();
      body.append('action', 'kira_studio_save_session');
      body.append('nonce', appStore.nonce);
      body.append('data', JSON.stringify(data));
      fetch(appStore.ajaxUrl, { method: 'POST', body }).catch(() => {});
    }
  };

  return { persistSession };
};
