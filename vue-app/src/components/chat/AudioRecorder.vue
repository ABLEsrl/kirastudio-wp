<template>
  <div
    class="audio-recorder d-flex align-center w-100"
  >
    <v-btn
      size="small"
      class="audio-recorder__cancel"
      icon
      @click="cancelRecording()"
    >
      <v-icon
        icon="$mdiClose"
        size="20"
      />
    </v-btn>

    <div
      class="audio-recorder__center flex-1 d-flex align-center justify-center ga-3"
    >
      <div
        class="volume-bars text-success"
      >
        <div
          v-for="i in 12"
          :key="i"
          :style="{ height: getBarHeight(i) }"
          class="bar"
        />
      </div>
      <span
        class="text-body-2 text-medium-emphasis"
      >
        {{ t('chat.recorderListening') }}
      </span>
    </div>

    <v-btn
      color="secondary"
      variant="tonal"
      size="small"
      class="chat-send-btn"
      icon
      @click="sendRecording()"
    >
      <v-icon
        icon="$mdiArrowUp"
        size="20"
      />
    </v-btn>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive } from 'vue';
import { useI18n } from 'vue-i18n';

const emit = defineEmits(['recorded', 'cancelled']);
const { t } = useI18n();

const state = reactive({
  isRecording: false,
  duration: 0,
  volumeLevels: Array(12).fill(0),
  mediaRecorder: null,
  audioChunks: [],
  audioContext: null,
  analyser: null,
  microphone: null,
  animationId: null,
  durationInterval: null,
});

const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      audio: { echoCancellation: true, noiseSuppression: true },
    });

    state.mediaRecorder = new MediaRecorder(stream);
    state.audioChunks = [];

    state.mediaRecorder.ondataavailable = (event) => {
      state.audioChunks.push(event.data);
    };

    state.audioContext = new (window.AudioContext || window.webkitAudioContext)();

    if (state.audioContext.state === 'suspended') {
      await state.audioContext.resume();
    }

    state.analyser = state.audioContext.createAnalyser();
    state.microphone = state.audioContext.createMediaStreamSource(stream);
    state.analyser.fftSize = 512;
    state.analyser.smoothingTimeConstant = 0.6;
    state.analyser.minDecibels = -90;
    state.analyser.maxDecibels = -10;
    state.microphone.connect(state.analyser);

    state.mediaRecorder.start();
    state.isRecording = true;
    state.duration = 0;
    state.durationInterval = setInterval(() => {
      state.duration += 1;
    }, 1000);

    visualizeVolume();
  } catch (error) {
    emit('cancelled');
  }
};

const visualizeVolume = () => {
  const dataArray = new Uint8Array(state.analyser.frequencyBinCount);

  const updateBars = () => {
    if (!state.isRecording) return;

    state.analyser.getByteFrequencyData(dataArray);

    const barCount = 12;
    const usableFrequencies = Math.min(120, dataArray.length);
    const chunkSize = Math.floor(usableFrequencies / barCount);
    const newLevels = [];

    for (let i = 0; i < barCount; i++) {
      const start = i * chunkSize;
      const end = start + chunkSize;
      let sum = 0;

      for (let j = start; j < end; j++) {
        sum += dataArray[j];
      }

      const average = sum / chunkSize;
      const boosted = (average / 255) * 5;
      const normalized = Math.min(Math.max(boosted, 0.05), 1);
      newLevels.push(normalized);
    }

    state.volumeLevels = newLevels;
    state.animationId = requestAnimationFrame(updateBars);
  };

  updateBars();
};

const getBarHeight = (index) => {
  const level = state.volumeLevels[index - 1] || 0;
  const minHeight = 3;
  const maxHeight = 14;
  const height = minHeight + level * (maxHeight - minHeight);
  return `${height}px`;
};

const sendRecording = () => {
  if (!state.mediaRecorder || state.mediaRecorder.state === 'inactive') return;

  state.mediaRecorder.onstop = () => {
    const audioBlob = new Blob(state.audioChunks, { type: 'audio/webm' });

    if (state.audioChunks.length === 0 || audioBlob.size === 0 || state.duration === 0) {
      cleanup();
      emit('cancelled');
      return;
    }

    emit('recorded', {
      blob: audioBlob,
      duration: state.duration,
    });

    cleanup();
  };

  state.mediaRecorder.stop();
  state.isRecording = false;
};

const cancelRecording = () => {
  if (state.mediaRecorder && state.mediaRecorder.state !== 'inactive') {
    state.mediaRecorder.onstop = () => cleanup();
    state.mediaRecorder.stop();
  }

  state.isRecording = false;
  cleanup();
  emit('cancelled');
};

const cleanup = () => {
  if (state.durationInterval) {
    clearInterval(state.durationInterval);
    state.durationInterval = null;
  }

  if (state.animationId) {
    cancelAnimationFrame(state.animationId);
    state.animationId = null;
  }

  if (state.microphone) {
    state.microphone.disconnect();
    state.microphone = null;
  }

  if (state.audioContext) {
    state.audioContext.close();
    state.audioContext = null;
  }

  if (state.mediaRecorder && state.mediaRecorder.stream) {
    state.mediaRecorder.stream.getTracks().forEach((track) => track.stop());
  }

  state.analyser = null;
  state.mediaRecorder = null;
  state.audioChunks = [];
  state.volumeLevels = Array(12).fill(0);
  state.duration = 0;
};

onMounted(startRecording);
onBeforeUnmount(cleanup);
</script>

<style lang="scss" scoped>
.audio-recorder {
  padding: 0 4px;
  min-height: 40px;
}

.audio-recorder__center {
  min-height: 32px;
}

.audio-recorder__cancel {
  box-shadow: none !important;
}

.volume-bars {
  display: flex;
  align-items: center;
  gap: 3px;
  height: 20px;
}

.bar {
  width: 3px;
  background: currentColor;
  opacity: 0.4;
  border-radius: 2px;
  transition: height 0.1s ease;
  min-height: 3px;
}

.chat-send-btn {
  border-radius: 50% !important;
  box-shadow: none !important;
  min-width: 32px !important;
  min-height: 32px !important;
}
</style>
