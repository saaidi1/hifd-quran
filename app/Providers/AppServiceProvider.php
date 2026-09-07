<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
     *
     * Point central des réglages globaux de l'application.
     */
    public function boot(): void
    {
        /* L'interface est en Bootstrap 5 (RTL) : le paginateur doit suivre. */
        Paginator::useBootstrapFive();
    }
}
