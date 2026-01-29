<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Coins } from 'lucide-vue-next';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

interface Props {
    balance: number;
}

const props = defineProps<Props>();

const balanceColor = computed(() => {
    if (props.balance <= 0) return 'destructive';
    if (props.balance <= 10) return 'outline';
    return 'secondary';
});

const formattedBalance = computed(() => {
    if (props.balance >= 1000) {
        return `${(props.balance / 1000).toFixed(1)}k`;
    }
    return props.balance.toString();
});
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger asChild>
                <Link href="/tokens" class="inline-flex">
                    <Badge
                        :variant="balanceColor"
                        class="gap-1.5 px-2.5 py-1 transition-colors hover:bg-primary hover:text-primary-foreground"
                    >
                        <Coins class="h-3.5 w-3.5" />
                        <span class="font-medium">{{ formattedBalance }}</span>
                    </Badge>
                </Link>
            </TooltipTrigger>
            <TooltipContent side="bottom">
                <p>{{ balance }} tokens available</p>
                <p class="text-xs text-muted-foreground">Click to buy more</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
