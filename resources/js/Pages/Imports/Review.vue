<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    token: String,
    mapping: Object,
    summary: Object,
    rows: Array,
    commitRoute: String,
    backRoute: String,
});

const form = useForm({
    token: props.token,
    mapping: props.mapping,
});

function commit() {
    form.post(props.commitRoute);
}
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div class="rounded-md bg-gray-50 px-4 py-2">
                            <div class="text-xs text-gray-500">対象行数</div>
                            <div class="text-lg font-semibold text-gray-800">
                                {{ summary.total }}
                            </div>
                        </div>
                        <div class="rounded-md bg-green-50 px-4 py-2">
                            <div class="text-xs text-green-700">
                                取り込み可能
                            </div>
                            <div class="text-lg font-semibold text-green-800">
                                {{ summary.valid }}
                            </div>
                        </div>
                        <div
                            v-if="summary.invalid > 0"
                            class="rounded-md bg-red-50 px-4 py-2"
                        >
                            <div class="text-xs text-red-700">
                                エラー（スキップ）
                            </div>
                            <div class="text-lg font-semibold text-red-800">
                                {{ summary.invalid }}
                            </div>
                        </div>
                        <div
                            v-if="summary.warned > 0"
                            class="rounded-md bg-yellow-50 px-4 py-2"
                        >
                            <div class="text-xs text-yellow-700">警告あり</div>
                            <div class="text-lg font-semibold text-yellow-800">
                                {{ summary.warned }}
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="summary.invalid > 0"
                        class="mt-4 text-sm text-gray-600"
                    >
                        エラーのある行は取り込まれません。内容を確認するか、Excel側を修正して再度アップロードしてください。
                    </p>

                    <div
                        class="mt-6 overflow-x-auto rounded-md border border-gray-200"
                    >
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-3 py-2 text-left font-medium text-gray-500"
                                    >
                                        行
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left font-medium text-gray-500"
                                    >
                                        判定
                                    </th>
                                    <th
                                        class="px-3 py-2 text-left font-medium text-gray-500"
                                    >
                                        内容
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in rows" :key="row.row_number">
                                    <td class="px-3 py-2 align-top text-gray-500">
                                        {{ row.row_number }}
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <span
                                            v-if="row.errors.length"
                                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                                        >
                                            エラー
                                        </span>
                                        <span
                                            v-else-if="row.warnings.length"
                                            class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800"
                                        >
                                            警告あり
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                        >
                                            OK
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-top text-gray-700">
                                        <dl
                                            class="grid grid-cols-2 gap-x-4 gap-y-0.5 text-xs sm:grid-cols-3"
                                        >
                                            <template
                                                v-for="item in row.display"
                                                :key="item.label"
                                            >
                                                <div v-if="item.value">
                                                    <dt class="inline text-gray-400">
                                                        {{ item.label }}:
                                                    </dt>
                                                    <dd class="inline">
                                                        {{ item.value }}
                                                    </dd>
                                                </div>
                                            </template>
                                        </dl>
                                        <ul
                                            v-if="row.errors.length"
                                            class="mt-1 list-inside list-disc text-xs text-red-600"
                                        >
                                            <li
                                                v-for="(error, index) in row.errors"
                                                :key="index"
                                            >
                                                {{ error }}
                                            </li>
                                        </ul>
                                        <ul
                                            v-if="row.warnings.length"
                                            class="mt-1 list-inside list-disc text-xs text-yellow-600"
                                        >
                                            <li
                                                v-for="(warning, index) in row.warnings"
                                                :key="index"
                                            >
                                                {{ warning }}
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr v-if="rows.length === 0">
                                    <td
                                        colspan="3"
                                        class="px-3 py-8 text-center text-sm text-gray-500"
                                    >
                                        取り込み対象のデータ行がありませんでした。
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                    >
                        <Link :href="backRoute">
                            <SecondaryButton type="button">
                                やり直す
                            </SecondaryButton>
                        </Link>
                        <PrimaryButton
                            type="button"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing || summary.valid === 0"
                            @click="commit"
                        >
                            確定してインポート（{{ summary.valid }}件）
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
