<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { AlertCircle, User as UserIcon, Users, Key, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface User {
  id: number;
  name: string;
  email: string;
  slack_id?: string;
  hired_date?: string;
  primary_role?: { name: string };
  secondary_role?: { name: string };
  default_approvers?: {
    hr_approver_id?: number;
    lead_approver_id?: number;
    pm_approver_id?: number;
  };
}

interface Approver {
  id: number;
  name: string;
}

interface Props {
  user: User;
  hrApprovers: Approver[];
  leadApprovers: Approver[];
  pmApprovers: Approver[];
  status?: string;
  errors?: Record<string, string>;
}

const props = defineProps<Props>();

// Default Approvers Form
const approversForm = useForm({
  hr_approver_id: props.user.default_approvers?.hr_approver_id?.toString() || '',
  lead_approver_id: props.user.default_approvers?.lead_approver_id?.toString() || '',
  pm_approver_id: props.user.default_approvers?.pm_approver_id?.toString() || '',
});

const submitApprovers = () => {
  approversForm.post(route('profile.approvers'), {
    preserveScroll: true,
  });
};

// Change Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const submitPassword = () => {
  passwordForm.post(route('profile.password'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
};

// Refresh Slack Name
const isRefreshing = ref(false);
const refreshSlackName = () => {
  isRefreshing.value = true;
  fetch(route('profile.slack'), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  }).finally(() => {
    isRefreshing.value = false;
    window.location.reload();
  });
};

const hasErrors = computed(() => Object.keys(props.errors || {}).length > 0);
</script>

<template>
  <div class="container mx-auto py-8 max-w-4xl">
    <Head title="Edit Profile" />

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold tracking-tight">Profile Settings</h1>
      <p class="text-muted-foreground mt-2">
        Manage your account settings and default approvers
      </p>
    </div>

    <!-- Success Message -->
    <Alert v-if="status" class="mb-6 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950">
      <AlertDescription class="text-green-800 dark:text-green-200">
        {{ status }}
      </AlertDescription>
    </Alert>

    <!-- Error Summary -->
    <Alert v-if="hasErrors" variant="destructive" class="mb-6">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>
        <p class="font-semibold mb-2">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-1">
          <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
        </ul>
      </AlertDescription>
    </Alert>

    <Tabs default-value="info" class="space-y-6">
      <TabsList class="grid w-full grid-cols-3">
        <TabsTrigger value="info">
          <UserIcon class="mr-2 h-4 w-4" />
          Information
        </TabsTrigger>
        <TabsTrigger value="approvers">
          <Users class="mr-2 h-4 w-4" />
          Default Approvers
        </TabsTrigger>
        <TabsTrigger value="security">
          <Key class="mr-2 h-4 w-4" />
          Security
        </TabsTrigger>
      </TabsList>

      <!-- Account Information Tab -->
      <TabsContent value="info">
        <Card>
          <CardHeader>
            <CardTitle>Account Information</CardTitle>
            <CardDescription>
              View your account details. Contact HR to update this information.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label>Full Name</Label>
                <p class="text-sm text-muted-foreground mt-1">{{ user.name }}</p>
              </div>
              
              <div>
                <Label>Email Address</Label>
                <p class="text-sm text-muted-foreground mt-1">{{ user.email }}</p>
              </div>
              
              <div>
                <Label>Slack ID</Label>
                <div class="flex items-center gap-2 mt-1">
                  <code v-if="user.slack_id" class="text-xs bg-muted px-2 py-1 rounded">
                    {{ user.slack_id }}
                  </code>
                  <p v-else class="text-sm text-muted-foreground">Not set</p>
                  <Button 
                    @click="refreshSlackName" 
                    size="sm" 
                    variant="ghost"
                    :disabled="isRefreshing"
                  >
                    <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': isRefreshing }" />
                  </Button>
                </div>
                <p class="text-xs text-muted-foreground mt-1">
                  Sync your display name from Slack
                </p>
              </div>
              
              <div>
                <Label>Hired Date</Label>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ user.hired_date || 'Not set' }}
                </p>
              </div>
              
              <div>
                <Label>Primary Role</Label>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ user.primary_role?.name || 'Not assigned' }}
                </p>
              </div>
              
              <div v-if="user.secondary_role">
                <Label>Secondary Role</Label>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ user.secondary_role.name }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Default Approvers Tab -->
      <TabsContent value="approvers">
        <Card>
          <CardHeader>
            <CardTitle>Default Approvers</CardTitle>
            <CardDescription>
              Set your default approvers for leave requests. You can override these when submitting a request.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submitApprovers" class="space-y-6">
              <!-- HR Approver -->
              <div class="space-y-2">
                <Label for="hr_approver_id">HR Approver</Label>
                <Select v-model="approversForm.hr_approver_id">
                  <SelectTrigger :disabled="approversForm.processing">
                    <SelectValue placeholder="Select HR approver (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="approver in hrApprovers" :key="approver.id" :value="approver.id.toString()">
                      {{ approver.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                  HR staff member who handles administrative leave approvals
                </p>
              </div>

              <!-- Team Lead Approver -->
              <div class="space-y-2">
                <Label for="lead_approver_id">Team Lead Approver</Label>
                <Select v-model="approversForm.lead_approver_id">
                  <SelectTrigger :disabled="approversForm.processing">
                    <SelectValue placeholder="Select team lead (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="approver in leadApprovers" :key="approver.id" :value="approver.id.toString()">
                      {{ approver.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                  Your direct team lead for operational approvals
                </p>
              </div>

              <!-- PM Approver -->
              <div class="space-y-2">
                <Label for="pm_approver_id">Project Manager Approver</Label>
                <Select v-model="approversForm.pm_approver_id">
                  <SelectTrigger :disabled="approversForm.processing">
                    <SelectValue placeholder="Select project manager (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None</SelectItem>
                    <SelectItem v-for="approver in pmApprovers" :key="approver.id" :value="approver.id.toString()">
                      {{ approver.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p class="text-xs text-muted-foreground">
                  Project manager for project-specific leave coordination
                </p>
              </div>

              <Separator />

              <div class="flex justify-end">
                <Button type="submit" :disabled="approversForm.processing">
                  <span v-if="approversForm.processing">Saving...</span>
                  <span v-else>Save Approvers</span>
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Security Tab -->
      <TabsContent value="security">
        <Card>
          <CardHeader>
            <CardTitle>Change Password</CardTitle>
            <CardDescription>
              Update your password. Your password must meet complexity requirements.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submitPassword" class="space-y-6">
              <!-- Current Password -->
              <div class="space-y-2">
                <Label for="current_password">Current Password <span class="text-destructive">*</span></Label>
                <Input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  autocomplete="current-password"
                  :disabled="passwordForm.processing"
                />
              </div>

              <!-- New Password -->
              <div class="space-y-2">
                <Label for="password">New Password <span class="text-destructive">*</span></Label>
                <Input
                  id="password"
                  v-model="passwordForm.password"
                  type="password"
                  required
                  autocomplete="new-password"
                  :disabled="passwordForm.processing"
                />
                <p class="text-xs text-muted-foreground">
                  Must be at least 8 characters with uppercase, lowercase, numbers, and symbols
                </p>
              </div>

              <!-- Confirm Password -->
              <div class="space-y-2">
                <Label for="password_confirmation">Confirm New Password <span class="text-destructive">*</span></Label>
                <Input
                  id="password_confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  required
                  autocomplete="new-password"
                  :disabled="passwordForm.processing"
                />
              </div>

              <Separator />

              <div class="flex justify-end">
                <Button type="submit" :disabled="passwordForm.processing">
                  <span v-if="passwordForm.processing">Changing Password...</span>
                  <span v-else>Change Password</span>
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>
  </div>
</template>
