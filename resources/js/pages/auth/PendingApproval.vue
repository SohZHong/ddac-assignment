<template>
    <AuthLayout>
        <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-8">
                <div class="text-center">
                    <div class="mx-auto h-12 w-12 text-yellow-600">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Account Pending Verification</h2>
                    <p class="mt-2 text-sm text-gray-600">Your professional credentials are under review</p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="space-y-4">
                        <div class="rounded-md border border-yellow-200 bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Waiting for Admin Verification</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>
                                            Thank you for registering as a <strong>{{ user.role }}</strong
                                            >. Your professional credentials require admin verification before you can access the dashboard.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Name:</span>
                                <span class="font-medium text-gray-900">{{ user.name }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Email:</span>
                                <span class="font-medium text-gray-900">{{ user.email }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Role:</span>
                                <span class="font-medium text-gray-900">{{ user.role }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Submitted:</span>
                                <span class="font-medium text-gray-900">{{ user.submitted_at }}</span>
                            </div>
                        </div>

                        <div class="rounded-md border border-blue-200 bg-blue-50 p-4">
                            <div class="text-sm text-blue-700">
                                <p class="font-medium">What happens next?</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    <li>An administrator will verify your professional credentials</li>
                                    <li>You'll receive an email notification once verified</li>
                                    <li>After verification, you can access all dashboard features</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <button
                                @click="logout"
                                class="flex-1 rounded-md bg-gray-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Logout
                            </button>
                            <button
                                @click="refresh"
                                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Refresh Status
                            </button>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-xs text-gray-500">
                        Need help? Contact support at
                        <a href="mailto:support@example.com" class="text-blue-600 hover:text-blue-500"> support@example.com </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { router } from '@inertiajs/vue3';

interface User {
    name: string;
    email: string;
    role: string;
    submitted_at: string;
}

interface Props {
    user: User;
}

defineProps<Props>();

const logout = () => {
    router.post(route('logout'));
};

const refresh = () => {
    router.reload();
};
</script>
