<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Pagination\Paginator;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->resetTable();
                    Notification::make()
                        ->title('Data diperbarui')
                        ->success()
                        ->send();
                })
                ->tooltip('Refresh data'),
                
            Actions\Action::make('purge')
                ->label('Bersihkan Log Lama')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Bersihkan Log Aktivitas')
                ->modalDescription('Yakin ingin menghapus log aktivitas yang lebih dari 30 hari? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Bersihkan')
                ->modalCancelActionLabel('Batal')
                ->action(function () {
                    $thirtyDaysAgo = now()->subDays(30);
                    $recordsDeleted = Activity::where('created_at', '<', $thirtyDaysAgo)->delete();
                    
                    $this->dispatch('notify', [
                        'title' => 'Log dibersihkan!',
                        'message' => "{$recordsDeleted} log aktivitas lama telah dihapus.",
                        'icon' => 'heroicon-o-check-circle',
                    ]);
                })
                ->visible(fn() => Auth::check()),
                
            Actions\Action::make('help')
                ->label('Bantuan')
                ->icon('heroicon-o-question-mark-circle')
                ->color('info')
                ->modalHeading('Bantuan Log Aktivitas')
                ->modalDescription('Log aktivitas mencatat semua perubahan data dalam sistem. Gunakan filter untuk mempermudah pencarian data.')
                ->modalSubmitAction(false)
                ->modalContent(view('filament.resources.activity-resource.help')),
        ];
    }
    
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-clipboard-document-list';
    }
    
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada log aktivitas';
    }
    
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Log aktivitas akan tercatat saat ada perubahan data di sistem.';
    }
    
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
    
    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->paginate(25);
    }
}
