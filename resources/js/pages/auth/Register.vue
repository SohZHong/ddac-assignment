<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/index';
import { focusAreas, medicalSpecialties, organizationTypes, registrationBodies, userRoles } from '@/data/roleSpecifics';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    role: '1',
    password: '',
    password_confirmation: '',
    license_number: '',
    medical_specialty: '',
    institution_name: '',
    years_experience: '',
    registration_body: '',
    organization_name: '',
    job_title: '',
    organization_type: '',
    focus_areas: '',
    work_email: '',
});

const isHealthcareProfessional = computed(() => form.role === '2');
const isHealthCampaignManager = computed(() => form.role === '3');

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid hidden gap-2">
                    <Label for="role">User role</Label>
                    <Select v-model="form.role" default-value="1">
                        <SelectTrigger :tabindex="3" class="w-full">
                            <SelectValue placeholder="Select your role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in userRoles" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <!-- Healthcare Professional Additional Fields -->
            <template v-if="isHealthcareProfessional">
                <div class="border-t pt-4">
                    <h3 class="mb-4 text-lg font-medium">Professional Information</h3>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="license_number">Professional License Number</Label>
                            <Input id="license_number" type="text" required v-model="form.license_number" placeholder="e.g., MD123456" />
                            <InputError :message="form.errors.license_number" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="medical_specialty">Medical Specialty</Label>
                            <Select v-model="form.medical_specialty">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select your specialty" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="specialty in medicalSpecialties" :key="specialty" :value="specialty">
                                        {{ specialty }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.medical_specialty" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="institution_name">Institution/Hospital Name</Label>
                            <Input
                                id="institution_name"
                                type="text"
                                required
                                v-model="form.institution_name"
                                placeholder="e.g., City General Hospital"
                            />
                            <InputError :message="form.errors.institution_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="years_experience">Years of Experience</Label>
                            <Input
                                id="years_experience"
                                type="number"
                                required
                                v-model="form.years_experience"
                                placeholder="e.g., 5"
                                min="0"
                                max="50"
                            />
                            <InputError :message="form.errors.years_experience" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="registration_body">Professional Registration Body</Label>
                            <Select v-model="form.registration_body">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select registration body" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="body in registrationBodies" :key="body" :value="body">
                                        {{ body }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.registration_body" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- Health Campaign Manager Additional Fields -->
            <template v-if="isHealthCampaignManager">
                <div class="border-t pt-4">
                    <h3 class="mb-4 text-lg font-medium">Organization Information</h3>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="organization_name">Organization Name</Label>
                            <Input
                                id="organization_name"
                                type="text"
                                required
                                v-model="form.organization_name"
                                placeholder="e.g., Ministry of Health"
                            />
                            <InputError :message="form.errors.organization_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="job_title">Job Title/Position</Label>
                            <Input id="job_title" type="text" required v-model="form.job_title" placeholder="e.g., Health Campaign Coordinator" />
                            <InputError :message="form.errors.job_title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="organization_type">Organization Type</Label>
                            <Select v-model="form.organization_type">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select organization type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in organizationTypes" :key="type" :value="type">
                                        {{ type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.organization_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="focus_areas">Primary Focus Areas</Label>
                            <Select v-model="form.focus_areas">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Select focus areas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="area in focusAreas" :key="area" :value="area">
                                        {{ area }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.focus_areas" />
                        </div>
                    </div>
                </div>
            </template>

            <div class="grid gap-2" v-if="isHealthcareProfessional || isHealthCampaignManager">
                <Label for="work_email">Work_email</Label>
                <Input id="work_email" type="email" required :tabindex="5" v-model="form.work_email" placeholder="Work email" />
                <InputError :message="form.errors.work_email" />
            </div>

            <Button type="submit" class="mt-2 w-full" :tabindex="6" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Create account
            </Button>
            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="7">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
