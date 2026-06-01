<?php

namespace App\Providers;

use App\Events\ShopOrderCreated;
use App\Listeners\SendShopOrderCreatedEmailNotification;
use App\Listeners\SendShopOrderCreatedTelegramNotification;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Event;
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
        Event::listen(
            ShopOrderCreated::class,
            SendShopOrderCreatedEmailNotification::class,
        );

        Event::listen(
            ShopOrderCreated::class,
            SendShopOrderCreatedTelegramNotification::class,
        );

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ru']);
        });
    }
}
