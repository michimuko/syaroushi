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

function defaultShifts() {
    const shifts = [];
    const today = new Date();

    for (let i = 0; i < 14; i++) {
        const d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        shifts.push({ date: `${y}-${m}-${day}`, hours: 8 });
    }

    return shifts;
}

const form = useForm({
    shifts: props.input?.shifts ?? defaultShifts(),
    task_id: props.task?.id ?? '',
});

function addRow() {
    form.shifts.push({ date: '', hours: 8 });
}

function removeRow(index) {
    form.shifts.splice(index, 1);
}

const shiftErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('shifts'))
        .map(([, message]) => message),
);

const submit = () => {
    form.post(route('calc-assistant.shift-schedule.calculate'), {
        preserveScroll: true,
    });
};

const saveForm = useForm({
    type: 'shift_schedule_check',
    input: {},
    result: {},
});

const saveToTask = () => {
    saveForm.input = { shifts: form.shifts };
    saveForm.result = props.result;
    saveForm.put(route('tasks.calc-result.update', props.task.id), {
        preserveScroll: true,
    });
};

const badgeClass = (violated) =>
    violated
        ? 'rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-700'
        : 'rounded bg-green-100 px-1.5 py-0.5 text-xs text-green-700';

const weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
function weekdayLabel(dateStr) {
    const d = new Date(`${dateStr}T00:00:00`);
    return Number.isNaN(d.getTime()) ? '' : weekdayLabels[d.getDay()];
}
</script>

<template>
    <Head title="勤務シフト表作成支援" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                勤務シフト表作成支援
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
                        1年単位の変形労働時間制で守るべき上限（1日10時間、1週52時間、連続勤務6日、対象期間280日）を日々のシフトから自動判定します。休日は労働時間0で入力してください。
                    </p>

                    <form @submit.prevent="submit" class="mt-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="py-2">日付</th>
                                    <th class="py-2">労働時間</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, index) in form.shifts" :key="index">
                                    <td class="py-1 pr-2">
                                        <input
                                            v-model="row.date"
                                            type="date"
                                            class="block w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </td>
                                    <td class="py-1 pr-2">
                                        <input
                                            v-model.number="row.hours"
                                            type="number"
                                            step="0.5"
                                            min="0"
                                            max="24"
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
                            v-for="(message, i) in shiftErrors"
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
                            対象期間の労働日数：{{ result.summary.total_work_days }}日／280日
                            <span :class="badgeClass(result.summary.exceeds_280_days)">
                                {{ result.summary.exceeds_280_days ? '上限超過' : 'OK' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            最大連続勤務日数：{{ result.summary.max_consecutive_work_days }}日／6日まで
                            <span :class="badgeClass(result.summary.exceeds_consecutive_limit)">
                                {{ result.summary.exceeds_consecutive_limit ? '上限超過' : 'OK' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            1日10時間超の日
                            <span :class="badgeClass(result.summary.any_daily_violation)">
                                {{ result.summary.any_daily_violation ? '該当あり' : 'OK' }}
                            </span>
                        </div>
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            1週52時間超の週
                            <span :class="badgeClass(result.summary.any_weekly_violation)">
                                {{ result.summary.any_weekly_violation ? '該当あり' : 'OK' }}
                            </span>
                        </div>
                    </div>

                    <h4 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        週別集計
                    </h4>
                    <table class="mt-2 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2">週開始日（月）</th>
                                <th class="py-2">週合計時間</th>
                                <th class="py-2">判定</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <tr v-for="week in result.weeks" :key="week.week_start">
                                <td class="py-2">{{ week.week_start }}</td>
                                <td class="py-2">{{ week.total_hours }}</td>
                                <td class="py-2">
                                    <span v-if="week.exceeds_weekly_limit" :class="badgeClass(true)">52h超</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        日別詳細
                    </h4>
                    <table class="mt-2 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2">日付</th>
                                <th class="py-2">曜日</th>
                                <th class="py-2">労働時間</th>
                                <th class="py-2">連続勤務</th>
                                <th class="py-2">判定</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <tr v-for="day in result.days" :key="day.date">
                                <td class="py-2">{{ day.date }}</td>
                                <td class="py-2">{{ weekdayLabel(day.date) }}</td>
                                <td class="py-2">{{ day.hours }}</td>
                                <td class="py-2">{{ day.consecutive_work_days }}日</td>
                                <td class="py-2">
                                    <span v-if="day.exceeds_daily_limit" :class="badgeClass(true)">10h超</span>
                                    <span v-if="day.exceeds_consecutive_limit" :class="badgeClass(true)">連続超過</span>
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
