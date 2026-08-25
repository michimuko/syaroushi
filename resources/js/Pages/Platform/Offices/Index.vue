<script setup>
import { ref, watch } from 'vue';
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useHighlightRow } from '@/Composables/useHighlightRow';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

useHighlightRow();

const props = defineProps({
    offices: Object,
    filters: Object,
    attentionCount: Number,
    physicalPurgeAfterDays: Number,
});

const search = ref(props.filters.search);
const attentionOnly = ref(props.filters.attention_only);
const trashed = ref(props.filters.trashed);

let searchDebounce = null;

function applyFilters() {
    router.get(
        route('platform.offices.index'),
        {
            search: search.value || undefined,
            attention_only: (!trashed.value && attentionOnly.value) || undefined,
            trashed: trashed.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

watch(attentionOnly, applyFilters);
watch(trashed, () => {
    if (trashed.value) {
        attentionOnly.value = false;
    }
    applyFilters();
});

function resetFilters() {
    search.value = '';
    attentionOnly.value = false;
    trashed.value = false;
}

const attentionLabels = {
    payment_failed: { text: '支払いエラー', class: 'bg-red-100 text-red-800' },
    no_plan: { text: 'プラン未設定', class: 'bg-amber-100 text-amber-800' },
    trial_ending_soon: { text: 'トライアル終了間近', class: 'bg-blue-100 text-blue-800' },
    pending_deletion: { text: '削除対象', class: 'bg-red-100 text-red-800' },
};

// バックエンド（billing:generate-invoices／請求確定ボタン）と同じ優先順位：
// 個別価格が設定されていればそれ、無ければプランの月額。
function actualBilledAmount(office) {
    const amount = office.custom_monthly_price ?? office.billing_plan?.monthly_price ?? null;

    return amount !== null ? `¥${amount.toLocaleString()}/月` : '未確定';
}

const restoreForm = useForm({});
function restore(office) {
    restoreForm.post(route('platform.offices.restore', office.id), { preserveScroll: true });
}

const confirmingPurgeOffice = ref(null);
const purgeForm = useForm({});
function confirmPurge(office) {
    confirmingPurgeOffice.value = office;
}
function closePurgeModal() {
    confirmingPurgeOffice.value = null;
}
function purge() {
    purgeForm.post(route('platform.offices.purge', confirmingPurgeOffice.value.id), {
        onSuccess: () => closePurgeModal(),
    });
}
</script>

<template>
    <Head title="顧客事務所一覧" />

    <PlatformAuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    顧客事務所一覧
                </h2>
                <Link :href="route('platform.offices.create')">
                    <PrimaryButton>事務所を新規契約</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div
                    v-if="attentionCount > 0"
                    class="mb-4 flex items-center justify-between rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    <span>
                        請求まわりで確認が必要な事務所が{{ attentionCount }}件あります（支払いエラー・プラン未設定・トライアル終了間近）。
                    </span>
                    <button
                        v-if="!attentionOnly"
                        type="button"
                        class="font-medium underline hover:text-amber-900"
                        @click="attentionOnly = true"
                    >
                        絞り込んで確認する
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="事務所名・事業所IDで検索"
                        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <label v-if="!trashed" class="flex items-center gap-1.5 text-sm text-gray-700">
                        <input
                            v-model="attentionOnly"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        要対応のみ表示
                    </label>
                    <label class="flex items-center gap-1.5 text-sm text-gray-700">
                        <input
                            v-model="trashed"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        削除済みを表示（復元可能）
                    </label>
                    <button
                        v-if="search || attentionOnly || trashed"
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
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        事務所番号
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        事業所ID（ログイン用）
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        事務所名
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        料金プラン
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ユーザー数
                                    </th>
                                    <th
                                        v-if="!trashed"
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        利用状況
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        トライアル終了日
                                    </th>
                                    <th
                                        v-if="!trashed"
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        要対応
                                    </th>
                                    <th
                                        v-if="trashed"
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        削除日
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="office in offices.data"
                                    :id="`row-${office.id}`"
                                    :key="office.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-gray-500"
                                    >
                                        {{ office.id }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono text-gray-500"
                                    >
                                        {{ office.office_code }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="
                                                route(
                                                    'platform.offices.edit',
                                                    office.id,
                                                )
                                            "
                                            class="font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ office.name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div>{{ office.billing_plan?.name || '-' }}</div>
                                        <div class="text-xs text-gray-400">
                                            実際請求額：{{ actualBilledAmount(office) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ office.users_count }}
                                    </td>
                                    <td v-if="!trashed" class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                office.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{
                                                office.is_active
                                                    ? '利用中'
                                                    : '停止中'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            office.trial_ends_at
                                                ? office.trial_ends_at.slice(
                                                      0,
                                                      10,
                                                  )
                                                : '-'
                                        }}
                                    </td>
                                    <td v-if="!trashed" class="px-4 py-3">
                                        <div
                                            v-if="office.billing_attention_reasons.length > 0"
                                            class="flex flex-wrap gap-1"
                                        >
                                            <span
                                                v-for="reason in office.billing_attention_reasons"
                                                :key="reason"
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="attentionLabels[reason].class"
                                            >
                                                {{ attentionLabels[reason].text }}
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-300">-</span>
                                    </td>
                                    <td v-if="trashed" class="px-4 py-3">
                                        <div class="text-gray-600">
                                            {{ office.deleted_at.slice(0, 10) }}
                                        </div>
                                        <div
                                            v-if="office.eligible_for_physical_purge"
                                            class="mt-1 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                                        >
                                            物理削除可能
                                        </div>
                                        <div v-else class="mt-1 text-xs text-gray-400">
                                            物理削除まで{{ physicalPurgeAfterDays }}日間の猶予
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            v-if="!trashed"
                                            :href="
                                                route(
                                                    'platform.offices.edit',
                                                    office.id,
                                                )
                                            "
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </Link>
                                        <div v-else class="flex justify-end gap-3">
                                            <button
                                                type="button"
                                                class="text-sm text-indigo-600 hover:text-indigo-900"
                                                @click="restore(office)"
                                            >
                                                復元する
                                            </button>
                                            <button
                                                v-if="office.eligible_for_physical_purge"
                                                type="button"
                                                class="text-sm text-red-600 hover:text-red-900"
                                                @click="confirmPurge(office)"
                                            >
                                                物理削除する
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="offices.data.length === 0">
                                    <td
                                        colspan="9"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        条件に一致する事務所がありません。
                                        <button
                                            v-if="search || attentionOnly || trashed"
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
                <div
                    v-if="offices.links.length > 3"
                    class="mt-4 flex flex-wrap gap-1"
                >
                    <Link
                        v-for="(link, index) in offices.links"
                        :key="index"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="{
                            'bg-gray-800 text-white': link.active,
                            'text-gray-500 hover:bg-gray-100':
                                !link.active && link.url,
                            'cursor-not-allowed text-gray-300': !link.url,
                        }"
                        preserve-state
                    />
                </div>
            </div>
        </div>

        <Modal :show="confirmingPurgeOffice !== null" @close="closePurgeModal">
            <div v-if="confirmingPurgeOffice" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    「{{ confirmingPurgeOffice.name }}」のデータを物理削除しますか？
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    顧問先・タスク・ユーザー・アップロード済み書類を含む全てのデータが完全に削除されます。
                    この操作は取り消せません。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closePurgeModal">
                        キャンセル
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': purgeForm.processing }"
                        :disabled="purgeForm.processing"
                        @click="purge"
                    >
                        物理削除を実行する
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </PlatformAuthenticatedLayout>
</template>
