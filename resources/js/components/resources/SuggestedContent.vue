<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowRight, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    currentSlug: string;
    limit?: number;
}>();

interface Suggestion {
    slug: string;
    type: string;
    title: string;
    url: string;
    excerpt: string;
}

const suggestions = ref<Suggestion[]>([]);
const loading = ref(true);

const typeLabels: Record<string, string> = {
    resource: 'Resource',
    industry: 'Industry Guide',
    platform: 'Platform Guide',
    comparison: 'Comparison',
};

onMounted(async () => {
    try {
        const limit = props.limit ?? 4;
        const res = await fetch(`/api/suggested-content?slug=${encodeURIComponent(props.currentSlug)}&limit=${limit}`);
        if (res.ok) {
            const data = await res.json();
            suggestions.value = data.suggestions ?? [];
        }
    } catch {
        // Gracefully degrade — no suggestions is fine
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <section v-if="!loading && suggestions.length > 0" aria-labelledby="suggested-heading" class="border-t bg-muted/30 py-12 sm:py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center gap-2">
                <Sparkles class="h-5 w-5 text-primary" aria-hidden="true" />
                <h2 id="suggested-heading" class="text-xl font-bold">Suggested Reading</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <Link
                    v-for="suggestion in suggestions"
                    :key="suggestion.slug"
                    :href="suggestion.url"
                    class="group block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                >
                    <Card class="h-full transition-colors hover:border-primary/50">
                        <CardContent class="pt-5">
                            <Badge variant="outline" class="mb-2 text-xs">
                                {{ typeLabels[suggestion.type] || suggestion.type }}
                            </Badge>
                            <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors line-clamp-2">
                                {{ suggestion.title }}
                            </h3>
                            <p class="mt-1.5 text-sm text-muted-foreground line-clamp-2">
                                {{ suggestion.excerpt }}
                            </p>
                            <span class="mt-3 inline-flex items-center text-sm font-medium text-primary" aria-hidden="true">
                                Read more
                                <ArrowRight class="ml-1 h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                            </span>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </section>
</template>
