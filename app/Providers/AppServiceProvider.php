<?php

namespace App\Providers;

use App\Services\WhatsApp\MetaWhatsAppCloudService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WhatsAppProviderInterface::class, MetaWhatsAppCloudService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
