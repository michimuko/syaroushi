<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CustomFieldInputs from '@/Components/CustomFieldInputs.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MyNumberWarning from '@/Components/MyNumberWarning.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { containsMyNumberLikeString } from '@/Composables/useMyNumberDetection';
import { initialCustomFieldValues } from '@/Composables/useCustomFieldValues';

const props = defineProps({
    clientOptions: Array,
    staffOptions: Array,
    procedureTypeOptions: Array,
    customFieldDefinitions: Array,
});

// <input type="date">に渡すため、UTC変換によるずれが出ないローカル日付から組み立てる
function todayDateString() {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
}

const form = useForm({
    client_id: '',
    procedure_type_id: '',
    title: '',
    due_date: todayDateString(),
    assigned_user_id: '',
    notes: '',
    custom_fields: initialCustomFieldValues(props.customFieldDefinitions),
});

const notesHasMyNumber = computed(() => containsMyNumberLikeString(form.notes));

const procedureTypesByCategory = computed(() => {
    const groups = {};
    for (const procedureType of props.procedureTypeOptions) {
        (groups[procedureType.category] ??= []).push(procedureType);
    }
    return groups;
});

function onProcedureTypeChange() {
    // 未入力の場合のみ選択した手続き名をタイトルの初期値として補完する（既存入力は上書きしない）
    if (form.title) {
        return;
    }
    const selected = props.procedureTypeOptions.find(
        (procedureType) => procedureType.id === Number(form.procedure_type_id),
    );
    if (selected) {
        form.title = selected.name;
    }
}

const submit = () => {
    form.post(route('tasks.store'));
};
</script>

<template>
    <Head title="タスクの新規作成" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                タスクの新規作成
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="client_id">
                                    顧問先
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <select
                                    id="client_id"
                                    v-model="form.client_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>
                                        選択してください
                                    </option>
                                    <option
                                        v-for="client in clientOptions"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.client_id"
                                />
                            </div>

                            <div>
                                <InputLabel for="procedure_type_id">
                                    手続き種別
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <select
                                    id="procedure_type_id"
                                    v-model="form.procedure_type_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="onProcedureTypeChange"
                                >
                                    <option value="" disabled>
                                        選択してください
                                    </option>
                                    <optgroup
                                        v-for="(items, category) in procedureTypesByCategory"
                                        :key="category"
                                        :label="category"
                                    >
                                        <option
                                            v-for="procedureType in items"
                                            :key="procedureType.id"
                                            :value="procedureType.id"
                                        >
                                            {{ procedureType.name }}
                                        </option>
                                    </optgroup>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.procedure_type_id"
                                />
                            </div>

                            <div>
                                <InputLabel for="title">
                                    タイトル
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="title"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.title"
                                    placeholder="例：算定基礎届の提出"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.title"
                                />
                            </div>

                            <div>
                                <InputLabel for="due_date">
                                    期限日
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="due_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.due_date"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.due_date"
                                />
                            </div>

                            <div>
                                <InputLabel for="assigned_user_id">
                                    担当者
                                </InputLabel>
                                <select
                                    id="assigned_user_id"
                                    v-model="form.assigned_user_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="対応履歴や留意事項などを入力してください（マイナンバーなど重要な個人情報は入力しないでください）"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.notes"
                                />
                                <MyNumberWarning
                                    class="mt-1"
                                    :show="notesHasMyNumber"
                                />
                            </div>
                        </div>

                        <CustomFieldInputs
                            v-model="form.custom_fields"
                            :definitions="customFieldDefinitions"
                            :errors="form.errors"
                        />

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="route('tasks.index')">
                                <SecondaryButton type="button">
                                    キャンセル
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                作成する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
