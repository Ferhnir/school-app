<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ParentBookingModal from '@/Components/Modals/ParentBookingModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { localeRouteHelpers } from '@/utils/route';

const { useRouteWithLocale } = localeRouteHelpers();

defineProps({
    week:  Object,
    dates: Array,
    rows:  Array,
});

const navigate = (offset) => {
    router.get(useRouteWithLocale('parent.bookings.index'), { week: offset }, { preserveState: true });
};

const thClass = 'pb-2 text-xs font-medium text-gray-400 uppercase tracking-wide';

const statusMap = {
    available:    { label: 'Available',    cls: 'bg-emerald-100 text-emerald-700' },
    fully_booked: { label: 'Fully booked', cls: 'bg-red-100 text-red-500' },
    booked:       { label: 'Booked',       cls: 'bg-indigo-100 text-indigo-700' },
};

const dayStatus = (day) => typeof day === 'object' ? day?.status : day;

const resolvedStatus = (day, isPast) => {
    const status = dayStatus(day);
    if (!status) return null;
    if (isPast && status !== 'booked') return null;
    const entry = statusMap[status] ?? null;
    if (!entry) return null;
    if (status === 'booked' && typeof day === 'object' && day.time) {
        return { ...entry, label: day.time };
    }
    return entry;
};

const modalOpen    = ref(false);
const modalTeacher = ref(null);
const modalDay     = ref(null);
const modalMode    = ref('book');

const openModal = (teacher, day, mode) => {
    modalTeacher.value = teacher;
    modalDay.value     = day;
    modalMode.value    = mode;
    modalOpen.value    = true;
};
</script>

<template>
    <Head title="Weekly Bookings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Weekly Bookings</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-semibold text-gray-900">Weekly schedule</h3>
                        <div class="flex items-center gap-3">
                            <button
                                @click="navigate(week.prev)"
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                &larr; Prev
                            </button>
                            <span class="text-sm font-medium text-gray-700 min-w-44 text-center">
                                {{ week.label }}
                            </span>
                            <button
                                @click="navigate(week.next)"
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Next &rarr;
                            </button>
                        </div>
                    </div>

                    <div v-if="rows.length === 0" class="text-sm text-gray-400">
                        No teachers available.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th :class="[thClass, 'text-left pr-8']">Teacher</th>
                                    <th
                                        v-for="d in dates"
                                        :key="d.date"
                                        :class="[thClass, 'text-center px-4', d.isToday ? 'text-indigo-500' : '']"
                                        style="min-width: 110px"
                                    >
                                        {{ d.label }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in rows"
                                    :key="row.teacher.id"
                                    class="border-b border-gray-50 hover:bg-gray-50 transition-colors"
                                >
                                    <td class="py-3.5 pr-8 font-semibold text-gray-800 whitespace-nowrap">
                                        {{ row.teacher.name }}
                                    </td>
                                    <td
                                        v-for="d in dates"
                                        :key="d.date"
                                        class="py-3.5 px-4 text-center"
                                        :class="d.isPast ? 'opacity-40' : ''"
                                    >
                                        <span
                                            v-if="resolvedStatus(row.days[d.date], d.isPast)"
                                            class="px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap"
                                            :class="[
                                                resolvedStatus(row.days[d.date], d.isPast).cls,
                                                (dayStatus(row.days[d.date]) === 'available' && !d.isPast) || dayStatus(row.days[d.date]) === 'booked'
                                                    ? 'cursor-pointer hover:opacity-75 transition-opacity'
                                                    : '',
                                            ]"
                                            @click="dayStatus(row.days[d.date]) === 'available' && !d.isPast
                                                ? openModal(row.teacher, d, 'book')
                                                : dayStatus(row.days[d.date]) === 'booked' && openModal(row.teacher, d, 'cancel')"
                                        >
                                            {{ resolvedStatus(row.days[d.date], d.isPast).label }}
                                        </span>
                                        <span v-else class="text-gray-300 text-sm">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ParentBookingModal
            :open="modalOpen"
            :teacher="modalTeacher"
            :day="modalDay"
            :mode="modalMode"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
