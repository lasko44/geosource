<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="timezone">Timezone</Label>
                        <select
                            id="timezone"
                            name="timezone"
                            class="mt-1 block w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            :value="user.timezone || 'UTC'"
                        >
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">Eastern Time (US & Canada)</option>
                            <option value="America/Chicago">Central Time (US & Canada)</option>
                            <option value="America/Denver">Mountain Time (US & Canada)</option>
                            <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
                            <option value="America/Anchorage">Alaska</option>
                            <option value="Pacific/Honolulu">Hawaii</option>
                            <option value="America/Phoenix">Arizona</option>
                            <option value="America/Toronto">Toronto</option>
                            <option value="America/Vancouver">Vancouver</option>
                            <option value="America/Mexico_City">Mexico City</option>
                            <option value="America/Sao_Paulo">Brasilia</option>
                            <option value="America/Buenos_Aires">Buenos Aires</option>
                            <option value="Europe/London">London</option>
                            <option value="Europe/Paris">Paris</option>
                            <option value="Europe/Berlin">Berlin</option>
                            <option value="Europe/Amsterdam">Amsterdam</option>
                            <option value="Europe/Madrid">Madrid</option>
                            <option value="Europe/Rome">Rome</option>
                            <option value="Europe/Zurich">Zurich</option>
                            <option value="Europe/Stockholm">Stockholm</option>
                            <option value="Europe/Moscow">Moscow</option>
                            <option value="Africa/Johannesburg">Johannesburg</option>
                            <option value="Africa/Cairo">Cairo</option>
                            <option value="Asia/Dubai">Dubai</option>
                            <option value="Asia/Kolkata">Mumbai, Kolkata</option>
                            <option value="Asia/Bangkok">Bangkok</option>
                            <option value="Asia/Singapore">Singapore</option>
                            <option value="Asia/Hong_Kong">Hong Kong</option>
                            <option value="Asia/Shanghai">Beijing, Shanghai</option>
                            <option value="Asia/Tokyo">Tokyo</option>
                            <option value="Asia/Seoul">Seoul</option>
                            <option value="Australia/Sydney">Sydney</option>
                            <option value="Australia/Melbourne">Melbourne</option>
                            <option value="Australia/Perth">Perth</option>
                            <option value="Australia/Brisbane">Brisbane</option>
                            <option value="Pacific/Auckland">Auckland</option>
                        </select>
                        <InputError class="mt-2" :message="errors.timezone" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
