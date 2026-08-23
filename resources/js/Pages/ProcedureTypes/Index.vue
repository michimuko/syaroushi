<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useHighlightRow } from '@/Composables/useHighlightRow';
import { Head, Link, usePage } from '@inertiajs/vue3';

useHighlightRow();

const props = defineProps({
    procedureTypes: Array,
});

const page = usePage();
const isOwner = () => page.props.auth.user.role === 'owner';
const canManageProcedureTypes = () =>
    isOwner() ||
    (page.props.auth.user.permissions ?? []).includes(
        'manage_procedure_types',
    );

const recurrenceLabels = {
    yearly: '毎年',
    monthly: '毎月',
    one_time: '都度',
    custom: 'カスタム',
};

const search = ref('');

const filteredProcedureTypes = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    if (!keyword) return props.procedureTypes;

    return props.procedureTypes.filter(
        (procedureType) =>
            procedureType.name.toLowerCase().includes(keyword) ||
            procedureType.category.toLowerCase().includes(keyword),
    );
});
</script>

<template>
    <Head title="手続き種別マスタ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                手続き種別マスタ
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500">
                    全事務所で共有される法定手続きのマスタデータです。周期・通知タイミングの編集は管理者のみ行えます。
                </p>

                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="手続き名・カテゴリで検索"
                        class="w-64 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <button
                        v-if="search"
                        type="button"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                        @click="search = ''"
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
                                        カテゴリ
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        手続き名
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        周期
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        通知タイミング（日前）
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        状態
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="procedureType in filteredProcedureTypes"
                                    :id="`row-${procedureType.id}`"
                                    :key="procedureType.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ procedureType.category }}
                                    </td>
                                    <td
                                        class="px-4 py-3 font-medium text-gray-900"
                                    >
                                        {{ procedureType.name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            recurrenceLabels[
                                                procedureType.recurrence_type
                                            ]
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            procedureType.default_lead_days.join(
                                                ' / ',
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="
                                                procedureType.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            "
                                        >
                                            {{
                                                procedureType.is_active
                                                    ? '有効'
                                                    : '無効'
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            v-if="canManageProcedureTypes()"
                                            :href="
                                                route(
                                                    'procedure-types.edit',
                                                    procedureType.id,
                                                )
                                            "
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="filteredProcedureTypes.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        条件に一致する手続き種別がありません。
                                        <button
                                            v-if="search"
                                            type="button"
                                            class="ml-1 text-indigo-600 underline hover:text-indigo-800"
                                            @click="search = ''"
                                        >
                                            フィルタをリセット
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
