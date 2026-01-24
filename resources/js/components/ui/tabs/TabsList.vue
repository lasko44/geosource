<script setup lang="ts">
import type { TabsListProps } from "reka-ui"
import { TabsList } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsListProps & { class?: string }>()
</script>

<template>
  <TabsList
    :class="cn(
      'inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-muted p-1 text-muted-foreground',
      props.class
    )"
  >
    <slot />
  </TabsList>
</template>
