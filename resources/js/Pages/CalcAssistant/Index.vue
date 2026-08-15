<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const tools = [
    {
        name: '年次有給休暇の付与日数計算',
        description:
            '入社日を基準に、フルタイム・パート／アルバイト（比例付与）両方の付与日数と基準日を自動計算します。',
        href: () => route('calc-assistant.paid-leave'),
        available: true,
    },
    {
        name: '時間外労働時間・36協定上限チェック',
        description: '準備中です。',
        available: false,
    },
    {
        name: '勤務シフト表作成支援',
        description: '準備中です。',
        available: false,
    },
];
</script>

<template>
    <Head title="計算アシスタント" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                計算アシスタント
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4">
                    <component
                        :is="tool.available ? Link : 'div'"
                        v-for="tool in tools"
                        :key="tool.name"
                        :href="tool.available ? tool.href() : undefined"
                        class="rounded-lg bg-white p-5 shadow-sm"
                        :class="
                            tool.available
                                ? 'block transition hover:shadow-md'
                                : 'opacity-60'
                        "
                    >
                        <h3 class="text-sm font-semibold text-gray-800">
                            {{ tool.name }}
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ tool.description }}
                        </p>
                    </component>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
