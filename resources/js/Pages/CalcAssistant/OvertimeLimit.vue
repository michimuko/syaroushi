<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: Object,
    result: Object,
    input: Object,
});

function defaultMonths() {
    const months = [];
    const now = new Date();

    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        months.push({
            month: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
            overtime_hours: 0,
            holiday_work_hours: 0,
        });
    }

    return months;
}

const form = useForm({
    months: props.input?.months ?? defaultMonths(),
    task_id: props.task?.id ?? '',
});

function addRow() {
    form.months.push({ month: '', overtime_hours: 0, holiday_work_hours: 0 });
}

const monthErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('months'))
        .map(([, message]) => message),
);

function removeRow(index) {
    form.months.splice(index, 1);
}

const submit = () => {
    form.post(route('calc-assistant.overtime-limit.calculate'), {
        preserveScroll: true,
    });
};

const saveForm = useForm({
    type: 'overtime_limit_check',
    input: {},
    result: {},
});

const saveToTask = () => {
    saveForm.input = { months: form.months };
    saveForm.result = props.result;
    saveForm.put(route('tasks.calc-result.update', props.task.id), {
        preserveScroll: true,
    });
};

const badgeClass = (violated) =>
    violated
        ? 'rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-700'
        : 'rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-700';
</script>

<template>
    <Head title="時間外労働時間・36協定上限チェック" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                時間外労働時間・36協定上限チェック
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div
                    v-if="task"
                    class="mb-4 rounded-md bg-indigo-50 px-4 py-2 text-sm text-indigo-700"
                >
                    タスク「{{ task.title }}」（{{ task.client_name }}）に結果を保存できます。
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-xs text-gray-500">
                        特別条項付き36協定でも超えられない上限（単月100時間未満、2〜6ヶ月平均80時間以内、年720時間以内、月45時間超は年6回まで）を月次データから自動判定します。
                    </p>

                    <form @submit.prevent="submit" class="mt-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="py-2">年月</th>
                                    <th class="py-2">時間外労働（時間）</th>
                                    <th class="py-2">休日労働（時間）</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, index) in form.months" :key="index">
                                    <td class="py-1 pr-2">
                                        <input
                                            v-model="row.month"
                                            type="text"
                                            placeholder="2026-04"
                                            class="block w-28 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input
                                            v-model.number="row.overtime_hours"
                                            type="number"
                                            step="0.1"
                                            min="0"
                                            placeholder="0"
                                            class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input
                                            v-model.number="row.holiday_work_hours"
                                            type="number"
                                            step="0.1"
                                            min="0"
                                            placeholder="0"
                                            class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="py-1">
                                        <button
                                            type="button"
                                            class="text-xs text-gray-400 hover:text-red-600"
                                            @click="removeRow(index)"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p
                            v-for="(message, i) in monthErrors"
                            :key="i"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ message }}
                        </p>

                        <SecondaryButton type="button" class="mt-3" @click="addRow">
                            行を追加
                        </SecondaryButton>

                        <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                チェックする
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div v-if="result" class="mt-6 rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">
                            判定結果
                        </h3>
                        <PrimaryButton
                            v-if="task"
                            :class="{ 'opacity-25': saveForm.processing }"
                            :disabled="saveForm.processing"
                            @click="saveToTask"
                        >
                            このタスクに保存する
                        </PrimaryButton>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            月45時間超：{{ result.summary.months_exceeding_45_count }}ヶ月／6ヶ月まで
                            <span :class="badgeClass(!result.summary.months_exceeding_45_within_allowance)">
                                {{ result.summary.months_exceeding_45_within_allowance ? 'OK' : '上限超過' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            年間時間外合計：{{ result.summary.annual_overtime_hours }}時間／720時間
                            <span :class="badgeClass(!result.summary.annual_overtime_within_720)">
                                {{ result.summary.annual_overtime_within_720 ? 'OK' : '上限超過' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            単月100時間以上（時間外+休日）
                            <span :class="badgeClass(result.summary.any_month_reaches_100_combined)">
                                {{ result.summary.any_month_reaches_100_combined ? '該当あり' : 'OK' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            複数月平均80時間超（時間外+休日）
                            <span :class="badgeClass(result.summary.any_multi_month_average_exceeds_80)">
                                {{ result.summary.any_multi_month_average_exceeds_80 ? '該当あり' : 'OK' }}
                            </span>
                        </div>
                    </div>

                    <table class="mt-4 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2">年月</th>
                                <th class="py-2">時間外</th>
                                <th class="py-2">休日労働</th>
                                <th class="py-2">合計</th>
                                <th class="py-2">複数月平均（最大）</th>
                                <th class="py-2">判定</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <tr v-for="row in result.months" :key="row.month">
                                <td class="py-2">{{ row.month }}</td>
                                <td class="py-2">{{ row.overtime_hours }}</td>
                                <td class="py-2">{{ row.holiday_work_hours }}</td>
                                <td class="py-2">{{ row.combined_hours }}</td>
                                <td class="py-2">
                                    {{ row.multi_month_average ?? '-' }}
                                </td>
                                <td class="py-2">
                                    <span v-if="row.exceeds_45" :class="badgeClass(true)">45h超</span>
                                    <span v-if="row.exceeds_100_combined" :class="badgeClass(true)">100h以上</span>
                                    <span v-if="row.exceeds_80_average" :class="badgeClass(true)">平均80h超</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <Link
                        :href="route('calc-assistant.index')"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                    >
                        計算アシスタント一覧に戻る
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
