<template>
  <div
    class="chat-main-area flex-2 d-flex flex-column justify-space-between overflow-hidden"
    @dragover.prevent
    @drop.prevent="onDropFiles"
  >
    <div
      ref="chatElement"
      class="flex-12 overflow-hidden"
    >
      <chat-block
        :items="items"
        :api-base-url="apiBaseUrl"
        ref="chatBlockRef"
      />
    </div>

    <div
      :class="{ 'py-1 rounded-lg': isRecordingAudio }"
      class="chat-input-card mx-5 mb-4 mt-4 align-self-stretch"
    >
      <audio-recorder
        v-if="isRecordingAudio"
        @recorded="sendAudioMessage"
        @cancelled="isRecordingAudio = false"
      />

      <template
        v-else
      >
        <div
          v-if="attachments.length"
          class="chat-attachments d-flex ga-2 pa-3 pb-0"
        >
          <div
            v-for="(file, index) in attachments"
            :key="file.id"
            class="chat-attachment-item"
          >
            <img
              v-if="file.type === 'image'"
              :src="file.base64"
              class="chat-attachment-thumb"
            />
            <div
              v-else
              class="chat-attachment-file d-flex align-center ga-1 px-2"
            >
              <v-icon
                icon="$mdiFileOutline"
                size="16"
              />
              <span
                class="text-caption text-truncate"
              >
                {{ file.name }}
              </span>
            </div>
            <v-btn
              icon="$mdiClose"
              size="x-small"
              variant="flat"
              color="error"
              rounded="pill"
              class="chat-attachment-remove"
              @click="removeAttachment(index)"
            />
          </div>
        </div>

        <v-textarea
          :model-value="prompt"
          :disabled="isLoading || !isConnected"
          :placeholder="t('chat.placeholder')"
          class="w-100 chat-input-textarea"
          rows="1"
          variant="plain"
          auto-grow
          hide-details
          @update:model-value="$emit('update:prompt', $event)"
          @keydown.enter="handleEnter"
          @paste="onPasteFiles"
        />

        <div
          class="chat-input-toolbar"
        >
          <div
            class="d-flex align-center ga-1 ml-2"
          >
            <v-menu
              location="top start"
              attach=".kira-overlay"
            >
              <template
                #activator="{ props: menuProps }"
              >
                <v-btn
                  :disabled="isLoading || !isConnected || !uploadActive"
                  v-tippy="{ theme: 'primary', content: t('chat.uploadCapture') }"
                  color="white"
                  size="small"
                  icon
                  v-bind="menuProps"
                >
                  <v-icon
                    icon="$mdiPlus"
                    size="20"
                  />
                </v-btn>
              </template>
              <v-list>
                <v-list-item
                  class="rounded-lg"
                  @click="state.uploadModal = true"
                >
                  <template
                    #prepend
                  >
                    <v-icon
                      icon="$mdiCloudUploadOutline"
                      size="20"
                    />
                  </template>
                  {{ t('chat.uploadFile') }}
                </v-list-item>
                <v-list-item
                  class="rounded-lg mt-1"
                  @click="captureScreenshot"
                >
                  <template
                    #prepend
                  >
                    <v-icon
                      icon="$mdiCameraOutline"
                      size="20"
                    />
                  </template>
                  {{ t('chat.captureScreenshot') }}
                </v-list-item>
              </v-list>
            </v-menu>

            <v-btn
              :disabled="isLoading || !isConnected || !audioActive"
              v-tippy="{ theme: 'primary', content: t('chat.recordVoice') }"
              color="white"
              size="small"
              class="ml-1"
              icon
              @click="isRecordingAudio = true"
            >
              <v-icon
                icon="$mdiMicrophone"
                size="20"
              />
            </v-btn>
          </div>

          <v-btn
            :disabled="isSendDisabled || !isConnected"
            :loading="isLoading"
            :icon="isLoading ? '$mdiLoading' : '$mdiArrowUp'"
            color="secondary"
            class="chat-send-btn"
            size="small"
            @click="sendOrEmit"
          />
        </div>
      </template>
    </div>

    <upload-modal
      v-if="state.uploadModal"
      :upload="uploadFiles"
      ref="uploadModalElement"
      @close="state.uploadModal = false"
    />

    <input
      ref="fileInputRef"
      type="file"
      class="d-none"
      multiple
      @change="onPickFiles"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AudioRecorder from '@/components/chat/AudioRecorder.vue';
import ChatBlock from '@/components/chat/ChatBlock.vue';
import UploadModal from '@/components/chat/UploadModal.vue';
const { t } = useI18n();

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  prompt: {
    type: String,
    required: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isConnected: {
    type: Boolean,
    default: false,
  },
  audioActive: {
    type: Boolean,
    default: true,
  },
  uploadActive: {
    type: Boolean,
    default: true,
  },
  apiBaseUrl: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:prompt', 'send-message', 'send-messages']);

const chatBlockRef = ref(null);
const fileInputRef = ref(null);
const chatElement = ref(null);
const uploadModalElement = ref(null);
const attachments = ref([]);
const isRecordingAudio = ref(false);
const state = reactive({
  uploadModal: false,
});

const toBase64 = (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => {
      resolve(reader.result);
    };
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
  });

const mapFileType = (mime) => {
  if (! mime) {
    return 'document';
  }

  if (mime.startsWith('image/')) {
    return 'image';
  }

  if (mime.startsWith('audio/')) {
    return 'audio';
  }

  if (mime.startsWith('video/')) {
    return 'video';
  }

  return 'document';
};

