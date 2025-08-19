<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <Breadcrumb class="mb-2">
            <BreadcrumbItem>
              <BreadcrumbLink :href="route('admin.approvals.index')">Approvals</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbItem>
              <BreadcrumbPage>Review Application</BreadcrumbPage>
            </BreadcrumbItem>
          </Breadcrumb>
          <Heading title="Review Professional Application" />
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Applicant Information -->
      <Card>
        <CardHeader>
          <CardTitle>Applicant Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-6 md:grid-cols-2">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Name</p>
              <p class="text-lg">{{ user.name }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-muted-foreground">Email</p>
              <p class="text-lg">{{ user.email }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-muted-foreground">Role</p>
              <Badge class="mt-1">{{ user.role_label }}</Badge>
            </div>
            <div>
              <p class="text-sm font-medium text-muted-foreground">Applied</p>
              <p>{{ user.created_at }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Professional Credentials -->
      <Card>
        <CardHeader>
          <CardTitle>Professional Credentials</CardTitle>
          <CardDescription>Review submitted credentials and documentation</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-6">
            <div v-for="credential in user.credentials" :key="credential.id" class="border rounded-lg p-4">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="text-lg font-semibold">{{ credential.type }}</h4>
                  <p class="text-sm text-muted-foreground">{{ credential.number }}</p>
                </div>
                <div>
                  <Badge v-if="credential.is_expired" variant="destructive">Expired</Badge>
                  <Badge v-else-if="credential.is_expiring_soon" variant="default">Expiring Soon</Badge>
                  <Badge v-else variant="default">Valid</Badge>
                </div>
              </div>

              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Issuing Authority</p>
                  <p>{{ credential.issuer }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Issue Date</p>
                  <p>{{ credential.issued_at }}</p>
                </div>
                <div v-if="credential.expires_at">
                  <p class="text-sm font-medium text-muted-foreground">Expiry Date</p>
                  <p>{{ credential.expires_at }}</p>
                </div>
              </div>

              <div v-if="credential.additional_info" class="mt-4">
                <p class="text-sm font-medium text-muted-foreground">Additional Information</p>
                <p class="mt-1 text-sm">{{ credential.additional_info }}</p>
              </div>

              <div class="mt-4">
                <Button asChild variant="outline">
                  <a :href="credential.document_url" target="_blank" rel="noopener noreferrer">
                    View Document
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Approval Actions -->
      <Card>
        <CardHeader>
          <CardTitle>Review Decision</CardTitle>
          <CardDescription>Approve or reject this application</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex space-x-4">
            <Button @click="showApproveDialog" :disabled="processing">
              Approve Application
            </Button>
            <Button variant="destructive" @click="showRejectDialog" :disabled="processing">
              Reject Application
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Reject Dialog -->
    <Dialog :open="rejectDialogOpen" @update:open="rejectDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject Application</DialogTitle>
          <DialogDescription>
            Please provide a reason for rejecting this application. This will be sent to the applicant.
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="reason">Rejection Reason</Label>
            <Textarea
              id="reason"
              v-model="rejectionReason"
              placeholder="Enter the reason for rejection..."
              :disabled="processing"
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="rejectDialogOpen = false" :disabled="processing">
            Cancel
          </Button>
          <Button variant="destructive" @click="reject" :disabled="processing || !rejectionReason">
            Confirm Rejection
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Approve Dialog -->
    <Dialog :open="approveDialogOpen" @update:open="approveDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirm Approval</DialogTitle>
          <DialogDescription>
            Approve this application and assign the requested role to the user?
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-2 py-2">
          <p class="text-sm">Requested role: <strong>{{ user.requested_role || user.role_label }}</strong></p>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="approveDialogOpen = false" :disabled="processing">
            Cancel
          </Button>
          <Button @click="approve" :disabled="processing">
            Confirm Approval
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbPage,
  Breadcrumb,
} from '@/components/ui/breadcrumb'
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
  additional_info: string | null
}

interface Props {
  user: {
    id: number
    name: string
    email: string
    role: UserRole
    role_label: string
    requested_role: string | null
    created_at: string
    credentials: Credential[]
  }
}

const props = defineProps<Props>()
const processing = ref(false)
const rejectDialogOpen = ref(false)
const approveDialogOpen = ref(false)
const rejectionReason = ref('')

const approve = () => {
  processing.value = true
  router.post(route('admin.approvals.approve', props.user.id), {}, {
    onFinish: () => {
      processing.value = false
      approveDialogOpen.value = false
      // redirect to admin dashboard on success
      router.visit(route('admin.index'))
    },
  })
}

const showRejectDialog = () => {
  rejectDialogOpen.value = true
}

const showApproveDialog = () => {
  approveDialogOpen.value = true
}

const reject = () => {
  if (!rejectionReason.value) return

  processing.value = true
  router.post(route('admin.approvals.reject', props.user.id), {
    reason: rejectionReason.value,
  }, {
    onFinish: () => {
      processing.value = false
      rejectDialogOpen.value = false
      rejectionReason.value = ''
    },
  })
}
</script>
