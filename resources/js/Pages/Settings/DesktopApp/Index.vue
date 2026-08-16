<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    token: Object,
});

const page = usePage();
const issuedToken = computed(() => page.props.flash?.desktopAppToken);

const issueForm = useForm({});
const revokeForm = useForm({});

const confirmingReissue = ref(false);
const confirmingRevoke = ref(false);

function issueToken() {
    issueForm.post(route('settings.desktop-app.token.store'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingReissue.value = false;
        },
    });
}

function revokeToken() {
    revokeForm.delete(route('settings.desktop-app.token.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingRevoke.value = false;
        },
    });
}
</script>

<template>
    <Head title="デスクトップ通知アプリ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                デスクトップ通知アプリ
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">
                    OS標準の通知センターに直接プッシュする常駐型デスクトップアプリ（Tauri製）を利用するには、
                    ここで発行するAPIトークンをアプリの初回起動時に設定してください。トークンは本人の通知のみ取得できます。
                </p>

                <div
                    v-if="issuedToken"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-6"
                >
                    <h3 class="text-sm font-semibold text-amber-900">
                        新しいトークンを発行しました
                    </h3>
                    <p class="mt-1 text-sm text-amber-800">
                        この画面を離れると二度と表示できません。デスクトップアプリの設定画面に貼り付けてください。
                    </p>
                    <code
                        class="mt-3 block break-all rounded-md bg-white p-3 text-sm text-gray-900"
                    >{{ issuedToken }}</code>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">
                        トークンの状態
                    </h3>

                    <div v-if="token" class="space-y-4">
                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs text-gray-500">
                                    発行日時
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ token.created_at }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">
                                    最終利用日時
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ token.last_used_at || '未使用' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="flex gap-3">
                            <SecondaryButton
                                @click="confirmingReissue = true"
                            >
                                再発行する
                            </SecondaryButton>
                            <DangerButton @click="confirmingRevoke = true">
                                失効させる
                            </DangerButton>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <p class="text-sm text-gray-500">
                            まだトークンを発行していません。
                        </p>
                        <PrimaryButton
                            :class="{ 'opacity-25': issueForm.processing }"
                            :disabled="issueForm.processing"
                            @click="issueToken"
                        >
                            トークンを発行する
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="confirmingReissue" @close="confirmingReissue = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    トークンを再発行しますか？
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    現在デスクトップアプリに設定済みのトークンは無効になり、通知を受け取れなくなります。再発行後は新しいトークンをデスクトップアプリに設定し直してください。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingReissue = false">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': issueForm.processing }"
                        :disabled="issueForm.processing"
                        @click="issueToken"
                    >
                        再発行する
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingRevoke" @close="confirmingRevoke = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    トークンを失効させますか？
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    デスクトップアプリはこのトークンで通知を取得できなくなります。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirmingRevoke = false">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton
                        :class="{ 'opacity-25': revokeForm.processing }"
                        :disabled="revokeForm.processing"
                        @click="revokeToken"
                    >
                        失効させる
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
