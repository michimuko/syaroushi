<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    targetUser: Object,
});

const permissionOptions = [
    {
        key: 'manage_procedure_types',
        label: '手続き種別マスタの編集',
        description: '周期・通知タイミングの設定を変更できます。',
    },
    {
        key: 'manage_custom_fields',
        label: 'カスタムフィールド設定',
        description: '独自項目の追加・編集・削除ができます。',
    },
    {
        key: 'manage_imports',
        label: 'Excel移行ウィザードの実行',
        description: '顧問先・タスクのExcel一括インポートができます。',
    },
];

const form = useForm({
    name: props.targetUser.name,
    email: props.targetUser.email,
    role: props.targetUser.role,
    permissions: props.targetUser.permissions ?? [],
});

const submit = () => {
    form.put(route('users.update', props.targetUser.id));
};
</script>

<template>
    <Head title="ユーザー情報の編集" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                ユーザー情報の編集
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="name">
                                    氏名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    placeholder="例：山田 花子"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="email">
                                    メールアドレス
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    placeholder="例：hanako@example.co.jp"
                                    required
                                    autocomplete="username"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="role">
                                    権限
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="staff">社員</option>
                                    <option value="owner">オーナー</option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.role"
                                />
                            </div>

                            <div v-if="form.role === 'staff'" class="sm:col-span-2">
                                <InputLabel>個別権限</InputLabel>
                                <p class="mt-1 text-xs text-gray-500">
                                    通常オーナー限定の操作を、この社員にも個別に許可できます。
                                </p>
                                <div class="mt-2 space-y-2">
                                    <label
                                        v-for="option in permissionOptions"
                                        :key="option.key"
                                        class="flex items-start gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="option.key"
                                            v-model="form.permissions"
                                            class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        />
                                        <span class="text-sm text-gray-700">
                                            {{ option.label }}
                                            <span class="block text-xs text-gray-500">{{
                                                option.description
                                            }}</span>
                                        </span>
                                    </label>
                                </div>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.permissions"
                                />
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-gray-500">
                            パスワードの変更は本人がプロフィール画面から行ってください。
                        </p>

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="route('users.index')">
                                <SecondaryButton type="button">
                                    キャンセル
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                更新する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
