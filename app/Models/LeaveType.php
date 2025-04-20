<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $primaryKey = 'leave_type_id';
    public $incrementing = true;

    protected $fillable = ['name', 'description', 'max_days'];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }

    public function leaveQuotas(): HasMany
    {
        return $this->hasMany(LeaveQuota::class, 'leave_type_id');
    }
}
