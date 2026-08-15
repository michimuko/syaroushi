<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div v-show="show">
        <p class="text-sm text-amber-600 dark:text-amber-400">
            マイナンバーらしき数字が含まれています。マイナンバーは入力せず書類アップロードを使ってください。
        </p>
    </div>
</template>
