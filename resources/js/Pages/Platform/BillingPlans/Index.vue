<script setup>
import { ref } from 'vue';
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useHighlightRow } from '@/Composables/useHighlightRow';
import { Head, useForm } from '@inertiajs/vue3';

useHighlightRow();

const props = defineProps({
    billingPlans: Array,
    billingSetting: Object,
    billingCycles: Array,
});

const settingForm = useForm({
    trial_days: props.billingSetting.trial_days,
    billing_cycle: props.billingSetting.billing_cycle,
});

const submitSetting = () => {
    settingForm.put(route('platform.billing-settings.update'));
};

const showModal = ref(false);
const editingPlan = ref(null);

const planForm = useForm({
    name: '',
    max_clients: '',
    max_users: '',
    monthly_price: '',
    stripe_price_id: '',
    sort_order: 0,
    is_active: true,
});

const openCreateModal = () => {
    editingPlan.value = null;
    planForm.reset();
    planForm.clearErrors();
    showModal.value = true;
};

const openEditModal = (plan) => {
    editingPlan.value = plan;
    planForm.name = plan.name;
    planForm.max_clients = plan.max_clients ?? '';
    planForm.max_users = plan.max_users ?? '';
    planForm.monthly_price = plan.monthly_price ?? '';
    planForm.stripe_price_id = plan.stripe_price_id ?? '';
    planForm.sort_order = plan.sort_order;
    planForm.is_active = plan.is_active;
    planForm.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitPlan = () => {
    const options = { onSuccess: () => closeModal() };

    if (editingPlan.value) {
        planForm.put(
            route('platform.billing-plans.update', editingPlan.value.id),
            options,
        );
    } else {
        planForm.post(route('platform.billing-plans.store'), options);
    }
};

const formatYen = (value) =>
    value === null ? '個別見積り' : `¥${value.toLocaleString()}/月`;
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
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">
                        トライアル・請求サイクル
                    </h3>
                    <form
                        @submit.prevent="submitSetting"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <InputLabel for="trial_days">
                                無料トライアル期間（日数）
                                <span class="text-red-600">*</span>
                            </InputLabel>
                            <TextInput
                                id="trial_days"
                                type="number"
                                min="0"
                                class="mt-1 block w-full"
                                v-model.number="settingForm.trial_days"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                新規事務所を契約するとき、この日数だけ自動的にトライアル期間として設定されます（既存事務所の期間は変わりません）。
                            </p>
                            <InputError
                                class="mt-1"
                                :message="settingForm.errors.trial_days"
                            />
                        </div>

                        <div>
                            <InputLabel for="billing_cycle">
                                請求サイクル
                                <span class="text-red-600">*</span>
                            </InputLabel>
                            <select
                                id="billing_cycle"
                                v-model="settingForm.billing_cycle"
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
                                :message="settingForm.errors.billing_cycle"
                            />
                        </div>

                        <div class="sm:col-span-2 flex justify-end">
                            <PrimaryButton
                                :class="{
                                    'opacity-25': settingForm.processing,
                                }"
                                :disabled="settingForm.processing"
                            >
                                更新する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 p-6 pb-4"
                    >
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">
                                料金プラン一覧
                            </h3>
                            <p class="mt-1 text-xs text-gray-500">
                                顧問先数上限・ユーザー数上限・月額料金の組み合わせでプランを管理します。この一覧がそのまま顧客向けの料金表になります。
                            </p>
                        </div>
                        <PrimaryButton @click="openCreateModal">
                            新しいプランを追加
                        </PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        プラン名
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        顧問先数上限
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ユーザー数上限
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        月額料金
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        表示順
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
                                    v-for="plan in billingPlans"
                                    :id="`row-${plan.id}`"
                                    :key="plan.id"
                                    class="hover:bg-gray-50"
                                    :class="{ 'opacity-50': !plan.is_active }"
                                >
                                    <td
                                        class="px-4 py-3 font-medium text-gray-900"
                                    >
                                        {{ plan.name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ plan.max_clients ?? '無制限' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ plan.max_users ?? '無制限' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ formatYen(plan.monthly_price) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ plan.sort_order }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                plan.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{
                                                plan.is_active
                                                    ? '有効'
                                                    : '無効'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                            @click="openEditModal(plan)"
                                        >
                                            編集
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="billingPlans.length === 0">
                                    <td
                                        colspan="7"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        プランが登録されていません。
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="submitPlan" class="p-6">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ editingPlan ? 'プランを編集' : '新しいプランを追加' }}
                </h3>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <InputLabel for="plan_name">
                            プラン名
                            <span class="text-red-600">*</span>
                        </InputLabel>
                        <TextInput
                            id="plan_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="planForm.name"
                            required
                            autofocus
                        />
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.name"
                        />
                    </div>

                    <div>
                        <InputLabel for="max_clients">
                            顧問先数上限
                        </InputLabel>
                        <TextInput
                            id="max_clients"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                            v-model.number="planForm.max_clients"
                            placeholder="空欄で無制限"
                        />
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.max_clients"
                        />
                    </div>

                    <div>
                        <InputLabel for="max_users">
                            ユーザー数上限
                        </InputLabel>
                        <TextInput
                            id="max_users"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                            v-model.number="planForm.max_users"
                            placeholder="空欄で無制限"
                        />
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.max_users"
                        />
                    </div>

                    <div>
                        <InputLabel for="monthly_price">
                            月額料金（円）
                        </InputLabel>
                        <TextInput
                            id="monthly_price"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            v-model.number="planForm.monthly_price"
                            placeholder="空欄で個別見積り"
                        />
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.monthly_price"
                        />
                    </div>

                    <div>
                        <InputLabel for="sort_order">
                            表示順（小さい順に並ぶ）
                        </InputLabel>
                        <TextInput
                            id="sort_order"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            v-model.number="planForm.sort_order"
                            required
                        />
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.sort_order"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="stripe_price_id">
                            Stripe Price ID
                        </InputLabel>
                        <TextInput
                            id="stripe_price_id"
                            type="text"
                            class="mt-1 block w-full font-mono text-sm"
                            v-model="planForm.stripe_price_id"
                            placeholder="price_... （空欄だと事務所側のお申し込みボタンは表示されません）"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Stripeダッシュボードで発行したPrice
                            IDを設定する。テスト環境ではサンドボックスの、本番環境では本番アカウントのIDを使うこと。エンタープライズ等の個別見積りプランは空欄のままでよい。
                        </p>
                        <InputError
                            class="mt-1"
                            :message="planForm.errors.stripe_price_id"
                        />
                    </div>

                    <div class="sm:col-span-2 flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            v-model:checked="planForm.is_active"
                        />
                        <InputLabel for="is_active" value="有効にする" />
                    </div>
                    <p class="sm:col-span-2 -mt-2 text-xs text-gray-500">
                        無効にすると新規の事務所への割当対象から外れます（既に割り当て済みの事務所には影響しません）。
                    </p>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton @click="closeModal">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': planForm.processing }"
                        :disabled="planForm.processing"
                    >
                        {{ editingPlan ? '更新する' : '追加する' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </PlatformAuthenticatedLayout>
</template>
