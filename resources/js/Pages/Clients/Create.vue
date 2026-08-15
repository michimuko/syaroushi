<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    staffOptions: Array,
});

const form = useForm({
    name: '',
    representative_name: '',
    address: '',
    phone: '',
    email: '',
    contract_start_date: '',
    status: 'active',
    assigned_user_id: '',
    notes: '',
});

const submit = () => {
    form.post(route('clients.store'));
};
</script>

<template>
    <Head title="顧問先の新規登録" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                顧問先の新規登録
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <!-- 基本情報 -->
                        <h3
                            class="text-sm font-semibold text-gray-700"
                        >
                            基本情報
                        </h3>
                        <div
                            class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <InputLabel for="name">
                                    顧問先名
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
                                <InputLabel for="representative_name">
                                    代表者名
                                </InputLabel>
                                <TextInput
                                    id="representative_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.representative_name"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.representative_name"
                                />
                            </div>

                            <div>
                                <InputLabel for="contract_start_date">
                                    契約開始日
                                </InputLabel>
                                <TextInput
                                    id="contract_start_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.contract_start_date"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.contract_start_date"
                                />
                            </div>
                        </div>

                        <!-- 連絡先 -->
                        <h3
                            class="mt-5 border-t border-gray-100 pt-5 text-sm font-semibold text-gray-700"
                        >
                            連絡先
                        </h3>
                        <div
                            class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <InputLabel for="phone">電話番号</InputLabel>
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.phone"
                                />
                            </div>

                            <div>
                                <InputLabel for="email">メールアドレス</InputLabel>
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div>
                                <InputLabel for="address">住所</InputLabel>
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.address"
                                />
                            </div>
                        </div>

                        <!-- 管理情報 -->
                        <h3
                            class="mt-5 border-t border-gray-100 pt-5 text-sm font-semibold text-gray-700"
                        >
                            管理情報
                        </h3>
                        <div
                            class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div>
                                <InputLabel for="status">ステータス</InputLabel>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="active">契約中</option>
                                    <option value="inactive">契約終了</option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.status"
                                />
                            </div>

                            <div>
                                <InputLabel for="assigned_user_id">
                                    担当者
                                </InputLabel>
                                <select
                                    id="assigned_user_id"
                                    v-model="form.assigned_user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">未割当</option>
                                    <option
                                        v-for="staff in staffOptions"
                                        :key="staff.id"
                                        :value="staff.id"
                                    >
                                        {{ staff.name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.assigned_user_id"
                                />
                            </div>

                            <div class="sm:col-span-2 lg:col-span-1">
                                <InputLabel for="notes">メモ</InputLabel>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.notes"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-5 flex items-center justify-end gap-3 border-t border-gray-100 pt-5"
                        >
                            <Link :href="route('clients.index')">
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
