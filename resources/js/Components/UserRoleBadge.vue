<script setup>
defineProps({
    role: {
        type: String,
        required: true,
    },
});

const labels = {
    owner: 'オーナー',
    staff: '社員',
};

const classes = {
    owner: 'bg-indigo-100 text-indigo-800',
    staff: 'bg-gray-100 text-gray-700',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes[role]"
    >
        {{ labels[role] }}
    </span>
</template>
