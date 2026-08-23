<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav v-if="items.length > 0" aria-label="パンくずリスト" class="mb-2">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-1.5"
            >
                <Link
                    v-if="item.href && index !== items.length - 1"
                    :href="item.href"
                    class="hover:text-indigo-600 hover:underline"
                >
                    {{ item.label }}
                </Link>
                <span
                    v-else
                    class="font-medium text-gray-700"
                    :aria-current="index === items.length - 1 ? 'page' : undefined"
                >
                    {{ item.label }}
                </span>
                <svg
                    v-if="index !== items.length - 1"
                    class="h-3.5 w-3.5 shrink-0 text-gray-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </li>
        </ol>
    </nav>
</template>
