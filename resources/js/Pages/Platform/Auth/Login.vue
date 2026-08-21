<script setup>
import PlatformGuestLayout from '@/Layouts/PlatformGuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    login_id: '',
    password: '',
});

const submit = () => {
    form.post(route('platform.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PlatformGuestLayout>
        <Head title="運営者ログイン" />

        <h1 class="mb-4 text-base font-semibold text-gray-800">
            運営者ログイン
        </h1>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="login_id" value="ユーザーID" />

                <TextInput
                    id="login_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.login_id"
                    placeholder="運営者アカウントのユーザーID"
                    required
                    autofocus
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

            <div class="mt-6 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    ログイン
                </PrimaryButton>
            </div>
        </form>
    </PlatformGuestLayout>
</template>
