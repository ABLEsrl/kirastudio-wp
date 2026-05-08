<template>
  <v-virtual-scroll
    :items="items"
    ref="scroller"
    class="h-100 py-5 px-5 chat-content chat-block-scroll"
  >
    <template
      #default="{ item, index }"
    >
      <msg-bubble
        v-if="item.message_content?.value?.length > 0"
        :class="[
          item.role === 'assistant' ? 'inbound' : 'outbound',
          { last: isLastInSequence(index) },
          `is-${item.message_content?.type || 'text'}`,
        ]"
      >
        <template
          v-if="item.message_content?.type === 'html' || hasChartCanvas(item.message_content?.value)"
        >
          <div
            v-html="sanitizedContent(item.message_content.value)"
            class="pre-wrap fs-14"
          />
        </template>
        <template
          v-else-if="item.message_content?.type === 'image'"
        >
          <remote-attachment
            :src="item.message_content.value"
            class="max-250"
          >
            <template
              #default="{ src }"
            >
              <chat-img
                :src="resolveSrc(src)"
                class="chat-image bg-white"
              />
            </template>
          </remote-attachment>
        </template>
        <template
          v-else-if="item.message_content?.type === 'audio'"
        >
          <remote-attachment
            :src="item.message_content.value"
          >
            <template
              #default="{ src }"
            >
              <chat-audio
                :src="resolveSrc(src)"
                class="bg-white"
              />
            </template>
          </remote-attachment>
        </template>
        <template
          v-else-if="item.message_content?.type === 'document'"
        >
          <remote-attachment
            :src="item.message_content.value"
          >
            <template
              #default="{ src }"
            >
              <chat-document
                :src="resolveSrc(src)"
                :name="item.message_content?.name"
                :original-path="item.message_content.value"
              />
            </template>
          </remote-attachment>
        </template>
        <template
          v-else
        >
          <markdown
            :value="item.message_content?.value || ''"
          />
        </template>
      </msg-bubble>
    </template>
  </v-virtual-scroll>
</template>

<script setup>
import { nextTick, ref } from 'vue';
import ChatAudio from '@/components/chat/ChatAudio.vue';
import ChatDocument from '@/components/chat/ChatDocument.vue';
import ChatImg from '@/components/chat/ChatImg.vue';
import Markdown from '@/components/chat/Markdown.vue';
import MsgBubble from '@/components/chat/MsgBubble.vue';
import RemoteAttachment from '@/components/chat/RemoteAttachment.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  apiBaseUrl: {
    type: String,
    default: '',
  },
});

const scroller = ref(null);

const hasChartCanvas = (value) => typeof value === 'string' && /<canvas[\s>]|chart-element/.test(value);

const fixCanvasDataAttrs = (canvasHtml) =>
  canvasHtml
    .replace(/data-labels='(.*?)\]'\s/g, (_, inner) => {
      const json = `${inner}]`.replace(/'([^']*)'/g, '"$1"');
      return `data-labels="${json.replace(/"/g, '&quot;')}" `;
    })
    .replace(/data-datasets='(.*?)'\s*(?=\s|>)/g, (_, json) => `data-datasets="${json.replace(/"/g, '&quot;')}" `);

const wrapCanvasWithLoader = (canvasHtml) =>
  `<div class="canvas-wrap" data-render-ready="false">
    <div class="canvas-loader"></div>
    ${canvasHtml}
  </div>`;

const renderContentWithCanvas = (content) =>
  content.replace(/<canvas[\s\S]*?<\/canvas>/gi, (match) => wrapCanvasWithLoader(fixCanvasDataAttrs(match)));

const sanitizedContent = (content) => {
  if (hasChartCanvas(content)) {
    return renderContentWithCanvas(content);
  }

  return content || '';
};

const resolveSrc = (value) => {
  if (!value) {
    return '';
  }

  if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('data:')) {
    return value;
  }

  const base = props.apiBaseUrl.replace(/\/$/, '');

  if (!base) {
    return value;
  }

  return `${base}/${value.replace(/^\//, '')}`;
};

const isLastInSequence = (index) => {
  const current = props.items[index];
  const next = props.items[index + 1];

  if (!current) {
    return false;
  }

  return !next || current.role !== next.role;
};

