<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const page = usePage();
const isOwner = () => page.props.auth.user.role === 'owner';

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const confirmingDeletion = ref(null);
const deleteForm = useForm({});

function confirmDelete(client) {
    confirmingDeletion.value = client;
}

function closeDeleteModal() {
    confirmingDeletion.value = null;
}

function destroyClient() {
    deleteForm.delete(route('clients.destroy', confirmingDeletion.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}

let searchDebounce = null;

function applyFilters() {
    router.get(
        route('clients.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

function resetFilters() {
    search.value = '';
    status.value = '';
}
</script>

<template>
    <Head title="顧問先一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2
                    class="text-xl font-semibold leading-tight text-gray-800"
                >
                    顧問先
                </h2>
                <Link :href="route('clients.create')">
                    <PrimaryButton>新規登録</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="顧問先名で検索"
                        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <select
                        v-model="status"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">すべてのステータス</option>
                        <option value="active">契約中</option>
                        <option value="inactive">契約終了</option>
                    </select>
                    <button
                        v-if="search || status"
                        type="button"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                        @click="resetFilters"
                    >
                        フィルタをリセット
                    </button>
                </div>

                <div
                    class="overflow-hidden rounded-lg bg-white shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        顧問先名
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        代表者
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        電話番号
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        担当者
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ステータス
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="client in clients.data"
                                    :key="client.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="
                                                route(
                                                    'clients.edit',
                                                    client.id,
                                                )
                                            "
                                            class="font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ client.name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ client.representative_name || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ client.phone || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            client.assigned_user?.name || '未割当'
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <StatusBadge :status="client.status" />
                                    </td>
                                    <td
                                        class="space-x-3 px-4 py-3 text-right"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'clients.edit',
                                                    client.id,
                                                )
                                            "
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </Link>
                                        <button
                                            v-if="isOwner()"
                                            type="button"
                                            class="text-sm text-red-600 hover:text-red-900"
                                            @click="confirmDelete(client)"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="clients.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        条件に一致する顧問先がありません。
                                        <button
                                            v-if="search || status"
                                            type="button"
                                            class="ml-1 text-indigo-600 underline hover:text-indigo-800"
                                            @click="resetFilters"
                                        >
                                            フィルタをリセット
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="clients.links.length > 3"
                    class="mt-4 flex flex-wrap gap-1"
                >
                    <Link
                        v-for="(link, index) in clients.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="{
                            'bg-gray-800 text-white': link.active,
                            'text-gray-500 hover:bg-gray-100': !link.active && link.url,
                            'cursor-not-allowed text-gray-300': !link.url,
                        }"
                        preserve-state
                    />
                </div>
            </div>
        </div>

        <Modal :show="confirmingDeletion !== null" @close="closeDeleteModal">
            <div class="p-6" v-if="confirmingDeletion">
                <h2 class="text-lg font-medium text-gray-900">
                    顧問先「{{ confirmingDeletion.name }}」を削除しますか？
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    この操作は取り消せません。関連する情報も参照できなくなります。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        キャンセル
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': deleteForm.processing }"
                        :disabled="deleteForm.processing"
                        @click="destroyClient"
                    >
                        削除する
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
