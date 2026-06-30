<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateUserForm from '@/Pages/Admin/Partials/CreateUserForm.vue';
import EditUserModal from '@/Pages/Admin/Partials/EditUserModal.vue';
import { Head } from '@inertiajs/vue3';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { localeRouteHelpers } from '@/utils/route';
import TabButton from '@/Components/TabButton.vue';

const { useRouteWithLocale } = localeRouteHelpers();

const page = usePage();

const search = ref(page.props.filters?.search ?? '');
const activePanelTab = ref('list');
const showEditModal = ref(false);
const selectedUser = ref(null);

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

const openEditModal = (user) => {
    selectedUser.value = user;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedUser.value = null;
};

const suspendUser = (user) => {
    router.patch(route('admin.users.suspend', { user: user.id }), {}, {
        preserveScroll: true,
    });
};

const formatRole = (role) => {
    if (! role) return '—';

    return role.charAt(0).toUpperCase() + role.slice(1);
};

const userStatus = (user) => {
    if (user.suspended_at) {
        return {
            label: 'Suspended',
            class: 'bg-red-100 text-red-700',
        };
    }

    if (! user.email_verified_at) {
        return {
            label: 'Awaiting verification',
            class: 'bg-blue-100 text-blue-700',
        };
    }

    return {
        label: 'Active',
        class: 'bg-green-100 text-green-700',
    };
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
                    v-if="page.props.flash?.error"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    {{ page.props.flash.error }}
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
                                        {{ formatRole(role.name) }}s ({{ users_counts[role.name] ?? 0 }})
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

                        <div class="flex justify-left flex-wrap gap-3 p-4 overflow-x-auto">
                            <table class="min-w-full bg-white border rounded p-4">
                                <thead>
                                    <tr class="bg-gray-100 text-left">
                                        <th class="p-2">Name</th>
                                        <th class="p-2">Email</th>
                                        <th class="p-2">Role</th>
                                        <th class="p-2">Status</th>
                                        <th class="p-2 text-right">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="user in users.data"
                                        :key="user.id"
                                        class="border-t"
                                        :class="{ 'bg-red-50': user.suspended_at }"
                                    >
                                        <td class="p-2">{{ user.name }}</td>
                                        <td class="p-2">{{ user.email }}</td>
                                        <td class="p-2">{{ formatRole(user.role) }}</td>
                                        <td class="p-2">
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="userStatus(user).class"
                                            >
                                                {{ userStatus(user).label }}
                                            </span>
                                        </td>
                                        <td class="p-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    title="Edit"
                                                    @click="openEditModal(user)"
                                                    class="inline-flex items-center justify-center rounded-md p-2 text-indigo-600 hover:bg-indigo-50 transition"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    :title="user.suspended_at ? 'Reactivate account' : 'Suspend account'"
                                                    @click="suspendUser(user)"
                                                    class="inline-flex items-center justify-center rounded-md p-2 transition"
                                                    :class="user.suspended_at
                                                        ? 'text-green-600 hover:bg-green-50'
                                                        : 'text-red-600 hover:bg-red-50'"
                                                >
                                                    <svg
                                                        v-if="user.suspended_at"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <svg
                                                        v-else
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
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

        <EditUserModal
            :show="showEditModal"
            :user="selectedUser"
            :roles="roles"
            @close="closeEditModal"
        />
    </AuthenticatedLayout>
</template>
