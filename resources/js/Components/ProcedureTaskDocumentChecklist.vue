<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    task: Object,
    documents: Array,
    canManage: Boolean,
});

const addForm = useForm({
    name: '',
    is_required: true,
    retention_years: '',
});

function addDocument() {
    addForm.post(route('tasks.documents.store', props.task.id), {
        preserveScroll: true,
        onSuccess: () => addForm.reset(),
    });
}

const toggleForm = useForm({ is_collected: false, retention_years: null });
const togglingId = ref(null);

function toggleCollected(document) {
    togglingId.value = document.id;
    toggleForm.is_collected = !document.is_collected;
    toggleForm.retention_years = document.retention_years;
    toggleForm.patch(route('tasks.documents.update', [props.task.id, document.id]), {
        preserveScroll: true,
        onFinish: () => (togglingId.value = null),
    });
}

const uploadForm = useForm({ file: null });
const uploadingId = ref(null);

function upload(document, event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }
    uploadForm.file = file;
    uploadingId.value = document.id;
    uploadForm.post(route('tasks.documents.upload', [props.task.id, document.id]), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            uploadingId.value = null;
            event.target.value = '';
        },
    });
}

const confirmingDeletion = ref(null);
const deleteForm = useForm({});

function confirmDelete(document) {
    confirmingDeletion.value = document;
}

function closeDeleteModal() {
    confirmingDeletion.value = null;
}

function destroyDocument() {
    deleteForm.delete(
        route('tasks.documents.destroy', [props.task.id, confirmingDeletion.value.id]),
        {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        },
    );
}
</script>

<template>
    <div class="mt-6 rounded-lg bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-900">書類チェックリスト</h3>

        <p v-if="documents.length === 0" class="mt-3 text-sm text-gray-500">
            書類チェックリストがまだ登録されていません。
        </p>

        <ul v-else class="mt-3 divide-y divide-gray-100">
            <li
                v-for="document in documents"
                :key="document.id"
                class="flex flex-wrap items-center gap-3 py-3"
            >
                <Checkbox
                    :checked="document.is_collected"
                    :disabled="!canManage || togglingId === document.id"
                    @update:checked="toggleCollected(document)"
                />

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        {{ document.name }}
                        <span
                            v-if="!document.is_required"
                            class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500"
                        >
                            任意
                        </span>
                    </p>
                    <p class="text-xs text-gray-500">
                        <span v-if="document.is_collected">
                            収集済み（{{ document.collected_at?.slice(0, 10) }}）
                        </span>
                        <span v-else>未収集</span>
                        <span v-if="document.retention_until">
                            ・保存期限：{{ document.retention_until }}
                        </span>
                    </p>
                    <InputError class="mt-1" :message="uploadForm.errors.file" />
                </div>

                <a
                    v-if="document.file_path"
                    :href="route('tasks.documents.download', [task.id, document.id])"
                    target="_blank"
                    class="text-sm text-indigo-600 underline hover:text-indigo-800"
                >
                    ダウンロード
                </a>

                <template v-if="canManage">
                    <label
                        class="cursor-pointer text-sm text-gray-600 underline hover:text-gray-800"
                        :class="{ 'pointer-events-none opacity-50': uploadingId === document.id }"
                    >
                        {{ document.file_path ? '再アップロード' : 'アップロード' }}
                        <input
                            type="file"
                            class="hidden"
                            @change="upload(document, $event)"
                        />
                    </label>

                    <button
                        type="button"
                        class="text-sm text-red-600 underline hover:text-red-800"
                        @click="confirmDelete(document)"
                    >
                        削除
                    </button>
                </template>
            </li>
        </ul>

        <form
            v-if="canManage"
            @submit.prevent="addDocument"
            class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4"
        >
            <div class="min-w-[10rem] flex-1">
                <InputLabel for="document-name">書類名</InputLabel>
                <TextInput
                    id="document-name"
                    v-model="addForm.name"
                    class="mt-1 block w-full"
                    placeholder="例：雇用契約書の写し"
                />
                <InputError class="mt-1" :message="addForm.errors.name" />
            </div>

            <div class="w-32">
                <InputLabel for="document-retention-years">保存年数</InputLabel>
                <TextInput
                    id="document-retention-years"
                    v-model="addForm.retention_years"
                    type="number"
                    min="1"
                    class="mt-1 block w-full"
                    placeholder="任意"
                />
                <InputError class="mt-1" :message="addForm.errors.retention_years" />
            </div>

            <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                <Checkbox v-model:checked="addForm.is_required" />
                必須
            </label>

            <PrimaryButton
                :class="{ 'opacity-25': addForm.processing }"
                :disabled="addForm.processing || !addForm.name"
            >
                追加
            </PrimaryButton>
        </form>
    </div>

    <Modal :show="confirmingDeletion !== null" @close="closeDeleteModal">
        <div class="p-6" v-if="confirmingDeletion">
            <h2 class="text-lg font-medium text-gray-900">
                書類「{{ confirmingDeletion.name }}」をチェックリストから削除しますか？
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                アップロード済みのファイルも削除されます。この操作は取り消せません。
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="closeDeleteModal">
                    キャンセル
                </SecondaryButton>
                <DangerButton
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="destroyDocument"
                >
                    削除する
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
