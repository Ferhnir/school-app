<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    schedule: Array,
});

const form = useForm({
    date:          '',
    start_time:    '',
    end_time:      '',
    slot_duration: 10,
});

const today = new Date().toISOString().split('T')[0];

const timeOptions = [];
for (let h = 8; h < 22; h++) {
    for (let m of [0, 15, 30, 45]) {
        timeOptions.push(
            String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0')
        );
    }
}

const filteredEndTimes = computed(() =>
    form.start_time ? timeOptions.filter(t => t > form.start_time) : []
);

const authUserId = usePage().props.auth.user.id;

const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

const submit = () => {
    const day = dayNames[new Date(form.date + 'T00:00:00').getDay()];

    form.transform(data => ({
        start_date:    data.date,
        end_date:      data.date,
        days:          [day],
        start_time:    data.start_time,
        end_time:      data.end_time,
        slot_duration: data.slot_duration,
    })).post(route('teachers.availabilities.store', { teacher: authUserId }), {
        onSuccess: () => form.reset(),
    });
};

const inputClass = 'w-full mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition';
const labelClass = 'text-sm font-medium text-gray-700';
</script>

<template>
    <Head title="Teacher Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Teacher Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Add availability form -->
                    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-5">Add Availability</h3>

                        <form @submit.prevent="submit" class="space-y-4">

                            <!-- Date -->
                            <div>
                                <label :class="labelClass">Date</label>
                                <input
                                    type="date"
                                    v-model="form.date"
                                    :min="today"
                                    :class="[inputClass, form.errors.date ? 'border-red-400' : '']"
                                />
                                <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.date }}
                                </p>
                            </div>

                            <!-- Start / End time -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label :class="labelClass">From</label>
                                    <select
                                        v-model="form.start_time"
                                        :class="[inputClass, form.errors.start_time ? 'border-red-400' : '']"
                                        @change="form.end_time = ''"
                                    >
                                        <option value="">Select time</option>
                                        <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                    <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.start_time }}
                                    </p>
                                </div>

                                <div>
                                    <label :class="labelClass">Until</label>
                                    <select
                                        v-model="form.end_time"
                                        :disabled="!form.start_time"
                                        :class="[inputClass, !form.start_time ? 'opacity-50 cursor-not-allowed' : '', form.errors.end_time ? 'border-red-400' : '']"
                                    >
                                        <option value="">Select time</option>
                                        <option v-for="t in filteredEndTimes" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                    <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.end_time }}
                                    </p>
                                </div>
                            </div>

                            <!-- Slot duration -->
                            <div>
                                <label :class="labelClass">Slot duration (minutes)</label>
                                <select v-model="form.slot_duration" :class="inputClass">
                                    <option :value="5">5 min</option>
                                    <option :value="10">10 min</option>
                                    <option :value="15">15 min</option>
                                    <option :value="20">20 min</option>
                                    <option :value="30">30 min</option>
                                </select>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Saving…' : 'Add availability' }}
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Upcoming schedule -->
                    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-5">Upcoming (next 14 days)</h3>

                        <div v-if="schedule.length === 0" class="text-sm text-gray-400">
                            No availability set yet.
                        </div>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="slot in schedule"
                                :key="slot.date"
                                class="flex items-center justify-between px-4 py-3 rounded-xl border border-gray-100 bg-gray-50 text-sm"
                            >
                                <span class="font-medium text-gray-800">{{ slot.label }}</span>
                                <div class="flex items-center gap-2 text-gray-500">
                                    <span class="tabular-nums">{{ slot.start_time }} – {{ slot.end_time }}</span>
                                    <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-medium">
                                        {{ slot.slot_duration }} min slots
                                    </span>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="slot.bookings_count > 0
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-400'"
                                    >
                                        {{ slot.bookings_count }} {{ slot.bookings_count === 1 ? 'booking' : 'bookings' }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
