<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Building2, Check } from 'lucide-vue-next';
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

interface Property {
    account_id: string;
    account_name: string;
    property_id: string;
    property_name: string;
}

interface Props {
    properties: Property[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'GA4 Analytics', href: '/analytics/ga4' },
    { title: 'Select Property', href: '#' },
];

const selectedProperty = ref<Property | null>(null);

const form = useForm({
    property_id: '',
    property_name: '',
    account_id: '',
});

const selectProperty = (property: Property) => {
    selectedProperty.value = property;
    form.property_id = property.property_id;
    form.property_name = property.property_name;
    form.account_id = property.account_id;
};

const submit = () => {
    form.post('/analytics/ga4/select-property');
};
</script>

<template>
    <Head title="Select GA4 Property" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center">
                <Building2 class="mx-auto h-12 w-12 text-muted-foreground" />
                <h1 class="mt-4 text-2xl font-bold">Select a GA4 Property</h1>
                <p class="mt-2 text-muted-foreground">
                    Choose which Google Analytics 4 property you want to connect for AI referral tracking.
                </p>
            </div>

            <!-- Properties List -->
            <Card>
                <CardHeader>
                    <CardTitle>Available Properties</CardTitle>
                    <CardDescription>
                        Found {{ properties.length }} GA4 {{ properties.length === 1 ? 'property' : 'properties' }} in your account
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="property in properties"
                            :key="property.property_id"
                            class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-colors"
                            :class="{
                                'border-primary bg-primary/5': selectedProperty?.property_id === property.property_id,
                                'hover:border-muted-foreground': selectedProperty?.property_id !== property.property_id,
                            }"
                            @click="selectProperty(property)"
                        >
                            <div>
                                <p class="font-medium">{{ property.property_name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ property.account_name }} · {{ property.property_id }}
                                </p>
                            </div>
                            <div
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="{
                                    'border-primary bg-primary text-primary-foreground': selectedProperty?.property_id === property.property_id,
                                    'border-muted': selectedProperty?.property_id !== property.property_id,
                                }"
                            >
                                <Check
                                    v-if="selectedProperty?.property_id === property.property_id"
                                    class="h-4 w-4"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-4">
                        <Button
                            variant="outline"
                            @click="$inertia.visit('/analytics/ga4')"
                        >
                            Cancel
                        </Button>
                        <Button
                            :disabled="!selectedProperty || form.processing"
                            @click="submit"
                        >
                            {{ form.processing ? 'Connecting...' : 'Connect Property' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
