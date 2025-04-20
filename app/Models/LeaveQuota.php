<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveQuota extends Model
{
    use HasFactory;

    protected $primaryKey = 'quota_id';
    public $incrementing = true;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_quota',
        'used_quota',
        'remaining_quota',
        'prorated_quota',
        'reset_date'
    ];

    protected $casts = [
        'reset_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
