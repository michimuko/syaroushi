<script setup>
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    billingSetting: Object,
    billingCycles: Array,
});

const form = useForm({
    unit_price_per_client: props.billingSetting.unit_price_per_client,
    trial_days: props.billingSetting.trial_days,
    billing_cycle: props.billingSetting.billing_cycle,
});

const submit = () => {
    form.put(route('platform.billing-settings.update'));
};
</script>

<template>
    <Head title="料金設定" />

    <PlatformAuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                料金設定
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500">
                    全事務所共通の従量課金の単価・無料トライアル期間・請求サイクルを設定します（企画書11章）。
                    オプションモジュールの利用可否はこの設定の対象外で、全プラン共通で利用できます。
                </p>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <InputLabel for="unit_price_per_client">
                                    顧問先1件あたりの月額単価（円）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="unit_price_per_client"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model.number="form.unit_price_per_client"
                                    placeholder="例：500"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.unit_price_per_client"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="trial_days">
                                    無料トライアル期間（日数）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="trial_days"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model.number="form.trial_days"
                                    placeholder="例：30"
                                    required
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    新規事務所を契約するとき、この日数だけ自動的にトライアル期間として設定されます（既存事務所の期間は変わりません）。
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.trial_days"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="billing_cycle">
                                    請求サイクル
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <select
                                    id="billing_cycle"
                                    v-model="form.billing_cycle"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="cycle in billingCycles"
                                        :key="cycle.value"
                                        :value="cycle.value"
                                    >
                                        {{ cycle.label }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.billing_cycle"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
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
