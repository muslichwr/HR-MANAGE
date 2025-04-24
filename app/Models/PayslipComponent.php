<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PayslipComponent extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'payslip_component_id';
    public $incrementing = true;

    protected $fillable = [
        'payslip_id',
        'component_id',
        'amount'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payslip_id', 'component_id', 'amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'komponen slip gaji ditambahkan',
                    'updated' => 'komponen slip gaji diubah',
                    'deleted' => 'komponen slip gaji dihapus',
                    default => $eventName
                };
            })
            ->useLogName('payslip_component');
    }

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
