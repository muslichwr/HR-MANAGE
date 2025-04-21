<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'log_id';
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_value',
        'new_value'
    ];

    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'record_id', 'department_id');
    }

    // public function scopeForDepartment($query)
    // {
    //     return $query->where('table_name', 'departments');
    // }
}
