<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    open: Boolean,
    teachers: Array, // pass from backend
})

const form = ref({
    teacher_id: '',
    start_date: '',
    end_date: '',
    days: [],
    start_time: '',
    end_time: '',
    duration: 10,
    break: 0,
    max_bookings: 1,
    notes: '',
})

const daysOfWeek = [
    { label: 'Mon', value: 'monday' },
    { label: 'Tue', value: 'tuesday' },
    { label: 'Wed', value: 'wednesday' },
    { label: 'Thu', value: 'thursday' },
    { label: 'Fri', value: 'friday' },
    { label: 'Sat', value: 'saturday' },
    { label: 'Sun', value: 'sunday' },
];

const timeOptions = []

for (let h = 8; h < 22; h++) {
    for (let m of [0, 15, 30, 45]) {
        const hour = String(h).padStart(2, '0')
        const min = String(m).padStart(2, '0')
        timeOptions.push(`${hour}:${min}`)
    }
}

const filteredEndTimes = computed(() => {
    if (!form.value.start_time) return []

    return timeOptions.filter(t => t > form.value.start_time)
})

const today = new Date().toISOString().split('T')[0]
form.value.start_date = today

const toggleDay = (day) => {
    if (form.value.days.includes(day)) {
        form.value.days = form.value.days.filter(d => d !== day)
    } else {
        form.value.days.push(day)
    }
}

const submit = () => {
  router.post(route('availability.store'), {
    // form data
  })
}

const inputClass =
  "w-full mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"

const labelClass = "text-sm font-medium text-gray-700"
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 scale-100"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-100"
    >
        <div
            v-if="open"
            class="fixed inset-0 flex items-center justify-center z-50"
        >
            <div
                class="absolute inset-0 bg-black/40"
            ></div>

            <!-- MODAL -->
            <div
               class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-8 z-10 border border-gray-100"
            >
                <div class="space-y-8">

                    <h3 class="text-lg font-semibold mb-4">
                        Create Booking Slots
                    </h3>

                    <!-- Teacher -->
                    <div>
                        <label :class="labelClass">Teacher</label>
                        <select v-model="form.teacher_id" :class="inputClass">
                            <option value="">Select teacher</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">
                                {{ t.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date range -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label :class="labelClass">Start date</label>
                            <input
                                type="date"
                                v-model="form.start_date"
                                :min="today"
                                :class="inputClass"
                            />
                        </div>

                        <div>
                            <label :class="labelClass">End date</label>
                            <input
                                type="date"
                                v-model="form.end_date"
                                :min="form.start_date || today"
                                :class="inputClass"
                            />
                        </div>
                    </div>

                    <!-- Days -->
                    <div>
                        <label :class="labelClass">Days of week</label>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <button
                                v-for="day in daysOfWeek"
                                :key="day.value"
                                type="button"
                                @click="toggleDay(day.value)"
                                :class="[
                                    'px-3 py-1 rounded-full text-sm border',
                                    form.days.includes(day.value)
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white text-gray-600 border-gray-300'
                                ]"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label :class="labelClass">Start time</label>
                            <select v-model="form.start_time" :class="inputClass">
                                <option value="">Select time</option>
                                <option v-for="time in timeOptions" :key="time" :value="time">
                                    {{ time }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label :class="labelClass">End time</label>
                            <select
                                v-model="form.end_time"
                                :disabled="!form.start_time"
                                :class="[inputClass, !form.start_time && 'bg-gray-100 cursor-not-allowed']"
                            >
                                <option value="">Select time</option>

                                <option
                                    v-for="time in filteredEndTimes"
                                    :key="time"
                                    :value="time"
                                >
                                    {{ time }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Slot config -->
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label :class="labelClass">Duration (min)</label>
                            <select v-model="form.duration" :class="inputClass">
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="20">20</option>
                                <option :value="30">30</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label :class="labelClass">Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            :class="inputClass"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            @click="$emit('close')"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                        >
                            Cancel
                        </button>

                        <button
                            @click="submit"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition"
                        >
                            Create Slots
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </Transition>
</template>
