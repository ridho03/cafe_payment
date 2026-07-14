<?php

namespace App\Providers;

use App\Models\Cafe;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            static $fallbackCafe = false;

            $currentCafe = request()->user()?->cafe;

            if (! $currentCafe) {
                if ($fallbackCafe === false) {
                    $fallbackCafe = Schema::hasTable('cafes')
                        ? Cafe::query()->orderBy('id')->first()
                        : null;
                }

                $currentCafe = $fallbackCafe;
            }

            $view->with('currentCafe', $currentCafe);
            $view->with('panelBrandName', $currentCafe?->name ?: config('app.name', 'Payment Cafe'));
            $view->with('appLogoUrl', asset('images/logo.png'));
            $view->with('impersonator', session('impersonator_id') ? User::find(session('impersonator_id')) : null);
        });
    }
}
