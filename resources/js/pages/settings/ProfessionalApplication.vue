<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="space-y-1">
          <div class="mb-2">
            <BreadcrumbItem>
              <BreadcrumbLink :href="route('profile.edit')">Settings</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbItem>
              <BreadcrumbPage>Professional Application</BreadcrumbPage>
            </BreadcrumbItem>
          </div>
          <Heading title="Apply as Professional" />
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Pending banner -->
      <Card v-if="pending_application?.is_pending" class="border-yellow-400 bg-yellow-50 text-yellow-900">
        <CardContent class="py-4">
          <p class="text-sm">
            Your application to become
            <strong>{{ pending_application.requested_role_label || pending_application.requested_role }}</strong>
            is currently pending review by an administrator.
          </p>
        </CardContent>
      </Card>

      <!-- Rejected banner -->
      <Card v-if="application_status === 'rejected'" class="border-red-400 bg-red-50 text-red-900">
        <CardContent class="py-4">
          <p class="text-sm">
            Your application to become
            <strong>{{ pending_application?.requested_role_label || pending_application?.requested_role }}</strong>
            has been rejected. You may submit a new application with updated credentials.
          </p>
        </CardContent>
      </Card>

      <!-- Approved banner -->
      <Card v-if="application_status === 'approved'" class="border-green-400 bg-green-50 text-green-900">
        <CardContent class="py-4">
          <p class="text-sm">
            Congratulations! Your application to become
            <strong>{{ pending_application?.requested_role_label || pending_application?.requested_role }}</strong>
            has been approved. You now have access to all features available to your role.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Professional Role Application</CardTitle>
          <CardDescription>
            Apply to become a Healthcare Professional or Health Campaign Manager
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Role Selection -->
            <div class="space-y-2">
              <Label for="role">Professional Role*</Label>
              <select v-model="form.role" class="w-full rounded-md border border-input bg-background px-3 py-2">
                <option value="">Select the role you're applying for</option>
                <option v-for="role in available_roles" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
              <InputError :message="form.errors.role" />
              <p class="text-sm text-muted-foreground">
                Select the professional role you wish to apply for. Each role has different responsibilities and requirements.
              </p>
            </div>

            <!-- Credentials Section -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-medium">Professional Credentials</h3>
                  <p class="text-sm text-muted-foreground">
                    Add your professional credentials and certifications
                  </p>
                </div>
                <Button type="button" variant="outline" @click="addCredential">
                  Add Credential
                </Button>
              </div>

              <div v-if="form.errors.credentials" class="text-sm text-red-500">
                {{ form.errors.credentials }}
              </div>

              <!-- Credentials List -->
              <div v-for="(credential, index) in credentials" :key="index" class="border rounded-lg p-4 space-y-4">
                <div class="flex justify-between items-start">
                  <h4 class="text-sm font-medium">Credential {{ index + 1 }}</h4>
                  <Button type="button" variant="ghost" size="sm" @click="removeCredential(index)">
                    <XMarkIcon class="h-4 w-4" />
                  </Button>
                </div>

                <!-- Credential Type -->
                <div class="space-y-2">
                  <Label :for="'credential-type-' + index">Credential Type*</Label>
                  <Input
                    :id="'credential-type-' + index"
                    v-model="credential.type"
                    placeholder="e.g., Medical License, Board Certification"
                  />
                  <InputError :message="form.errors[`credentials.${index}.type`]" />
                </div>

                <!-- Credential Number -->
                <div class="space-y-2">
                  <Label :for="'credential-number-' + index">License/Certificate Number*</Label>
                  <Input
                    :id="'credential-number-' + index"
                    v-model="credential.number"
                    placeholder="Enter your credential number"
                  />
                  <InputError :message="form.errors[`credentials.${index}.number`]" />
                </div>

                <!-- Issuing Authority -->
                <div class="space-y-2">
                  <Label :for="'credential-issuer-' + index">Issuing Authority*</Label>
                  <Input
                    :id="'credential-issuer-' + index"
                    v-model="credential.issuer"
                    placeholder="Organization that issued the credential"
                  />
                  <InputError :message="form.errors[`credentials.${index}.issuer`]" />
                </div>

                <!-- Issue Date -->
                <div class="space-y-2">
                  <Label :for="'credential-issue-date-' + index">Issue Date*</Label>
                  <Input
                    :id="'credential-issue-date-' + index"
                    type="date"
                    v-model="credential.issue_date"
                  />
                  <InputError :message="form.errors[`credentials.${index}.issue_date`]" />
                </div>

                <!-- Expiry Date -->
                <div class="space-y-2">
                  <Label :for="'credential-expiry-date-' + index">Expiry Date (Optional)</Label>
                  <Input
                    :id="'credential-expiry-date-' + index"
                    type="date"
                    v-model="credential.expiry_date"
                  />
                  <InputError :message="form.errors[`credentials.${index}.expiry_date`]" />
                </div>

                <!-- Document Upload -->
                <div class="space-y-2">
                  <Label :for="'credential-document-' + index">Upload Document*</Label>
                  <Input
                    :id="'credential-document-' + index"
                    type="file"
                    @change="(e: Event) => handleFileUpload(e, index)"
                    accept=".pdf,.jpg,.jpeg,.png"
                  />
                  <p class="text-sm text-muted-foreground">
                    Upload a scanned copy of your credential (PDF, JPG, PNG, max 10MB)
                  </p>
                  <InputError :message="form.errors[`credentials.${index}.document`]" />
                </div>

                <!-- Additional Information -->
                <div class="space-y-2">
                  <Label :for="'credential-info-' + index">Additional Information (Optional)</Label>
                  <Textarea
                    :id="'credential-info-' + index"
                    v-model="credential.additional_info"
                    placeholder="Any additional details about this credential"
                  />
                  <InputError :message="form.errors[`credentials.${index}.additional_info`]" />
                </div>
              </div>

              <div v-if="credentials.length === 0" class="text-center py-8 border rounded-lg">
                <DocumentIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                <h3 class="mt-2 text-sm font-medium">No credentials added</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  Add your professional credentials to support your application
                </p>
                <Button type="button" variant="outline" class="mt-4" @click="addCredential">
                  Add Credential
                </Button>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <Button type="submit" :disabled="form.processing || !form.role">
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                Submit Application
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
 </template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

