<script setup lang="ts">
interface Props {
    headers: string[];
    rows: string[][];
    highlightColumn?: number; // 0-indexed column to highlight (usually GEO column)
}

const props = withDefaults(defineProps<Props>(), {
    highlightColumn: -1,
});
</script>

<template>
    <div class="overflow-x-auto my-6" role="region" aria-label="Data table" tabindex="0">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b">
                    <th
                        v-for="(header, i) in headers"
                        :key="i"
                        scope="col"
                        class="py-3 px-4 text-left font-semibold"
                        :class="{ 'text-primary': i === highlightColumn }"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, rowIndex) in rows" :key="rowIndex" class="border-b">
                    <td
                        v-for="(cell, cellIndex) in row"
                        :key="cellIndex"
                        class="py-3 px-4"
                        :class="{
                            'font-medium': cellIndex === 0,
                            'text-muted-foreground': cellIndex !== 0 && cellIndex !== highlightColumn,
                            'text-primary font-medium': cellIndex === highlightColumn,
                        }"
                        v-html="cell"
                    />
                </tr>
            </tbody>
        </table>
    </div>
</template>
