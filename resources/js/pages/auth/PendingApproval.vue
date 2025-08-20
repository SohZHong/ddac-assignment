<template>
  <AuthSimpleLayout>
    <Card class="w-full max-w-lg">
      <CardHeader>
        <CardTitle>Account Pending Approval</CardTitle>
        <CardDescription>
          Your professional account is currently under review
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="text-center">
          <div class="mb-4">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100">
              <ClockIcon class="w-6 h-6 text-yellow-600" />
            </span>
          </div>
          <p class="text-muted-foreground">
            Thank you for registering as a professional on our platform. Our team is currently reviewing your credentials.
            You will receive an email notification once your account has been approved.
          </p>
        </div>

        <Separator />

        <div class="space-y-2">
          <p class="text-sm font-medium">While you wait:</p>
          <ul class="list-disc list-inside text-sm text-muted-foreground space-y-1">
            <li>Make sure your email address is correct to receive approval notifications</li>
            <li>You can still access public features of the platform</li>
            <li>Contact support if you have any questions about your application</li>
          </ul>
        </div>
      </CardContent>
      <CardFooter class="flex justify-between">
        <Button variant="outline" asChild>
          <Link href="/">Return Home</Link>
        </Button>
        <Button asChild>
          <Link href="/settings/profile">View Profile</Link>
        </Button>
      </CardFooter>
    </Card>
  </AuthSimpleLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { ClockIcon } from '@heroicons/vue/24/outline'
</script>
