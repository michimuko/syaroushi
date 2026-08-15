<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
});

const labels = {
    active: '契約中',
    inactive: '契約終了',
};

const classes = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-600',
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
