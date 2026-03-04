<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Edit, CheckCircle, XCircle, User, Mail, Calendar, Slack } from 'lucide-vue-next';
import { ref } from 'vue';
import { format } from 'date-fns';

interface Role {
  id: number;
  name: string;
}

interface AuditLog {
  id: number;
  action: string;
  reason?: string;
  ip_address: string;
  performed_by_name: string;
  created_at: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  slack_id?: string;
  status: number;
  status_label: string;
  verified_at?: string;
  hired_date?: string;
  created_at: string;
  primary_role?: Role;
  secondary_role?: Role;
}

interface Props {
  user: User;
  auditLogs: AuditLog[];
  canActivate: boolean;
  canDeactivate: boolean;
}

const props = defineProps<Props>();

const deactivationReason = ref('');

const activateAccount = () => {
  router.post(route('admin.accounts.activate', props.user.id));
};

const deactivateAccount = () => {
  if (!deactivationReason.value.trim()) {
    alert('Please provide a reason for deactivation');
    return;
  }
  
  router.post(route('admin.accounts.deactivate', props.user.id), {
    reason: deactivationReason.value,
  });
};

const getStatusVariant = (status: number): 'default' | 'secondary' | 'destructive' => {
  switch (status) {
    case 1: return 'default'; // active
    case 2: return 'secondary'; // for_verification
    case 0: return 'destructive'; // deactivated
    default: return 'secondary';
  }
};

const formatDate = (dateString?: string) => {
  if (!dateString) return '—';
  return format(new Date(dateString), 'PPP');
};

const formatDateTime = (dateString: string) => {
  return format(new Date(dateString), 'PPP p');
};
</script>

<template>
  <div class="container mx-auto py-8 max-w-5xl">
    <Head :title="`Account: ${user.name}`" />

    <!-- Header -->
    <div class="mb-8">
      <Link :href="route('admin.accounts.index')" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4">
        <ArrowLeft class="mr-2 h-4 w-4" />
        Back to Accounts
      </Link>
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">{{ user.name }}</h1>
          <p class="text-muted-foreground mt-2">Employee account details and activity log</p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('admin.accounts.edit', user.id)">
            <Button variant="outline">
              <Edit class="mr-2 h-4 w-4" />
              Edit Account
            </Button>
          </Link>
          
          <!-- Activate Button -->
          <Button
            v-if="canActivate && user.status === 0"
            @click="activateAccount"
            variant="default"
          >
            <CheckCircle class="mr-2 h-4 w-4" />
            Activate
          </Button>
          
          <!-- Deactivate Button -->
          <AlertDialog v-if="canDeactivate && user.status === 1">
            <AlertDialogTrigger as-child>
              <Button variant="destructive">
                <XCircle class="mr-2 h-4 w-4" />
                Deactivate
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Deactivate Account</AlertDialogTitle>
                <AlertDialogDescription>
                  This will prevent {{ user.name }} from accessing the system and invalidate all active sessions.
                  Please provide a reason for this action.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <div class="space-y-2 py-4">
                <Label for="reason">Reason for Deactivation</Label>
                <Textarea
                  id="reason"
                  v-model="deactivationReason"
                  placeholder="Enter reason (10-500 characters)..."
                  rows="4"
                  minlength="10"
                  maxlength="500"
                />
                <p class="text-xs text-muted-foreground">
                  {{ deactivationReason.length }}/500 characters
                </p>
              </div>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="deactivateAccount" class="bg-destructive hover:bg-destructive/90">
                  Deactivate Account
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      </div>
    </div>

    <!-- Account Information Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <Card>
        <CardHeader>
          <CardTitle>Account Information</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-start gap-3">
            <User class="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p class="text-sm font-medium">Full Name</p>
              <p class="text-sm text-muted-foreground">{{ user.name }}</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <Mail class="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p class="text-sm font-medium">Email Address</p>
              <p class="text-sm text-muted-foreground">{{ user.email }}</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <Slack class="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p class="text-sm font-medium">Slack ID</p>
              <code v-if="user.slack_id" class="text-xs bg-muted px-2 py-1 rounded">
                {{ user.slack_id }}
              </code>
              <p v-else class="text-sm text-muted-foreground">Not set</p>
            </div>
          </div>
          
          <div class="flex items-start gap-3">
            <Calendar class="h-5 w-5 text-muted-foreground mt-0.5" />
            <div>
              <p class="text-sm font-medium">Hired Date</p>
              <p class="text-sm text-muted-foreground">{{ formatDate(user.hired_date) }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status & Roles</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div>
            <p class="text-sm font-medium mb-2">Account Status</p>
            <Badge :variant="getStatusVariant(user.status)">
              {{ user.status_label }}
            </Badge>
          </div>
          
          <div>
            <p class="text-sm font-medium mb-2">Email Verification</p>
            <div class="flex items-center gap-2">
              <CheckCircle v-if="user.verified_at" class="h-4 w-4 text-green-600" />
              <XCircle v-else class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm text-muted-foreground">
                {{ user.verified_at ? `Verified on ${formatDate(user.verified_at)}` : 'Not verified' }}
              </span>
            </div>
          </div>
          
          <div>
            <p class="text-sm font-medium mb-2">Primary Role</p>
            <Badge v-if="user.primary_role" variant="outline">
              {{ user.primary_role.name }}
            </Badge>
            <p v-else class="text-sm text-muted-foreground">Not assigned</p>
          </div>
          
          <div>
            <p class="text-sm font-medium mb-2">Secondary Role</p>
            <Badge v-if="user.secondary_role" variant="secondary">
              {{ user.secondary_role.name }}
            </Badge>
            <p v-else class="text-sm text-muted-foreground">None</p>
          </div>
          
          <div>
            <p class="text-sm font-medium mb-2">Account Created</p>
            <p class="text-sm text-muted-foreground">{{ formatDate(user.created_at) }}</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Audit Log Card -->
    <Card>
      <CardHeader>
        <CardTitle>Activity Log</CardTitle>
        <CardDescription>
          Complete history of all actions performed on this account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Date & Time</TableHead>
              <TableHead>Action</TableHead>
              <TableHead>Performed By</TableHead>
              <TableHead>IP Address</TableHead>
              <TableHead>Reason</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="log in auditLogs" :key="log.id">
              <TableCell class="whitespace-nowrap">
                {{ formatDateTime(log.created_at) }}
              </TableCell>
              <TableCell>
                <Badge variant="outline">{{ log.action }}</Badge>
              </TableCell>
              <TableCell>{{ log.performed_by_name }}</TableCell>
              <TableCell>
                <code class="text-xs bg-muted px-2 py-1 rounded">{{ log.ip_address }}</code>
              </TableCell>
              <TableCell>
                <span v-if="log.reason" class="text-sm">{{ log.reason }}</span>
                <span v-else class="text-sm text-muted-foreground">—</span>
              </TableCell>
            </TableRow>

            <TableRow v-if="auditLogs.length === 0">
              <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                No activity recorded yet
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>

;
