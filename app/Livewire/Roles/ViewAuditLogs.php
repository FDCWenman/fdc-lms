<?php

namespace App\Livewire\Roles;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ViewAuditLogs extends Component
{
    use WithPagination;

    public $actionFilter = '';

    public $userFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public function updatedActionFilter()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->actionFilter = '';
        $this->userFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Activity::with(['causer', 'subject'])
            ->whereIn('subject_type', [
                'Spatie\Permission\Models\Role',
                'App\Models\User',
            ])
            ->latest();

        if ($this->actionFilter) {
            $query->where('description', $this->actionFilter);
        }

        if ($this->userFilter) {
            $query->where('causer_id', $this->userFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $activities = $query->paginate(50);

        // Get users who have caused activities (causers)
        $userIds = Activity::whereIn('subject_type', [
            'Spatie\Permission\Models\Role',
            'App\Models\User',
        ])
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $users = User::whereIn('id', $userIds)->orderBy('name')->get();

        $actionTypes = Activity::whereIn('subject_type', [
            'Spatie\Permission\Models\Role',
            'App\Models\User',
        ])
            ->select('description')
            ->distinct()
            ->pluck('description');

        return view('livewire.roles.view-audit-logs', [
            'activities' => $activities,
            'users' => $users,
            'actionTypes' => $actionTypes,
        ]);
    }
}
