<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateUserForm from '@/Pages/Admin/Partials/CreateUserForm.vue';
import { Head } from '@inertiajs/vue3';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { localeRouteHelpers } from '@/utils/route';
import TabButton from '@/Components/TabButton.vue';

const { useRouteWithLocale } = localeRouteHelpers();

const page = usePage();

const search = ref(page.props.filters?.search ?? '');
const activePanelTab = ref('list');

defineProps({
    users: Object,
    users_counts: Object,
    roles: Object,
    filters: Object,
});

const changeRole = (role) => {
    router.get(
        useRouteWithLocale('admin.users.index', { role }),
        {},
        {
            preserveState: true,
            replace: true,
        }
    );
};

const searchUsers = () => {
    router.get(useRouteWithLocale('admin.users.index'), {
        search: search.value,
        role: page.props.filters.role,
    }, {
        preserveState: true,
        replace: true,
    });
};

const formatRole = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1) + 's';
};

const formatDate = (date) => {
    if (!date) return '';

    return new Intl.DateTimeFormat('pl-PL', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
};
</script>

<template>
    <Head title="UsersPanel" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Users Panel
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.message"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                >
                    {{ page.props.flash.message }}
                </div>

                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="border-b border-gray-200 bg-gray-50 pt-4">
                        <nav class="flex w-full" aria-label="Panel tabs">
                            <button
                                type="button"
                                @click="activePanelTab = 'list'"
                                :class="[
                                    'w-1/2 rounded-t-lg border border-b-0 px-4 py-2.5 text-center text-sm font-medium transition',
                                    activePanelTab === 'list'
                                        ? 'border-gray-200 bg-white text-indigo-600 shadow-sm'
                                        : 'border-transparent text-gray-600 hover:bg-white/70 hover:text-gray-800',
                                ]"
                            >
                                Users
                            </button>
                            <button
                                type="button"
                                @click="activePanelTab = 'create'"
                                :class="[
                                    'w-1/2 rounded-t-lg border border-b-0 px-4 py-2.5 text-center text-sm font-medium transition',
                                    activePanelTab === 'create'
                                        ? 'border-gray-200 bg-white text-indigo-600 shadow-sm'
                                        : 'border-transparent text-gray-600 hover:bg-white/70 hover:text-gray-800',
                                ]"
                            >
                                Create account
                            </button>
                        </nav>
                    </div>

                    <div v-if="activePanelTab === 'list'">
                        <div class="border-b border-gray-200 mb-4">
                            <div class="flex items-center justify-between flex-wrap gap-4 p-4">

                                <!-- LEFT: Tabs -->
                                <div class="flex flex-wrap gap-3">
                                    <TabButton
                                        v-for="role in roles"
                                        :key="role.name"
                                        :active="filters.role === role.name"
                                        @click="changeRole(role.name)"
                                    >
                                        {{ formatRole(role.name) }} ({{ users_counts[role.name] ?? 0 }})
                                    </TabButton>

                                    <TabButton
                                        :active="filters.role === 'no_roles'"
                                        @click="changeRole('no_roles')"
                                    >
                                        No role ({{ users_counts.no_roles }})
                                    </TabButton>

                                    <TabButton
                                        :active="!filters.role"
                                        @click="changeRole(null)"
                                    >
                                        All ({{ users_counts.total }})
                                    </TabButton>
                                </div>

                                <!-- RIGHT: Search -->
                                <div>
                                    <input
                                        v-model="search"
                                        @input="searchUsers"
                                        type="text"
                                        placeholder="Search by name..."
                                        class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    />
                                </div>

                            </div>
                        </div>

                        <div class="flex justify-left flex-wrap gap-3 p-4">
                            <table class="min-w-full bg-white border rounded p-4">
                                <thead>
                                    <tr class="bg-gray-100 text-left">
                                        <th class="p-2">Name</th>
                                        <th class="p-2">Email</th>
                                        <th class="p-2">Verified</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="user in users.data" :key="user.id" class="border-t">
                                        <td class="p-2">{{ user.name }}</td>
                                        <td class="p-2">{{ user.email }}</td>
                                        <td class="p-2">{{ formatDate(user.email_verified_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-center p-4">
                            <div class="flex flex-wrap gap-2">
                                <template v-for="(link, i) in users.links" :key="i">

                                    <!-- Disabled -->
                                    <span
                                        v-if="!link.url"
                                        v-html="link.label"
                                        class="px-3 py-1 text-gray-400 bg-gray-100 rounded-md text-sm"
                                    />

                                    <!-- Active -->
                                    <span
                                        v-else-if="link.active"
                                        v-html="link.label"
                                        class="px-3 py-1 text-white bg-indigo-500 rounded-md text-sm"
                                    />

                                    <!-- Clickable -->
                                    <a
                                        v-else
                                        :href="link.url"
                                        v-html="link.label"
                                        class="px-3 py-1 text-gray-700 bg-white border rounded-md text-sm hover:bg-gray-100"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex justify-center p-6">
                        <CreateUserForm :roles="roles" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
