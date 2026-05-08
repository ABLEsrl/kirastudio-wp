<template>
  <div
    class="msg-bubble"
    v-bind="$attrs"
  >
    <div
      class="msg-bubble-content"
    >
      <slot />
    </div>
  </div>
</template>