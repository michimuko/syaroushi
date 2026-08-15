<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
});

const labels = {
    not_started: '未着手',
    in_progress: '進行中',
    documents_collected: '書類収集済',
    submitted: '提出済',
    completed: '完了',
};

// ステータス色マッピングは画面をまたいで統一する（デザイン指針2章）
const classes = {
    not_started: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-blue-100 text-blue-800',
    documents_collected: 'bg-indigo-100 text-indigo-800',
    submitted: 'bg-amber-100 text-amber-800',
    completed: 'bg-green-100 text-green-800',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes[status]"
    >
        {{ labels[status] }}
    </span>
</template>
