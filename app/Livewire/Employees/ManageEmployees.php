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

    public function render()
    {
        $this->authorize('view-employees');

        return view('livewire.employees.manage-employees');
    }
}
