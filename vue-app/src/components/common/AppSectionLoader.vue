<template>
  <div
    v-if="status"
    class="loader-overlay"
  >
    <v-card
      v-if="message"
      variant="flat"
      class="loader-message-card"
    >
      <div
        class="pa-2 d-flex align-center nowrap"
      >
        <div
          class="mr-3"
        >
          {{ message }}
        </div>
        <v-progress-circular
          size="20"
          width="2"
          color="secondary"
          indeterminate
        />
      </div>
    </v-card>
    <v-progress-circular
      v-else
      color="secondary"
      indeterminate
    />
  </div>
</template>

<script setup>
defineProps({
  status: {
    type: [Boolean, String],
    default: false,
  },
  message: {
    type: String,
    default: null,
  },
});
</script>

<style scoped lang="scss">
.loader-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.loader-message-card {
  border: 1px solid var(--border);
  background: var(--surface);
}
</style>
