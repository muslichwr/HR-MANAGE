<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Observers\DepartmentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'Organization Management' => 'Manajemen Organisasi',
            ]);
        });

        Carbon::setLocale('id');
        config(['app.locale' => 'id']);
        
        Department::observe(DepartmentObserver::class);
    }
}
