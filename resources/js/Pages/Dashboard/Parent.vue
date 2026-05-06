<script setup>
import { ref, computed, watch, shallowRef } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { localeRouteHelpers } from '@/utils/route';

const { useRouteWithLocale } = localeRouteHelpers();

const props = defineProps({
    bookings: Array,
    today:    String,
    calendar: Object,
});

const page         = usePage();
const emailSending = ref(false);
const emailMessage = ref(page.props.flash?.message ?? '');

watch(() => page.props.flash, (flash) => {
    if (flash?.message) emailMessage.value = flash.message;
});

const PREVIEW_LIMIT  = 5;
const showAllBookings = ref(false);
const visibleBookings = computed(() =>
    showAllBookings.value ? props.bookings : props.bookings.slice(0, PREVIEW_LIMIT)
);

const sendEmail = () => {
    emailSending.value = true;
    emailMessage.value = '';
    router.post(
        useRouteWithLocale('parent.dashboard.email'),
        {},
        {
            onSuccess: () => { emailSending.value = false; },
            onError:   () => { emailSending.value = false; emailMessage.value = 'Failed to send email.'; },
        }
    );
};

// ── Calendar ─────────────────────────────────────────────────────────────────

const DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const navigateMonth = (monthStr) => {
    router.get(
        useRouteWithLocale('parent.dashboard'),
        { month: monthStr },
        { preserveState: false, preserveScroll: false }
    );
};

// ── Day modal ─────────────────────────────────────────────────────────────────

const selectedDay       = ref(null);
const modalEmailSending = ref(false);
const modalEmailMsg     = ref('');

const openDay = (day) => {
    if (!day.count) return;
    selectedDay.value       = day;
    modalEmailMsg.value     = '';
    modalEmailSending.value = false;
};

const closeModal = () => { selectedDay.value = null; };

const modalDateLabel = computed(() => {
    if (!selectedDay.value) return '';
    const [y, m, d] = selectedDay.value.date.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
});

const sendModalEmail = () => {
    modalEmailSending.value = true;
    modalEmailMsg.value     = '';
    router.post(
        useRouteWithLocale('parent.calendar.email', { date: selectedDay.value.date }),
        {},
        {
            onSuccess: () => {
                modalEmailSending.value = false;
                modalEmailMsg.value = page.props.flash?.message ?? 'Email sent!';
            },
            onError: () => {
                modalEmailSending.value = false;
                modalEmailMsg.value = 'Failed to send email.';
            },
        }
    );
};
</script>

<template>
    <Head title="Today Bookings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Today Bookings</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- ── Today's bookings ───────────────────────────────────── -->
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">

                    <div class="flex items-start justify-between gap-4 mb-6">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">{{ today }}</p>
                            <h3 class="text-lg font-semibold text-gray-900">
                                <template v-if="bookings.length === 0">You have no bookings today.</template>
                                <template v-else>
                                    Today you have
                                    <span class="text-indigo-600">{{ bookings.length }}</span>
                                    {{ bookings.length === 1 ? 'booking' : 'bookings' }}
                                </template>
                            </h3>
                        </div>

                        <div v-if="bookings.length > 0" class="flex items-center gap-2 shrink-0">
                            <a
                                :href="useRouteWithLocale('parent.dashboard.download')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download PDF
                            </a>
                            <button
                                @click="sendEmail"
                                :disabled="emailSending"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ emailSending ? 'Sending…' : 'Send via Email' }}
                            </button>
                        </div>
                    </div>

                    <div v-if="emailMessage" class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
                        {{ emailMessage }}
                    </div>

                    <div v-if="bookings.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="(booking, i) in visibleBookings"
                            :key="i"
                            class="flex items-center justify-between py-3.5"
                        >
                            <span class="font-medium text-gray-800">{{ booking.teacher }}</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 tabular-nums">
                                {{ booking.time }}
                            </span>
                        </div>

                        <div v-if="bookings.length > PREVIEW_LIMIT" class="pt-3">
                            <button
                                @click="showAllBookings = !showAllBookings"
                                class="w-full py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-xl transition-colors"
                            >
                                {{ showAllBookings ? 'Show less' : `Show more (${bookings.length - PREVIEW_LIMIT} more)` }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Monthly calendar ───────────────────────────────────── -->
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">

                    <!-- Month navigation -->
                    <div class="flex items-center justify-between mb-5">
                        <button
                            @click="navigateMonth(calendar.prev_month)"
                            class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 transition-colors"
                            aria-label="Previous month"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h3 class="text-base font-semibold text-gray-900">{{ calendar.label }}</h3>
                        <button
                            @click="navigateMonth(calendar.next_month)"
                            class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 transition-colors"
                            aria-label="Next month"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Day-of-week headers -->
                    <div class="grid grid-cols-7 mb-1">
                        <div
                            v-for="label in DAY_LABELS"
                            :key="label"
                            class="text-center text-xs font-medium text-gray-400 uppercase tracking-wide py-1"
                        >
                            {{ label }}
                        </div>
                    </div>

                    <!-- Day cells -->
                    <div class="grid grid-cols-7 gap-1">
                        <!-- Empty offset cells before the 1st -->
                        <div v-for="n in calendar.start_weekday" :key="'empty-' + n" />

                        <button
                            v-for="day in calendar.days"
                            :key="day.date"
                            @click="openDay(day)"
                            :disabled="!day.count"
                            :class="[
                                'relative flex flex-col items-center justify-center rounded-xl py-2 min-h-[52px] transition-colors',
                                day.is_today ? 'bg-indigo-50 ring-2 ring-indigo-400' : 'hover:bg-gray-50',
                                day.count ? 'cursor-pointer' : 'cursor-default',
                            ]"
                        >
                            <span :class="[
                                'text-sm font-medium leading-none',
                                day.is_today ? 'text-indigo-700' : 'text-gray-700',
                            ]">
                                {{ day.label }}
                            </span>
                            <span
                                v-if="day.count"
                                class="mt-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] font-bold leading-none"
                            >
                                {{ day.count }}
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Day detail modal ──────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="selectedDay"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closeModal"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">

                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-0.5">Bookings</p>
                            <h3 class="text-base font-semibold text-gray-900">{{ modalDateLabel }}</h3>
                        </div>
                        <button @click="closeModal" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="divide-y divide-gray-50 mb-5">
                        <div
                            v-for="(booking, i) in selectedDay.bookings"
                            :key="i"
                            class="flex items-center justify-between py-3"
                        >
                            <span class="font-medium text-gray-800">{{ booking.teacher }}</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 tabular-nums">
                                {{ booking.time }}
                            </span>
                        </div>
                    </div>

                    <div v-if="modalEmailMsg" class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
                        {{ modalEmailMsg }}
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            :href="useRouteWithLocale('parent.calendar.download', { date: selectedDay.date })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download PDF
                        </a>
                        <button
                            @click="sendModalEmail"
                            :disabled="modalEmailSending"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ modalEmailSending ? 'Sending…' : 'Send via Email' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
