<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NavLink from '@/Components/NavLink.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    test: String
});

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #navigation-menu>
            <NavLink
                :href="route('admin.users.index', { locale: $page.props.locale })"
                :active="route().current('admin.users.*')"
            >
                Users
            </NavLink>
        </template>

        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Admin Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        {{ test }}
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
