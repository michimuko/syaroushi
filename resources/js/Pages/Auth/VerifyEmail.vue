<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="メールアドレスの確認" />

        <div class="mb-4 text-sm text-gray-600">
            ご登録ありがとうございます。ご利用開始の前に、登録時のメールアドレス宛に届いたリンクをクリックして本人確認を行ってください。メールが届いていない場合は、以下のボタンから再送できます。
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            登録時のメールアドレス宛に、確認用のリンクを再送しました。
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    確認メールを再送する
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >ログアウト</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
