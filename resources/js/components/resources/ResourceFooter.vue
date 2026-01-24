<script setup lang="ts">
import { Globe, Mail } from 'lucide-vue-next';
</script>

<template>
    <footer role="contentinfo" class="border-t py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center gap-6">
                <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2">
                    <Mail class="h-5 w-5 text-primary" aria-hidden="true" />
                    <span class="text-sm font-medium">Need help?</span>
                    <a
                        href="mailto:support@geosource.ai"
                        class="text-sm font-semibold text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                        aria-label="Email support at support@geosource.ai"
                    >
                        support@geosource.ai
                    </a>
                </div>
                <div class="flex w-full flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <Globe class="h-6 w-6 text-primary" aria-hidden="true" />
                        <span class="font-semibold">GeoSource.ai</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} GeoSource.ai. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
