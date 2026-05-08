<template>
  <img
    :src="src"
    class="cursor-pointer"
    v-bind="$attrs"
  />
</template>

<script setup>
defineProps({
  src: {
    type: String,
    required: true,
  },
});
</script>
