<template>
  <v-btn
    :color="props.color"
    :size="props.size"
    :class="[
      props.btnClass,
      'border drop-border',
      !state.error ? 'border-primary' : 'text-error border-error',
    ]"
    variant="flat"
    class="dropzone-btn"
    v-bind="getRootProps()"
  >
    <input
      v-bind="getInputProps()"
    />

    <template
      v-if="!Boolean(props.loading)"
    >
      <div
        v-if="state.files.length && !props.outerList"
        class="d-flex flex-wrap"
      >
        <div
          v-for="(file, index) in state.files"
          :key="file.name + index"
          class="d-flex align-center pa-1 border rounded bg-lighten ma-1"
        >
          <div>
            {{ file.name }}
          </div>
          <v-btn
            class="px-0 ml-2"
            variant="text"
            color="error"
            iconb
            @click.stop="removeFile(index)"
          >
            <v-icon
              icon="$mdiClose"
            />
          </v-btn>
        </div>
      </div>

      <template
        v-if="props.outerList || !state.files.length"
      >
        <template
          v-if="!state.error"
        >
          <div
            v-if="isDragActive"
            class="fs-18"
          >
            {{ t('chat.dropFilesHere') }}
          </div>
          <slot
            v-else
          />
        </template>
        <div
          v-else
          class="pointer fs-16 fw-500 d-flex align-center justify-center pa-2 bg-lighten rounded border border-error"
          @click.stop="state.error = null"
        >
          {{ state.error }}
          <v-icon
            class="ml-2"
            icon="$mdiClose"
          />
        </div>
      </template>
    </template>

    <v-progress-circular
      v-if="Boolean(props.loading)"
      :model-value="props.loading"
      color="success"
      width="4"
      size="30"
      @click.stop="emit('cancel')"
    >
      <v-icon
        color="error"
        icon="$mdiClose"
      />
    </v-progress-circular>
  </v-btn>
</template>

<script setup>
import { reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useDropzone } from 'vue3-dropzone';

const props = defineProps({
  options: {
    type: Object,
    default: () => ({}),
  },
  btnClass: {
    type: String,
    default: '',
  },
  outerList: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Number,
    default: 0,
  },
  size: {
    type: String,
    default: 'large',
  },
  color: {
    type: String,
    default: 'white',
  },
  error: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['cancel', 'selected']);
const { t } = useI18n();

const state = reactive({
  error: null,
  files: [],
});

const removeFile = (index) => {
  state.files.splice(index, 1);
  emit('selected', state.files.length ? state.files : null);
};

const onDrop = (acceptedFiles, rejectReasons) => {
  if (rejectReasons?.length) {
    state.error = t('chat.uploadError') || 'Upload error';
    return;
  }
  if (acceptedFiles.length) {
    state.files = acceptedFiles;
    emit('selected', acceptedFiles);
  }
};

const reset = () => {
  state.error = null;
  state.files = [];
};

const options = reactive(Object.assign({ onDrop }, props.options));

const { getRootProps, getInputProps, isDragActive, open } = useDropzone(options);

defineExpose({ reset, open });
</script>
