<script setup>
import { ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const flashMessage = ref('');
let flashTimer = null;

watch(
    () => page.props.flash?.success,
    (message) => {
        if (!message) return;

        flashMessage.value = message;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(() => {
            flashMessage.value = '';
        }, 3000);
    },
    { immediate: true },
);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-slate-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('platform.offices.index')"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="rounded-md bg-slate-800 px-2 py-1 text-xs font-semibold tracking-wide text-slate-300"
                            >
                                運営管理
                            </span>
                            <span class="text-sm font-semibold text-white">
                                顧客事務所一覧
                            </span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('platform.billing-plans.index')"
                            class="text-sm text-slate-300 hover:text-white"
                        >
                            料金設定
                        </Link>
                        <span class="text-sm text-slate-300">
                            {{ page.props.platformAuth.admin.name }}
                        </span>
                        <Link
                            :href="route('platform.logout')"
                            method="post"
                            as="button"
                            class="rounded-md bg-slate-800 px-3 py-1.5 text-sm text-slate-200 hover:bg-slate-700"
                        >
                            ログアウト
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>

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
                class="fixed bottom-6 right-6 z-50 rounded-md bg-slate-900 px-4 py-3 text-sm text-white shadow-lg"
                role="status"
            >
                {{ flashMessage }}
            </div>
        </Transition>
    </div>
</template>
