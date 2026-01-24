<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const CONSENT_KEY = 'geosource_cookie_consent';
const showBanner = ref(false);

const loadGA = () => {
    const gaId = document.querySelector('meta[name="ga-id"]')?.getAttribute('content');
    if (!gaId || window.gtag) return;

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${gaId}`;
    document.head.appendChild(script);

    window.dataLayer = window.dataLayer || [];
    window.gtag = function() { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', gaId);
};

const acceptCookies = () => {
    localStorage.setItem(CONSENT_KEY, 'accepted');
    showBanner.value = false;
    loadGA();
};

const declineCookies = () => {
    localStorage.setItem(CONSENT_KEY, 'declined');
    showBanner.value = false;
};

onMounted(() => {
    const consent = localStorage.getItem(CONSENT_KEY);

    if (consent === 'accepted') {
        loadGA();
    } else if (!consent) {
        showBanner.value = true;
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showBanner"
            class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6"
        >
            <div class="mx-auto max-w-4xl rounded-lg border bg-background shadow-lg">
                <div class="flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between md:p-6">
                    <div class="flex-1 pr-4">
                        <h3 class="font-semibold">We value your privacy</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            We use cookies to analyze site traffic and optimize your experience.
                            By accepting, you consent to our use of analytics cookies.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 gap-2">
                        <Button variant="outline" size="sm" @click="declineCookies">
                            Decline
                        </Button>
                        <Button size="sm" @click="acceptCookies">
                            Accept
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
