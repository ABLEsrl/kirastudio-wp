<template>
  <v-container
    class="chat-view-container pa-0 ma-0"
    fluid
  >
    <v-card
      class="chat-shell d-flex flex-column"
    >
      <v-alert
        v-if="!appStore.token"
        type="warning"
        color="warning"
        variant="tonal"
        density="compact"
        class="ma-3"
      >
        {{ t('chat.tokenMissing') }}
      </v-alert>
      <v-alert
        v-if="state.connectionError"
        type="error"
        color="error"
        variant="tonal"
        density="compact"
        class="ma-3"
      >
        {{ state.connectionError }}
      </v-alert>

      <div
        class="d-flex align-center justify-space-between px-4 py-3 border-bottom"
      >
        <v-chip
          :color="connectionStatusColor"
          size="x-small"
          variant="flat"
        >
          {{ connectionStatusText }}
        </v-chip>

        <div
          class="d-flex align-center ga-2"
        >
          <v-btn
            :disabled="state.isLoading || !wssState.isConnected"
            v-tippy="{ theme: 'secondary', content: t('chat.newChat') }"
            color="secondary"
            variant="text"
            class="input-height"
            iconb
            @click="createNewChat"
          >
            <v-icon
              icon="$mdiSquareEditOutline"
              size="24"
            />
          </v-btn>
        </div>
      </div>

      <div
        class="flex-12 min-h-0 overflow-hidden"
      >
        <chat-messages
          :items="state.items"
          :prompt="state.prompt"
          :is-loading="state.isLoading"
          :is-connected="wssState.isConnected && !!appStore.token"
          :audio-active="true"
          :upload-active="true"
          :api-base-url="appStore.apiBaseUrl"
          ref="chatMessagesRef"
          @update:prompt="state.prompt = $event"
          @send-message="sendTextMessages"
          @send-messages="sendMessages"
        />
      </div>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, nextTick, onBeforeMount, onBeforeUnmount, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ChatMessages from '@/components/chat/ChatMessages.vue';
import { useRenderElements } from '@/composables/useRenderElements';
import { useWebsocketMgr } from '@/composables/useWebsocketMgr';
import { useSession } from '@/composables/useSession';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const { t } = useI18n();
const chatMessagesRef = ref(null);
const { renderHtmlElements } = useRenderElements(() => chatMessagesRef.value?.chatElement);
const MODEL_ID = 'gpt-4.1';

const { wssConnect, wssSendMessage, wssKill, wssState } = useWebsocketMgr();
const { persistSession } = useSession();

const state = reactive({
  prompt: '',
  items: [],
  isLoading: false,
  isCreatingChat: false,
  isConnecting: false,
  connectionError: '',
});

const connectionStatusText = computed(() => {
  if (wssState.isConnected) return t('chat.connected');
  if (state.isConnecting) return t('chat.connecting');
  return t('chat.disconnected');
});

const connectionStatusColor = computed(() => {
  if (wssState.isConnected) return 'success';
  if (state.isConnecting) return 'warning';
  return 'error';
});

