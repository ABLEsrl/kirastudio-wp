<template>
  <div
    class="d-flex align-center pointer pr-3"
  >
    <v-icon
      :icon="icon"
      size="34"
    />
    <div
      class="fw-600 underline ml-2"
    >
      {{ fileName }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Mimes from '@/data/Mimes';

const props = defineProps({
  src: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    default: null,
  },
  originalPath: {
    type: String,
    default: null,
  },
});

const { t } = useI18n();

const fileName = computed(() => {
  if (props.name) {
    return props.name;
  }

  if (props.originalPath && !props.originalPath.startsWith('data:')) {
    return props.originalPath.split('/').pop();
  }

  if (props.src?.startsWith('data:') || props.src?.startsWith('blob:')) {
    return t('chat.genericDocument');
  }

  return props.src?.split('/').pop() || t('chat.genericDocument');
});

const extension = computed(() => {
  if (!fileName.value || !fileName.value.includes('.')) {
    return 'pdf';
  }

  return fileName.value.split('.').pop();
});

const icon = computed(() => Mimes.list.find((item) => item.ext.includes(extension.value))?.md || '$mdiFile');
</script>
