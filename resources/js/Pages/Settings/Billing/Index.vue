<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    office: Object,
    billingPlan: Object,
    billingCycleLabel: String,
    currentClientCount: Number,
    currentUserCount: Number,
    estimatedMonthlyAmount: Number,
    exceededLimits: Array,
    invoices: Object,
});

const formatYen = (amount) =>
    amount === null ? '個別見積り' : `${amount.toLocaleString('ja-JP')}円`;

const subscriptionStatusLabel = {
    active: '契約中',
    trialing: 'トライアル中（Stripe）',
    past_due: '支払い遅延',
    canceled: '解約済み',
    unpaid: '未払い',
};

const checkoutProcessing = ref(false);

const startCheckout = () => {
    checkoutProcessing.value = true;
    router.post(route('settings.billing.checkout'), {}, {
        onFinish: () => {
            checkoutProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head title="契約・請求" />

    <AuthenticatedLayout
        :breadcrumbs="[
            { label: 'ダッシュボード', href: route('dashboard') },
            { label: '契約・請求' },
        ]"
    >
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                契約・請求
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">
                    課金は契約プランの月額固定です。下記からStripe（決済代行）を通じてお申し込み・お支払い方法の変更ができます。
                </p>

                <div
                    v-if="exceededLimits.length > 0"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                >
                    現在の
                    <span v-if="exceededLimits.includes('clients')"
                        >顧問先数</span
                    ><span
                        v-if="
                            exceededLimits.includes('clients') &&
                            exceededLimits.includes('users')
                        "
                        >・</span
                    ><span v-if="exceededLimits.includes('users')"
                        >ユーザー数</span
                    >がご契約プランの上限を超えています。プランのアップグレードについては運営者までお問い合わせください。
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">
                        現在のご契約状況
                    </h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-gray-500">
                                ステータス
                            </dt>
                            <dd class="mt-1">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="
                                        office.is_trial_active
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-green-100 text-green-800'
                                    "
                                >
                                    {{
                                        office.is_trial_active
                                            ? `トライアル中（${office.trial_ends_at}まで）`
                                            : '本契約'
                                    }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">
                                ご契約プラン
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ billingPlan?.name ?? '未設定（運営者にお問い合わせください）' }}
                            </dd>
                            <p
                                v-if="billingPlan"
                                class="mt-1 text-xs text-gray-500"
                            >
                                顧問先{{
                                    billingPlan.max_clients ?? '無制限'
                                }}件・ユーザー{{
                                    billingPlan.max_users ?? '無制限'
                                }}名まで（{{ billingCycleLabel }}）
                            </p>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">決済状況</dt>
                            <dd class="mt-1">
                                <span
                                    v-if="office.stripe_subscription_status"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="
                                        office.has_active_stripe_subscription
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    "
                                >
                                    {{
                                        subscriptionStatusLabel[
                                            office.stripe_subscription_status
                                        ] ?? office.stripe_subscription_status
                                    }}
                                </span>
                                <span
                                    v-else
                                    class="text-sm text-gray-500"
                                >
                                    未お申し込み
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">
                                現在の課金対象顧問先数
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ currentClientCount }}件
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">
                                現在のユーザー数
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ currentUserCount }}名
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500">
                                今月の見込み金額
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold text-gray-900"
                            >
                                {{ office.is_trial_active ? '0円' : formatYen(estimatedMonthlyAmount) }}
                            </dd>
                            <p
                                v-if="office.is_trial_active && office.stripe_subscription_status === 'trialing'"
                                class="mt-1 text-xs text-gray-500"
                            >
                                トライアル中です。{{ office.trial_ends_at }}から自動的に{{ formatYen(estimatedMonthlyAmount) }}/月の課金が開始されます（お支払い方法は登録済みです）。
                            </p>
                            <p
                                v-else-if="office.is_trial_active"
                                class="mt-1 text-xs font-medium text-amber-700"
                            >
                                お支払い方法がまだ登録されていません。{{ office.trial_ends_at }}を過ぎるとご利用を継続いただけなくなります。
                            </p>
                            <p v-else class="mt-1 text-xs text-gray-500">
                                実際の請求額は、月初のバッチで確定した内容をもとに算出されます（下記の請求履歴を参照）。
                            </p>
                        </div>
                    </dl>

                    <div
                        class="mt-6 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4"
                    >
                        <PrimaryButton
                            v-if="
                                billingPlan?.checkout_available &&
                                !office.has_active_stripe_subscription
                            "
                            :class="{ 'opacity-25': checkoutProcessing }"
                            :disabled="checkoutProcessing"
                            @click="startCheckout"
                        >
                            {{
                                checkoutProcessing
                                    ? '手続き中...'
                                    : office.is_trial_active
                                        ? 'お支払い方法を登録する'
                                        : 'このプランでお申し込みする'
                            }}
                        </PrimaryButton>
                        <p
                            v-else-if="
                                !billingPlan?.checkout_available &&
                                !office.has_active_stripe_subscription
                            "
                            class="text-xs text-gray-500"
                        >
                            このプランはオンラインでのお申し込みに対応していません。運営者までお問い合わせください。
                        </p>
                        <Link
                            v-if="office.has_stripe_customer"
                            :href="route('settings.billing.portal')"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            お支払い方法・請求書を管理する（Stripe）
                        </Link>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">
                        請求履歴
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        対象期間
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        顧問先数
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ユーザー数
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ご契約プラン
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        請求額
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        支払い状況
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="invoice in invoices.data"
                                    :key="invoice.id"
                                >
                                    <td class="px-3 py-2 text-sm text-gray-900">
                                        {{ invoice.period_start.slice(0, 10) }}
                                        〜
                                        {{ invoice.period_end.slice(0, 10) }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        {{ invoice.client_count }}件
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        {{ invoice.user_count }}名
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600">
                                        {{ invoice.plan_name }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-sm font-medium text-gray-900"
                                    >
                                        {{ formatYen(invoice.amount) }}
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="invoice.is_paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        >
                                            {{ invoice.is_paid ? '支払い済み' : '未収金' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-3 py-8 text-center text-sm text-gray-500"
                                    >
                                        請求履歴はまだありません。
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="invoices.links.length > 3"
                        class="mt-4 flex flex-wrap gap-1"
                    >
                        <Link
                            v-for="(link, index) in invoices.links"
                            :key="index"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="rounded-md px-3 py-1 text-sm"
                            :class="{
                                'bg-gray-800 text-white': link.active,
                                'text-gray-500 hover:bg-gray-100':
                                    !link.active && link.url,
                                'cursor-not-allowed text-gray-300':
                                    !link.url,
                            }"
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