const scrollToEnd = async () => {
  await nextTick();
  const element = scroller.value?.$el;

  if (element) {
    element.scrollTop = element.scrollHeight;
  }
};

defineExpose({
  scrollToEnd,
});
</script>

<style scoped lang="scss">
.chat-block {
  width: 100%;
  height: 100%;
  overflow-y: auto;
}

.chat-image {
  max-width: 100%;
  border-radius: 12px;
  display: block;
}

.chat-audio-player {
  min-width: 225px;
  height: 35px;
  border-radius: 12px;
}

.chat-document {
  text-decoration: none;
  color: inherit;
}

.chat-content {
  :deep(.v-virtual-scroll__container) {
    display: flex;
    flex-direction: column;
    width: 100%;
  }

  :deep(.msg-bubble) {
    border-radius: 15px;
    margin-top: 5px;
    margin-bottom: 5px;
    display: inline-block;
    line-height: 1;
    position: relative;
    max-width: min(88%, 800px);
  }

  :deep(.msg-bubble > div) {
    word-break: break-word;
  }

  :deep(.msg-bubble .msg-bubble-content) {
    border-radius: 15px;
    padding: 16px;
    z-index: 3;
    position: relative;
    background: transparent;
  }

  :deep(.msg-bubble.is-image .msg-bubble-content),
  :deep(.msg-bubble.is-audio .msg-bubble-content),
  :deep(.msg-bubble.is-document .msg-bubble-content) {
    padding: 5px !important;
  }

  :deep(.msg-bubble.is-image > div),
  :deep(.msg-bubble.is-audio > div),
  :deep(.msg-bubble.is-document > div) {
    z-index: 3;
    position: relative;
  }

  :deep(.msg-bubble.is-image img),
  :deep(.msg-bubble.is-audio .audio-player) {
    border-radius: 12px;
    overflow: hidden;
  }

  :deep(.msg-bubble.inbound) {
    margin-right: 0;
    margin-left: 12px;
    color: var(--text);
    position: relative;
    float: left;
    clear: both;
  }

  :deep(.msg-bubble.outbound) {
    margin-left: 0;
    margin-right: 12px;
    float: right;
    clear: both;
  }

  :deep(.msg-bubble.outbound .msg-bubble-content) {
    background: linear-gradient(
      170deg,
      rgba(var(--v-theme-secondary), 0.08) 0%,
      rgba(var(--v-theme-secondary), 0.08) 70%,
      rgba(var(--v-theme-secondary), 0.4) 120%
    );
    color: var(--secondary);
    background-attachment: fixed;
  }

  .markdown :deep(p) {
    margin-bottom: 0;
    line-height: 1.2;
  }

  .markdown :deep(p:not(:first-child)) {
    margin-top: 8px;
  }

  .markdown :deep(ul),
  .markdown :deep(ol) {
    margin-left: 16px;
  }

  .markdown :deep(li) {
    margin-top: 8px;
  }

  .markdown :deep(a) {
    color: var(--secondary);
    text-decoration: underline;
  }
}

.chat-block-scroll::after {
  content: '';
  display: block;
  clear: both;
}

:deep(canvas.chart-element) {
  width: 100%;
  max-width: 100%;
  max-height: 400px;
}

:deep(.canvas-wrap) {
  position: relative;
  width: 100%;
}

:deep(.canvas-wrap .canvas-loader) {
  position: absolute;
  inset: 0;
  border-radius: 8px;
  background-color: var(--background-2);
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    var(--background-2) 20%,
    rgba(255, 255, 255, 0.28) 50%,
    var(--background-2) 80%,
    rgba(255, 255, 255, 0) 100%
  );
  background-size: 220% 100%;
  background-position: 120% 0;
  animation: chart-pending-shimmer 1.9s ease-in-out infinite;
  z-index: 1;
  margin-bottom: 16px;
  margin-top: 16px;
}

:deep(.canvas-wrap[data-render-ready='false'] canvas.chart-element) {
  visibility: hidden;
}

:deep(.canvas-wrap[data-render-ready='true'] .canvas-loader) {
  display: none;
}

:deep(.canvas-wrap[data-render-ready='true'] canvas.chart-element) {
  visibility: visible;
}

@keyframes chart-pending-shimmer {
  0% {
    background-position: 120% 0;
  }

  100% {
    background-position: -120% 0;
  }
}
</style>
