const platform = import.meta.env.VITE_PLATFORM;

const deriveWsBaseUrl = (apiBaseUrl) => {
  if (! apiBaseUrl) {
    return '';
  }

  const normalized = apiBaseUrl.replace(/\/$/, '');

  if (normalized.startsWith('https://')) {
    return `${normalized.replace('https://', 'wss://')}/ws`;
  }

  if (normalized.startsWith('http://')) {
    return `${normalized.replace('http://', 'ws://')}/ws`;
  }

  return `${normalized}/ws`;
};

const normalizeApiBaseUrl = (value) => {
  if (! value) {
    return 'https://kirastudio.it/api';
  }

  const normalized = value.replace(/\/$/, '');

  if (normalized.endsWith('/api')) {
    return normalized;
  }

  return `${normalized}/api`;
};

export const getRuntimeConfig = () => {
  let rawConfig = {};

  if (platform === 'drupal') {
    rawConfig = window.drupalSettings?.kiraStudio || {};
  } else {
    rawConfig = window.kiraStudioConfig || {};
  }

  const envToken      = import.meta.env.VITE_KIRA_TOKEN || '';
  const envApiBaseUrl = import.meta.env.VITE_KIRA_API_BASE_URL || '';
  const envWsBaseUrl  = import.meta.env.VITE_KIRA_WS_BASE_URL || '';
  const envWsVersion  = import.meta.env.VITE_KIRA_WS_VERSION || '';
  const envWsPath     = import.meta.env.VITE_KIRA_WS_PATH || '';

  const token      = rawConfig.token || envToken;
  const apiBaseUrl = normalizeApiBaseUrl(rawConfig.apiBaseUrl || envApiBaseUrl || 'https://kirastudio.it');

  return {
    token,
    title:          rawConfig.title || 'Kira Studio',
    appId:          rawConfig.appId || 'app',
    apiBaseUrl,
    wsBaseUrl:      rawConfig.wsBaseUrl || envWsBaseUrl || deriveWsBaseUrl(apiBaseUrl),
    wsVersion:      rawConfig.wsVersion || envWsVersion || '/',
    wsPath:         rawConfig.wsPath || envWsPath || 'plugin',
    conversationId: rawConfig.conversationId || '',
    chatOpen:       !!rawConfig.chatOpen,
    ajaxUrl:        rawConfig.ajaxUrl || '',
    sessionId:      rawConfig.sessionId || '',
    loggedUserId:   rawConfig.loggedUserId || 0,
    panelTop:       rawConfig.panelTop    || 0,
    panelLeft:      rawConfig.panelLeft   || 0,
    panelWidth:     rawConfig.panelWidth  || 0,
    panelHeight:    rawConfig.panelHeight || 0,
  };
};
