<script setup>
import { computed, onMounted, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({
    type: String,
    required: true,
});

const attrs = useAttrs();
const inputAttrs = computed(() => {
    const { class: _class, ...rest } = attrs;
    return rest;
});

const input = ref(null);
const visible = ref(false);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div class="relative" :class="$attrs.class">
        <input
            :type="visible ? 'text' : 'password'"
            class="block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
            v-model="model"
            v-bind="inputAttrs"
            ref="input"
        />
        <button
            type="button"
            :title="visible ? 'パスワードを隠す' : 'パスワードを表示する'"
            :aria-label="visible ? 'パスワードを隠す' : 'パスワードを表示する'"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 focus:outline-none dark:text-gray-500 dark:hover:text-gray-300"
            @click="visible = !visible"
        >
            <svg
                v-if="visible"
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
                    d="M2 10s3-6 8-6 8 6 8 6-3 6-8 6-8-6-8-6z"
                />
                <circle cx="10" cy="10" r="2.5" stroke-linecap="round" />
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
                    d="M2 10s3-6 8-6c1.6 0 3 .4 4.2 1.05M18 10s-1.02 2.04-3.02 3.7M8.5 8.5a2.5 2.5 0 003.53 3.53"
                />
                <path stroke-linecap="round" d="M2 2l16 16" />
            </svg>
        </button>
    </div>
</template>
