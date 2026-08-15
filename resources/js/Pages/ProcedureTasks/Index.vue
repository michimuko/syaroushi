<script setup>
import { reactive, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TaskStatusBadge from '@/Components/TaskStatusBadge.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tasks: Object,
    filters: Object,
    clientOptions: Array,
    staffOptions: Array,
    statusOptions: Array,
});

const statusLabels = {
    not_started: '未着手',
    in_progress: '進行中',
    documents_collected: '書類収集済',
    submitted: '提出済',
    completed: '完了',
};

const status = ref(props.filters.status);
const clientId = ref(props.filters.client_id);
const assignedUserId = ref(props.filters.assigned_user_id);
const dueFrom = ref(props.filters.due_from);
const dueTo = ref(props.filters.due_to);

function applyFilters() {
    router.get(
        route('tasks.index'),
        {
            status: status.value || undefined,
            client_id: clientId.value || undefined,
            assigned_user_id: assignedUserId.value || undefined,
            due_from: dueFrom.value || undefined,
            due_to: dueTo.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch([status, clientId, assignedUserId, dueFrom, dueTo], applyFilters);

function resetFilters() {
    status.value = '';
    clientId.value = '';
    assignedUserId.value = '';
    dueFrom.value = '';
    dueTo.value = '';
}

const hasActiveFilters = () =>
    status.value ||
    clientId.value ||
    assignedUserId.value ||
    dueFrom.value ||
    dueTo.value;

const localStatus = reactive({});
watch(
    () => props.tasks.data,
    (data) => {
        data.forEach((task) => {
            localStatus[task.id] = task.status;
        });
    },
    { immediate: true },
);

const revertingTask = ref(null);
const processingStatusIds = reactive(new Set());

function goToEdit(task) {
    router.visit(route('tasks.edit', task.id));
}

function handleStatusChange(task, newStatus) {
    localStatus[task.id] = newStatus;

    if (task.status === 'completed' && newStatus !== 'completed') {
        revertingTask.value = { task, newStatus };
        return;
    }

    submitStatus(task, newStatus);
}

function submitStatus(task, newStatus) {
    revertingTask.value = null;
    processingStatusIds.add(task.id);
    router.put(
        route('tasks.update', task.id),
        {
            status: newStatus,
            return_to: window.location.pathname + window.location.search,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                localStatus[task.id] = task.status;
            },
            onFinish: () => {
                processingStatusIds.delete(task.id);
            },
        },
    );
}

function confirmRevert() {
    submitStatus(revertingTask.value.task, revertingTask.value.newStatus);
}

function cancelRevert() {
    if (revertingTask.value) {
        localStatus[revertingTask.value.task.id] = revertingTask.value.task.status;
    }
    revertingTask.value = null;
}
</script>

<template>
    <Head title="タスク一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2
                    class="text-xl font-semibold leading-tight text-gray-800"
                >
                    タスク一覧
                </h2>
                <Link :href="route('tasks.create')">
                    <PrimaryButton>新規作成</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500"
                            >ステータス</label
                        >
                        <select
                            v-model="status"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">すべて</option>
                            <option
                                v-for="option in statusOptions"
                                :key="option"
                                :value="option"
                            >
                                {{ statusLabels[option] }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500"
                            >顧問先</label
                        >
                        <select
                            v-model="clientId"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">すべて</option>
                            <option
                                v-for="client in clientOptions"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500"
                            >担当者</label
                        >
                        <select
                            v-model="assignedUserId"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">すべて</option>
                            <option
                                v-for="staff in staffOptions"
                                :key="staff.id"
                                :value="staff.id"
                            >
                                {{ staff.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500"
                            >期限（開始）</label
                        >
                        <input
                            v-model="dueFrom"
                            type="date"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500"
                            >期限（終了）</label
                        >
                        <input
                            v-model="dueTo"
                            type="date"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <button
                        v-if="hasActiveFilters()"
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
                                    v-for="task in tasks.data"
                                    :key="task.id"
                                    class="cursor-pointer hover:bg-gray-50"
                                    @click="goToEdit(task)"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900">
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
                                        <div
                                            v-if="
                                                task.original_due_date &&
                                                task.original_due_date.slice(0, 10) !==
                                                    task.due_date?.slice(0, 10)
                                            "
                                            class="text-xs font-normal text-gray-400"
                                        >
                                            元の期限：{{
                                                task.original_due_date.slice(0, 10)
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" @click.stop>
                                        <select
                                            v-if="task.can_update"
                                            :value="localStatus[task.id]"
                                            :disabled="processingStatusIds.has(task.id)"
                                            class="rounded-md border-gray-300 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400"
                                            @change="
                                                handleStatusChange(
                                                    task,
                                                    $event.target.value,
                                                )
                                            "
                                        >
                                            <option
                                                v-for="(label, value) in statusLabels"
                                                :key="value"
                                                :value="value"
                                            >
                                                {{ label }}
                                            </option>
                                        </select>
                                        <TaskStatusBadge
                                            v-else
                                            :status="task.status"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{
                                            task.assigned_user?.name ||
                                            '未割当'
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-300">
                                        ›
                                    </td>
                                </tr>
                                <tr v-if="tasks.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-4 py-12 text-center text-sm text-gray-500"
                                    >
                                        条件に一致するタスクがありません。
                                        <button
                                            v-if="hasActiveFilters()"
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
                    v-if="tasks.links.length > 3"
                    class="mt-4 flex flex-wrap gap-1"
                >
                    <Link
                        v-for="(link, index) in tasks.links"
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

        <Modal :show="revertingTask !== null" @close="cancelRevert">
            <div v-if="revertingTask" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    「{{ revertingTask.task.client?.name }}」の「{{
                        revertingTask.task.procedure_type?.name
                    }}」を「完了」から「{{
                        statusLabels[revertingTask.newStatus]
                    }}」に戻しますか？
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    完了済みタスクのステータスを後戻しします。この操作は取り消せません。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="cancelRevert">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton @click="confirmRevert">
                        後戻しする
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
