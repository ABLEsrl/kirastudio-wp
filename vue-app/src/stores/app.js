import { defineStore } from 'pinia';

export const useAppStore = defineStore(
  'app',
  {
    state: () => ({
      token: '',
      title: 'Kira Studio',
      apiBaseUrl: '',
      wsBaseUrl: '',
      wsVersion: '/',
      wsPath: 'plugin',
      conversationId: '',
      chatOpen: false,
      ajaxUrl: '',
      nonce: '',
      sessionId: '',
      loggedUserId: 0,
      panelTop: 0,
      panelLeft: 0,
      panelWidth: 0,
      panelHeight: 0,
    }),
    actions: {
      hydrate(payload) {
        this.token = payload?.token || '';
        this.title = payload?.title || 'Kira Studio';
        this.apiBaseUrl = payload?.apiBaseUrl || '';
        this.wsBaseUrl = payload?.wsBaseUrl || '';
        this.wsVersion = payload?.wsVersion || '/';
        this.wsPath = payload?.wsPath || 'plugin';
        this.conversationId = payload?.conversationId || '';
        this.chatOpen = payload?.chatOpen === true;
        this.ajaxUrl = payload?.ajaxUrl || '';
        this.nonce = payload?.nonce || '';
        this.sessionId = payload?.sessionId || '';
        this.loggedUserId = payload?.loggedUserId || 0;
        this.panelTop = payload?.panelTop || 0;
        this.panelLeft = payload?.panelLeft || 0;
        this.panelWidth = payload?.panelWidth || 0;
        this.panelHeight = payload?.panelHeight || 0;
      },
    },
    persist: true,
  }
);
