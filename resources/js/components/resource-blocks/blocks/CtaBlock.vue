<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    buttonText: string;
    buttonHref: string;
    variant?: 'default' | 'card';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <Card v-if="variant === 'card'" class="my-6 border-primary/50 bg-primary/5">
        <CardContent class="pt-6 text-center">
            <h3 class="text-xl font-bold">{{ title }}</h3>
            <p v-if="description" class="mt-2 text-muted-foreground">{{ description }}</p>
            <div class="mt-4">
                <Link :href="buttonHref">
                    <Button class="gap-2">
                        {{ buttonText }}
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>
        </CardContent>
    </Card>

    <div v-else class="text-center my-8">
        <p v-if="description" class="text-lg text-muted-foreground mb-4">{{ description }}</p>
        <Link :href="buttonHref">
            <Button size="lg" class="gap-2">
                {{ buttonText }}
                <ArrowRight class="h-4 w-4" />
            </Button>
        </Link>
    </div>
</template>
