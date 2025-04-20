<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory;

    protected $primaryKey = 'payslip_id';
    public $incrementing = true;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'pdf_url'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payslipComponents(): HasMany
    {
        return $this->hasMany(PayslipComponent::class, 'payslip_id');
    }
}
