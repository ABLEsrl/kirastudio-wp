import { useAppStore } from '@/stores/app';

const platform = import.meta.env.VITE_PLATFORM;

export const useSession = () => {
  const appStore = useAppStore();

  const persistSession = (data) => {
    if (! appStore.ajaxUrl) return;

    if (platform === 'drupal' || platform === 'standalone') {
      fetch(appStore.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      }).catch(() => {});
    } else {
      const body = new FormData();
      body.append('action', 'ablekist_save_session');
      body.append('data', JSON.stringify(data));
      fetch(appStore.ajaxUrl, { method: 'POST', body }).catch(() => {});
    }
  };

  return { persistSession };
};
