<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    token: String,
    headers: Array,
    previewRows: Array,
    targetFields: Array,
    validateRoute: String,
    backRoute: String,
});

const parentLabel = computed(() =>
    props.title.includes('顧問先') ? '顧問先' : 'タスク',
);

const breadcrumbs = computed(() => [
    { label: 'ダッシュボード', href: route('dashboard') },
    {
        label: parentLabel.value,
        href: route(parentLabel.value === '顧問先' ? 'clients.index' : 'tasks.index'),
    },
    { label: 'Excelインポート', href: props.backRoute },
    { label: '列の割り当て' },
]);

const initialMapping = {};
props.headers.forEach((_, index) => {
    initialMapping[index] = '';
});

const form = useForm({
    token: props.token,
    mapping: initialMapping,
});

function submit() {
    form.post(props.validateRoute);
}
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :breadcrumbs="breadcrumbs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">
                        各列がどの項目に対応するかを選択してください。対応しない列は「マッピングしない」のままにしてください（自動マッピングは行いません）。
                    </p>
                    <InputError class="mt-1" :message="form.errors.token" />

                    <form class="mt-6" @submit.prevent="submit">
                        <div
                            class="overflow-x-auto rounded-md border border-gray-200"
                        >
                            <table
                                class="min-w-full divide-y divide-gray-200 text-sm"
                            >
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            v-for="(header, index) in headers"
                                            :key="index"
                                            class="min-w-[10rem] px-3 py-2 text-left align-top font-medium text-gray-500"
                                        >
                                            <div class="mb-1 text-xs text-gray-400">
                                                列{{ index + 1 }}
                                            </div>
                                            <div class="mb-2 font-semibold text-gray-800">
                                                {{ header || '(見出しなし)' }}
                                            </div>
                                            <select
                                                v-model="form.mapping[index]"
                                                class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">
                                                    マッピングしない
                                                </option>
                                                <option
                                                    v-for="field in targetFields"
                                                    :key="field.key"
                                                    :value="field.key"
                                                >
                                                    {{ field.label
                                                    }}{{ field.required ? ' *' : '' }}
                                                </option>
                                            </select>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(row, rowIndex) in previewRows"
                                        :key="rowIndex"
                                    >
                                        <td
                                            v-for="(cell, cellIndex) in row"
                                            :key="cellIndex"
                                            class="whitespace-nowrap px-3 py-2 text-gray-600"
                                        >
                                            {{ cell }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="mt-2 text-xs text-gray-400">
                            先頭{{ previewRows.length }}行のプレビューです。「*」は必須項目です。
                        </p>

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="backRoute">
                                <SecondaryButton type="button">
                                    やり直す
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                この内容で検証する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
