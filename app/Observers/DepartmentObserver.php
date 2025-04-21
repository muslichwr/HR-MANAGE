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

    private function logAction(Department $department, string $action, ?array $oldData = null, ?array $newData = null)
    {
         $user = Auth::user();
         
         AuditLog::create([
             'user_id' => $user ? $user->id : null,
             'action' => $action,
             'table_name' => 'departments',
             'record_id' => $department->department_id,
             'old_value' => $oldData ?: null,
             'new_value' => $newData ?: null
         ]);
    }

    public function created(Department $department)
    {
        $this->logAction($department, 'create', null, $department->toArray());
    }

    public function updated(Department $department)
    {
        $changes = $department->getChanges();
        $original = $department->getOriginal();
        
        $oldData = array_intersect_key($original, $changes);
        $newData = $changes;
        
        $this->logAction($department, 'update', $oldData, $newData);
    }

    public function deleted(Department $department)
    {
        $this->logAction($department, 'delete', $department->toArray(), null);
    }

    public function restored(Department $department)
    {
        $this->logAction($department, 'restore', null, $department->toArray());
    }

    public function forceDeleted(Department $department)
    {
        $this->logAction($department, 'force_delete', $department->toArray(), null);
    }
}
