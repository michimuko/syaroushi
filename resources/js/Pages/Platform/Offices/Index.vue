<script setup>
import { ref, watch } from 'vue';
import PlatformAuthenticatedLayout from '@/Layouts/PlatformAuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useHighlightRow } from '@/Composables/useHighlightRow';
import { Head, Link, router } from '@inertiajs/vue3';

useHighlightRow();

const props = defineProps({
    offices: Object,
    filters: Object,
});

const search = ref(props.filters.search);

let searchDebounce = null;

function applyFilters() {
    router.get(
        route('platform.offices.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 300);
});

function resetFilters() {
    search.value = '';
}

// バックエンド（billing:generate-invoices／請求確定ボタン）と同じ優先順位：
// 個別価格が設定されていればそれ、無ければプランの月額。
function actualBilledAmount(office) {
    const amount = office.custom_monthly_price ?? office.billing_plan?.monthly_price ?? null;

    return amount !== null ? `¥${amount.toLocaleString()}/月` : '未確定';
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
                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="事務所名・事業所IDで検索"
                        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button
                        v-if="search"
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
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        利用状況
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        トライアル終了日
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
                                    <td class="px-4 py-3">
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
                                    <td class="px-4 py-3 text-right">
                                        <Link
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
                                    </td>
                                </tr>
                                <tr v-if="offices.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        条件に一致する事務所がありません。
                                        <button
                                            v-if="search"
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
    </PlatformAuthenticatedLayout>
</template>
