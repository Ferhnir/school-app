<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateUserModal from '@/Pages/Admin/Partials/CreateUserModal.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { localeRouteHelpers } from '@/utils/route';

const { useRouteWithLocale } = localeRouteHelpers();

const page = usePage();
const showCreateUserModal = ref(false);

defineProps({
    users_counts: Object,
    roles: Array,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <div
                    v-if="page.props.flash?.message"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    {{ page.props.flash.message }}
                </div>

                <!-- Users -->
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Users</h3>
                        <p class="mt-1 text-sm text-gray-600">Manage accounts and roles.</p>
                    </div>
                    <div class="flex flex-wrap gap-3 p-6">
                        <Link
                            :href="useRouteWithLocale('admin.users.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            List of users
                        </Link>
                        <button
                            type="button"
                            @click="showCreateUserModal = true"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Create user
                        </button>
                    </div>
                </section>

                <!-- Bookings -->
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Bookings</h3>
                        <p class="mt-1 text-sm text-gray-600">View and export booking schedules.</p>
                    </div>
                    <div class="flex flex-wrap gap-3 p-6">
                        <Link
                            :href="useRouteWithLocale('admin.bookings.index')"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            This week bookings
                        </Link>
                        <button
                            type="button"
                            disabled
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-5 py-3 text-sm font-medium text-gray-400 shadow-sm cursor-not-allowed"
                            title="Coming soon"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download bookings (today)
                        </button>
                    </div>
                </section>

                <!-- Catering -->
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">Catering</h3>
                        <p class="mt-1 text-sm text-gray-600">Meals and catering management.</p>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500">Coming soon.</p>
                    </div>
                </section>

            </div>
        </div>

        <CreateUserModal
            :show="showCreateUserModal"
            :roles="roles"
            @close="showCreateUserModal = false"
        />
    </AuthenticatedLayout>
</template>
