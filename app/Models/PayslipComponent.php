<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayslipComponent extends Model
{
    use HasFactory;

    protected $primaryKey = 'payslip_component_id';
    public $incrementing = true;

    protected $fillable = [
        'payslip_id',
        'component_id',
        'amount'
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
