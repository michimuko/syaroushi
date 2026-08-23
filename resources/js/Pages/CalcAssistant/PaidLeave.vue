<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    task: Object,
    result: Object,
    input: Object,
});

const form = useForm({
    hire_date: props.input?.hire_date ?? '',
    weekly_scheduled_days: props.input?.weekly_scheduled_days ?? '',
    task_id: props.task?.id ?? '',
});

const submit = () => {
    form.post(route('calc-assistant.paid-leave.calculate'), {
        preserveScroll: true,
    });
};

const saveForm = useForm({
    type: 'annual_paid_leave',
    input: {},
    result: [],
});

const saveToTask = () => {
    saveForm.input = {
        hire_date: props.result.hire_date,
        weekly_scheduled_days: props.result.weekly_scheduled_days,
    };
    saveForm.result = props.result.schedule;
    saveForm.put(route('tasks.calc-result.update', props.task.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="年次有給休暇の付与日数計算" />

    <AuthenticatedLayout
        :breadcrumbs="[
            { label: 'ダッシュボード', href: route('dashboard') },
            {
                label: '計算アシスタント',
                href: route('calc-assistant.index'),
            },
            { label: '年次有給休暇の付与日数計算' },
        ]"
    >
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                年次有給休暇の付与日数計算
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div
                    v-if="task"
                    class="mb-4 rounded-md bg-indigo-50 px-4 py-2 text-sm text-indigo-700"
                >
                    タスク「{{ task.title }}」（{{ task.client_name }}）に結果を保存できます。
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form
                        @submit.prevent="submit"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <InputLabel for="hire_date">
                                入社日
                                <span class="text-red-600">*</span>
                            </InputLabel>
                            <TextInput
                                id="hire_date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.hire_date"
                                required
                                autofocus
                            />
                            <InputError
                                class="mt-1"
                                :message="form.errors.hire_date"
                            />
                        </div>

                        <div>
                            <InputLabel for="weekly_scheduled_days">
                                所定労働日数の区分
                            </InputLabel>
                            <select
                                id="weekly_scheduled_days"
                                v-model="form.weekly_scheduled_days"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    フルタイム（週5日以上／週30時間以上、原則付与）
                                </option>
                                <option value="4">
                                    週4日（比例付与、年169〜216日）
                                </option>
                                <option value="3">
                                    週3日（比例付与、年121〜168日）
                                </option>
                                <option value="2">
                                    週2日（比例付与、年73〜120日）
                                </option>
                                <option value="1">
                                    週1日（比例付与、年48〜72日）
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="form.errors.weekly_scheduled_days"
                            />
                        </div>

                        <div class="sm:col-span-2 flex justify-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                計算する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div v-if="result" class="mt-6 rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">
                            計算結果
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
                    <p class="mt-1 text-xs text-gray-500">
                        各基準日において全労働日の8割以上出勤していることが前提です。
                    </p>

                    <table class="mt-3 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr
                                class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                <th class="py-2">基準日</th>
                                <th class="py-2">勤続</th>
                                <th class="py-2">付与日数</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 text-sm text-gray-700"
                        >
                            <tr v-for="row in result.schedule" :key="row.grant_date">
                                <td class="py-2">{{ row.grant_date }}</td>
                                <td class="py-2">{{ row.service_label }}</td>
                                <td class="py-2">{{ row.days_granted }}日</td>
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
