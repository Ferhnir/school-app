<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { localeRouteHelpers } from '@/utils/route';
import TabButton from '@/Components/TabButton.vue';
import AvailabilityModal from '@/Components/Modals/AvailabilityModal.vue';
import BookingModal from '@/Components/Modals/BookingModal.vue';

const { useRouteWithLocale } = localeRouteHelpers();

const availabilityModalShow = ref(false);
const bookingModalShow      = ref(false);
const selectedTeacher       = ref(null);
const selectedDay           = ref(null);

const props = defineProps({
    week:     Object,
    teachers: Array,
    parents:  Array,
});

const days = computed(() => props.teachers[0]?.days?.map(d => d.label) ?? []);

const statusClass = (day) => {
    const map = {
        free:        'bg-green-100 text-green-700',
        partial:     'bg-yellow-100 text-yellow-700',
        full:        'bg-red-100 text-red-700',
        unavailable: 'bg-gray-100 text-gray-400',
    };
    return map[day.status] ?? 'bg-gray-100 text-gray-400';
};

const formatStatus = (day) => {
    if (day.status === 'unavailable') return 'No slots';
    if (day.status === 'free')        return 'Available';
    if (day.status === 'full')        return 'Fully booked';
    return `${day.bookings_count} booking${day.bookings_count !== 1 ? 's' : ''}`;
};

const today = new Date().toISOString().slice(0, 10);

const isPast = (day) => day.date < today;

const isClickable = (day) => !isPast(day) && day.status !== 'unavailable' && day.status !== 'full';

const openDay = (teacher, day) => {
    if (!isClickable(day)) return;
    selectedTeacher.value  = teacher;
    selectedDay.value      = day;
    bookingModalShow.value = true;
};

const navigate = (offset) => {
    router.get(useRouteWithLocale('admin.bookings.index'), { week: offset }, { preserveState: true });
};
</script>

<template>
    <Head title="Bookings Panel" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bookings Panel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">

                    <div class="border-b border-gray-200 mb-4">
                        <div class="flex items-center justify-between flex-wrap gap-4 p-4">

                            <div class="flex flex-wrap gap-3">
                                <TabButton @click="availabilityModalShow = true">
                                    Create Booking Slots
                                </TabButton>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    @click="navigate(week.prev)"
                                    class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50"
                                >
                                    &larr; Prev
                                </button>
                                <span class="text-sm font-medium text-gray-700 min-w-36 text-center">
                                    {{ week.label }}
                                </span>
                                <button
                                    @click="navigate(week.next)"
                                    class="px-3 py-1.5 text-sm border rounded hover:bg-gray-50"
                                >
                                    Next &rarr;
                                </button>
                            </div>

                        </div>
                    </div>

                    <div>
                        <table class="w-full border rounded-xl overflow-hidden p-4">
                            <thead class="bg-gray-50 text-sm">
                                <tr>
                                    <th class="p-3 text-left">Teacher</th>
                                    <th v-for="day in days" :key="day" class="p-3 text-center">
                                        {{ day }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="teacher in teachers" :key="teacher.id" class="border-t">
                                    <td class="p-3 font-medium">{{ teacher.name }}</td>

                                    <td
                                        v-for="day in teacher.days"
                                        :key="day.date"
                                        class="p-3 text-center"
                                        :class="isClickable(day) ? 'cursor-pointer hover:bg-gray-50' : ''"
                                        @click="openDay(teacher, day)"
                                    >
                                        <span
                                            :class="[statusClass(day), isPast(day) ? 'opacity-40' : '']"
                                            class="px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ formatStatus(day) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <AvailabilityModal
            :open="availabilityModalShow"
            :teachers="teachers"
            @close="availabilityModalShow = false"
        />

        <BookingModal
            :open="bookingModalShow"
            :teacher="selectedTeacher"
            :day="selectedDay"
            :parents="parents"
            @close="bookingModalShow = false"
        />

    </AuthenticatedLayout>
</template>
