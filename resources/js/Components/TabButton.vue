<script setup>
defineProps({
    active: Boolean
});
</script>

<template>
    <button
        :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition',
            active
                ? 'bg-indigo-600 text-white shadow'
                : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
        ]"
    >
        <slot />
    </button>
</template>
