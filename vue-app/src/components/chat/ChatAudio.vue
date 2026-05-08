<template>
  <div
    class="chat-audio-player audio-player d-flex align-center px-1 border rounded relative overhidden"
  >
    <audio
      :src="src"
      ref="audioEl"
      preload="auto"
      class="d-none"
      @loadeddata="loaded"
      @ended="stop"
      @canplaythrough="state.valid = true"
      @error="onError"
      @timeupdate="onTimeUpdate"
    />
    <div
      v-if="state.valid && !state.loader"
      class="d-flex align-center w-100"
    >
      <div
        class="d-flex align-center justify-space-between z-2 relative w-100"
      >
        <div
          class="d-flex align-center flex-shrink-0 min-50"
        >
          <v-btn
            :color="state.playing ? 'warning' : 'success'"
            variant="text"
            class="pa-0"
            icon
            @click.stop="playPause"
          >
            <v-icon
              :icon="!state.playing ? '$mdiPlay' : '$mdiPause'"
              size="22"
            />
          </v-btn>
          <v-btn
            :class="{ invisible: state.seconds <= 0 && !state.playing }"
            color="error"
            variant="text"
            class="pa-0"
            icon
            @click.stop="stop"
          >
            <v-icon
              icon="$mdiStop"
              size="22"
            />
          </v-btn>
        </div>

        <div
          ref="waveformContainer"
          class="flex-1 position-relative"
          style="min-width: 0;"
        >
          <canvas
            :width="canvasWidth"
            :height="35"
            ref="waveformCanvas"
            class="cursor-pointer d-block"
            style="width: 100%; height: 35px;"
            @click="seekToPosition"
          />
        </div>

        <div
          v-if="state.seconds > 0 || state.playing"
          class="ma-1 lh-1 text-center fs-12 flex-shrink-0 min-50"
        >
          {{ milliSecondsToDuration(state.seconds) }}
        </div>
        <div
          v-else
          class="ma-1 lh-1 text-center fs-12 flex-shrink-0 min-50"
        >
          {{ milliSecondsToDuration(state.duration * 1000) }}
        </div>
      </div>
    </div>
    <div
      v-if="!state.valid && !state.loader"
      class="text-error d-flex align-center justify-center w-100"
    >
      Invalid audio file
    </div>
    <v-progress-linear
      v-if="state.loader"
      color="primary"
      height="3"
      indeterminate rounded
    />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { milliSecondsToDuration } from '@/utils/date';
import { useFileManager } from '@/utils/fileManager';

const { isBase64 } = useFileManager();

defineProps({
  src: {
    type: String,
    default: null,
  },
});

const audioEl = ref(null);
const waveformCanvas = ref(null);
const waveformContainer = ref(null);
const containerWidth = ref(0);

const state = reactive({
  playing: false,
  duration: 0,
  seconds: 0,
  loader: true,
  valid: true,
  waveformData: [],
});

const drawWaveform = () => {
  const canvas = waveformCanvas.value;
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const width = canvas.width;
  const height = canvas.height;
  const barWidth = 2;
  const gap = 1;
  const totalBarWidth = barWidth + gap;
  const numBars = Math.floor(width / totalBarWidth);

  ctx.clearRect(0, 0, width, height);

  const progress = state.duration > 0 ? state.seconds / 1000 / state.duration : 0;

  for (let i = 0; i < numBars; i++) {
    const dataIndex = Math.floor((i / numBars) * state.waveformData.length);
    const barHeight = (state.waveformData[dataIndex] || 0.3) * (height - 10);
    const x = i * totalBarWidth;
    const y = (height - barHeight) / 2;
    ctx.fillStyle = i / numBars < progress ? 'rgba(0, 177, 232, 0.8)' : 'rgba(0, 177, 232, 0.25)';
    ctx.fillRect(x, y, barWidth, barHeight);
  }
};

const generateWaveform = async () => {
  try {
    const response = await fetch(audioEl.value.src);
    const arrayBuffer = await response.arrayBuffer();
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
    const rawData = audioBuffer.getChannelData(0);
    const samples = 50;
    const blockSize = Math.floor(rawData.length / samples);
    const waveformData = [];

    for (let i = 0; i < samples; i++) {
      let sum = 0;
      for (let j = 0; j < blockSize; j++) {
        sum += Math.abs(rawData[i * blockSize + j]);
      }
      waveformData.push(sum / blockSize);
    }

    const max = Math.max(...waveformData);
    state.waveformData = waveformData.map((value) => value / max);
    nextTick(() => drawWaveform());
  } catch (error) {
    state.waveformData = Array.from({ length: 50 }, () => Math.random() * 0.6 + 0.2);
    nextTick(() => drawWaveform());
  }
};

const playPause = () => {
  if (!state.playing) {
    audioEl.value.play();
    state.playing = true;
    return;
  }

  audioEl.value.pause();
  state.playing = false;
};

const stop = () => {
  state.playing = false;
  state.seconds = 0;
  audioEl.value.currentTime = 0;
  audioEl.value.pause();
  drawWaveform();
};

const loaded = () => {
  state.duration = parseFloat(audioEl.value.duration || 0);
  state.loader = false;
  generateWaveform();
};

const onTimeUpdate = () => {
  state.seconds = audioEl.value.currentTime * 1000;
  drawWaveform();
};

const onError = () => {
  state.loader = false;
  if (isBase64(audioEl.value.src)) return;
  state.valid = false;
};

const seekToPosition = (event) => {
  const canvas = waveformCanvas.value;
  if (!canvas || !audioEl.value) return;

  const rect = canvas.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const percentage = Math.max(0, Math.min(1, x / rect.width));
  const newTime = percentage * state.duration;
  audioEl.value.currentTime = newTime;
  state.seconds = newTime * 1000;
  drawWaveform();
};

const updateCanvasWidth = () => {
  if (waveformContainer.value) {
    containerWidth.value = waveformContainer.value.offsetWidth || 100;
    nextTick(() => drawWaveform());
  }
};

const canvasWidth = computed(() => containerWidth.value || 175);

onMounted(() => {
  updateCanvasWidth();
  window.addEventListener('resize', updateCanvasWidth);
});

onUnmounted(() => {
  window.removeEventListener('resize', updateCanvasWidth);
});

watch(
  () => audioEl.value?.src,
  () => {
    state.loader = true;
    state.waveformData = [];
  }
);
</script>
