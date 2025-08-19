<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <Heading title="Pending Approvals" />
      </div>
    </template>

    <div class="space-y-6">
      <!-- Stats Overview -->
      <Card>
        <CardHeader>
          <CardTitle>Overview</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-6 md:grid-cols-3">
          <div>
            <p class="text-sm font-medium text-muted-foreground">Total Pending</p>
            <h3 class="text-2xl font-bold">{{ pendingUsers.total }}</h3>
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">Healthcare Professionals</p>
            <h3 class="text-2xl font-bold">{{ healthcareProfessionalCount }}</h3>
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">Campaign Managers</p>
            <h3 class="text-2xl font-bold">{{ campaignManagerCount }}</h3>
          </div>
        </CardContent>
      </Card>

      <!-- Pending Approvals Table -->
      <Card>
        <CardHeader>
          <CardTitle>Pending Applications</CardTitle>
          <CardDescription>Review and manage professional account applications</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="pendingUsers.data.length === 0" class="py-6 text-center">
            <p class="text-muted-foreground">No pending approvals</p>
          </div>
          <div v-else class="space-y-4">
            <div v-for="user in pendingUsers.data" :key="user.id" class="border rounded-lg p-4">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="text-lg font-semibold">{{ user.name }}</h4>
                  <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                  <Badge class="mt-1">{{ user.role_label }}</Badge>
                </div>
                <div class="flex space-x-2">
                  <Button asChild variant="outline">
                    <Link :href="route('admin.approvals.show', user.id)">
                      Review Application
                    </Link>
                  </Button>
                </div>
              </div>
              
              <!-- Credentials Preview -->
              <div class="mt-4 space-y-2">
                <p class="text-sm font-medium">Submitted Credentials:</p>
                <ul class="space-y-1">
                  <li v-for="credential in user.credentials" :key="credential.id" class="text-sm">
                    <span class="font-medium">{{ credential.type }}:</span>
                    {{ credential.number }} ({{ credential.issuer }})
                    <Badge v-if="credential.is_expired" variant="destructive" class="ml-2">Expired</Badge>
                    <Badge v-else-if="credential.is_expiring_soon" variant="default" class="ml-2">Expiring Soon</Badge>
                  </li>
                </ul>
              </div>

              <div class="mt-4 text-sm text-muted-foreground">
                Applied {{ user.created_at }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import Heading from '@/components/Heading.vue'
import { UserRole } from '@/types/role'

interface Credential {
  id: number
  type: string
  number: string
  issuer: string
  issued_at: string
  expires_at: string | null
  document_url: string
  is_expired: boolean
  is_expiring_soon: boolean
}

interface User {
  id: number
  name: string
  email: string
  role: UserRole
  role_label: string
  created_at: string
  credentials: Credential[]
}

interface Props {
  pendingUsers: {
    data: User[]
    total: number
  }
}

const props = defineProps<Props>()

const healthcareProfessionalCount = computed(() => 
  props.pendingUsers.data.filter(user => user.role === UserRole.HEALTHCARE_PROFESSIONAL).length
)

const campaignManagerCount = computed(() => 
  props.pendingUsers.data.filter(user => user.role === UserRole.HEALTH_CAMPAIGN_MANAGER).length
)
</script>
