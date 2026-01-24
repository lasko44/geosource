<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Calendar, ArrowLeft } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';

interface ScheduledScan {
    uuid: string;
    url: string;
    name: string;
    frequency: 'daily' | 'weekly' | 'monthly';
    scheduled_time: string;
    day_of_week: number | null;
    day_of_month: number | null;
    is_active: boolean;
}

interface Props {
    scheduledScan: ScheduledScan;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scheduled Scans', href: '/scheduled-scans' },
    { title: 'Edit', href: `/scheduled-scans/${props.scheduledScan.uuid}/edit` },
];

const form = useForm({
    url: props.scheduledScan.url,
    name: props.scheduledScan.name || '',
    frequency: props.scheduledScan.frequency,
    scheduled_time: props.scheduledScan.scheduled_time || '09:00',
    day_of_week: props.scheduledScan.day_of_week ?? 1,
    day_of_month: props.scheduledScan.day_of_month ?? 1,
    is_active: props.scheduledScan.is_active,
});

const submit = () => {
    form.put(`/scheduled-scans/${props.scheduledScan.uuid}`);
};

const daysOfWeek = [
    { value: 0, label: 'Sunday' },
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
];

const daysOfMonth = Array.from({ length: 28 }, (_, i) => ({
    value: i + 1,
    label: `${i + 1}${getOrdinalSuffix(i + 1)}`,
}));

function getOrdinalSuffix(n: number): string {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return s[(v - 20) % 10] || s[v] || s[0];
}
</script>

<template>
    <Head title="Edit Scheduled Scan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center gap-4">
                <Link href="/scheduled-scans">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">Edit Scheduled Scan</h1>
                    <p class="text-muted-foreground">Update your scan schedule settings</p>
                </div>
            </div>

            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Schedule Configuration
                    </CardTitle>
                    <CardDescription>
                        Modify when and how often to scan your URL
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5">
                                <Label>Active</Label>
                                <p class="text-sm text-muted-foreground">Enable or disable this scheduled scan</p>
                            </div>
                            <Switch v-model:checked="form.is_active" />
                        </div>

                        <div class="space-y-2">
                            <Label for="url">URL to Scan</Label>
                            <Input
                                id="url"
                                v-model="form.url"
                                type="url"
                                placeholder="https://example.com/page"
                                required
                            />
                            <p v-if="form.errors.url" class="text-sm text-destructive">{{ form.errors.url }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="name">Name (optional)</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="My Homepage"
                            />
                            <p class="text-xs text-muted-foreground">A friendly name to identify this schedule</p>
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="frequency">Frequency</Label>
                                <Select v-model="form.frequency">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select frequency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="daily">Daily</SelectItem>
                                        <SelectItem value="weekly">Weekly</SelectItem>
                                        <SelectItem value="monthly">Monthly</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.frequency" class="text-sm text-destructive">{{ form.errors.frequency }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="scheduled_time">Time</Label>
                                <Input
                                    id="scheduled_time"
                                    v-model="form.scheduled_time"
                                    type="time"
                                    required
                                />
                                <p v-if="form.errors.scheduled_time" class="text-sm text-destructive">{{ form.errors.scheduled_time }}</p>
                            </div>
                        </div>

                        <div v-if="form.frequency === 'weekly'" class="space-y-2">
                            <Label for="day_of_week">Day of Week</Label>
                            <Select v-model="form.day_of_week">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select day" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="day in daysOfWeek"
                                        :key="day.value"
                                        :value="day.value"
                                    >
                                        {{ day.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.day_of_week" class="text-sm text-destructive">{{ form.errors.day_of_week }}</p>
                        </div>

                        <div v-if="form.frequency === 'monthly'" class="space-y-2">
                            <Label for="day_of_month">Day of Month</Label>
                            <Select v-model="form.day_of_month">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select day" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="day in daysOfMonth"
                                        :key="day.value"
                                        :value="day.value"
                                    >
                                        {{ day.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">Days 29-31 are not available to ensure consistent scheduling</p>
                            <p v-if="form.errors.day_of_month" class="text-sm text-destructive">{{ form.errors.day_of_month }}</p>
                        </div>

                        <Alert v-if="form.errors.feature" variant="destructive">
                            <AlertDescription>{{ form.errors.feature }}</AlertDescription>
                        </Alert>

                        <div class="flex gap-3">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                            <Link href="/scheduled-scans">
                                <Button variant="outline" type="button">Cancel</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
