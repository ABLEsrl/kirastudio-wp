<template>
  <div
    v-html="renderedHtml"
    class="markdown"
  />
</template>

<script setup>
import MarkdownIt from 'markdown-it';
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: String,
    required: true,
  },
});

const md = new MarkdownIt({
  html: true,
  linkify: true,
  typographer: true,
});

const renderedHtml = computed(() => md.render(props.value || '').trim());
</script>
