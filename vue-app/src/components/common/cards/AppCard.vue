<template>
  <div
    :class="selected ? 'bg-primary-lighter' : 'bg-background'"
    class="app-card"
    @click="emit('click')"
  >
    <div
      v-if="header"
      :class="headerClass"
      class="app-card-header"
    >
      <slot name="header" />
    </div>

    <div
      :class="contentClass"
      class="nicescroll app-card-content"
    >
      <slot />
    </div>

    <div
      v-if="footer"
      :class="footerClass"
      class="app-card-footer"
    >
      <slot name="footer" />
    </div>

    <slot name="root" />
  </div>
</template>

<script setup>
defineProps({
  header: {
    type: [Boolean, String],
    default: false,
  },
  footer: {
    type: [Boolean, String],
    default: false,
  },
  contentClass: {
    type: String,
    default: '',
  },
  headerClass: {
    type: String,
    default: '',
  },
  footerClass: {
    type: String,
    default: '',
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['click']);
</script>
