<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Eye, Edit, CheckCircle, XCircle } from 'lucide-vue-next';

interface User {
  id: number;
  name: string;
  email: string;
  slack_id?: string;
  status: number;
  status_label: string;
  primary_role?: { name: string };
  secondary_role?: { name: string };
  verified_at?: string;
  hired_date?: string;
}

interface Props {
  users: {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: {
    search?: string;
    status?: string;
    role?: string;
  };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const roleFilter = ref(props.filters?.role || '');

const applyFilters = () => {
  router.get(route('admin.accounts.index'), {
    search: search.value,
    status: statusFilter.value,
    role: roleFilter.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  search.value = '';
  statusFilter.value = '';
  roleFilter.value = '';
  router.get(route('admin.accounts.index'));
};

const getStatusVariant = (status: number): 'default' | 'secondary' | 'destructive' | 'outline' => {
  switch (status) {
    case 1: return 'default'; // active (green)
    case 2: return 'secondary'; // for_verification (yellow)
    case 0: return 'destructive'; // deactivated (red)
    default: return 'outline';
  }
};

const getStatusBadge = (status: number, label: string) => {
  return {
    variant: getStatusVariant(status),
    label,
  };
};
</script>

<template>
  <div class="container mx-auto py-8">
    <Head title="Account Management" />

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Account Management</h1>
        <p class="text-muted-foreground mt-2">
          Manage employee accounts, roles, and permissions
        </p>
      </div>
      <Link :href="route('admin.accounts.create')">
        <Button>
          <Plus class="mr-2 h-4 w-4" />
          Create Account
        </Button>
      </Link>
    </div>

    <!-- Filters -->
    <div class="bg-card rounded-lg border p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <Label for="search" class="mb-2">Search</Label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              id="search"
              v-model="search"
              placeholder="Search by name, email, or Slack ID..."
              class="pl-10"
              @keyup.enter="applyFilters"
            />
          </div>
        </div>

        <!-- Status Filter -->
        <div>
          <Label for="status" class="mb-2">Status</Label>
          <Select v-model="statusFilter">
            <SelectTrigger>
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All Statuses</SelectItem>
              <SelectItem value="1">Active</SelectItem>
              <SelectItem value="2">For Verification</SelectItem>
              <SelectItem value="0">Deactivated</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Role Filter -->
        <div>
          <Label for="role" class="mb-2">Role</Label>
          <Select v-model="roleFilter">
            <SelectTrigger>
              <SelectValue placeholder="All Roles" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="">All Roles</SelectItem>
              <SelectItem value="1">Employee</SelectItem>
              <SelectItem value="2">HR</SelectItem>
              <SelectItem value="3">Team Lead</SelectItem>
              <SelectItem value="4">Project Manager</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="flex gap-2 mt-4">
        <Button @click="applyFilters" size="sm">
          Apply Filters
        </Button>
        <Button @click="resetFilters" variant="outline" size="sm">
          Reset
        </Button>
      </div>
    </div>

    <!-- Accounts Table -->
    <div class="bg-card rounded-lg border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Slack ID</TableHead>
            <TableHead>Primary Role</TableHead>
            <TableHead>Secondary Role</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Verified</TableHead>
            <TableHead class="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="user in users.data" :key="user.id">
            <TableCell class="font-medium">{{ user.name }}</TableCell>
            <TableCell>{{ user.email }}</TableCell>
            <TableCell>
              <code v-if="user.slack_id" class="text-xs bg-muted px-2 py-1 rounded">
                {{ user.slack_id }}
              </code>
              <span v-else class="text-muted-foreground text-sm">—</span>
            </TableCell>
            <TableCell>
              <Badge variant="outline" v-if="user.primary_role">
                {{ user.primary_role.name }}
              </Badge>
              <span v-else class="text-muted-foreground text-sm">—</span>
            </TableCell>
            <TableCell>
              <Badge variant="secondary" v-if="user.secondary_role">
                {{ user.secondary_role.name }}
              </Badge>
              <span v-else class="text-muted-foreground text-sm">—</span>
            </TableCell>
            <TableCell>
              <Badge :variant="getStatusBadge(user.status, user.status_label).variant">
                {{ user.status_label }}
              </Badge>
            </TableCell>
            <TableCell>
              <CheckCircle v-if="user.verified_at" class="h-4 w-4 text-green-600" />
              <XCircle v-else class="h-4 w-4 text-muted-foreground" />
            </TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Link :href="route('admin.accounts.show', user.id)">
                  <Button variant="ghost" size="sm">
                    <Eye class="h-4 w-4" />
                  </Button>
                </Link>
                <Link :href="route('admin.accounts.edit', user.id)">
                  <Button variant="ghost" size="sm">
                    <Edit class="h-4 w-4" />
                  </Button>
                </Link>
              </div>
            </TableCell>
          </TableRow>

          <TableRow v-if="users.data.length === 0">
            <TableCell colspan="8" class="text-center py-8 text-muted-foreground">
              No accounts found. Try adjusting your filters or create a new account.
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination -->
      <div v-if="users.last_page > 1" class="border-t p-4">
        <div class="flex items-center justify-between">
          <div class="text-sm text-muted-foreground">
            Showing {{ (users.current_page - 1) * users.per_page + 1 }} to 
            {{ Math.min(users.current_page * users.per_page, users.total) }} of 
            {{ users.total }} accounts
          </div>
          <div class="flex gap-2">
            <Button
              v-for="page in users.last_page"
              :key="page"
              :variant="page === users.current_page ? 'default' : 'outline'"
              size="sm"
              @click="router.get(route('admin.accounts.index', { page }))"
            >
              {{ page }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
