<template>
  <div v-if="show" class="fixed inset-0 flex items-center justify-center bg-black/50 z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: Boolean
})
</script>
