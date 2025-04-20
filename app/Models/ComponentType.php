<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComponentType extends Model
{
    use HasFactory;

    protected $primaryKey = 'type_id';
    public $incrementing = true;

    protected $fillable = ['name'];

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class, 'type_id');
    }
}