import { Textarea } from '@/components/ui/textarea'
import {
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbPage,
} from '@/components/ui/breadcrumb'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { DocumentIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { LoaderCircle } from 'lucide-vue-next'

interface Props {
  available_roles: {
    value: string
    label: string
  }[]
  pending_application?: {
    is_pending: boolean
    requested_role: string | null
    requested_role_label: string | null
  }
  application_status?: string
}

defineProps<Props>()

interface Credential {
  type: string
  number: string
  issuer: string
  issue_date: string
  expiry_date?: string
  document?: File
  additional_info?: string
}

const credentials = ref<Credential[]>([])

const form = useForm<{ role: string } & Record<string, any>>({
  role: ''
})

const addCredential = () => {
  credentials.value.push({
    type: '',
    number: '',
    issuer: '',
    issue_date: '',
  })
}

const removeCredential = (index: number) => {
  credentials.value.splice(index, 1)
}

const handleFileUpload = (event: Event, index: number) => {
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    credentials.value[index].document = input.files[0]
  }
}

const submit = () => {
  form
    .transform(() => {
      const formData = new FormData()
      formData.append('role', form.role)

      credentials.value.forEach((cred, index) => {
        formData.append(`credentials[${index}][type]`, cred.type)
        formData.append(`credentials[${index}][number]`, cred.number)
        formData.append(`credentials[${index}][issuer]`, cred.issuer)
        formData.append(`credentials[${index}][issue_date]`, cred.issue_date)
        if (cred.expiry_date) formData.append(`credentials[${index}][expiry_date]`, cred.expiry_date)
        if (cred.document) formData.append(`credentials[${index}][document]`, cred.document)
        if (cred.additional_info) formData.append(`credentials[${index}][additional_info]`, cred.additional_info)
      })

      return formData
    })
    .post(route('professional-application.store'), {
      preserveScroll: true,
    })
}
</script>