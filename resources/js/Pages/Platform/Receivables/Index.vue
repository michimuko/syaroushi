<script setup>
import { ref, watch } from 'vue';
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useHighlightRow } from '@/Composables/useHighlightRow';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

useHighlightRow();

const props = defineProps({
    invoices: Object,
    filters: Object,
    summary: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);

let searchDebounce = null;

function applyFilters() {
    router.get(
        route('platform.receivables.index'),
        {
            search: search.value || undefined,
            status: status.value,
        },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

function resetFilters() {
    search.value = '';
    status.value = 'unpaid';
}

function formatYen(amount) {
    return `¥${amount.toLocaleString()}`;
}

const confirmingInvoice = ref(null);
const markPaidForm = useForm({});

function confirmMarkPaid(invoice) {
    confirmingInvoice.value = invoice;
}

function closeMarkPaidModal() {
    confirmingInvoice.value = null;
}

function markPaid() {
    markPaidForm.post(route('platform.receivables.mark-paid', confirmingInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => closeMarkPaidModal(),
    });
}
</script>

<template>
    <Head title="未収金管理" />

    <PlatformAuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                未収金管理
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500">
                    月次請求バッチ（billing:generate-invoices）が生成したDB請求記録のうち、入金が未確認のものを一覧表示します。
                    Stripeで決済連携済みの事務所はStripe側が請求の正のため、ここには含まれません。
                </p>

                <!-- Summary -->
                <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-gray-500">未収金合計</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ formatYen(summary.unpaidTotal) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm">
                        <div class="text-xs text-gray-500">未収金件数</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900">
                            {{ summary.unpaidCount }}件
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="事務所名で検索"
                        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <select
                        v-model="status"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="unpaid">未収金のみ</option>
                        <option value="paid">支払い済みのみ</option>
                        <option value="all">すべて</option>
                    </select>
                    <button
                        v-if="search || status !== 'unpaid'"
                        type="button"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                        @click="resetFilters"
                    >
                        フィルタをリセット
                    </button>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        事務所
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        対象期間
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        プラン
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        金額
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        生成日
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        支払い状況
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="invoice in invoices.data"
                                    :id="`row-${invoice.id}`"
                                    :key="invoice.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="route('platform.offices.edit', invoice.office.id)"
                                            class="font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ invoice.office.name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ invoice.period_start.slice(0, 7) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ invoice.plan_name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ formatYen(invoice.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ invoice.generated_at.slice(0, 10) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="invoice.is_paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        >
                                            {{ invoice.is_paid ? '支払い済み' : '未収金' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            v-if="!invoice.is_paid"
                                            type="button"
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                            @click="confirmMarkPaid(invoice)"
                                        >
                                            支払い確認済みにする
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                        条件に一致する請求記録がありません。
                                        <button
                                            v-if="search || status !== 'unpaid'"
                                            type="button"
                                            class="ml-1 text-indigo-600 underline hover:text-indigo-800"
                                            @click="resetFilters"
                                        >
                                            フィルタをリセット
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links.length > 3" class="mt-4 flex flex-wrap gap-1">
                    <Link
                        v-for="(link, index) in invoices.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="{
                            'bg-gray-800 text-white': link.active,
                            'text-gray-500 hover:bg-gray-100': !link.active && link.url,
                            'cursor-not-allowed text-gray-300': !link.url,
                        }"
                        preserve-state
                    />
                </div>
            </div>
        </div>

        <Modal :show="confirmingInvoice !== null" @close="closeMarkPaidModal">
            <div v-if="confirmingInvoice" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    「{{ confirmingInvoice.office.name }}」（{{ confirmingInvoice.period_start.slice(0, 7) }}分・{{ formatYen(confirmingInvoice.amount) }}）の入金を確認済みにしますか？
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    未収金一覧から外れます。この操作は取り消せません。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeMarkPaidModal">
                        キャンセル
                    </SecondaryButton>

                    <PrimaryButton
                        :class="{ 'opacity-25': markPaidForm.processing }"
                        :disabled="markPaidForm.processing"
                        @click="markPaid"
                    >
                        支払い確認済みにする
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </PlatformAuthenticatedLayout>
</template>
