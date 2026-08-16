<script setup>
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    office: Object,
});

const form = useForm({
    name: props.office.name,
    contract_plan: props.office.contract_plan ?? '',
    is_active: props.office.is_active,
    trial_ends_at: props.office.trial_ends_at
        ? props.office.trial_ends_at.slice(0, 10)
        : '',
});

const submit = () => {
    form.put(route('platform.offices.update', props.office.id));
};
</script>

<template>
    <Head title="事務所情報の編集" />

    <PlatformAuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                事務所情報の編集
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="name">
                                    事務所名
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

                            <div class="sm:col-span-2">
                                <InputLabel for="contract_plan">
                                    契約プラン
                                </InputLabel>
                                <TextInput
                                    id="contract_plan"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.contract_plan"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.contract_plan"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="trial_ends_at">
                                    トライアル終了日
                                </InputLabel>
                                <TextInput
                                    id="trial_ends_at"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.trial_ends_at"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    空欄にするとトライアル期間なし（即時課金対象）になります。この日以降、月初のバッチで請求記録が生成されます。
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.trial_ends_at"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-6 border-t border-gray-100 pt-6"
                        >
                            <label class="flex items-center">
                                <Checkbox
                                    name="is_active"
                                    v-model:checked="form.is_active"
                                />
                                <span class="ms-2 text-sm text-gray-700">
                                    この事務所の利用を許可する
                                </span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">
                                チェックを外すと、この事務所に所属する全ユーザーが
                                次回ログイン時点でログインできなくなります。
                            </p>
                            <InputError
                                class="mt-1"
                                :message="form.errors.is_active"
                            />
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
                                更新する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PlatformAuthenticatedLayout>
</template>
