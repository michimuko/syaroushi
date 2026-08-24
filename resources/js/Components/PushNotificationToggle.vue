<script setup>
import { onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { usePushNotifications } from '@/Composables/usePushNotifications';

const page = usePage();
const { isSupported, isSubscribed, isLoading, refreshStatus, subscribe, unsubscribe } =
    usePushNotifications(page.props.vapidPublicKey);

onMounted(() => {
    refreshStatus();
});

function toggle() {
    if (isSubscribed.value) {
        unsubscribe();
    } else {
        subscribe();
    }
}
</script>

<template>
    <button
        v-if="isSupported"
        type="button"
        :disabled="isLoading"
        :title="
            isSubscribed
                ? 'ブラウザ通知（Web Push）を無効にする'
                : 'ブラウザ通知（Web Push）を有効にする'
        "
        class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none disabled:opacity-50 dark:text-gray-400 dark:hover:text-gray-300"
        @click="toggle"
    >
        <svg
            v-if="isLoading"
            class="h-5 w-5 animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
        </svg>
        <svg
            v-else-if="isSubscribed"
            class="h-5 w-5"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
        >
            <path
                d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a2 2 0 001.995-1.85L12 16H8a2 2 0 001.85 1.995L10 18z"
            />
        </svg>
        <svg
            v-else
            class="h-5 w-5"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM8 16a2 2 0 004 0"
            />
            <path stroke-linecap="round" d="M3 3l14 14" />
        </svg>
    </button>
</template>
