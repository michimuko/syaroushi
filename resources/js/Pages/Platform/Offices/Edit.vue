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
    availableModules: Array,
    assignableBillingPlans: Array,
    usage: Object,
    exceededLimits: Array,
});

const form = useForm({
    name: props.office.name,
    is_active: props.office.is_active,
    trial_ends_at: props.office.trial_ends_at
        ? props.office.trial_ends_at.slice(0, 10)
        : '',
    // office.enabled_modules が null（未設定）の事務所は「全モジュール有効」として表示する
    enabled_modules:
        props.office.enabled_modules ??
        props.availableModules.map((m) => m.value),
    billing_plan_id: props.office.billing_plan_id,
    custom_monthly_price: props.office.custom_monthly_price ?? '',
});

const planLabel = (plan) => {
    const clients = plan.max_clients ?? '無制限';
    const users = plan.max_users ?? '無制限';
    const price =
        plan.monthly_price !== null
            ? `¥${plan.monthly_price.toLocaleString()}/月`
            : '個別見積り';

    return `${plan.name}（顧問先${clients}・ユーザー${users}・${price}）`;
};

const presets = {
    basic: [],
    standard: ['excel_migration', 'custom_fields'],
    premium: props.availableModules.map((m) => m.value),
};

const applyPreset = (key) => {
    form.enabled_modules = [...presets[key]];
};

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
                    <div class="mb-6 text-sm text-gray-500">
                        事務所ID：
                        <span class="font-mono text-gray-700">{{
                            office.id
                        }}</span>
                    </div>

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
                                    placeholder="例：〇〇社会保険労務士事務所"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.name"
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

                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <InputLabel>利用できる機能</InputLabel>
                            <p class="mt-1 text-xs text-gray-500">
                                機能ごとに個別にON/OFFできます（依存関係はないため自由に組み合わせ可能）。
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <SecondaryButton
                                    type="button"
                                    @click="applyPreset('basic')"
                                >
                                    ベーシックにする
                                </SecondaryButton>
                                <SecondaryButton
                                    type="button"
                                    @click="applyPreset('standard')"
                                >
                                    スタンダードにする
                                </SecondaryButton>
                                <SecondaryButton
                                    type="button"
                                    @click="applyPreset('premium')"
                                >
                                    プレミアムにする
                                </SecondaryButton>
                            </div>

                            <div class="mt-3 space-y-2">
                                <label
                                    v-for="module in availableModules"
                                    :key="module.value"
                                    class="flex items-center"
                                >
                                    <Checkbox
                                        :value="module.value"
                                        v-model:checked="form.enabled_modules"
                                    />
                                    <span class="ms-2 text-sm text-gray-700">
                                        {{ module.label }}
                                    </span>
                                </label>
                            </div>
                            <InputError
                                class="mt-1"
                                :message="form.errors.enabled_modules"
                            />
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <InputLabel for="billing_plan_id">
                                料金プラン
                            </InputLabel>
                            <select
                                id="billing_plan_id"
                                v-model="form.billing_plan_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">未設定</option>
                                <option
                                    v-for="plan in assignableBillingPlans"
                                    :key="plan.id"
                                    :value="plan.id"
                                >
                                    {{ planLabel(plan) }}
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors.billing_plan_id"
                            />

                            <div class="mt-4">
                                <InputLabel for="custom_monthly_price">
                                    月額の個別見積り／値引き（円）
                                </InputLabel>
                                <TextInput
                                    id="custom_monthly_price"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model="form.custom_monthly_price"
                                    placeholder="空欄ならプランの月額料金をそのまま適用"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    選択したプランの月額料金より優先されます。プランの月額料金が「個別見積り」の場合は、この欄に金額を入力しないと請求記録が生成されません。
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.custom_monthly_price"
                                />
                            </div>

                            <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">
                                        顧問先数
                                    </span>
                                    <span class="font-medium text-gray-900">
                                        {{ usage.clientCount }}
                                    </span>
                                </div>
                                <div
                                    v-if="exceededLimits.includes('clients')"
                                    class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    プラン上限を超過しています
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between"
                                >
                                    <span class="text-gray-600">
                                        ユーザー数
                                    </span>
                                    <span class="font-medium text-gray-900">
                                        {{ usage.userCount }}
                                    </span>
                                </div>
                                <div
                                    v-if="exceededLimits.includes('users')"
                                    class="mt-1 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                                >
                                    プラン上限を超過しています
                                </div>
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
                                更新する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PlatformAuthenticatedLayout>
</template>
