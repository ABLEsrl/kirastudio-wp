<template>
  <v-btn
    variant="text"
    class="px-0 py-0 close-btn"
    color="grey"
    iconb small
    v-bind="$attrs"
    @click="emit('close')"
  >
    <v-icon
      :size="size"
      icon="$mdiClose"
    />
  </v-btn>
</template>

<script setup>
defineProps({
  size: {
    type: [Number, String],
    default: 24,
  },
});

const emit = defineEmits(['close']);
</script>
