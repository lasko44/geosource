<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Upload, Trash2, Palette, Building2, Mail, Globe, FileText, HelpCircle, ChevronDown, ChevronUp, FileDown, Zap, Eye } from 'lucide-vue-next';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamData {
    id: number;
    name: string;
    slug: string;
    company_name: string | null;
    logo_path: string | null;
    logo_url: string | null;
    primary_color: string;
    secondary_color: string;
    report_footer: string | null;
    contact_email: string | null;
    website_url: string | null;
}

interface Props {
    team: TeamData;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.slug}`,
    },
    {
        title: 'White Label',
        href: `/teams/${props.team.slug}/white-label`,
    },
];

const form = useForm({
    company_name: props.team.company_name || '',
    primary_color: props.team.primary_color,
    secondary_color: props.team.secondary_color,
    report_footer: props.team.report_footer || '',
    contact_email: props.team.contact_email || '',
    website_url: props.team.website_url || '',
});

const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(props.team.logo_url);
const uploadingLogo = ref(false);
const removingLogo = ref(false);
const instructionsOpen = ref(false);

const submit = () => {
    form.put(`/teams/${props.team.slug}/white-label`);
};

const onLogoSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        logoFile.value = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const uploadLogo = () => {
    if (!logoFile.value) return;

    uploadingLogo.value = true;
    router.post(`/teams/${props.team.slug}/white-label/logo`, {
        logo: logoFile.value,
    }, {
        forceFormData: true,
        onFinish: () => {
            uploadingLogo.value = false;
            logoFile.value = null;
        },
    });
};

const removeLogo = () => {
    removingLogo.value = true;
    router.delete(`/teams/${props.team.slug}/white-label/logo`, {
        onFinish: () => {
            removingLogo.value = false;
            logoPreview.value = null;
        },
    });
};

const cancelLogoUpload = () => {
    logoFile.value = null;
    logoPreview.value = props.team.logo_url;
};
</script>

<template>
    <Head :title="`White Label - ${team.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <HeadingSmall
                title="White Label Settings"
                description="Customize your reports with your own branding"
            />

            <!-- Instructions Section -->
            <Collapsible v-model:open="instructionsOpen" class="mt-6">
                <Card>
                    <CollapsibleTrigger as-child>
                        <CardHeader class="cursor-pointer hover:bg-muted/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <HelpCircle class="h-5 w-5 text-primary" />
                                    <CardTitle>How to Use White Label Reports</CardTitle>
                                </div>
                                <ChevronDown v-if="!instructionsOpen" class="h-5 w-5 text-muted-foreground" />
                                <ChevronUp v-else class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <CardDescription>
                                Learn how to customize your reports with your agency's branding
                            </CardDescription>
                        </CardHeader>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <CardContent class="pt-0 space-y-6">
                            <!-- What is White Labeling -->
                            <div>
                                <h4 class="font-semibold text-sm mb-2">What is White Labeling?</h4>
                                <p class="text-sm text-muted-foreground">
                                    White labeling allows you to brand GEO scan reports with your agency's identity.
                                    When you export PDF reports, they will display your logo, colors, and contact information
                                    instead of GeoSource branding, making them perfect for sharing with your clients.
                                </p>
                            </div>

                            <!-- Where Branding Appears -->
                            <div>
                                <h4 class="font-semibold text-sm mb-2 flex items-center gap-2">
                                    <Eye class="h-4 w-4" />
                                    Where Your Branding Appears
                                </h4>
                                <ul class="text-sm text-muted-foreground space-y-2 ml-6 list-disc">
                                    <li><strong>PDF Report Header</strong> - Your logo and company name appear at the top of every exported report</li>
                                    <li><strong>Report Colors</strong> - Primary and secondary colors are used for headings, score displays, and accent elements</li>
                                    <li><strong>Report Footer</strong> - Your contact email, website, and custom footer text appear at the bottom of each page</li>
                                </ul>
                            </div>

                            <!-- Quick Setup Guide -->
                            <div>
                                <h4 class="font-semibold text-sm mb-2 flex items-center gap-2">
                                    <Zap class="h-4 w-4" />
                                    Quick Setup Guide
                                </h4>
                                <ol class="text-sm text-muted-foreground space-y-2 ml-6 list-decimal">
                                    <li><strong>Upload your logo</strong> - Use a PNG, JPG, or SVG file. Recommended size: 200x50 pixels for best results.</li>
                                    <li><strong>Set your colors</strong> - Choose a primary color (main headings, score card) and secondary color (gradients, accents) that match your brand.</li>
                                    <li><strong>Add company details</strong> - Enter your company name, contact email, and website URL for the report footer.</li>
                                    <li><strong>Add footer text</strong> - Optionally include a custom message like "Confidential - Prepared for [Client]".</li>
                                    <li><strong>Export a report</strong> - Go to any scan and click "Export PDF" to generate a white-labeled report.</li>
                                </ol>
                            </div>

                            <!-- Tips for Best Results -->
                            <div>
                                <h4 class="font-semibold text-sm mb-2 flex items-center gap-2">
                                    <FileDown class="h-4 w-4" />
                                    Tips for Best Results
                                </h4>
                                <ul class="text-sm text-muted-foreground space-y-2 ml-6 list-disc">
                                    <li><strong>Logo format</strong> - Use PNG with transparent background or SVG for crisp rendering at any size</li>
                                    <li><strong>Logo dimensions</strong> - Keep your logo under 200px wide and 50px tall for optimal placement</li>
                                    <li><strong>Color contrast</strong> - Ensure your primary color has good contrast against white backgrounds</li>
                                    <li><strong>File size</strong> - Keep logo files under 2MB for faster PDF generation</li>
                                    <li><strong>Test your reports</strong> - Export a sample PDF to preview how your branding looks before sending to clients</li>
                                </ul>
                            </div>

                            <!-- Alert about applying to all team scans -->
                            <Alert>
                                <AlertDescription class="text-sm">
                                    White label settings apply to all PDF exports from this team's scans.
                                    Each team can have its own white label configuration, allowing you to create
                                    different branded reports for different clients or projects.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </CollapsibleContent>
                </Card>
            </Collapsible>

            <!-- Logo Upload Section -->
            <Card class="mt-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Upload class="h-5 w-5" />
                        Company Logo
                    </CardTitle>
                    <CardDescription>
                        Upload your logo to appear on PDF reports and emails. Recommended size: 200x50px
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-start gap-6">
                        <!-- Logo Preview -->
                        <div class="flex h-24 w-48 items-center justify-center rounded-lg border-2 border-dashed bg-muted/50">
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                alt="Company logo"
                                class="max-h-20 max-w-44 object-contain"
                            />
                            <span v-else class="text-sm text-muted-foreground">No logo</span>
                        </div>

                        <!-- Upload Controls -->
                        <div class="flex flex-col gap-3">
                            <div>
                                <Input
                                    id="logo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                    class="w-full cursor-pointer file:cursor-pointer"
                                    @change="onLogoSelect"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    PNG, JPG, or SVG. Max 2MB.
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    v-if="logoFile"
                                    size="sm"
                                    :disabled="uploadingLogo"
                                    @click="uploadLogo"
                                >
                                    {{ uploadingLogo ? 'Uploading...' : 'Upload Logo' }}
                                </Button>
                                <Button
                                    v-if="logoFile"
                                    variant="outline"
                                    size="sm"
                                    @click="cancelLogoUpload"
                                >
                                    Cancel
                                </Button>
                                <Button
                                    v-if="team.logo_path && !logoFile"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="removingLogo"
                                    @click="removeLogo"
                                >
                                    <Trash2 class="mr-1 h-4 w-4" />
                                    {{ removingLogo ? 'Removing...' : 'Remove' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Branding Settings -->
            <Card class="mt-6">
                <form @submit.prevent="submit">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Brand Settings
                        </CardTitle>
                        <CardDescription>
                            Customize how your reports look
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="company_name">Company Name</Label>
                            <Input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                placeholder="Your Company Name"
                            />
                            <p class="text-xs text-muted-foreground">
                                This will appear in the report header. Leave blank to use team name.
                            </p>
                            <InputError :message="form.errors.company_name" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="primary_color" class="flex items-center gap-2">
                                    <Palette class="h-4 w-4" />
                                    Primary Color
                                </Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="primary_color"
                                        v-model="form.primary_color"
                                        type="color"
                                        class="h-10 w-14 cursor-pointer p-1"
                                    />
                                    <Input
                                        v-model="form.primary_color"
                                        type="text"
                                        class="flex-1 font-mono"
                                        placeholder="#3b82f6"
                                    />
                                </div>
                                <InputError :message="form.errors.primary_color" />
                            </div>

                            <div class="space-y-2">
                                <Label for="secondary_color" class="flex items-center gap-2">
                                    <Palette class="h-4 w-4" />
                                    Secondary Color
                                </Label>
                                <div class="flex gap-2">
                                    <Input
                                        id="secondary_color"
                                        v-model="form.secondary_color"
                                        type="color"
                                        class="h-10 w-14 cursor-pointer p-1"
                                    />
                                    <Input
                                        v-model="form.secondary_color"
                                        type="text"
                                        class="flex-1 font-mono"
                                        placeholder="#1e40af"
                                    />
                                </div>
                                <InputError :message="form.errors.secondary_color" />
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="rounded-lg border p-4">
                            <p class="mb-2 text-sm font-medium">Color Preview</p>
                            <div class="flex gap-2">
                                <div
                                    class="h-10 flex-1 rounded"
                                    :style="{ backgroundColor: form.primary_color }"
                                />
                                <div
                                    class="h-10 flex-1 rounded"
                                    :style="{ backgroundColor: form.secondary_color }"
                                />
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="mt-6">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Branding' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <!-- Contact & Footer Settings -->
            <Card class="mt-6">
                <form @submit.prevent="submit">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5" />
                            Report Details
                        </CardTitle>
                        <CardDescription>
                            Add contact information and custom footer text to your reports
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="contact_email" class="flex items-center gap-2">
                                <Mail class="h-4 w-4" />
                                Contact Email
                            </Label>
                            <Input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                placeholder="reports@yourcompany.com"
                            />
                            <InputError :message="form.errors.contact_email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="website_url" class="flex items-center gap-2">
                                <Globe class="h-4 w-4" />
                                Website URL
                            </Label>
                            <Input
                                id="website_url"
                                v-model="form.website_url"
                                type="url"
                                placeholder="https://yourcompany.com"
                            />
                            <InputError :message="form.errors.website_url" />
                        </div>

                        <div class="space-y-2">
                            <Label for="report_footer">Custom Footer Text</Label>
                            <Textarea
                                id="report_footer"
                                v-model="form.report_footer"
                                rows="2"
                                placeholder="e.g., Confidential - For internal use only"
                            />
                            <p class="text-xs text-muted-foreground">
                                This text will appear at the bottom of your PDF reports.
                            </p>
                            <InputError :message="form.errors.report_footer" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Details' }}
                        </Button>
                        <Button variant="ghost" as-child>
                            <Link :href="`/teams/${team.slug}`">Back to Team</Link>
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
