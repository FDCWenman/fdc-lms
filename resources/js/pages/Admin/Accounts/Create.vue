<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
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
import { ArrowLeft, AlertCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Role {
  id: number;
  name: string;
}

interface Props {
  roles: Role[];
  errors?: Record<string, string>;
}

const props = defineProps<Props>();

const form = useForm({
  name: '',
  email: '',
  slack_id: '',
  hired_date: '',
  primary_role_id: '',
  secondary_role_id: '',
});

const submit = () => {
  form.post(route('admin.accounts.store'), {
    preserveScroll: true,
  });
};

const hasErrors = computed(() => Object.keys(props.errors || {}).length > 0);
</script>

<template>
  <div class="container mx-auto py-8 max-w-2xl">
    <Head title="Create Employee Account" />

    <!-- Header -->
    <div class="mb-8">
      <Link :href="route('admin.accounts.index')" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back to Accounts
      </Link>
      <h1 class="text-3xl font-bold tracking-tight">Create Employee Account</h1>
      <p class="text-muted-foreground mt-2">
        Add a new employee to the leave management system
      </p>
    </div>

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

    <!-- Form Card -->
    <Card>
      <CardHeader>
        <CardTitle>Employee Information</CardTitle>
        <CardDescription>
          Enter the employee's details. A verification email will be sent after account creation.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Full Name -->
          <div class="space-y-2">
            <Label for="name">Full Name <span class="text-destructive">*</span></Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="John Doe"
              required
              autofocus
              :disabled="form.processing"
              :class="{ 'border-destructive': errors?.name }"
            />
            <p v-if="errors?.name" class="text-sm text-destructive">{{ errors.name }}</p>
          </div>

          <!-- Email Address -->
          <div class="space-y-2">
            <Label for="email">Email Address <span class="text-destructive">*</span></Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="john.doe@example.com"
              required
              autocomplete="email"
              :disabled="form.processing"
              :class="{ 'border-destructive': errors?.email }"
            />
            <p v-if="errors?.email" class="text-sm text-destructive">{{ errors.email }}</p>
            <p class="text-xs text-muted-foreground">Must be a valid company email address</p>
          </div>

          <!-- Slack ID -->
          <div class="space-y-2">
            <Label for="slack_id">Slack User ID <span class="text-destructive">*</span></Label>
            <Input
              id="slack_id"
              v-model="form.slack_id"
              type="text"
              placeholder="U01234ABCDE"
              required
              :disabled="form.processing"
              :class="{ 'border-destructive': errors?.slack_id }"
            />
            <p v-if="errors?.slack_id" class="text-sm text-destructive">{{ errors.slack_id }}</p>
            <p class="text-xs text-muted-foreground">
              Format: U + 10 alphanumeric characters (e.g., U01234ABCDE). Used for password resets and notifications.
            </p>
          </div>

          <!-- Hired Date -->
          <div class="space-y-2">
            <Label for="hired_date">Hired Date <span class="text-destructive">*</span></Label>
            <Input
              id="hired_date"
              v-model="form.hired_date"
              type="date"
              required
              :disabled="form.processing"
              :class="{ 'border-destructive': errors?.hired_date }"
            />
            <p v-if="errors?.hired_date" class="text-sm text-destructive">{{ errors.hired_date }}</p>
          </div>

          <!-- Primary Role -->
          <div class="space-y-2">
            <Label for="primary_role_id">Primary Role <span class="text-destructive">*</span></Label>
            <Select v-model="form.primary_role_id" required>
              <SelectTrigger :disabled="form.processing">
                <SelectValue placeholder="Select primary role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="role in roles" :key="role.id" :value="role.id.toString()">
                  {{ role.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors?.primary_role_id" class="text-sm text-destructive">{{ errors.primary_role_id }}</p>
            <p class="text-xs text-muted-foreground">The employee's main role in the organization</p>
          </div>

          <!-- Secondary Role -->
          <div class="space-y-2">
            <Label for="secondary_role_id">Secondary Role (Optional)</Label>
            <Select v-model="form.secondary_role_id">
              <SelectTrigger :disabled="form.processing">
                <SelectValue placeholder="Select secondary role (optional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">None</SelectItem>
                <SelectItem v-for="role in roles" :key="role.id" :value="role.id.toString()">
                  {{ role.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="errors?.secondary_role_id" class="text-sm text-destructive">{{ errors.secondary_role_id }}</p>
            <p class="text-xs text-muted-foreground">
              Optional additional role for users with multiple responsibilities
            </p>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Link :href="route('admin.accounts.index')">
              <Button type="button" variant="outline" :disabled="form.processing">
                Cancel
              </Button>
            </Link>
            <Button type="submit" :disabled="form.processing">
              <span v-if="form.processing">Creating Account...</span>
              <span v-else>Create Account</span>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>

    <!-- Info Box -->
    <Alert class="mt-6">
      <AlertDescription>
        <p class="font-semibold mb-2">What happens next:</p>
        <ol class="list-decimal list-inside space-y-1 text-sm">
          <li>Account will be created with "For Verification" status</li>
          <li>Verification email will be sent to the employee</li>
          <li>Employee will be added to the Slack leave channel</li>
          <li>Employee can log in after verifying their email</li>
        </ol>
      </AlertDescription>
    </Alert>
  </div>
</template>
