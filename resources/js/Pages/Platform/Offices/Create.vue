<script setup>
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    office_name: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
});

const submit = () => {
    form.post(route('platform.offices.store'));
};
</script>

<template>
    <Head title="事務所の新規契約" />

    <PlatformAuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                事務所の新規契約
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <h3 class="text-sm font-semibold text-gray-700">
                            事務所情報
                        </h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="office_name">
                                    事務所名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="office_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.office_name"
                                    placeholder="例：〇〇社会保険労務士事務所"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.office_name"
                                />
                            </div>
                        </div>

                        <h3
                            class="mt-6 border-t border-gray-100 pt-6 text-sm font-semibold text-gray-700"
                        >
                            最初のオーナーアカウント
                        </h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="owner_name">
                                    氏名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="owner_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.owner_name"
                                    placeholder="例：山田 太郎"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.owner_name"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="owner_email">
                                    メールアドレス
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="owner_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.owner_email"
                                    placeholder="例：taro@example.co.jp"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.owner_email"
                                />
                            </div>

                            <div>
                                <InputLabel for="owner_password">
                                    パスワード
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="owner_password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.owner_password"
                                    placeholder="8文字以上の半角英数字"
                                    required
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.owner_password"
                                />
                            </div>

                            <div>
                                <InputLabel for="owner_password_confirmation">
                                    パスワード（確認用）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="owner_password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.owner_password_confirmation"
                                    placeholder="確認のためもう一度入力してください"
                                    required
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors.owner_password_confirmation
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="route('platform.offices.index')">
                                <SecondaryButton type="button">
                                    キャンセル
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                契約する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PlatformAuthenticatedLayout>
</template>
