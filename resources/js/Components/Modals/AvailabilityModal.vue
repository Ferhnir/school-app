<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { toIsoDateTime } from '@/utils/date'

const props = defineProps({
    open:     Boolean,
    teachers: Array,
})

const emit = defineEmits(['close'])

const form = useForm({
    teacher_id:    '',
    start_date:    '',
    end_date:      '',
    days:          [],
    start_time:    '',
    end_time:      '',
    slot_duration: 10,
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

const filteredEndTimes = computed(() =>
    form.start_time ? timeOptions.filter(t => t > form.start_time) : []
)

const today = new Date().toISOString().split('T')[0]
form.start_date = today

const toggleDay = (day) => {
    if (form.days.includes(day)) {
        form.days = form.days.filter(d => d !== day)
    } else {
        form.days.push(day)
    }
}

const submit = () => {
    if (!form.teacher_id) return

    form.transform(data => ({
        start_date:    toIsoDateTime(data.start_date),
        end_date:      toIsoDateTime(data.end_date),
        days:          data.days,
        start_time:    toIsoDateTime(data.start_date, data.start_time),
        end_time:      toIsoDateTime(data.start_date, data.end_time),
        slot_duration: data.slot_duration,
    })).post(
        route('teachers.availabilities.store', { teacher: form.teacher_id }),
        { onSuccess: () => emit('close') }
    )
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
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.teacher_id" class="mt-1 text-xs text-red-600">{{ form.errors.teacher_id }}</p>
                    </div>

                    <!-- Date range -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label :class="labelClass">Start date</label>
                            <input type="date" v-model="form.start_date" :min="today" :class="inputClass" />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">End date</label>
                            <input type="date" v-model="form.end_date" :min="form.start_date || today" :class="inputClass" />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
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
                                    'px-3 py-1 rounded-full text-sm border transition',
                                    form.days.includes(day.value)
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400'
                                ]"
                            >{{ day.label }}</button>
                        </div>
                        <p v-if="form.errors.days" class="mt-1 text-xs text-red-600">{{ form.errors.days }}</p>
                    </div>

                    <!-- Time -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label :class="labelClass">Start time</label>
                            <select v-model="form.start_time" :class="inputClass" @change="form.end_time = ''">
                                <option value="">Select time</option>
                                <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
                            </select>
                            <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600">{{ form.errors.start_time }}</p>
                        </div>
                        <div>
                            <label :class="labelClass">End time</label>
                            <select v-model="form.end_time" :disabled="!form.start_time" :class="[inputClass, !form.start_time && 'opacity-50 cursor-not-allowed']">
                                <option value="">Select time</option>
                                <option v-for="time in filteredEndTimes" :key="time" :value="time">{{ time }}</option>
                            </select>
                            <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600">{{ form.errors.end_time }}</p>
                        </div>
                    </div>

                    <!-- Slot duration -->
                    <div>
                        <label :class="labelClass">Slot duration (min)</label>
                        <select v-model="form.slot_duration" :class="inputClass">
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="20">20</option>
                            <option :value="30">30</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            @click="emit('close')"
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                        >Cancel</button>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                        >{{ form.processing ? 'Saving…' : 'Create Slots' }}</button>
                    </div>

                </div>

            </div>
        </div>
    </Transition>
</template>
