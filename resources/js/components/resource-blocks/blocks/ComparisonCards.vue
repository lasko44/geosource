<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Search, Brain } from 'lucide-vue-next';

interface CardData {
    title: string;
    subtitle: string;
    description: string;
    highlighted?: boolean;
}

interface Props {
    leftCard: CardData;
    rightCard: CardData;
    conclusion?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="my-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <Card :class="{ 'border-muted': !leftCard.highlighted }">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Search class="h-6 w-6" />
                        <CardTitle>{{ leftCard.title }}</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-xl font-medium">{{ leftCard.subtitle }}</p>
                    <p class="mt-2 text-muted-foreground">{{ leftCard.description }}</p>
                </CardContent>
            </Card>

            <Card :class="rightCard.highlighted ? 'border-primary/50 bg-primary/5' : ''">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Brain :class="rightCard.highlighted ? 'text-primary' : ''" class="h-6 w-6" />
                        <CardTitle :class="rightCard.highlighted ? 'text-primary' : ''">{{ rightCard.title }}</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-xl font-medium">{{ rightCard.subtitle }}</p>
                    <p class="mt-2 text-muted-foreground">{{ rightCard.description }}</p>
                </CardContent>
            </Card>
        </div>

        <p v-if="conclusion" class="mt-6 text-center text-lg font-medium text-foreground">
            {{ conclusion }}
        </p>
    </div>
</template>
