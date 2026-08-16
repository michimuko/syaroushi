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
    release: Object,
});

const page = usePage();
const issuedToken = computed(() => page.props.flash?.desktopAppToken);

const osOptions = [
    { key: 'windows', label: 'Windows', hint: '.msi' },
    { key: 'macos', label: 'Mac', hint: '.dmg' },
    { key: 'linux', label: 'Linux', hint: '.AppImage' },
];

// ダウンロードボタンを並べる順番の先頭に、利用者のOSを推定して出す（誤クリック防止のための案内であり、
// 判定を誤っても他OS用のボタンも常に表示するので実害はない）。
const detectedOs = (() => {
    if (typeof navigator === 'undefined') {
        return null;
    }
    const ua = navigator.userAgent;
    if (/Windows/i.test(ua)) return 'windows';
    if (/Mac OS X|Macintosh/i.test(ua)) return 'macos';
    if (/Linux/i.test(ua)) return 'linux';
    return null;
})();

const orderedOsOptions = computed(() => {
    if (!detectedOs) {
        return osOptions;
    }
    return [...osOptions].sort((a, b) =>
        a.key === detectedOs ? -1 : b.key === detectedOs ? 1 : 0,
    );
});

function assetFor(osKey) {
    return props.release?.assets?.[osKey] ?? null;
}

function formatSize(bytes) {
    if (!bytes) return '';
    const mb = bytes / 1024 / 1024;
    return `${mb.toFixed(1)} MB`;
}

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
                    下記からインストーラーをダウンロードして実行し、続けてAPIトークンをアプリの初回起動時に設定してください。トークンは本人の通知のみ取得できます。
                </p>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-1 text-sm font-semibold text-gray-700">
                        1. アプリをダウンロードしてインストールする
                    </h3>

                    <template v-if="release">
                        <p class="mb-4 text-xs text-gray-500">
                            最新バージョン：{{ release.version }}
                        </p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <a
                                v-for="option in orderedOsOptions"
                                :key="option.key"
                                :href="
                                    assetFor(option.key)
                                        ? route('settings.desktop-app.download', option.key)
                                        : undefined
                                "
                                class="rounded-md border px-4 py-3 text-center"
                                :class="
                                    assetFor(option.key)
                                        ? option.key === detectedOs
                                            ? 'border-indigo-600 bg-indigo-50 hover:bg-indigo-100'
                                            : 'border-gray-200 hover:bg-gray-50'
                                        : 'cursor-not-allowed border-gray-100 text-gray-300'
                                "
                            >
                                <span
                                    v-if="option.key === detectedOs"
                                    class="mb-1 block text-xs font-semibold text-indigo-600"
                                >
                                    お使いのPC向け
                                </span>
                                <span class="block text-sm font-semibold text-gray-800">
                                    {{ option.label }}用をダウンロード
                                </span>
                                <span class="mt-0.5 block text-xs text-gray-400">
                                    <template v-if="assetFor(option.key)">
                                        {{ option.hint }}・{{
                                            formatSize(assetFor(option.key).size)
                                        }}
                                    </template>
                                    <template v-else> 準備中 </template>
                                </span>
                            </a>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">
                            ダウンロードしたインストーラーを実行するだけでセットアップは完了します（Rustや開発ツールのインストールは不要です）。
                        </p>
                    </template>
                    <div
                        v-else
                        class="rounded-md border border-dashed border-gray-200 bg-gray-50 p-4"
                    >
                        <p class="text-sm font-medium text-gray-700">
                            インストーラーは現在準備中です
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            配布の準備が整い次第、この画面からダウンロードできるようになります。準備が整うまでは、下記の手順2・3は行わなくても構いません（先にトークンだけ発行しておくことも可能です）。
                        </p>
                    </div>
                </div>

                <h3 class="mb-1 mt-2 text-sm font-semibold text-gray-700">
                    2. アプリにAPIトークンを設定する
                    <span v-if="!release" class="ml-1 text-xs font-normal text-gray-400"
                        >（アプリのダウンロードが可能になってからで構いません）</span
                    >
                </h3>

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
                        3. トークンの状態
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
