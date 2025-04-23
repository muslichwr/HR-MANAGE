<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Helpers\PdfHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding PDF Helper untuk digunakan dengan facade
        $this->app->singleton('pdf-helper', function ($app) {
            return new PdfHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'Organization Management' => 'Manajemen Organisasi',
                'Payroll Management' => 'Manajemen Gaji',
            ]);
        });

        Carbon::setLocale('id');
        config(['app.locale' => 'id']);
        
    }
}
