<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MyNumberWarning from '@/Components/MyNumberWarning.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { containsMyNumberLikeString } from '@/Composables/useMyNumberDetection';

const props = defineProps({
    client: Object,
    reports: Array,
});

const today = new Date().toISOString().slice(0, 10);
const firstDayOfMonth = new Date();
firstDayOfMonth.setDate(1);

const form = useForm({
    period_start: firstDayOfMonth.toISOString().slice(0, 10),
    period_end: today,
    comment: '',
});

const commentHasMyNumber = computed(() =>
    containsMyNumberLikeString(form.comment),
);

const submit = () => {
    form.post(route('clients.reports.store', props.client.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('comment');
        },
    });
};
</script>

<template>
    <Head :title="`${client.name}の進捗レポート`" />

    <AuthenticatedLayout
        :breadcrumbs="[
            { label: 'ダッシュボード', href: route('dashboard') },
            { label: '顧問先', href: route('clients.index') },
            { label: client.name, href: route('clients.edit', client.id) },
            { label: '進捗レポート' },
        ]"
    >
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ client.name }}の進捗レポート
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-700">
                        レポートを生成する
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        指定期間内が期限日の手続きを一覧化したPDFを生成します。
                    </p>

                    <form @submit.prevent="submit" class="mt-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="period_start">
                                    対象期間（開始）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="period_start"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.period_start"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.period_start"
                                />
                            </div>

                            <div>
                                <InputLabel for="period_end">
                                    対象期間（終了）
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="period_end"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.period_end"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.period_end"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="comment">
                                    所感コメント（任意）
                                </InputLabel>
                                <textarea
                                    id="comment"
                                    v-model="form.comment"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="PDFの末尾に差し込まれます"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.comment"
                                />
                                <MyNumberWarning
                                    class="mt-1"
                                    :show="commentHasMyNumber"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                レポートを生成する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="mt-6 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-700">
                        生成履歴
                    </h3>

                    <p
                        v-if="reports.length === 0"
                        class="mt-3 text-sm text-gray-500"
                    >
                        まだレポートは生成されていません。
                    </p>

                    <table v-else class="mt-3 min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2">対象期間</th>
                                <th class="py-2">作成者</th>
                                <th class="py-2">作成日時</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-700">
                            <tr v-for="report in reports" :key="report.id">
                                <td class="py-2">
                                    {{ report.period_start }} 〜
                                    {{ report.period_end }}
                                </td>
                                <td class="py-2">
                                    {{ report.generated_by_name }}
                                </td>
                                <td class="py-2">{{ report.created_at }}</td>
                                <td class="py-2 text-right">
                                    <a
                                        :href="
                                            route(
                                                'clients.reports.download',
                                                [client.id, report.id],
                                            )
                                        "
                                        class="text-indigo-600 underline hover:text-indigo-800"
                                    >
                                        ダウンロード
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="text-sm text-gray-500 underline hover:text-gray-700"
                    >
                        顧問先編集に戻る
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
