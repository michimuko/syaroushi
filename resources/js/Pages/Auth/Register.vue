<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    billingPlans: {
        type: Array,
        default: () => [],
    },
    selectedPlanId: {
        type: Number,
        default: null,
    },
    contactEmail: {
        type: String,
        default: null,
    },
});

const form = useForm({
    office_name: '',
    office_code: '',
    name: '',
    login_id: '',
    email: '',
    password: '',
    password_confirmation: '',
    billing_plan_id: props.selectedPlanId,
});

const formatYen = (amount) => `${amount.toLocaleString('ja-JP')}円/月`;

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="新規登録" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel value="プランを選択" />

                <div class="mt-1 space-y-2">
                    <label
                        v-for="plan in billingPlans"
                        :key="plan.id"
                        class="block cursor-pointer rounded-md border px-3 py-2 text-sm transition"
                        :class="form.billing_plan_id === plan.id
                            ? 'border-indigo-600 bg-indigo-50'
                            : 'border-gray-300 hover:border-gray-400'"
                    >
                        <span class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    :value="plan.id"
                                    v-model="form.billing_plan_id"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="font-medium text-gray-800">{{ plan.name }}</span>
                            </span>
                            <span class="font-semibold text-gray-800">{{ formatYen(plan.monthly_price) }}</span>
                        </span>
                        <span class="mt-0.5 block pl-6 text-xs text-gray-400">
                            顧問先{{ plan.max_clients }}件・ユーザー{{ plan.max_users }}人まで
                        </span>
                    </label>
                </div>

                <p v-if="contactEmail" class="mt-2 text-xs text-gray-500">
                    より大規模なご利用は
                    <a :href="`mailto:${contactEmail}`" class="underline hover:text-gray-700">お問い合わせください</a>（個別見積り）。
                </p>

                <InputError class="mt-2" :message="form.errors.billing_plan_id" />
            </div>

            <div class="mt-4">
                <InputLabel for="office_name" value="事務所名" />

                <TextInput
                    id="office_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.office_name"
                    placeholder="例：〇〇社会保険労務士事務所"
                    required
                    autofocus
                    autocomplete="organization"
                />

                <InputError class="mt-2" :message="form.errors.office_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="office_code" value="事業所ID" />

                <TextInput
                    id="office_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.office_code"
                    placeholder="例：samplekabusikigaisya（ログイン時に使用します）"
                    required
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="form.errors.office_code" />
            </div>

            <div class="mt-4">
                <InputLabel for="name" value="お名前" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    placeholder="例：山田 太郎"
                    required
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="login_id" value="ユーザーID" />

                <TextInput
                    id="login_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.login_id"
                    placeholder="例：taro（ログイン時に使用します）"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.login_id" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="メールアドレス" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    placeholder="例：taro@example.co.jp"
                    required
                    autocomplete="email"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="パスワード" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    placeholder="8文字以上の半角英数字"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="パスワード（確認用）"
                />

                <PasswordInput
                    id="password_confirmation"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    placeholder="確認のためもう一度入力してください"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <p class="mt-4 text-xs text-gray-500">
                トライアル期間中は課金されません。続けてStripeの決済ページでお支払い方法をご登録いただきます。
            </p>

            <div class="mt-2 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    すでにアカウントをお持ちですか？
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    登録してトライアルを開始する
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
