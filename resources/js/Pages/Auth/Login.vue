<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    rememberedOfficeCode: {
        type: String,
        default: null,
    },
});

const form = useForm({
    office_code: props.rememberedOfficeCode ?? '',
    login_id: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="ログイン" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="office_code" value="事業所ID" />

                <TextInput
                    id="office_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.office_code"
                    placeholder="例：samplekabusikigaisya"
                    required
                    :autofocus="!rememberedOfficeCode"
                    autocomplete="organization"
                />

                <InputError class="mt-2" :message="form.errors.office_code" />
            </div>

            <div class="mt-4">
                <InputLabel for="login_id" value="ユーザーID" />

                <TextInput
                    id="login_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.login_id"
                    placeholder="例：taro"
                    required
                    :autofocus="!!rememberedOfficeCode"
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.login_id" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="パスワード" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">
                        ログイン状態を保持する
                    </span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    パスワードをお忘れですか？
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    ログイン
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
