<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import UserRoleBadge from '@/Components/UserRoleBadge.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    users: Object,
});

const page = usePage();
const currentUserId = () => page.props.auth.user.id;

const permissionLabels = {
    manage_procedure_types: '手続き種別マスタ',
    manage_custom_fields: 'カスタムフィールド設定',
    manage_imports: 'Excelインポート',
};

const confirmingDeletion = ref(null);
const deleteForm = useForm({});

function confirmDelete(targetUser) {
    confirmingDeletion.value = targetUser;
}

function closeDeleteModal() {
    confirmingDeletion.value = null;
}

function destroyUser() {
    deleteForm.delete(route('users.destroy', confirmingDeletion.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
}
</script>

<template>
    <Head title="ユーザー一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    ユーザー管理
                </h2>
                <Link :href="route('users.create')">
                    <PrimaryButton>新規登録</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        氏名
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        メールアドレス
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        権限
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="targetUser in users.data"
                                    :key="targetUser.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="
                                                route(
                                                    'users.edit',
                                                    targetUser.id,
                                                )
                                            "
                                            class="font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ targetUser.name }}
                                        </Link>
                                        <span
                                            v-if="
                                                targetUser.id ===
                                                currentUserId()
                                            "
                                            class="ml-1 text-xs text-gray-400"
                                        >
                                            （自分）
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ targetUser.email }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <UserRoleBadge
                                            :role="targetUser.role"
                                        />
                                        <p
                                            v-if="
                                                targetUser.permissions
                                                    ?.length > 0
                                            "
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            {{
                                                targetUser.permissions
                                                    .map(
                                                        (key) =>
                                                            permissionLabels[
                                                                key
                                                            ],
                                                    )
                                                    .join('、')
                                            }}
                                        </p>
                                    </td>
                                    <td class="space-x-3 px-4 py-3 text-right">
                                        <Link
                                            :href="
                                                route(
                                                    'users.edit',
                                                    targetUser.id,
                                                )
                                            "
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </Link>
                                        <button
                                            v-if="
                                                targetUser.id !==
                                                currentUserId()
                                            "
                                            type="button"
                                            class="text-sm text-red-600 hover:text-red-900"
                                            @click="confirmDelete(targetUser)"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td
                                        colspan="4"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        登録されているユーザーがいません。
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.links.length > 3"
                    class="mt-4 flex flex-wrap gap-1"
                >
                    <Link
                        v-for="(link, index) in users.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="{
                            'bg-gray-800 text-white': link.active,
                            'text-gray-500 hover:bg-gray-100':
                                !link.active && link.url,
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
                    ユーザー「{{ confirmingDeletion.name }}」を削除しますか？
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    この操作は取り消せません。削除後はこのユーザーでログインできなくなります。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        キャンセル
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': deleteForm.processing }"
                        :disabled="deleteForm.processing"
                        @click="destroyUser"
                    >
                        削除する
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
