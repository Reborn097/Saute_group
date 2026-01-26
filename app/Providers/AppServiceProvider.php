<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;


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
    
    public function boot()
    {
        if (config('time_travel.enabled') && config('time_travel.date')) {
            $tz = config('time_travel.tz', config('app.timezone'));
            Carbon::setTestNow(Carbon::parse(config('time_travel.date'), $tz));
        }
    }
}
