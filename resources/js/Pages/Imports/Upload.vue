<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    uploadRoute: String,
    backRoute: String,
    templateRoute: String,
});

const parentLabel = computed(() =>
    props.title.includes('顧問先') ? '顧問先' : 'タスク',
);

const breadcrumbs = computed(() => [
    { label: 'ダッシュボード', href: route('dashboard') },
    { label: parentLabel.value, href: props.backRoute },
    { label: 'Excelインポート' },
]);

const form = useForm({
    file: null,
});

function onFileChange(event) {
    form.file = event.target.files[0] ?? null;
}

function submit() {
    form.post(props.uploadRoute, { forceFormData: true });
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
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">
                        Excel（xlsx/xls）またはCSV/TSVファイルをアップロードしてください。1行目は見出し行として扱われます。
                        列の割り当ては次の画面で手動で指定します（自動マッピングは行いません）。
                        文字コード（UTF-8、BOMの有無）や区切り文字（カンマ/タブ）は自動判定されるため、意識せずアップロードして問題ありません。
                    </p>

                    <div
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-indigo-50 px-4 py-3 text-sm text-indigo-700"
                    >
                        <p>
                            まずはテンプレートをダウンロードし、入力例の行を自事務所のデータに書き換えてからアップロードしてください。
                        </p>
                        <a
                            :href="templateRoute"
                            class="inline-flex shrink-0 items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        >
                            テンプレートをダウンロード
                        </a>
                    </div>

                    <form class="mt-6" @submit.prevent="submit">
                        <InputLabel for="file">ファイル</InputLabel>
                        <input
                            id="file"
                            type="file"
                            accept=".xlsx,.xls,.csv,.tsv"
                            class="mt-1 block w-full text-sm text-gray-700"
                            @change="onFileChange"
                        />
                        <InputError class="mt-1" :message="form.errors.file" />

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="backRoute">
                                <SecondaryButton type="button">
                                    キャンセル
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing || !form.file"
                            >
                                次へ（列を割り当てる）
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
