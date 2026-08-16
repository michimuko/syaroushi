<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomFieldInputs from '@/Components/CustomFieldInputs.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MyNumberWarning from '@/Components/MyNumberWarning.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { containsMyNumberLikeString } from '@/Composables/useMyNumberDetection';
import { initialCustomFieldValues } from '@/Composables/useCustomFieldValues';

const props = defineProps({
    staffOptions: Array,
    customFieldDefinitions: Array,
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
    custom_fields: initialCustomFieldValues(props.customFieldDefinitions),
});

const notesHasMyNumber = computed(() => containsMyNumberLikeString(form.notes));

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

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <!-- 基本情報 -->
                        <h3
                            class="text-sm font-semibold text-gray-700"
                        >
                            基本情報
                        </h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="name">
                                    顧問先名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    placeholder="例：株式会社〇〇商事"
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
                                    placeholder="例：山田 太郎"
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
                            class="mt-6 border-t border-gray-100 pt-6 text-sm font-semibold text-gray-700"
                        >
                            連絡先
                        </h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="phone">電話番号</InputLabel>
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    placeholder="例：03-1234-5678"
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
                                    placeholder="例：info@example.co.jp"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="address">住所</InputLabel>
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                    placeholder="例：東京都千代田区〇〇1-2-3"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.address"
                                />
                            </div>
                        </div>

                        <!-- 管理情報 -->
                        <h3
                            class="mt-6 border-t border-gray-100 pt-6 text-sm font-semibold text-gray-700"
                        >
                            管理情報
                        </h3>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                            <div class="sm:col-span-2">
                                <InputLabel for="notes">メモ</InputLabel>
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="対応履歴や留意事項などを入力してください（マイナンバーなど重要な個人情報は入力しないでください）"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.notes"
                                />
                                <MyNumberWarning
                                    class="mt-1"
                                    :show="notesHasMyNumber"
                                />
                            </div>
                        </div>

                        <CustomFieldInputs
                            v-model="form.custom_fields"
                            :definitions="customFieldDefinitions"
                            :errors="form.errors"
                        />

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
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