const selectConversation = async (conversationId) => {
  const res = await fetch(`${appStore.apiBaseUrl}/conversations/select/${conversationId}`, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${appStore.token}`,
      'Content-Type': 'application/json',
    },
  });

  return res.ok;
};

const buildQueryParams = () => {
  const params = new URLSearchParams();

  if (appStore.token) params.set('token', appStore.token);
  if (appStore.sessionId) params.set('session_id', appStore.sessionId);
  if (appStore.loggedUserId) params.set('logged_user_id', String(appStore.loggedUserId));

  return params.toString();
};

const sendInitChatMessage = async () => {
  state.isLoading = true;

  await wssSendMessage(
    JSON.stringify({
      type: 'CREATE_CHAT',
      payload: { model: MODEL_ID },
    })
  );
};

const createNewChat = async () => {
  if (!wssState.isConnected) return;

  state.isCreatingChat = true;
  state.items = [];
  state.prompt = '';
  appStore.conversationId = '';
  persistSession({ conversation_id: '', chat_open: true });
  await sendInitChatMessage();
  await nextTick();
  state.isCreatingChat = false;
};

const sendHistoryMessage = async () => {
  await wssSendMessage(JSON.stringify({ type: 'HISTORY' }));
};

const scrollToEnd = () => {
  chatMessagesRef.value?.scrollToEnd();
};

const concatenateLastAssistantMessage = (message) => {
  const last = state.items[state.items.length - 1];

  if (last?.role === 'assistant') {
    last.message_content.value += message;
  }
};

const onMessage = async (data) => {
  const type = String(data?.type || '').toUpperCase();

  if (type === 'CREATE_CHAT') {
    state.isLoading = false;
    await sendHistoryMessage();
    return;
  }

  if (type === 'HISTORY') {
    const convId = data?.payload?.conversation_id || '';

    if (convId) {
      appStore.conversationId = convId;
      persistSession({ conversation_id: convId, chat_open: true });
    }

    state.items = Array.isArray(data?.payload?.history) ? data.payload.history : [];
    state.isLoading = false;
    await renderHtmlElements();
    scrollToEnd();
    return;
  }

  if (type === 'INIT_DELTA_MSG') {
    if (data?.payload?.message) {
      state.items.push(data.payload.message);
      scrollToEnd();
    }
    return;
  }

  if (type === 'DELTA_MSG') {
    const chunk = data?.payload?.message?.message_content?.value || '';

    if (chunk) {
      concatenateLastAssistantMessage(chunk);
      state.isLoading = true;
      scrollToEnd();
    }
    return;
  }

  if (type === 'MESSAGE' || type === 'END_DELTA_MSG') {
    const incomingMessage = data?.payload?.message;

    if (incomingMessage?.role && incomingMessage.role !== 'user' && type === 'MESSAGE') {
      state.items.push(incomingMessage);
    }

    if (type === 'END_DELTA_MSG' && incomingMessage?.message_content) {
      const last = state.items[state.items.length - 1];

      if (last?.role === 'assistant') {
        last.message_content = incomingMessage.message_content;
      }
    }

    state.isLoading = false;
    await renderHtmlElements();
    scrollToEnd();
    return;
  }

  if (type === 'ERROR') {
    state.isLoading = false;
  }
};

const connectWebsocket = (useHistory = false) => {
  if (!appStore.token || !appStore.wsBaseUrl) return;

  state.isConnecting = true;
  state.connectionError = '';

  wssConnect({
    baseUrl: `${appStore.wsBaseUrl}${appStore.wsVersion}`,
    path: appStore.wsPath,
    queryParams: buildQueryParams(),
    onOpen: async () => {
      state.isConnecting = false;

      if (useHistory) {
        await sendHistoryMessage();
      } else {
        await sendInitChatMessage();
      }
    },
    onMessage,
    onError: () => {
      state.isConnecting = false;
      state.isLoading = false;
      state.connectionError = t('chat.wsError');
    },
    onClose: () => {
      state.isConnecting = false;
    },
  });
};

const sendMessages = async (messages, caption = '') => {
  if (!wssState.isConnected || state.isLoading) return;

  state.isLoading = true;
  const outgoing = [...messages];

  if (caption && caption.trim()) {
    outgoing.unshift({ type: 'text', value: caption.trim() });
  }

  outgoing.forEach((message) => {
    state.items.push({ role: 'user', message_content: message });
  });

  await wssSendMessage(
    JSON.stringify({
      type: 'MESSAGE',
      payload: {
        model: MODEL_ID,
        prompt: outgoing,
        vector_store_ids: [],
      },
    })
  );

  state.prompt = '';
  scrollToEnd();
};

const sendTextMessages = async () => {
  const prompt = state.prompt.trim();

  if (!prompt) return;

  await sendMessages([{ type: 'text', value: prompt }]);
};

onBeforeMount(async () => {
  let useHistory = false;

  if (appStore.conversationId) {
    try {
      useHistory = await selectConversation(appStore.conversationId);
    } catch {
      useHistory = false;
    }

    if (! useHistory) {
      appStore.conversationId = '';
    }
  }

  connectWebsocket(useHistory);
});

onBeforeUnmount(() => {
  wssKill();
});
</script>

<style scoped lang="scss">
.chat-view-container {
  width: 100%;
  height: 100%;
}

.chat-shell {
  width: 100%;
  height: 100%;
  min-height: 0;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}

.input-height {
  min-height: 40px;
}

.border-bottom {
  border-bottom: 1px solid #e2e8f0;
}
</style>
