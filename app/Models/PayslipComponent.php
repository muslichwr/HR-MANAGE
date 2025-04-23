<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

    /**
     * Perintah yang dijalankan saat model sedang diboot
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            static::validateComponentIntegrity($model);
        });
        
        static::updating(function ($model) {
            static::validateComponentIntegrity($model);
        });
    }
    
    /**
     * Validasi integritas komponen
     */
    protected static function validateComponentIntegrity($model): void
    {
        // Pastikan amount selalu positif
        if ($model->amount < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nilai komponen gaji harus positif.'
            ]);
        }
        
        // Pastikan component_id valid
        $component = SalaryComponent::with('componentType')->find($model->component_id);
        if (!$component) {
            throw ValidationException::withMessages([
                'component_id' => 'Komponen gaji tidak valid.'
            ]);
        }
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'payslip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }
}
