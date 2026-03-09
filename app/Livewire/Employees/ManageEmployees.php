<?php

namespace App\Livewire\Employees;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ManageEmployees extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public ?int $statusFilter = null;

    public bool $showStatusModal = false;

    public ?int $selectedUserId = null;

    public string $statusChangeReason = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function getEmployeesProperty()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('middle_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== null, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    public function openStatusModal(int $userId): void
    {
        // Prevent self-deactivation
        if ($userId === auth()->id()) {
            return;
        }

        $this->selectedUserId = $userId;
        $this->statusChangeReason = '';
        $this->showStatusModal = true;
    }

    public function confirmStatusChange(): void
    {
        $this->authorize('manage-employees');

        if (! $this->selectedUserId) {
            return;
        }

        $selectedUser = User::findOrFail($this->selectedUserId);
        $oldStatus = $selectedUser->status;
        $newStatus = $oldStatus === 1 ? 2 : 1;

        $selectedUser->update(['status' => $newStatus]);

        // Log the status change
        activity()
            ->performedOn($selectedUser)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $this->statusChangeReason ?: null,
            ])
            ->log('status_changed');

        session()->flash('message', 'Employee status updated successfully.');

        $this->showStatusModal = false;
        $this->selectedUserId = null;
        $this->statusChangeReason = '';
    }

    public function getSelectedUserProperty(): ?User
    {
        return $this->selectedUserId ? User::find($this->selectedUserId) : null;
    }

    public function render()
    {
        $this->authorize('view-employees');

        return view('livewire.employees.manage-employees');
    }
}
