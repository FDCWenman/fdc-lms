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
import { computed } from 'vue';

interface Role {
  id: number;
  name: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  slack_id?: string;
  hired_date?: string;
  primary_role_id?: number;
  secondary_role_id?: number;
}

interface Props {
  user: User;
  roles: Role[];
  errors?: Record<string, string>;
}

const props = defineProps<Props>();

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  slack_id: props.user.slack_id || '',
  hired_date: props.user.hired_date || '',
  primary_role_id: props.user.primary_role_id?.toString() || '',
  secondary_role_id: props.user.secondary_role_id?.toString() || '',
});

const submit = () => {
  form.put(route('admin.accounts.update', props.user.id), {
    preserveScroll: true,
  });
};

const hasErrors = computed(() => Object.keys(props.errors || {}).length > 0);
</script>

<template>
  <div class="container mx-auto py-8 max-w-2xl">
    <Head :title="`Edit: ${user.name}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link :href="route('admin.accounts.show', user.id)" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back to Account Details
      </Link>
      <h1 class="text-3xl font-bold tracking-tight">Edit Employee Account</h1>
      <p class="text-muted-foreground mt-2">
        Update employee information and role assignments
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
          Update the employee's details and role assignments
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
              required
              autocomplete="email"
              :disabled="form.processing"
              :class="{ 'border-destructive': errors?.email }"
            />
            <p v-if="errors?.email" class="text-sm text-destructive">{{ errors.email }}</p>
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
              Format: U + 10 alphanumeric characters
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
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Link :href="route('admin.accounts.show', user.id)">
              <Button type="button" variant="outline" :disabled="form.processing">
                Cancel
              </Button>
            </Link>
            <Button type="submit" :disabled="form.processing">
              <span v-if="form.processing">Saving Changes...</span>
              <span v-else>Save Changes</span>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
