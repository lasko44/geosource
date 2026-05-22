<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

interface PlatformTrend {
    name: string;
    color: string;
    citations: number[];
    checks: number[];
}

interface TrendData {
    dates: string[];
    platforms: Record<string, PlatformTrend>;
    totals: {
        cited: number[];
        checks: number[];
    };
}

const props = defineProps<{
    trendData: TrendData;
    darkMode?: boolean;
}>();

const formatDateLabel = (dateStr: string) => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const textColor = computed(() => props.darkMode ? '#b0bec5' : '#64748b');
const gridColor = computed(() => props.darkMode ? 'rgba(148, 163, 184, 0.12)' : 'rgba(100, 116, 139, 0.1)');
const citedColor = computed(() => props.darkMode ? '#4ade80' : '#22c55e');
const notCitedColor = computed(() => props.darkMode ? '#f87171' : '#ef4444');

// Citation count per platform over time
const citationsByPlatformData = computed(() => {
    const labels = props.trendData.dates.map(formatDateLabel);
    const datasets = Object.entries(props.trendData.platforms).map(([, platform]) => ({
        label: platform.name,
        data: platform.citations,
        borderColor: platform.color,
        backgroundColor: platform.color + '20',
        borderWidth: 2,
        pointStyle: 'rectRounded',
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: platform.color,
        tension: 0.3,
        fill: false,
    }));

    return { labels, datasets };
});

// Platform legend items
const platformLegendItems = computed(() => {
    return Object.entries(props.trendData.platforms).map(([, platform]) => ({
        name: platform.name,
        color: platform.color,
    }));
});

// Total citations vs total checks over time
const overallTrendData = computed(() => {
    const labels = props.trendData.dates.map(formatDateLabel);
    return {
        labels,
        datasets: [
            {
                label: 'Cited',
                data: props.trendData.totals.cited,
                borderColor: citedColor.value,
                backgroundColor: props.darkMode ? 'rgba(74, 222, 128, 0.15)' : 'rgba(34, 197, 94, 0.15)',
                borderWidth: 2,
                pointStyle: 'rectRounded',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: citedColor.value,
                tension: 0.3,
                fill: true,
            },
            {
                label: 'Not Cited',
                data: props.trendData.totals.checks.map((total, i) => total - props.trendData.totals.cited[i]),
                borderColor: notCitedColor.value,
                backgroundColor: props.darkMode ? 'rgba(248, 113, 113, 0.15)' : 'rgba(239, 68, 68, 0.15)',
                borderWidth: 2,
                pointStyle: 'rectRounded',
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: notCitedColor.value,
                tension: 0.3,
                fill: true,
            },
        ],
    };
});

const overallLegendItems = computed(() => [
    { name: 'Cited', color: citedColor.value },
    { name: 'Not Cited', color: notCitedColor.value },
]);

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 3,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: props.darkMode ? '#1e293b' : '#ffffff',
            titleColor: props.darkMode ? '#e2e8f0' : '#1e293b',
            bodyColor: props.darkMode ? '#cbd5e1' : '#475569',
            borderColor: props.darkMode ? '#334155' : '#e2e8f0',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: { color: gridColor.value },
            ticks: { color: textColor.value, font: { size: 11 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: gridColor.value },
            ticks: {
                color: textColor.value,
                font: { size: 11 },
                stepSize: 1,
            },
        },
    },
}));

const hasData = computed(() => props.trendData.dates.length > 0);
</script>

<template>
    <div v-if="hasData" class="space-y-6">
        <!-- Citations by Platform -->
        <div>
            <h3 class="mb-3 text-sm font-medium text-muted-foreground">Citations by Platform</h3>
            <div>
                <Line :data="citationsByPlatformData" :options="chartOptions" />
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-4">
                <div v-for="item in platformLegendItems" :key="item.name" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="h-3 w-3 shrink-0 rounded" :style="{ backgroundColor: item.color }" />
                    {{ item.name }}
                </div>
            </div>
        </div>

        <!-- Overall Cited vs Not Cited -->
        <div>
            <h3 class="mb-3 text-sm font-medium text-muted-foreground">Overall Citation Results</h3>
            <div>
                <Line :data="overallTrendData" :options="chartOptions" />
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-4">
                <div v-for="item in overallLegendItems" :key="item.name" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="h-3 w-3 shrink-0 rounded" :style="{ backgroundColor: item.color }" />
                    {{ item.name }}
                </div>
            </div>
        </div>
    </div>
    <div v-else class="flex items-center justify-center py-12 text-sm text-muted-foreground">
        No check data yet. Run some checks to see trends.
    </div>
</template>
