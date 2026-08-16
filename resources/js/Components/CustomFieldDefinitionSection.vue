<script setup>
import { computed, ref } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    target: String,
    fields: Array,
});

const fieldTypeLabels = {
    text: 'テキスト',
    number: '数値',
    date: '日付',
    select: '選択肢（単一選択）',
    checkbox: 'チェックボックス',
};

function freshAddForm() {
    return {
        target: props.target,
        label: '',
        field_type: 'text',
        options: [''],
    };
}

const addForm = useForm(freshAddForm());

const addOptionErrors = computed(() =>
    Object.entries(addForm.errors)
        .filter(([key]) => key.startsWith('options'))
        .map(([, message]) => message),
);

function addOption() {
    addForm.options.push('');
}

function removeOption(index) {
    addForm.options.splice(index, 1);
}

function submitAdd() {
    addForm.post(route('settings.custom-fields.store'), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(...Object.keys(freshAddForm())),
    });
}

const editing = ref(null);
const editForm = useForm({ label: '', options: [] });

const editOptionErrors = computed(() =>
    Object.entries(editForm.errors)
        .filter(([key]) => key.startsWith('options'))
        .map(([, message]) => message),
);

function startEdit(field) {
    editing.value = field;
    editForm.clearErrors();
    editForm.label = field.label;
    editForm.options = field.field_type === 'select' ? [...field.options] : [];
}

function closeEdit() {
    editing.value = null;
}

function addEditOption() {
    editForm.options.push('');
}

function removeEditOption(index) {
    editForm.options.splice(index, 1);
}

function submitEdit() {
    editForm.put(route('settings.custom-fields.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}

const confirmingDeletion = ref(null);
const deleteForm = useForm({});

function confirmDelete(field) {
    confirmingDeletion.value = field;
}

function closeDeleteModal() {
    confirmingDeletion.value = null;
}

function destroyField() {
    deleteForm.delete(
        route('settings.custom-fields.destroy', confirmingDeletion.value.id),
        {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        },
    );
}
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700">{{ title }}</h3>

        <table v-if="fields.length > 0" class="mt-3 min-w-full divide-y divide-gray-200">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <th class="py-2">項目名</th>
                    <th class="py-2">種類</th>
                    <th class="py-2">選択肢</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                <tr v-for="field in fields" :key="field.id">
                    <td class="py-2">{{ field.label }}</td>
                    <td class="py-2">{{ fieldTypeLabels[field.field_type] }}</td>
                    <td class="py-2">
                        <span v-if="field.field_type === 'select'">
                            {{ field.options?.join('、') }}
                        </span>
                    </td>
                    <td class="space-x-3 py-2 text-right">
                        <button
                            type="button"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                            @click="startEdit(field)"
                        >
                            編集
                        </button>
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-900"
                            @click="confirmDelete(field)"
                        >
                            削除
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-else class="mt-2 text-sm text-gray-500">
            まだカスタムフィールドは登録されていません。
        </p>

        <form @submit.prevent="submitAdd" class="mt-4 border-t border-gray-100 pt-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <InputLabel :for="`add-label-${target}`">項目名</InputLabel>
                    <TextInput
                        :id="`add-label-${target}`"
                        class="mt-1 block w-full"
                        v-model="addForm.label"
                        placeholder="例：管理番号"
                        required
                    />
                    <InputError class="mt-1" :message="addForm.errors.label" />
                </div>
                <div>
                    <InputLabel>種類</InputLabel>
                    <select
                        v-model="addForm.field_type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            v-for="(label, value) in fieldTypeLabels"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError class="mt-1" :message="addForm.errors.field_type" />
                </div>
            </div>

            <div v-if="addForm.field_type === 'select'" class="mt-3">
                <InputLabel>選択肢</InputLabel>
                <div
                    v-for="(option, index) in addForm.options"
                    :key="index"
                    class="mt-1 flex items-center gap-2"
                >
                    <TextInput class="block w-48" v-model="addForm.options[index]" placeholder="例：Aプラン" />
                    <button
                        type="button"
                        class="text-xs text-gray-400 hover:text-red-600"
                        @click="removeOption(index)"
                    >
                        削除
                    </button>
                </div>
                <SecondaryButton type="button" class="mt-2" @click="addOption">
                    選択肢を追加
                </SecondaryButton>
                <p
                    v-for="(message, i) in addOptionErrors"
                    :key="i"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ message }}
                </p>
            </div>

            <div class="mt-3">
                <PrimaryButton
                    :class="{ 'opacity-25': addForm.processing }"
                    :disabled="addForm.processing"
                >
                    追加する
                </PrimaryButton>
            </div>
        </form>
    </div>

    <Modal :show="editing !== null" @close="closeEdit">
        <div class="p-6" v-if="editing">
            <h2 class="text-lg font-medium text-gray-900">
                「{{ editing.label }}」を編集
            </h2>

            <div class="mt-4">
                <InputLabel for="edit-label">項目名</InputLabel>
                <TextInput
                    id="edit-label"
                    class="mt-1 block w-full"
                    v-model="editForm.label"
                    placeholder="例：管理番号"
                    required
                />
                <InputError class="mt-1" :message="editForm.errors.label" />
            </div>

            <div v-if="editing.field_type === 'select'" class="mt-3">
                <InputLabel>選択肢</InputLabel>
                <div
                    v-for="(option, index) in editForm.options"
                    :key="index"
                    class="mt-1 flex items-center gap-2"
                >
                    <TextInput class="block w-48" v-model="editForm.options[index]" placeholder="例：Aプラン" />
                    <button
                        type="button"
                        class="text-xs text-gray-400 hover:text-red-600"
                        @click="removeEditOption(index)"
                    >
                        削除
                    </button>
                </div>
                <SecondaryButton type="button" class="mt-2" @click="addEditOption">
                    選択肢を追加
                </SecondaryButton>
                <p
                    v-for="(message, i) in editOptionErrors"
                    :key="i"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ message }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="closeEdit">キャンセル</SecondaryButton>
                <PrimaryButton
                    :class="{ 'opacity-25': editForm.processing }"
                    :disabled="editForm.processing"
                    @click="submitEdit"
                >
                    保存する
                </PrimaryButton>
            </div>
        </div>
    </Modal>

    <Modal :show="confirmingDeletion !== null" @close="closeDeleteModal">
        <div class="p-6" v-if="confirmingDeletion">
            <h2 class="text-lg font-medium text-gray-900">
                カスタムフィールド「{{ confirmingDeletion.label }}」を削除しますか？
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                この操作は取り消せません。削除後はこの項目を入力できなくなります。既存データに保存済みの値は自動削除されませんが、次回そのレコードを保存すると表示・保持されなくなります。
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="closeDeleteModal">
                    キャンセル
                </SecondaryButton>
                <DangerButton
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="destroyField"
                >
                    削除する
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
