import { useAppStore } from '@/stores/app';

export const apiGet = async (path, options = {}) => {
  const appStore = useAppStore();
  const baseUrl = appStore.apiBaseUrl?.replace(/\/$/, '') || '';
  const url = `${baseUrl}/${path.replace(/^\//, '')}`;

  const headers = {
    Authorization: appStore.token ? `Bearer ${appStore.token}` : '',
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };

  const response = await fetch(url, {
    method: 'GET',
    headers,
    ...options,
  });

  if (! response.ok) {
    throw new Error(`GET ${path} failed with status ${response.status}`);
  }

  if (options.responseType === 'arraybuffer') {
    return await response.arrayBuffer();
  }

  return await response.json();
};
