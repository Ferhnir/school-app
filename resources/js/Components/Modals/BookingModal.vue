<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import moment from 'moment';

const props = defineProps({
    open:    Boolean,
    teacher: Object,
    day:     Object,
    parents: Array,
})

const emit = defineEmits(['close'])

const slots            = ref([])
const loading          = ref(false)
const selectedSlotTime = ref('')
const parentId         = ref('')
const error            = ref('')
const submitting       = ref(false)

const selectedSlot = computed(() => slots.value.find(s => s.start_time === selectedSlotTime.value) ?? null)

const fetchSlots = async () => {
    if (!props.teacher || !props.day) return

    loading.value          = true
    slots.value            = []
    selectedSlotTime.value = ''
    parentId.value         = ''
    error.value            = ''

    try {
        const url = route('teacher.slots.index', { teacher: props.teacher.id, date: moment.utc(props.day.date).unix() })
        const res = await fetch(url)
        slots.value = await res.json()
    } finally {
        loading.value = false
    }
}

watch(() => props.open, (val) => {
    if (val) fetchSlots()
})

const submit = () => {
    if (!selectedSlot.value || !parentId.value) return

    submitting.value = true
    error.value      = ''

    router.post(
        route('teacher.slots.store', { teacher: props.teacher.id, date: moment.utc(props.day.date).unix() }),
        {
            parent_id:  parentId.value,
            start_time: selectedSlot.value.start_time,
        },
        {
            onSuccess: () => {
                submitting.value = false
                emit('close')
            },
            onError: (errors) => {
                submitting.value = false
                error.value = errors.booking ?? 'Something went wrong.'
            },
        }
    )
}

const formatTime = (t) => t?.slice(0, 5) ?? t

const inputClass = 'w-full mt-1 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition'
const labelClass = 'text-sm font-medium text-gray-700'
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="open" class="fixed inset-0 flex items-center justify-center z-50">
            <div class="absolute inset-0 bg-black/40" @click="$emit('close')" />

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 z-10 border border-gray-100">

                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Book appointment</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ teacher?.name }} &mdash; {{ day?.label }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <!-- Error -->
                <div v-if="error" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                    {{ error }}
                </div>

                <!-- Slots -->
                <div class="mb-6">
                    <label :class="labelClass">Time slot</label>

                    <div v-if="loading" class="mt-2 text-sm text-gray-400">Loading slots…</div>

                    <select
                        v-else
                        v-model="selectedSlotTime"
                        :disabled="slots.length === 0"
                        :class="[inputClass, slots.length === 0 && 'opacity-50 cursor-not-allowed']"
                    >
                        <option value="">
                            {{ slots.length === 0 ? 'No available slots' : 'Select a time slot' }}
                        </option>
                        <option v-for="slot in slots" :key="slot.start_time" :value="slot.start_time">
                            {{ formatTime(slot.start_time) }} – {{ formatTime(slot.end_time) }}
                        </option>
                    </select>
                </div>

                <!-- Parent -->
                <div class="mb-8">
                    <label :class="labelClass">Parent</label>
                    <select v-model="parentId" :class="inputClass">
                        <option value="">Select parent</option>
                        <option v-for="p in parents" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submit"
                        :disabled="!selectedSlot || !parentId || submitting"
                        class="px-5 py-2 bg-indigo-600 text-white rounded-xl shadow hover:bg-indigo-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {{ submitting ? 'Booking…' : 'Book slot' }}
                    </button>
                </div>

            </div>
        </div>
    </Transition>
</template>
