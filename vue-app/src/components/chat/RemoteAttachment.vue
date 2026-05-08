<template>
  <div
    class="overflow-hidden"
  >
    <div
      v-if="state.loading"
      class="min-150 rounded overflow-hidden"
    >
      <v-skeleton-loader
        width="100%"
        height="40px"
        type="image"
        class="mx-auto"
      />
    </div>
    <div
      v-else-if="!state.src"
      class="py-1 px-3"
    >
      <v-icon
        color="error"
        icon="$mdiAlertCircle"
      />
    </div>
    <slot
      v-else
      :src="state.src"
    />
  </div>
</template>

<script setup>
import { onBeforeMount, reactive } from 'vue';
import { apiGet } from '@/services/api';

const props = defineProps({
  src: {
    type: String,
    required: true,
  },
});

const state = reactive({
  loading: true,
  src: null,
});

const getAttachment = async () => {
  try {
    state.loading = true;
    const arrayBuffer = await apiGet('attachments?s3_path=' + encodeURIComponent(props.src), {
      responseType: 'arraybuffer',
    });

    const blob = new Blob([new Uint8Array(arrayBuffer)]);
    state.src = URL.createObjectURL(blob);
  } catch (error) {
    state.src = null;
  } finally {
    state.loading = false;
  }
};

onBeforeMount(() => {
  if (props.src.startsWith('data-')) {
    getAttachment();
    return;
  }

  state.src = props.src;
  state.loading = false;
});
</script>
