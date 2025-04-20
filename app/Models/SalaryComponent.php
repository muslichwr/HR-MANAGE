<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $primaryKey = 'component_id';
    public $incrementing = true;

    protected $fillable = ['type_id', 'name', 'description'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'type_id');
    }

    public function payslipComponents(): HasMany
    {
        return $this->hasMany(PayslipComponent::class, 'component_id');
    }
}
