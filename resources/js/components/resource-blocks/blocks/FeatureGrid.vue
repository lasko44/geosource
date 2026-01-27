<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import {
    Database,
    Search,
    Cpu,
    FileCheck,
    BookOpen,
    Brain,
    BarChart,
    Eye,
    Shield,
    Zap,
    Clock,
    CheckCircle,
} from 'lucide-vue-next';

interface Feature {
    icon: string;
    title: string;
    description?: string;
}

interface Props {
    features: Feature[];
    columns?: 2 | 3 | 4;
}

const props = withDefaults(defineProps<Props>(), {
    columns: 2,
});

const iconComponents: Record<string, unknown> = {
    Database, Search, Cpu, FileCheck, BookOpen, Brain, BarChart, Eye, Shield, Zap, Clock, CheckCircle,
};

const getIcon = (name: string) => iconComponents[name] || BookOpen;

const gridClass = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
};
</script>

<template>
    <div class="grid gap-4 my-6" :class="gridClass[columns]">
        <Card v-for="feature in features" :key="feature.title">
            <CardContent class="pt-6">
                <div class="flex items-start gap-3">
                    <component :is="getIcon(feature.icon)" class="h-6 w-6 text-primary shrink-0" />
                    <div>
                        <p class="font-medium">{{ feature.title }}</p>
                        <p v-if="feature.description" class="text-sm text-muted-foreground mt-1">{{ feature.description }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
