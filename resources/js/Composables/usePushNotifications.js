import { ref } from 'vue';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function usePushNotifications(vapidPublicKey) {
    const isSupported =
        'serviceWorker' in navigator && 'PushManager' in window;
    const isSubscribed = ref(false);
    const isLoading = ref(false);

    async function getRegistration() {
        return navigator.serviceWorker.register('/sw.js');
    }

    async function refreshStatus() {
        if (!isSupported) return;

        const registration = await getRegistration();
        const subscription = await registration.pushManager.getSubscription();
        isSubscribed.value = subscription !== null;
    }

    async function subscribe() {
        if (!isSupported) return;

        isLoading.value = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const registration = await getRegistration();
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            const subscriptionJson = subscription.toJSON();
            await window.axios.post(route('push-subscriptions.store'), {
                endpoint: subscriptionJson.endpoint,
                public_key: subscriptionJson.keys?.p256dh,
                auth_token: subscriptionJson.keys?.auth,
                content_encoding: 'aes128gcm',
            });

            isSubscribed.value = true;
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe() {
        if (!isSupported) return;

        isLoading.value = true;
        try {
            const registration = await getRegistration();
            const subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                isSubscribed.value = false;
                return;
            }

            const endpoint = subscription.endpoint;
            await subscription.unsubscribe();
            await window.axios.delete(route('push-subscriptions.destroy'), {
                data: { endpoint },
            });

            isSubscribed.value = false;
        } finally {
            isLoading.value = false;
        }
    }

    return { isSupported, isSubscribed, isLoading, refreshStatus, subscribe, unsubscribe };
}
