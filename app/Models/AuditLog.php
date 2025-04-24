<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

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

    protected $appends = [
        'subject_name',
        'subject_url',
        'related_entity'
    ];

    /**
     * Relasi ke pengguna yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mendapatkan entitas terkait berdasarkan table_name dan record_id
     */
    public function getRelatedEntityAttribute()
    {
        try {
            return match($this->table_name) {
                'departments' => $this->department,
                'positions' => $this->position,
                'employees' => $this->employee,
                'component_types' => $this->componentType,
                'salary_components' => $this->salaryComponent,
                'payslips' => $this->payslip,
                'payslip_components' => $this->payslipComponent,
                'users' => User::find($this->record_id),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Error getting related entity: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Relasi polymorphic untuk departemen
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'record_id', 'department_id')
            ->when($this->table_name === 'departments', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk jabatan
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'record_id', 'position_id')
            ->when($this->table_name === 'positions', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk karyawan
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'record_id', 'employee_id')
            ->when($this->table_name === 'employees', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk tipe komponen
     */
    public function componentType(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'record_id', 'type_id')
            ->when($this->table_name === 'component_types', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk komponen gaji
     */
    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'record_id', 'component_id')
            ->when($this->table_name === 'salary_components', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk slip gaji
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'record_id', 'payslip_id')
            ->when($this->table_name === 'payslips', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Relasi polymorphic untuk komponen slip gaji
     */
    public function payslipComponent(): BelongsTo
    {
        return $this->belongsTo(PayslipComponent::class, 'record_id', 'payslip_component_id')
            ->when($this->table_name === 'payslip_components', fn ($query) => $query, fn ($query) => $query->whereRaw('0=1'));
    }
    
    /**
     * Mendapatkan nama objek berdasarkan relasi
     */
    public function getSubjectNameAttribute(): ?string
    {
        try {
            $entity = $this->related_entity;
            
            if (!$entity) return null;
            
            return match($this->table_name) {
                'departments' => $entity->name,
                'positions' => $entity->title,
                'employees' => $entity->full_name,
                'component_types' => $entity->name,
                'salary_components' => $entity->name,
                'payslips' => $entity->employee?->full_name . ' (' . $this->getMonthName($entity->month) . ' ' . $entity->year . ')',
                'payslip_components' => $entity->component?->name,
                'users' => $entity->name,
                default => $entity->name ?? $entity->title ?? $entity->full_name ?? null,
            };
        } catch (\Throwable $e) {
            Log::error('Error getting subject name: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mendapatkan URL untuk melihat detail objek
     */
    public function getSubjectUrlAttribute(): ?string
    {
        try {
            if (!$this->record_id || !$this->table_name) return null;
            
            $baseUrl = config('app.url');
            $id = $this->record_id;
            
            return match($this->table_name) {
                'departments' => $baseUrl . "/admin/departments/{$id}",
                'positions' => $baseUrl . "/admin/positions/{$id}",
                'employees' => $baseUrl . "/admin/employees/{$id}",
                'component_types' => $baseUrl . "/admin/component-types/{$id}",
                'salary_components' => $baseUrl . "/admin/salary-components/{$id}",
                'payslips' => $baseUrl . "/admin/payslips/{$id}",
                'users' => $baseUrl . "/admin/users/{$id}",
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Error getting subject URL: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Helper untuk mendapatkan nama bulan dalam bahasa Indonesia
     */
    protected function getMonthName(?int $month): string
    {
        if (!$month) return '';
        
        return match($month) {
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
            default => (string)$month,
        };
    }
    
    /**
     * Scope untuk memfilter log berdasarkan tabel
     */
    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }
    
    /**
     * Deskripsi yang lebih manusiawi dari aksi
     */
    public function getActionLabelAttribute(): string 
    {
        return match($this->action) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus',
            default => $this->action ?? 'Lainnya',
        };
    }
    
    /**
     * Mendapatkan deskripsi perubahan dari old_value dan new_value
     */
    public function getChangeDescriptionAttribute(): ?string
    {
        try {
            if (empty($this->old_value) && empty($this->new_value)) {
                return null;
            }
            
            $changes = [];
            $oldValues = is_array($this->old_value) ? $this->old_value : [];
            $newValues = is_array($this->new_value) ? $this->new_value : [];
            
            foreach ($newValues as $key => $newValue) {
                $oldValue = $oldValues[$key] ?? null;
                
                if ($oldValue !== $newValue) {
                    // Jika nilai berupa ID, coba dapatkan nama yang sebenarnya
                    if (is_numeric($oldValue) && str_ends_with($key, '_id')) {
                        $oldValue = $this->resolveIdToName($key, $oldValue);
                    }
                    
                    if (is_numeric($newValue) && str_ends_with($key, '_id')) {
                        $newValue = $this->resolveIdToName($key, $newValue);
                    }
                    
                    $oldValueFormatted = is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue;
                    $newValueFormatted = is_array($newValue) ? json_encode($newValue) : (string)$newValue;
                    
                    $fieldName = $this->getHumanFieldName($key);
                    $changes[] = "{$fieldName}: {$oldValueFormatted} → {$newValueFormatted}";
                }
            }
            
            return !empty($changes) ? implode(', ', $changes) : null;
        } catch (\Throwable $e) {
            Log::error('Error getting change description: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mengubah ID menjadi nama yang sesuai
     */
    protected function resolveIdToName(string $field, $id): string
    {
        try {
            if (empty($id)) return '';
            
            return match($field) {
                'department_id' => Department::find($id)?->name ?? $id,
                'position_id' => Position::find($id)?->title ?? $id,
                'employee_id' => Employee::find($id)?->full_name ?? $id,
                'type_id' => ComponentType::find($id)?->name ?? $id,
                'component_id' => SalaryComponent::find($id)?->name ?? $id,
                'payslip_id' => Payslip::find($id)?->id ? ('Slip #' . $id) : $id,
                'user_id' => User::find($id)?->name ?? $id,
                default => (string)$id,
            };
        } catch (\Throwable $e) {
            Log::error('Error resolving ID to name: ' . $e->getMessage());
            return (string)$id;
        }
    }
    
    /**
     * Mendapatkan nama field yang lebih manusiawi
     */
    protected function getHumanFieldName(string $field): string
    {
        return match($field) {
            'name' => 'Nama',
            'full_name' => 'Nama Lengkap',
            'title' => 'Judul',
            'description' => 'Deskripsi',
            'department_id' => 'Departemen',
            'position_id' => 'Jabatan',
            'employee_id' => 'Karyawan',
            'type_id' => 'Tipe',
            'component_id' => 'Komponen',
            'amount' => 'Jumlah',
            'month' => 'Bulan',
            'year' => 'Tahun',
            'total_earnings' => 'Total Pendapatan',
            'total_deductions' => 'Total Potongan',
            'net_salary' => 'Gaji Bersih',
            'status' => 'Status',
            'join_date' => 'Tanggal Bergabung',
            'address' => 'Alamat',
            'nik' => 'NIK',
            'level' => 'Level',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
