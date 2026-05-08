<template>
  <v-app>
    <v-btn
      v-if="!isOpen"
      ref="fabRef"
      color="primary"
      size="large"
      class="kira-fab text-white"
      rounded="circle"
      variant="tonal"
      icon
      @click="openPanel"
    >
      <img
        src="@/assets/icon.svg"
        width="30"
        height="30" 
        alt="Chat"
      >
    </v-btn>

    <Teleport to="body">
      <div
        v-if="isOpen"
        ref="overlayRef"
        class="kira-overlay"
        :style="overlayStyle"
      >
        <div
          class="kira-overlay-bar"
          :class="{ 'is-dragging': isDragging }"
          @mousedown.prevent="onBarDragStart"
          @touchstart.prevent="onBarTouchStart"
        >
          <span
            class="kira-overlay-bar-title"
          >
            {{ appStore.title }}
          </span>

          <v-btn
            variant="text"
            color="white"
            size="small"
            iconb
            @click="isOpen = false"
          >
            <v-icon
              icon="$mdiClose"
              size="18"
            />
          </v-btn>
        </div>

        <div
          class="kira-overlay-body"
        >
          <chat-view />
        </div>
      </div>
    </Teleport>
  </v-app>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useAppStore } from '@/stores/app';
import { useSession } from '@/composables/useSession';
import ChatView from '@/views/ChatView.vue';

const appStore = useAppStore();
const { persistSession } = useSession();

const isOpen     = ref(appStore.chatOpen);
const fabRef     = ref(null);
const overlayRef = ref(null);
const isDragging = ref(false);

const PANEL_W = 420;
const PANEL_H = 600;
const PAD     = 24;
const GAP     = 8;

const panelPos  = reactive({ top: 0, left: 0, originX: 50, originY: 100 });
const panelSize = reactive({ width: 0, height: 0 }); // 0 = use CSS default

const overlayStyle = computed(() => {
  const s = {
    top:  `${panelPos.top}px`,
    left: `${panelPos.left}px`,
    '--kira-origin-x': `${panelPos.originX}%`,
    '--kira-origin-y': `${panelPos.originY}%`,
  };
  if (panelSize.width  > 0) s.width  = `${panelSize.width}px`;
  if (panelSize.height > 0) s.height = `${panelSize.height}px`;
  return s;
});

// ── Clamp ────────────────────────────────────────────────────────────────────

const clampPos = (top, left, pw, ph) => {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const w  = pw ?? (overlayRef.value ? overlayRef.value.offsetWidth  : PANEL_W);
  const h  = ph ?? (overlayRef.value ? overlayRef.value.offsetHeight : PANEL_H);
  return {
    top:  Math.max(0, Math.min(top,  vh - h)),
    left: Math.max(0, Math.min(left, vw - w)),
  };
};

const applyClamp = () => {
  const el = overlayRef.value;
  if (!el) return;
  const clamped = clampPos(panelPos.top, panelPos.left, el.offsetWidth, el.offsetHeight);
  panelPos.top  = clamped.top;
  panelPos.left = clamped.left;
};

// ── Geometry persistence ──────────────────────────────────────────────────────

let saveTimer = null;

const saveGeometry = (debounce = false) => {
  clearTimeout(saveTimer);
  const doSave = () => {
    const el = overlayRef.value;
    if (!el) return;
    persistSession({
      panel_top:    panelPos.top,
      panel_left:   panelPos.left,
      panel_width:  el.offsetWidth,
      panel_height: el.offsetHeight,
    });
  };
  saveTimer = debounce ? setTimeout(doSave, 400) : (doSave(), null);
};

// ── Open ─────────────────────────────────────────────────────────────────────

const computePos = (rect) => {
  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const pw = Math.min(PANEL_W, vw - PAD * 2);
  const ph = Math.min(PANEL_H, vh - PAD * 2);

  const spaceRight = vw - rect.right;
  const spaceLeft  = rect.left;
  let left = spaceRight >= spaceLeft ? rect.left : rect.right - pw;
  left = Math.max(PAD, Math.min(left, vw - pw - PAD));

  let top = rect.top - ph - GAP;
  if (top < PAD) top = rect.bottom + GAP;
  top = Math.max(PAD, Math.min(top, vh - ph - PAD));

  const btnCx  = rect.left + rect.width  / 2;
  const btnCy  = rect.top  + rect.height / 2;
  const originX = Math.round(Math.max(0, Math.min(100, (btnCx - left) / pw * 100)));
  const originY = Math.round(Math.max(0, Math.min(100, (btnCy - top)  / ph * 100)));

  return { top, left, originX, originY };
};

const openPanel = () => {
  if (appStore.panelWidth > 0) {
    panelSize.width  = appStore.panelWidth;
    panelSize.height = appStore.panelHeight;
    const clamped    = clampPos(appStore.panelTop, appStore.panelLeft, appStore.panelWidth, appStore.panelHeight);
    panelPos.top     = clamped.top;
    panelPos.left    = clamped.left;
    panelPos.originX = 50;
    panelPos.originY = 100;
  } else {
    const el = fabRef.value?.$el ?? fabRef.value;
    if (el) Object.assign(panelPos, computePos(el.getBoundingClientRect()));
  }
  isOpen.value = true;
};

