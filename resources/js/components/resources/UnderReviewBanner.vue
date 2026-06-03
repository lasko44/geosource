<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight } from 'lucide-vue-next';

defineProps<{
    /** The specific reason this page is under review — research finding it conflicts with. */
    reason: string;
    /** Optional link to a specific study that informed the revision (default: /research). */
    researchHref?: string;
}>();
</script>

<template>
    <aside
        role="note"
        aria-label="Editorial notice"
        class="mb-8 rounded-lg border border-amber-500/40 bg-amber-50/50 dark:bg-amber-950/20 p-4 sm:p-5"
    >
        <div class="flex items-start gap-3">
            <AlertTriangle class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" aria-hidden="true" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    This guide is being rewritten
                </p>
                <p class="mt-1 text-sm text-amber-900/90 dark:text-amber-100/90 leading-relaxed">
                    {{ reason }}
                </p>
                <Link
                    :href="researchHref || '/research'"
                    class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-amber-900 dark:text-amber-200 hover:underline"
                >
                    Read the underlying research
                    <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                </Link>
            </div>
        </div>
    </aside>
</template>
