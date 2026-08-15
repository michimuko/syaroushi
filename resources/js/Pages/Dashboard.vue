<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskStatusBadge from '@/Components/TaskStatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    summary: Object,
    statusCounts: Object,
    upcomingTasks: Array,
    dateRanges: Object,
});

const statusOrder = [
    'not_started',
    'in_progress',
    'documents_collected',
    'submitted',
    'completed',
];

const statusLabels = {
    not_started: '未着手',
    in_progress: '進行中',
    documents_collected: '書類収集済',
    submitted: '提出済',
    completed: '完了',
};
</script>

<template>
    <Head title="ダッシュボード" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                ダッシュボード
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- 期限サマリ -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Link
                        :href="
                            route('tasks.index', { due_to: dateRanges.yesterday })
                        "
                        class="rounded-lg border border-red-200 bg-red-50 p-5 transition hover:border-red-300 hover:shadow-sm"
                    >
                        <div class="text-sm font-medium text-red-700">
                            期限超過
                        </div>
                        <div class="mt-2 text-3xl font-bold text-red-700">
                            {{ summary.overdue }}
                            <span class="text-base font-medium">件</span>
                        </div>
                    </Link>

                    <Link
                        :href="
                            route('tasks.index', {
                                due_from: dateRanges.weekStart,
                                due_to: dateRanges.weekEnd,
                            })
                        "
                        class="rounded-lg border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300 hover:shadow-sm"
                    >
                        <div class="text-sm font-medium text-amber-700">
                            7日以内が期限
                        </div>
                        <div class="mt-2 text-3xl font-bold text-amber-700">
                            {{ summary.dueSoon }}
                            <span class="text-base font-medium">件</span>
                        </div>
                    </Link>

                    <Link
                        :href="
                            route('tasks.index', {
                                due_from: dateRanges.monthStart,
                                due_to: dateRanges.monthEnd,
                            })
                        "
                        class="rounded-lg border border-blue-200 bg-blue-50 p-5 transition hover:border-blue-300 hover:shadow-sm"
                    >
                        <div class="text-sm font-medium text-blue-700">
                            30日以内が期限
                        </div>
                        <div class="mt-2 text-3xl font-bold text-blue-700">
                            {{ summary.dueLater }}
                            <span class="text-base font-medium">件</span>
                        </div>
                    </Link>
                </div>

                <!-- ステータス別件数 -->
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-700">
                        ステータス別件数
                    </h3>
                    <div class="mt-3 flex flex-wrap gap-4">
                        <div
                            v-for="status in statusOrder"
                            :key="status"
                            class="flex items-center gap-2"
                        >
                            <TaskStatusBadge :status="status" />
                            <span class="text-sm text-gray-600">
                                {{ statusCounts[status] ?? 0 }}件
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 直近のタスク -->
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                    >
                        <h3 class="text-sm font-semibold text-gray-700">
                            期限が近いタスク
                        </h3>
                        <Link
                            :href="route('tasks.index')"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            すべて見る
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        顧問先
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        手続き
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        期限日
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        ステータス
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        担当者
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr
                                    v-for="task in upcomingTasks"
                                    :key="task.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 font-medium text-gray-900"
                                    >
                                        {{ task.client?.name }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ task.procedure_type?.name }}
                                    </td>
                                    <td
                                        class="px-4 py-3"
                                        :class="
                                            task.is_overdue
                                                ? 'font-semibold text-red-600'
                                                : 'text-gray-600'
                                        "
                                    >
                                        {{ task.due_date?.slice(0, 10) }}
                                        <span v-if="task.is_overdue"
                                            >（期限超過）</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <TaskStatusBadge :status="task.status" />
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ task.assigned_user?.name || '未割当' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link
                                            :href="route('tasks.edit', task.id)"
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            詳細
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="upcomingTasks.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        現在、期限が近いタスクはありません。
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