const pushFiles = async (files) => {
  const processed = await Promise.all(
    Array.from(files).map(async (file) => ({
      id: `${Date.now()}-${file.name}-${Math.random().toString(16).slice(2)}`,
      name: file.name,
      base64: await toBase64(file),
      type: mapFileType(file.type),
    }))
  );

  attachments.value.push(...processed);
};

const onDropFiles = async (event) => {
  if (props.isLoading || ! props.isConnected || ! props.uploadActive) {
    return;
  }

  const files = event.dataTransfer?.files;

  if (! files?.length) {
    return;
  }

  await pushFiles(files);
};

const onPasteFiles = async (event) => {
  const files = Array.from(event.clipboardData?.files || []);

  if (! files.length) {
    return;
  }

  event.preventDefault();
  state.uploadModal = true;
  await nextTick();
  uploadModalElement.value?.setFiles(files);
};

const pickFiles = () => {
  fileInputRef.value?.click();
};

const onPickFiles = async (event) => {
  const files = event.target?.files;

  if (! files?.length) {
    return;
  }

  await pushFiles(files);
  event.target.value = '';
};

const removeAttachment = (index) => {
  attachments.value.splice(index, 1);
};

const sendAudioMessage = async ({ blob }) => {
  isRecordingAudio.value = false;

  const file = new File([blob], `audio-${Date.now()}.webm`, {
    type: blob.type || 'audio/webm',
  });
  const base64 = await toBase64(file);

  emit('send-messages', [
    {
      type: 'audio',
      value: base64,
      name: file.name,
    },
  ]);
};

const uploadFiles = async (files, caption) => {
  const messages = files.map((file) => ({
    value: file.base64,
    type: mapFileType(file.mime),
    name: file.name,
  }));
  emit('send-messages', messages, caption);
};

const captureScreenshot = async () => {
  if (! navigator.mediaDevices?.getDisplayMedia) {
    return;
  }

  let stream = null;

  try {
    stream = await navigator.mediaDevices.getDisplayMedia({
      video: {
        cursor: 'always',
      },
      audio: false,
    });

    const video = document.createElement('video');
    video.srcObject = stream;
    await video.play();
    await new Promise((resolve) => {
      if (video.videoWidth) {
        resolve();
        return;
      }

      video.addEventListener(
        'loadedmetadata',
        () => {
          resolve();
        },
        {
          once: true,
        }
      );
    });

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d')?.drawImage(video, 0, 0);

    stream.getTracks().forEach((track) => track.stop());
    stream = null;

    const blob = await new Promise((resolve) => {
      canvas.toBlob(resolve, 'image/png');
    });

    if (! blob) {
      return;
    }

    const file = new File([blob], `screenshot-${Date.now()}.png`, {
      type: 'image/png',
    });
    const base64 = await toBase64(file);

    attachments.value.push({
      id: `${Date.now()}-${file.name}`,
      name: file.name,
      base64,
      type: 'image',
    });
  } catch (error) {
    // permission cancelled
  } finally {
    stream?.getTracks()?.forEach((track) => track.stop());
  }
};

const isSendDisabled = computed(() => {
  return props.isLoading || (! props.prompt.trim() && attachments.value.length === 0);
});

const sendOrEmit = () => {
  if (attachments.value.length > 0) {
    emit(
      'send-messages',
      attachments.value.map((item) => ({
        type: item.type,
        value: item.base64,
        name: item.name,
      })),
      props.prompt
    );
    attachments.value = [];
    emit('update:prompt', '');
    return;
  }

  emit('send-message');
};

const handleEnter = (event) => {
  if (event.shiftKey) {
    return;
  }

  event.preventDefault();
  sendOrEmit();
};

const scrollToEnd = () => {
  chatBlockRef.value?.scrollToEnd();
};

defineExpose({
  scrollToEnd,
  chatElement,
});

watch(
  () => props.items.length,
  async () => {
    await nextTick();
    scrollToEnd();
  }
);
</script>

<style scoped lang="scss">
.chat-main-area {
  height: 100%;
  min-height: 0;
  background-color: var(--surface);
  border-radius: 10px;
}

.chat-input-card {
  border-radius: 16px;
  background: var(--background-2);
  overflow: hidden;
  transition:
    border 0.2s,
    box-shadow 0.2s;
}

.chat-input-card:global(.drag-over) {
  border: 2px dashed rgb(var(--v-theme-primary));
  box-shadow: 0 0 12px rgba(var(--v-theme-primary), 0.3);
}

.chat-input-textarea {
  max-height: 200px;
  overflow: auto;
}

.chat-input-textarea :deep(.v-field) {
  background: transparent;
}

.chat-input-textarea :deep(.v-field__input) {
  padding: 12px 16px 4px !important;
}

.chat-input-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 8px 8px;
}

.chat-attachments {
  overflow-x: auto;
  flex-wrap: nowrap;
}

.chat-attachment-item {
  position: relative;
  flex-shrink: 0;
}

.chat-attachment-thumb {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 8px;
}

.chat-attachment-file {
  height: 64px;
  max-width: 140px;
  border-radius: 8px;
  background: var(--background-1);
}

.chat-attachment-remove {
  position: absolute;
  top: -6px;
  right: -6px;
}

.chat-send-btn {
  border-radius: 50% !important;
  color: white;
}
</style>
