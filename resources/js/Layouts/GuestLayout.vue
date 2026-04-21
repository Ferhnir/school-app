<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 bg-school-blue"
    >
        <div>
            <Link href="/" class="flex items-center gap-3 text-white">
                <ApplicationLogo class="h-20 w-20 fill-current text-white" />
                <span class="text-[24px] font-bold pl-[15px] whitespace-normal text-white">
                    Prywatna Szkoła Podstawowa
                    <p class="text-center">SALWATOR</p>
                </span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>

<style>
    .bg-school-blue {
        background-color: #2060A0;
    }
</style>
