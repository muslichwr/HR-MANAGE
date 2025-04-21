<?php

namespace App\Observers;

use App\Models\Department;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class DepartmentObserver
{
    /**
     * Handle the Department "created" event.
     */

     private function logAction(Department $department, string $action, ?array $oldData = null)
    {
        $user = Auth::user() ?? filament()->auth()->user();

        $newData = $action === 'delete' 
        ? null 
        : collect($department->getAttributes())
            ->except(['created_at', 'updated_at'])
            ->toArray();

    AuditLog::create([
        'user_id' => $user?->id,
        'action' => $action,
        'table_name' => 'departments',
        'record_id' => $department->department_id,
        'old_value' => $oldData ? json_encode($oldData, JSON_PRETTY_PRINT) : null,
        'new_value' => $newData ? json_encode($newData, JSON_PRETTY_PRINT) : null,
    ]);
    }

    public function created(Department $department): void
    {
        $this->logAction($department, 'create');
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        $changes = $department->getDirty();
        $original = $department->getOriginal();
        
        $oldData = array_intersect_key($original, $changes);
        $newData = array_intersect_key($department->getAttributes(), $changes);
        
        // Exclude timestamps
        unset($oldData['created_at'], $oldData['updated_at']);
        unset($newData['created_at'], $newData['updated_at']);

        if(!empty($oldData) || !empty($newData)) {
            $this->logAction($department, 'update', $oldData);
        }
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        $this->logAction($department, 'delete', $department->getOriginal());
    }

    /**
     * Handle the Department "restored" event.
     */
    public function restored(Department $department): void
    {
        //
    }

    /**
     * Handle the Department "force deleted" event.
     */
    public function forceDeleted(Department $department): void
    {
        //
    }
}
