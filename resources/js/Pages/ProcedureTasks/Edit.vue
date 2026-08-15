<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProcedureTaskDocumentChecklist from '@/Components/ProcedureTaskDocumentChecklist.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TaskStatusBadge from '@/Components/TaskStatusBadge.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: Object,
    staffOptions: Array,
    canUpdate: Boolean,
    returnTo: String,
});

const statusLabels = {
    not_started: '未着手',
    in_progress: '進行中',
    documents_collected: '書類収集済',
    submitted: '提出済',
    completed: '完了',
};

const form = useForm({
    status: props.task.status,
    due_date: props.task.due_date?.slice(0, 10) ?? '',
    assigned_user_id: props.task.assigned_user_id ?? '',
    notes: props.task.notes ?? '',
    return_to: props.returnTo ?? null,
});

const backLabel = props.returnTo === '/calendar' ? 'カレンダーに戻る' : '一覧に戻る';

const originalDueDate = props.task.original_due_date?.slice(0, 10) ?? null;
const isDueDateCorrected =
    originalDueDate !== null && originalDueDate !== props.task.due_date?.slice(0, 10);

const confirmingRevert = ref(false);

function submit() {
    const isRevertingFromCompleted =
        props.task.status === 'completed' && form.status !== 'completed';

    if (isRevertingFromCompleted) {
        confirmingRevert.value = true;
        return;
    }

    save();
}

function save() {
    confirmingRevert.value = false;
    form.put(route('tasks.update', props.task.id));
}
</script>

<template>
    <Head :title="`${task.title}の詳細`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ task.title }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500">顧問先</dt>
                            <dd class="font-medium text-gray-900">
                                {{ task.client?.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">手続き種別</dt>
                            <dd class="font-medium text-gray-900">
                                {{ task.procedure_type?.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">期限日</dt>
                            <dd
                                class="font-medium"
                                :class="
                                    task.is_overdue
                                        ? 'text-red-600'
                                        : 'text-gray-900'
                                "
                            >
                                {{ task.due_date?.slice(0, 10) }}
                                <span v-if="task.is_overdue"
                                    >（期限超過）</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">現在のステータス</dt>
                            <dd class="mt-0.5">
                                <TaskStatusBadge :status="task.status" />
                            </dd>
                        </div>
                    </dl>

                    <p
                        v-if="!canUpdate"
                        class="mt-4 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500"
                    >
                        このタスクの担当者ではないため、ステータス・担当者・メモの変更はできません（閲覧のみ）。
                    </p>

                    <form @submit.prevent="submit" class="mt-6 space-y-4">
                        <div>
                            <InputLabel for="due_date">期限日</InputLabel>
                            <input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                :disabled="!canUpdate"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                            />
                            <p
                                v-if="isDueDateCorrected"
                                class="mt-1 text-xs text-gray-500"
                            >
                                自動生成時点の期限：{{ originalDueDate }}（訂正済み）
                            </p>
                            <p v-else class="mt-1 text-xs text-gray-500">
                                クライアントから正しい期限が確認できた場合など、訂正が必要なときのみ変更してください。
                            </p>
                            <InputError
                                class="mt-1"
                                :message="form.errors.due_date"
                            />
                        </div>

                        <div>
                            <InputLabel for="status">ステータス</InputLabel>
                            <select
                                id="status"
                                v-model="form.status"
                                :disabled="!canUpdate"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                            >
                                <option
                                    v-for="(label, value) in statusLabels"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors.status"
                            />
                        </div>

                        <div>
                            <InputLabel for="assigned_user_id">
                                担当者
                            </InputLabel>
                            <select
                                id="assigned_user_id"
                                v-model="form.assigned_user_id"
                                :disabled="!canUpdate"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                            >
                                <option value="">未割当</option>
                                <option
                                    v-for="staff in staffOptions"
                                    :key="staff.id"
                                    :value="staff.id"
                                >
                                    {{ staff.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                自分の担当から外すと、以降このタスクは編集できなくなります。
                            </p>
                            <InputError
                                class="mt-1"
                                :message="form.errors.assigned_user_id"
                            />
                        </div>

                        <div>
                            <InputLabel for="notes">メモ</InputLabel>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                :disabled="!canUpdate"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.notes"
                            />
                        </div>

                        <div
                            class="flex items-center justify-between border-t border-gray-100 pt-4"
                        >
                            <Link
                                :href="returnTo ?? route('tasks.index')"
                                class="text-sm text-gray-500 underline hover:text-gray-700"
                            >
                                {{ backLabel }}
                            </Link>

                            <PrimaryButton
                                v-if="canUpdate"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                保存する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <ProcedureTaskDocumentChecklist
                    :task="task"
                    :documents="task.documents"
                    :can-manage="canUpdate"
                />
            </div>
        </div>

        <Modal :show="confirmingRevert" @close="confirmingRevert = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    「完了」から「{{ statusLabels[form.status] }}」に戻しますか？
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    完了済みタスクのステータスを後戻しします。この操作は取り消せません。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingRevert = false">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="save"
                    >
                        後戻しする
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
