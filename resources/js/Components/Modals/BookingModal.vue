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
const bookedParentIds  = ref([])
const loading          = ref(false)
const selectedSlotTime = ref('')
const parentId         = ref('')
const error            = ref('')
const submitting       = ref(false)

const selectedSlot = computed(() => slots.value.find(s => s.start_time === selectedSlotTime.value) ?? null)

const isParentBooked = (parent) => bookedParentIds.value.includes(parent.id)

const parentOptionLabel = (parent) =>
    isParentBooked(parent) ? `${parent.name} (already booked)` : parent.name

const printBookingsUrl = computed(() => {
    if (! props.teacher || ! props.day) return null

    return route('admin.bookings.download', {
        teacher: props.teacher.id,
        date: moment.utc(props.day.date).unix(),
    })
})

const fetchSlots = async () => {
    if (!props.teacher || !props.day) return

    loading.value          = true
    slots.value            = []
    bookedParentIds.value  = []
    selectedSlotTime.value = ''
    parentId.value         = ''
    error.value            = ''

    try {
        const url = route('slots.index', { teacher: props.teacher.id, date: moment.utc(props.day.date).unix() })
        const res = await fetch(url)
        const data = await res.json()
        slots.value = data.slots ?? []
        bookedParentIds.value = data.booked_parent_ids ?? []
    } finally {
        loading.value = false
    }
}

watch(() => props.open, (val) => {
    if (val) fetchSlots()
})

const submit = () => {
    if (!selectedSlot.value || !parentId.value) return

    const parent = props.parents.find(p => p.id === Number(parentId.value))
    if (parent && isParentBooked(parent)) return

    submitting.value = true
    error.value      = ''

    router.post(
        route('slots.store', { teacher: props.teacher.id, date: moment.utc(props.day.date).unix() }),
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

const slotRowClass = (slot) => {
    if (!slot.is_available) {
        return 'bg-gray-50 border-gray-100 text-gray-400 cursor-not-allowed'
    }
    if (selectedSlotTime.value === slot.start_time) {
        return 'bg-indigo-50 border-indigo-300 text-indigo-900 cursor-pointer'
    }
    return 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 cursor-pointer'
}

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
                    <label :class="labelClass">Time slots</label>

                    <div v-if="loading" class="mt-2 text-sm text-gray-400">Loading slots…</div>

                    <div v-else-if="slots.length === 0" class="mt-2 text-sm text-gray-400">
                        No slots available for this day.
                    </div>

                    <div v-else class="mt-2 space-y-1.5 max-h-56 overflow-y-auto pr-1">
                        <button
                            v-for="slot in slots"
                            :key="slot.start_time"
                            type="button"
                            :disabled="!slot.is_available"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg border text-sm transition"
                            :class="slotRowClass(slot)"
                            @click="slot.is_available && (selectedSlotTime = slot.start_time)"
                        >
                            <span class="font-medium tabular-nums">
                                {{ formatTime(slot.start_time) }} – {{ formatTime(slot.end_time) }}
                            </span>
                            <span v-if="slot.booked_by" class="text-xs text-gray-400">
                                {{ slot.booked_by }}
                            </span>
                            <span v-else-if="selectedSlotTime === slot.start_time" class="text-xs font-medium text-indigo-600">
                                Selected
                            </span>
                            <span v-else class="text-xs text-green-600">
                                Free
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Parent -->
                <div class="mb-8">
                    <label :class="labelClass">Parent</label>
                    <select v-model="parentId" :class="inputClass">
                        <option value="">Select parent</option>
                        <option
                            v-for="p in parents"
                            :key="p.id"
                            :value="p.id"
                            :disabled="isParentBooked(p)"
                        >
                            {{ parentOptionLabel(p) }}
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
                    <a
                        v-if="printBookingsUrl"
                        :href="printBookingsUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print bookings
                    </a>
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
