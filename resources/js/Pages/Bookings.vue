<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { localeRouteHelpers } from '@/utils/route';
import TabButton from '@/Components/TabButton.vue';
import AvailabilityModal from '@/Components/Modals/AvailabilityModal.vue';

const { useRouteWithLocale } = localeRouteHelpers();

const page = usePage()

const availabilityModalShow = ref(false)

const props = defineProps({
    teachers: Object
});

const createBookingSlot = () => console.log('trigger event');

</script>

<template>
    <Head title="UsersPanel" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Bookings Panel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="border-b border-gray-200 mb-4">
                        <div class="flex items-center justify-between flex-wrap gap-4 p-4">

                            <div class="flex flex-wrap gap-3">
                                <TabButton
                                    @click="availabilityModalShow = true"
                                >
                                    Create Booking Slots
                                </TabButton>

                                <TabButton
                                    @click="createBookingSlot()"
                                >
                                    I don't know yet
                                </TabButton>
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
                                    <td class="p-3 font-medium">
                                        {{ teacher.name }}
                                    </td>

                                    <td
                                        v-for="(day, index) in teacher.days"
                                        :key="index"
                                        class="p-3 text-center cursor-pointer"
                                        @click="openDay(teacher.id, index)"
                                    >
                                        <span
                                            :class="statusClass(day)"
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

    </AuthenticatedLayout>
</template>
