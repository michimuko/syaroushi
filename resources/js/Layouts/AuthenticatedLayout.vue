<script setup>
import { ref, watch } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import PushNotificationToggle from '@/Components/PushNotificationToggle.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const showingSidebar = ref(false);

const page = usePage();
const flashMessage = ref('');
const flashIsError = ref(false);
let flashTimer = null;

const showFlash = (message, isError) => {
    if (!message) return;

    flashMessage.value = message;
    flashIsError.value = isError;
    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => {
        flashMessage.value = '';
    }, 3000);
};

watch(
    () => page.props.flash?.success,
    (message) => showFlash(message, false),
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (message) => showFlash(message, true),
    { immediate: true },
);
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Mobile backdrop -->
        <div
            v-if="showingSidebar"
            class="fixed inset-0 z-30 bg-gray-900/50 sm:hidden"
            @click="showingSidebar = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 -translate-x-full transform flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-in-out sm:translate-x-0"
            :class="{ 'translate-x-0': showingSidebar }"
        >
            <div
                class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-100 px-4"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-2"
                >
                    <ApplicationLogo
                        class="h-8 w-auto shrink-0 fill-current text-indigo-600"
                    />
                    <span class="flex flex-col leading-tight">
                        <span class="text-base font-semibold text-gray-800">
                            キゲンバン
                        </span>
                        <span class="text-[10px] text-gray-400">
                            社労士業務進捗管理
                        </span>
                    </span>
                </Link>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                <ResponsiveNavLink
                    :href="route('dashboard')"
                    :active="route().current('dashboard')"
                >
                    ダッシュボード
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('clients.index')"
                    :active="route().current('clients.*')"
                >
                    顧問先
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('tasks.index')"
                    :active="route().current('tasks.*')"
                >
                    タスク
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('calendar.index')"
                    :active="route().current('calendar.*')"
                >
                    カレンダー
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    v-if="page.props.auth.enabledModules?.includes('calc_assistant')"
                    :href="route('calc-assistant.index')"
                    :active="route().current('calc-assistant.*')"
                >
                    計算アシスタント
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    :href="route('procedure-types.index')"
                    :active="route().current('procedure-types.*')"
                >
                    手続き種別マスタ
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    v-if="page.props.auth.user.role === 'owner'"
                    :href="route('users.index')"
                    :active="route().current('users.*')"
                >
                    ユーザー管理
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    v-if="
                        page.props.auth.enabledModules?.includes(
                            'custom_fields',
                        ) &&
                        (page.props.auth.user.role === 'owner' ||
                            page.props.auth.user.permissions?.includes(
                                'manage_custom_fields',
                            ))
                    "
                    :href="route('settings.custom-fields.index')"
                    :active="route().current('settings.custom-fields.*')"
                >
                    カスタムフィールド設定
                </ResponsiveNavLink>
                <ResponsiveNavLink
                    v-if="page.props.auth.user.role === 'owner'"
                    :href="route('settings.billing.index')"
                    :active="route().current('settings.billing.*')"
                >
                    契約・請求
                </ResponsiveNavLink>
            </nav>

            <div class="shrink-0 border-t border-gray-100 p-4">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-medium text-gray-800"
                        >
                            {{ page.props.auth.user.name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">
                            {{ page.props.auth.user.email }}
                        </p>
                    </div>
                    <PushNotificationToggle
                        v-if="page.props.auth.enabledModules?.includes('web_push')"
                    />
                </div>

                <div class="mt-3 space-y-1">
                    <Link
                        :href="route('profile.edit')"
                        class="block rounded-md px-2 py-1.5 text-sm text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900"
                    >
                        プロフィール
                    </Link>
                    <Link
                        :href="route('settings.desktop-app.index')"
                        class="block rounded-md px-2 py-1.5 text-sm text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900"
                    >
                        デスクトップ通知アプリ
                    </Link>
                    <a
                        :href="route('manual.download')"
                        target="_blank"
                        class="block rounded-md px-2 py-1.5 text-sm text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900"
                    >
                        操作マニュアル
                    </a>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="block w-full rounded-md px-2 py-1.5 text-start text-sm text-gray-600 transition duration-150 ease-in-out hover:bg-gray-50 hover:text-gray-900"
                    >
                        ログアウト
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex min-w-0 flex-1 flex-col sm:ml-64">
            <!-- Mobile top bar -->
            <div
                class="flex h-14 shrink-0 items-center gap-3 border-b border-gray-100 bg-white px-4 sm:hidden"
            >
                <button
                    type="button"
                    aria-label="メニューを開閉する"
                    @click="showingSidebar = !showingSidebar"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                >
                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            :class="{
                                hidden: showingSidebar,
                                'inline-flex': !showingSidebar,
                            }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{
                                hidden: !showingSidebar,
                                'inline-flex': showingSidebar,
                            }"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
                <span class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold text-gray-800">
                        キゲンバン
                    </span>
                    <span class="text-[9px] text-gray-400">
                        社労士業務進捗管理
                    </span>
                </span>
            </div>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header || breadcrumbs.length > 0"
            >
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <Breadcrumbs :items="breadcrumbs" />
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>

        <!-- Flash Toast -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="flashMessage"
                class="fixed bottom-6 right-6 z-50 rounded-md px-4 py-3 text-sm text-white shadow-lg"
                :class="flashIsError ? 'bg-red-700' : 'bg-gray-900'"
                role="status"
            >
                {{ flashMessage }}
            </div>
        </Transition>
    </div>
</template>
