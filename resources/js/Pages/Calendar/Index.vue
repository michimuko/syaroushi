<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import jaLocale from '@fullcalendar/core/locales/ja';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';

const legend = [
    { label: '未着手', color: '#9ca3af' },
    { label: '進行中', color: '#3b82f6' },
    { label: '書類収集済', color: '#6366f1' },
    { label: '提出済', color: '#f59e0b' },
    { label: '完了', color: '#22c55e' },
    { label: '期限超過', color: '#dc2626' },
];

const calendarOptions = {
    plugins: [dayGridPlugin],
    initialView: 'dayGridMonth',
    locale: jaLocale,
    height: 'auto',
    dayMaxEvents: true, // 日付セル内で件数が多い場合は「+N件」に省略し、クリックで一覧展開する（FullCalendar標準機能）
    events: async (fetchInfo, successCallback, failureCallback) => {
        try {
            const response = await axios.get(route('calendar.events'), {
                params: {
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                },
            });
            successCallback(response.data);
        } catch (error) {
            failureCallback(error);
        }
    },
    eventClick: (info) => {
        router.visit(
            `${route('tasks.edit', info.event.id)}?return_to=${encodeURIComponent('/calendar')}`,
        );
    },
};
</script>

<template>
    <Head title="カレンダー" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                カレンダー
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- 凡例：色だけに頼らずステータス名も併記する -->
                <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <div
                        v-for="item in legend"
                        :key="item.label"
                        class="flex items-center gap-1.5 text-xs text-gray-600"
                    >
                        <span
                            class="h-2.5 w-2.5 rounded-full"
                            :style="{ backgroundColor: item.color }"
                        />
                        {{ item.label }}
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
