<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    user: Object,
    roles: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    role: '',
});

watch(
    () => props.user,
    (user) => {
        if (! user) return;

        form.name = user.name;
        form.email = user.email;
        form.role = user.role ?? '';
        form.clearErrors();
    },
    { immediate: true },
);

const submit = () => {
    if (! props.user) return;

    form.patch(route('admin.users.update', { user: props.user.id }), {
        onSuccess: () => emit('close'),
    });
};

const close = () => {
    form.clearErrors();
    emit('close');
};

const formatRole = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Edit user</h2>
            <p class="mt-1 text-sm text-gray-600">
                Update account details and role.
            </p>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div>
                    <InputLabel for="edit-name" value="Name" />
                    <TextInput
                        id="edit-name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autocomplete="name"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="edit-email" value="Email" />
                    <TextInput
                        id="edit-email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="edit-role" value="Role" />
                    <select
                        id="edit-role"
                        v-model="form.role"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="" disabled>Select a role</option>
                        <option v-for="role in roles" :key="role.name" :value="role.name">
                            {{ formatRole(role.name) }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton type="button" @click="close">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Save changes
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
