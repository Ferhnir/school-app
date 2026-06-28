<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { localeRouteHelpers } from '@/utils/route';

const { useRouteWithLocale } = localeRouteHelpers();

defineProps({
    roles: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: '',
    email: '',
    role: '',
});

const submit = () => {
    form.post(useRouteWithLocale('admin.users.store'));
};

const formatRole = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};
</script>

<template>
    <form @submit.prevent="submit" class="w-full max-w-xl space-y-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Create user account</h3>
            <p class="mt-1 text-sm text-gray-600">
                Add a new user with name, email, and role. A random password is generated automatically.
            </p>
        </div>

        <div>
            <InputLabel for="name" value="Name" />
            <TextInput
                id="name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="email" value="Email" />
            <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.email"
                required
                autocomplete="username"
            />
            <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div>
            <InputLabel for="role" value="Role" />
            <select
                id="role"
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

        <div class="flex items-center justify-end">
            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Create account
            </PrimaryButton>
        </div>
    </form>
</template>
