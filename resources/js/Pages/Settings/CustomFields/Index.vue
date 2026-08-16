<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomFieldDefinitionSection from '@/Components/CustomFieldDefinitionSection.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    definitions: Array,
});

const clientFields = computed(() =>
    props.definitions.filter((d) => d.target === 'client'),
);
const taskFields = computed(() =>
    props.definitions.filter((d) => d.target === 'procedure_task'),
);
</script>

<template>
    <Head title="カスタムフィールド設定" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                カスタムフィールド設定
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">
                    事務所ごとに管理したい独自項目（管理番号、契約プランなど）を追加できます。追加した項目は顧問先・タスクの登録／編集画面に表示されます。
                </p>

                <CustomFieldDefinitionSection
                    title="顧問先用フィールド"
                    target="client"
                    :fields="clientFields"
                />

                <CustomFieldDefinitionSection
                    title="手続きタスク用フィールド"
                    target="procedure_task"
                    :fields="taskFields"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
