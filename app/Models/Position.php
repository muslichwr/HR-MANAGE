<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Position extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'position_id';
    public $incrementing = true;
    protected $casts = ['level' => 'string'];
    protected $fillable = ['title', 'department_id', 'level'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'department_id', 'level'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Jabatan {$eventName}")
            ->dontSubmitEmptyLogs()
            ->useLogName('position');
    }
    
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
