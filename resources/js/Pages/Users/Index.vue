<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type UserRow = {
    id: number;
    name: string;
    email: string;
    created_at?: string | null;
    roles: string[];
};

const props = defineProps<{
    users: UserRow[];
}>();

const formatDate = (value?: string | null): string =>
    value ? new Date(value).toLocaleDateString() : '--';
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Directory</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">Users</h2>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="pos-btn pos-btn-outline"
                >
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="rounded-3xl bg-white/60 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-xl">
                <header class="flex items-center justify-between border-b border-white/30 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">All Users</h3>
                        <p class="text-sm text-slate-600">Manage access, roles, and team members.</p>
                    </div>
                    <p class="text-sm text-slate-500">{{ props.users.length }} total</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="pos-table">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 font-semibold">ID</th>
                                <th class="px-6 py-3 font-semibold">Name</th>
                                <th class="px-6 py-3 font-semibold">Email</th>
                                <th class="px-6 py-3 font-semibold">Roles</th>
                                <th class="px-6 py-3 font-semibold">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="props.users.length === 0">
                                <td class="px-6 py-8 text-center text-slate-500" colspan="5">
                                    No users found.
                                </td>
                            </tr>
                            <tr v-for="user in props.users" :key="user.id" class="border-t border-white/20">
                                <td class="px-6 py-3 text-slate-600">{{ user.id }}</td>
                                <td class="px-6 py-3 font-semibold text-slate-900">{{ user.name }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ user.email }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="role in user.roles.length ? user.roles : ['staff']"
                                            :key="role"
                                            class="pos-badge capitalize"
                                            :class="role === 'admin' ? 'pos-badge-success' : 'pos-badge-neutral'"
                                        >
                                            {{ role }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ formatDate(user.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
