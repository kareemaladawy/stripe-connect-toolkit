<?php

namespace Kareemaladawy\StripeConnectToolkit;

use Kareemaladawy\StripeConnectToolkit\Contracts\AccountOwnerResolver;
use Kareemaladawy\StripeConnectToolkit\Contracts\CountryNameResolver;
use Kareemaladawy\StripeConnectToolkit\Contracts\ExternalAccountSyncHandler;
use Kareemaladawy\StripeConnectToolkit\Services\StripeAccountSyncService;
use Kareemaladawy\StripeConnectToolkit\Services\StripeConnectService;
use Kareemaladawy\StripeConnectToolkit\Services\StripePaymentService;
use Kareemaladawy\StripeConnectToolkit\Services\StripeTransferService;
use Illuminate\Support\ServiceProvider;
use Stripe\Stripe;

class StripeConnectToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-connect-toolkit.php', 'stripe-connect-toolkit');

        $this->app->singleton(StripeConnectService::class, function ($app) {
            return new StripeConnectService($app->bound(CountryNameResolver::class) ? $app->make(CountryNameResolver::class) : null);
        });

        $this->app->singleton(StripePaymentService::class);
        $this->app->singleton(StripeTransferService::class);

        $this->app->singleton(StripeAccountSyncService::class, function ($app) {
            return new StripeAccountSyncService(
                $app->make(AccountOwnerResolver::class),
                $app->bound(ExternalAccountSyncHandler::class) ? $app->make(ExternalAccountSyncHandler::class) : null,
            );
        });

        foreach (config('stripe-connect-toolkit.bindings', []) as $contract => $implementation) {
            if ($implementation) {
                $this->app->bind($contract, $implementation);
            }
        }
    }

    public function boot(): void
    {
        Stripe::setApiKey(config('stripe-connect-toolkit.secret'));

        $this->publishes([
            __DIR__.'/../config/stripe-connect-toolkit.php' => config_path('stripe-connect-toolkit.php'),
        ], 'stripe-connect-toolkit-config');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'stripe-connect-toolkit');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/stripe-connect-toolkit'),
        ], 'stripe-connect-toolkit-lang');
    }
}
