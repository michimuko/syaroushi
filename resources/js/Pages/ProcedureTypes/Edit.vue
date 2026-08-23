<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    procedureType: Object,
});

const form = useForm({
    name: props.procedureType.name,
    category: props.procedureType.category,
    recurrence_type: props.procedureType.recurrence_type,
    recurrence_rule: {
        month: props.procedureType.recurrence_rule?.month ?? null,
        day: props.procedureType.recurrence_rule?.day ?? null,
        interval_months: props.procedureType.recurrence_rule?.interval_months ?? null,
        note: props.procedureType.recurrence_rule?.note ?? '',
    },
    default_lead_days: props.procedureType.default_lead_days.join(', '),
    description: props.procedureType.description ?? '',
    is_active: props.procedureType.is_active,
});

const leadDaysPreview = computed(() =>
    form.default_lead_days
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value !== ''),
);

const yearlyPreview = computed(() => {
    const { month, day } = form.recurrence_rule;
    if (!month || !day) {
        return '月・日が未設定のため、期限日は自動作成されません（このタイプは手動でタスクを作成してください）。';
    }
    return `毎年${month}月${day}日が期限日として、この手続きを購読している全顧問先に自動でタスクが作成されます。`;
});

const monthlyPreview = computed(() => {
    const { interval_months: intervalMonths, day } = form.recurrence_rule;
    if (!intervalMonths || !day) {
        return 'ヶ月数・日が未設定のため、期限日は自動作成されません（このタイプは手動でタスクを作成してください）。';
    }
    return `${intervalMonths}ヶ月ごとの${day}日が期限日として、この手続きを購読している全顧問先に自動でタスクが作成されます。`;
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        default_lead_days: leadDaysPreview.value.map(Number),
    })).put(route('procedure-types.update', props.procedureType.id));
};
</script>

<template>
    <Head :title="`${procedureType.name}の編集`" />

    <AuthenticatedLayout
        :breadcrumbs="[
            { label: 'ダッシュボード', href: route('dashboard') },
            {
                label: '手続き種別マスタ',
                href: route('procedure-types.index'),
            },
            { label: procedureType.name },
        ]"
    >
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ procedureType.name }}の編集
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="name">
                                    手続き名
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    placeholder="例：算定基礎届（定時決定）"
                                    required
                                    autofocus
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div>
                                <InputLabel for="category">
                                    カテゴリ
                                    <span class="text-red-600">*</span>
                                </InputLabel>
                                <TextInput
                                    id="category"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.category"
                                    placeholder="例：社会保険"
                                    required
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.category"
                                />
                            </div>

                            <div>
                                <InputLabel for="recurrence_type">
                                    周期
                                </InputLabel>
                                <select
                                    id="recurrence_type"
                                    v-model="form.recurrence_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="yearly">毎年</option>
                                    <option value="monthly">毎月</option>
                                    <option value="one_time">都度</option>
                                    <option value="custom">カスタム</option>
                                </select>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.recurrence_type"
                                />
                            </div>

                            <div
                                v-if="form.recurrence_type === 'yearly'"
                                class="rounded-md border border-gray-200 p-3"
                            >
                                <InputLabel>毎年の期限日</InputLabel>
                                <div class="mt-1 flex items-center gap-2">
                                    <select
                                        v-model.number="
                                            form.recurrence_rule.month
                                        "
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option :value="null">月</option>
                                        <option
                                            v-for="m in 12"
                                            :key="m"
                                            :value="m"
                                        >
                                            {{ m }}月
                                        </option>
                                    </select>
                                    <select
                                        v-model.number="
                                            form.recurrence_rule.day
                                        "
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option :value="null">日</option>
                                        <option
                                            v-for="d in 31"
                                            :key="d"
                                            :value="d"
                                        >
                                            {{ d }}日
                                        </option>
                                    </select>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ yearlyPreview }}
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors['recurrence_rule.month'] ||
                                        form.errors['recurrence_rule.day']
                                    "
                                />

                                <div class="mt-3">
                                    <InputLabel for="recurrence_note">
                                        備考（自動作成しない場合の理由など）
                                    </InputLabel>
                                    <TextInput
                                        id="recurrence_note"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.recurrence_rule.note"
                                        placeholder="例：有効期間は事業所ごとに異なるため個別管理"
                                    />
                                </div>
                            </div>

                            <div
                                v-else-if="form.recurrence_type === 'monthly'"
                                class="rounded-md border border-gray-200 p-3"
                            >
                                <InputLabel>期限日の周期</InputLabel>
                                <div
                                    class="mt-1 flex items-center gap-2 text-sm text-gray-700"
                                >
                                    <span>毎</span>
                                    <TextInput
                                        type="number"
                                        min="1"
                                        class="w-20"
                                        v-model.number="
                                            form.recurrence_rule
                                                .interval_months
                                        "
                                    />
                                    <span>ヶ月ごとの</span>
                                    <select
                                        v-model.number="
                                            form.recurrence_rule.day
                                        "
                                        class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option :value="null">日</option>
                                        <option
                                            v-for="d in 31"
                                            :key="d"
                                            :value="d"
                                        >
                                            {{ d }}日
                                        </option>
                                    </select>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ monthlyPreview }}
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors[
                                            'recurrence_rule.interval_months'
                                        ] || form.errors['recurrence_rule.day']
                                    "
                                />

                                <div class="mt-3">
                                    <InputLabel for="recurrence_note">
                                        備考（自動作成しない場合の理由など）
                                    </InputLabel>
                                    <TextInput
                                        id="recurrence_note"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.recurrence_rule.note"
                                        placeholder="例：初回のみ対応が異なるため個別管理"
                                    />
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-md bg-gray-50 p-3 text-xs text-gray-500"
                            >
                                「都度」「カスタム」を選択した場合、期限日は自動作成されません。この手続きの手続きタスクは顧問先ごとに個別で作成してください。
                            </div>

                            <div>
                                <InputLabel for="default_lead_days">
                                    通知タイミング（期限の何日前、カンマ区切り）
                                </InputLabel>
                                <TextInput
                                    id="default_lead_days"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.default_lead_days"
                                    placeholder="90, 30, 7"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    プレビュー：{{
                                        leadDaysPreview.length
                                            ? leadDaysPreview.join(' / ') +
                                              '日前'
                                            : '（未設定）'
                                    }}
                                </p>
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.default_lead_days"
                                />
                            </div>

                            <div>
                                <InputLabel for="description">説明</InputLabel>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="この手続きの概要や、対応時の注意点などを入力してください"
                                />
                                <InputError
                                    class="mt-1"
                                    :message="form.errors.description"
                                />
                            </div>

                            <div>
                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700"
                                        >有効にする</span
                                    >
                                </label>
                                <p class="mt-1 text-xs text-gray-500">
                                    無効にすると、この手続き種別は全事務所で新規購読の候補から外れます。
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                        >
                            <Link :href="route('procedure-types.index')">
                                <SecondaryButton type="button">
                                    キャンセル
                                </SecondaryButton>
                            </Link>
                            <PrimaryButton
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                保存する
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
