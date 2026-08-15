<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="ユーザーの新規登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                ユーザーの新規登録
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <InputLabel for="name">
                                    氏名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div>
                                <InputLabel for="email">
                                    メールアドレス
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div>
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

                            <div>
                                <InputLabel for="password">
                                    パスワード
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.password"
                                />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation">
                                    パスワード（確認用）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.password_confirmation"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-5 flex items-center justify-end gap-3 border-t border-gray-100 pt-5"
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
                                登録する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
