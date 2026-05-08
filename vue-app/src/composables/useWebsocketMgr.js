import { reactive } from 'vue';

export const useWebsocketMgr = () => {
  const state = reactive({
    lastConfig: {},
    pendingMessage: null,
    timeoutPid: 0,
    socket: null,
    isConnected: false,
    kill: false,
  });

  const close = () => {
    if (state.socket && state.socket.readyState !== WebSocket.CLOSED) {
      state.socket.close();
    }
  };

  const deleteState = () => {
    state.socket = null;
    state.isConnected = false;
    state.lastConfig = {};
    state.pendingMessage = null;
    state.timeoutPid = 0;
  };

  const reset = () => {
    close();
    state.socket = null;
    state.isConnected = false;

    if (state.timeoutPid === 0) {
      state.timeoutPid = setTimeout(() => {
        connect(state.lastConfig);
        state.timeoutPid = 0;
      }, 3000);
    }
  };

  const connect = (config) => {
    state.kill = false;
    state.isConnected = false;

    if (! config?.baseUrl) {
      return;
    }

    state.lastConfig = config;
    const fullUrl = `${config.baseUrl.replace(/\/$/, '')}/${config.path}?${config.queryParams || ''}`;

    try {
      state.socket = new WebSocket(fullUrl);
    } catch (error) {
      reset();
      return;
    }

    state.socket.onopen = async () => {
      state.isConnected = true;
      config.onOpen?.();

      if (state.pendingMessage) {
        await sendMessage(state.pendingMessage);
        state.pendingMessage = null;
      }
    };

    state.socket.onclose = () => {
      state.isConnected = false;
      config.onClose?.();

      if (! state.kill) {
        reset();
      }
    };

    state.socket.onerror = (event) => {
      state.isConnected = false;
      config.onError?.(event);

      if (! state.kill) {
        reset();
      }
    };

    state.socket.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        config.onMessage?.(data);
      } catch (error) {
        config.onError?.(error);
      }
    };
  };

  const sendMessage = async (data) => {
    if (! state.socket || ! state.isConnected || state.socket.readyState !== WebSocket.OPEN) {
      state.pendingMessage = data;
      reset();
      return;
    }

    try {
      await state.socket.send(data);
    } catch (error) {
      state.pendingMessage = data;
      reset();
    }
  };

  const kill = () => {
    state.kill = true;

    if (state.timeoutPid > 0) {
      clearTimeout(state.timeoutPid);
    }

    if (state.socket) {
      state.socket.onopen = null;
      state.socket.onclose = null;
      state.socket.onerror = null;
      state.socket.onmessage = null;
    }

    close();
    deleteState();
  };

  return {
    wssConnect: connect,
    wssSendMessage: sendMessage,
    wssKill: kill,
    wssState: state,
  };
};
