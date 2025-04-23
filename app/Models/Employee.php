<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'employee_id';
    public $incrementing = true;

    protected $fillable = [
        'nik', 
        'full_name', 
        'address', 
        'position_id', 
        'department_id', 
        'join_date', 
        'status'
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nik', 'full_name', 'position_id', 'department_id', 'join_date', 'status', 'address'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Karyawan {$eventName}")
            ->dontSubmitEmptyLogs()
            ->useLogName('employee');
    }
    
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employeeHistories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function leaveQuotas(): HasMany
    {
        return $this->hasMany(LeaveQuota::class, 'employee_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
