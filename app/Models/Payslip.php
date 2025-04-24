<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payslip extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['employee_id', 'month', 'year', 'total_earnings', 'total_deductions', 'net_salary', 'pdf_url'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'slip gaji baru dibuat',
                    'updated' => 'slip gaji diperbarui',
                    'deleted' => 'slip gaji dihapus',
                    default => $eventName
                };
            })
            ->useLogName('payslip');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            static::validateUniqueConstraint($model);
            
            // Pastikan net_salary dihitung dengan benar
            if ($model->total_earnings !== null && $model->total_deductions !== null) {
                $model->net_salary = $model->total_earnings - $model->total_deductions;
            }
        });
        
        static::updating(function ($model) {
            static::validateUniqueConstraint($model);
            
            // Pastikan net_salary dihitung dengan benar jika total pendapatan atau potongan berubah
            if ($model->isDirty('total_earnings') || $model->isDirty('total_deductions')) {
                $model->net_salary = $model->total_earnings - $model->total_deductions;
            }
        });
        
        static::updated(function ($model) {
            // Pastikan totals berdasarkan komponen selalu akurat setelah update
            static::recalculateFromComponents($model);
        });
    }
    
    /**
     * Memastikan kombinasi employee_id + month + year unik
     */
    protected static function validateUniqueConstraint($model): void
    {
        $query = static::where('employee_id', $model->employee_id)
            ->where('month', $model->month)
            ->where('year', $model->year);
        
        if ($model->exists) {
            $query->where('payslip_id', '!=', $model->payslip_id);
        }
        
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Payslip untuk karyawan ini pada periode yang sama sudah ada.'
            ]);
        }
    }
    
    /**
     * Menghitung ulang total berdasarkan komponen-komponen
     */
    public static function recalculateFromComponents($model): void
    {
        $model->loadMissing(['payslipComponents', 'payslipComponents.component', 'payslipComponents.component.componentType']);
        
        $components = $model->payslipComponents;
        if ($components->isEmpty()) {
            return;
        }
        
        $totalEarnings = $components
            ->filter(function ($item) {
                return $item->component && 
                    $item->component->componentType && 
                    $item->component->componentType->name === 'Pendapatan';
            })
            ->sum('amount');
            
        $totalDeductions = $components
            ->filter(function ($item) {
                return $item->component && 
                    $item->component->componentType && 
                    $item->component->componentType->name === 'Potongan';
            })
            ->sum('amount');
            
        $netSalary = $totalEarnings - $totalDeductions;
        
        // Update hanya jika ada perubahan
        if ($model->total_earnings != $totalEarnings || 
            $model->total_deductions != $totalDeductions ||
            $model->net_salary != $netSalary) {
            
            $model->update([
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
            ]);
        }
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payslipComponents(): HasMany
    {
        return $this->hasMany(PayslipComponent::class, 'payslip_id');
    }
}
