<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ComponentType extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'type_id';
    public $incrementing = true;

    protected $fillable = ['name'];

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'type_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Tipe komponen telah {$eventName}")
            ->useLogName('component_type');
    }
}
