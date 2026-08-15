<script setup>
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
});

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

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ title }}
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">
                        Excel（xlsx/xls）またはCSVファイルをアップロードしてください。1行目は見出し行として扱われます。
                        列の割り当ては次の画面で手動で指定します（自動マッピングは行いません）。
                    </p>

                    <form class="mt-6" @submit.prevent="submit">
                        <InputLabel for="file">ファイル</InputLabel>
                        <input
                            id="file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
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
