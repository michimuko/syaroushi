<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-10 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <ApplicationLogo class="h-12 w-12 fill-current text-indigo-600" />
                <span class="flex flex-col items-center">
                    <span class="text-lg font-semibold text-gray-800">
                        キゲンバン
                    </span>
                    <span class="text-xs text-gray-400">
                        社労士業務進捗管理
                    </span>
                </span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
