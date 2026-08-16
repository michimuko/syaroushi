<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    office: Object,
    billingSetting: Object,
    currentClientCount: Number,
    estimatedMonthlyAmount: Number,
    invoices: Object,
});

const formatYen = (amount) => `${amount.toLocaleString('ja-JP')}円`;
</script>

<template>
    <Head title="契約・請求" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                契約・請求
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">
                    課金は顧問先数に応じた従量制です（決済は本画面では行われません。請求方法は別途ご案内します）。
                </p>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">
                        現在のご契約状況
                    </h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-gray-500">
                                契約プラン
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ office.contract_plan || '-' }}
                            </dd>
                        </div>
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
                                顧問先1件あたりの単価
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{
                                    formatYen(
                                        billingSetting.unit_price_per_client,
                                    )
                                }}
                                /
                                {{ billingSetting.billing_cycle }}
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
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500">
                                今月の見込み金額
                            </dt>
                            <dd
                                class="mt-1 text-lg font-semibold text-gray-900"
                            >
                                {{ formatYen(estimatedMonthlyAmount) }}
                            </dd>
                            <p class="mt-1 text-xs text-gray-500">
                                実際の請求額は、月初のバッチで確定した顧問先数をもとに算出されます（下記の請求履歴を参照）。
                            </p>
                        </div>
                    </dl>
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
                                        単価
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        請求額
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
                                        {{ formatYen(invoice.unit_price) }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-sm font-medium text-gray-900"
                                    >
                                        {{ formatYen(invoice.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td
                                        colspan="4"
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
