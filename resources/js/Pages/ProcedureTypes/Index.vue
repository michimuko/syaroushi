<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
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
                                    v-for="procedureType in procedureTypes"
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