// ── Resize observer ───────────────────────────────────────────────────────────

let resizeObs = null;

const mountObserver = () => {
  nextTick(() => {
    const el = overlayRef.value;
    if (!el) return;
    resizeObs = new ResizeObserver(() => {
      const el = overlayRef.value;
      if (el) {
        panelSize.width  = el.offsetWidth;
        panelSize.height = el.offsetHeight;
      }
      applyClamp();
      saveGeometry(true);
    });
    resizeObs.observe(el);
    window.addEventListener('resize', applyClamp);
  });
};

const unmountObserver = () => {
  clearTimeout(saveTimer);
  resizeObs?.disconnect();
  resizeObs = null;
  window.removeEventListener('resize', applyClamp);
};

// ── Drag ─────────────────────────────────────────────────────────────────────

let anchor = { mx: 0, my: 0, top: 0, left: 0 };

const onBarDragStart = (e) => {
  if (e.target.closest('.v-btn')) return;
  isDragging.value = true;
  anchor = { mx: e.clientX, my: e.clientY, top: panelPos.top, left: panelPos.left };
  window.addEventListener('mousemove', onDragMove);
  window.addEventListener('mouseup', onDragEnd);
};

const onDragMove = (e) => {
  const c = clampPos(anchor.top + e.clientY - anchor.my, anchor.left + e.clientX - anchor.mx);
  panelPos.top  = c.top;
  panelPos.left = c.left;
};

const onDragEnd = () => {
  isDragging.value = false;
  window.removeEventListener('mousemove', onDragMove);
  window.removeEventListener('mouseup', onDragEnd);
  saveGeometry();
};

const onBarTouchStart = (e) => {
  if (e.target.closest('.v-btn')) return;
  const t = e.touches[0];
  isDragging.value = true;
  anchor = { mx: t.clientX, my: t.clientY, top: panelPos.top, left: panelPos.left };
  window.addEventListener('touchmove', onTouchMove, { passive: false });
  window.addEventListener('touchend', onTouchEnd);
};

const onTouchMove = (e) => {
  e.preventDefault();
  const t = e.touches[0];
  const c = clampPos(anchor.top + t.clientY - anchor.my, anchor.left + t.clientX - anchor.mx);
  panelPos.top  = c.top;
  panelPos.left = c.left;
};

const onTouchEnd = () => {
  isDragging.value = false;
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', onTouchEnd);
  saveGeometry();
};

// ── Lifecycle ─────────────────────────────────────────────────────────────────

watch(isOpen, (val) => {
  if (val) mountObserver();
  else unmountObserver();
  persistSession({ chat_open: val, conversation_id: appStore.conversationId });
});

onMounted(() => {
  if (!isOpen.value) return;
  if (appStore.panelWidth > 0) {
    panelSize.width  = appStore.panelWidth;
    panelSize.height = appStore.panelHeight;
    const clamped    = clampPos(appStore.panelTop, appStore.panelLeft, appStore.panelWidth, appStore.panelHeight);
    panelPos.top     = clamped.top;
    panelPos.left    = clamped.left;
  } else {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const pw = Math.min(PANEL_W, vw - PAD * 2);
    const ph = Math.min(PANEL_H, vh - PAD * 2);
    panelPos.top  = Math.max(PAD, vh - ph - PAD);
    panelPos.left = Math.max(PAD, (vw - pw) / 2);
  }
  panelPos.originX = 50;
  panelPos.originY = 100;
  mountObserver();
});

onBeforeUnmount(() => {
  unmountObserver();
  window.removeEventListener('mousemove', onDragMove);
  window.removeEventListener('mouseup', onDragEnd);
  window.removeEventListener('touchmove', onTouchMove);
  window.removeEventListener('touchend', onTouchEnd);
});
</script>

<style lang="scss">
.v-application {
  position: static !important;
  display: inline-flex !important;
  min-width: 0 !important;
  min-height: 0 !important;
  overflow: visible !important;
  background: transparent !important;
}

.v-application__wrap {
  min-height: 0 !important;
  overflow: visible !important;
  display: contents !important;
}

.kira-fab {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
  background-color: #E9DEFB !important;
}

.kira-overlay {
  position: fixed;
  width: 420px;
  height: 600px;
  max-width: calc(100vw - 48px);
  max-height: calc(100vh - 48px);
  min-width: 320px;
  min-height: 400px;
  resize: both;
  overflow: hidden;
  z-index: 9998;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  transform-origin: var(--kira-origin-x, 50%) var(--kira-origin-y, 100%);
  animation: kira-open 180ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes kira-open {
  from { opacity: 0; transform: scale(0.6); }
  to   { opacity: 1; transform: scale(1);   }
}

.kira-overlay-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px 8px 16px;
  background: rgb(var(--v-theme-primary));
  color: #fff;
  flex-shrink: 0;
  cursor: grab;
  user-select: none;

  &.is-dragging { cursor: grabbing; }
}

.kira-overlay-bar-title {
  font-size: 15px;
  font-weight: 600;
  color: #fff;
}

.kira-overlay-body {
  flex: 1;
  overflow: hidden;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
</style>
