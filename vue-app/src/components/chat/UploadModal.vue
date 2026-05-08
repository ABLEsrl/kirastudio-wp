<template>
  <v-dialog
    v-model="state.modal"
    attach=".kira-overlay"
    max-width="700"
    content-class="no-border w-100"
  >
    <app-card
      :footer="Boolean(state.files.length)"
      content-class="pa-5 upload-modal-content"
      class="rounded-lg type-primary upload-modal-card"
      header
    >
      <template
        #header
      >
        <div
          class="d-flex align-center justify-space-between py-2 px-5"
        >
          <div
            class="d-flex align-center"
          >
            <v-icon
              icon="$mdiCloudUploadOutline"
              size="24"
              class="mr-2"
              color="primary"
            />
            <h5
              class="mb-0"
            >
              {{ t('chat.uploadTitle') }}
            </h5>
          </div>
          <close-btn
            @close="emit('close')"
          />
        </div>
      </template>

      <template
        #footer
      >
        <div
          class="d-flex align-center justify-end px-5 pb-5"
        >
          <v-btn
            :disabled="!state.files.length"
            :loading="state.loader"
            color="primary"
            @click="upload"
          >
            {{ t('chat.upload') }}
          </v-btn>
        </div>
      </template>

      <div>
        <dropzone
          :options="uploadOptions"
          btn-class="min-h-200 w-100 h-auto"
          outer-list
          @selected="setFiles"
        >
          <div
            class="d-flex flex-column align-center"
          >
            <div>
              <v-icon
                icon="$mdiCloudUpload"
                size="50"
                color="grey"
              />
            </div>
            <div
              class="mt-2 fw-600 fs-18"
            >
              {{ t('chat.dragDropFiles') }}
            </div>
            <div
              class="d-flex align-center justify-center mt-2 mb-4 w-100"
            >
              <v-divider
                class="w-100"
              />
              <span
                class="mx-2 text-grey"
              >
                {{ t('chat.or') }}
              </span>
              <v-divider
                class="w-100"
              />
            </div>
            <div
              class="py-1 px-4 rounded-pill bg-primary text-white caps"
            >
              {{ t('chat.browse') }}
            </div>
          </div>
        </dropzone>

        <div
          v-if="audioFiles.length || docFiles.length"
          class="mt-4 upload-files-list"
        >
          <div
            v-for="file in [...audioFiles, ...docFiles]"
            :key="file.path || file.name"
            class="kira-file-item d-flex align-center"
          >
            <v-icon
              :icon="getFileIcon(file)"
              size="24"
              color="primary"
            />
            <span
              class="fs-14 text-text text-truncate flex-1"
            >
              {{ file.name }}
            </span>
            <v-btn
              variant="text"
              color="error"
              iconb
              @click="removeFile(file.path || file.name)"
            >
              <v-icon
                icon="$mdiClose"
                size="18"
              />
            </v-btn>
          </div>
        </div>

        <div
          v-if="imgFiles.length"
          class="mt-4 upload-gallery pa-3 border rounded"
        >
          <div
            v-for="file in imgFiles"
            :key="file.path || file.name"
            class="position-relative"
          >
            <img
              :src="createUrl(file)"
              class="upload-gallery-img border rounded bg-white"
            />
            <v-btn
              color="error"
              size="20"
              class="absolute-force top right mr-n1 mt-n1 border"
              icon
              @click="removeFile(file.path || file.name)"
            >
              <v-icon
                icon="$mdiClose"
                size="18"
              />
            </v-btn>
          </div>
        </div>

        <div
          v-if="state.files.length"
          class="mt-4"
        >
          <v-text-field
            v-model="state.caption"
            :label="t('chat.caption')"
            :placeholder="t('chat.addCaption')"
            hide-details
          />
        </div>
      </div>
    </app-card>
  </v-dialog>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppCard from '@/components/common/cards/AppCard.vue';
import CloseBtn from '@/components/common/CloseBtn.vue';
import { useFileManager } from '@/composables/FileMgr';
import { useSequentialUploader } from '@/composables/SequentialUploader';
import Mimes, { uploadFileExtensions } from '@/data/Mimes';
import Dropzone from '@/components/chat/Dropzone.vue';

const props = defineProps({
  upload: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits(['close', 'upload']);
const { t } = useI18n();
const { b64File } = useFileManager();
const { reset: resetUploads } = useSequentialUploader();

const state = reactive({
  modal: true,
  files: [],
  base64Files: [],
  caption: '',
  loader: false,
});

const imgFiles = computed(() => state.files.filter((file) => file.type?.includes('image')));
const audioFiles = computed(() => state.files.filter((file) => file.type?.includes('audio')));
const docFiles = computed(() => state.files.filter((file) => !file.type?.includes('image') && !file.type?.includes('audio')));

const uploadOptions = {
  accept: uploadFileExtensions,
  maxFiles: 10,
};

const getFileIcon = (file) => {
  if (!file.name) return '$mdiFileOutline';
  const ext = file.name.split('.').pop().toLowerCase();
  return Mimes.list.find((m) => m.ext.includes(ext))?.md || '$mdiFileOutline';
};

const setFiles = (files) => {
  if (! files) {
    state.files = [];
    return;
  }

  state.files = [...state.files, ...files.filter((file) => !state.files.find((item) => item.path === file.path))];
};

const removeFile = (identity) => {
  state.files = state.files.filter((file) => (file.path || file.name) !== identity);
};

const createUrl = (file) => window.URL.createObjectURL(file);

const parseToBase64 = async () => {
  state.base64Files = await Promise.all(state.files.map(async (file) => await b64File(file)));
};

const upload = async () => {
  try {
    state.loader = true;
    await parseToBase64();

    if (props.upload) {
      await props.upload(state.base64Files, state.caption);
      emit('close');
      return;
    }

    emit('upload', state.base64Files, state.caption);
    emit('close');
  } finally {
    state.loader = false;
    resetUploads();
  }
};

watch(
  () => state.modal,
  (value) => {
    if (! value) {
      emit('close');
    }
  }
);

defineExpose({
  setFiles,
});
</script>

<style scoped lang="scss">
.upload-modal-card {
  border: 1px solid var(--border);
}

.upload-modal-content {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.upload-files-list {
  overflow: auto;
  max-height: 180px;
}

.kira-file-item {
  gap: 12px;
  padding: 10px 12px;
  border-radius: 24px;
  transition: background-color 0.15s ease;
}

.kira-file-item:hover {
  background-color: var(--background-2);
}

.upload-gallery {
  display: grid;
  grid-auto-flow: row dense;
  gap: 10px;
  grid-template-columns: repeat(auto-fit, minmax(120px, 180px));
  background: var(--background-2);
}

.upload-gallery-img {
  width: 100%;
  max-width: 180px;
  display: block;
}
</style>
