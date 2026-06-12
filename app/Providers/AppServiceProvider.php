<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

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
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        Schema::defaultStringLength(191);

        // Implicitly grant "Superadmin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadmin') ? true : null;
        });

        /*
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if (in_array($status, [401, 403, 404, 419, 429, 500, 503])) {
                try {
                    return $response->render('Error', [
                        'status' => $status,
                    ])->withSharedData();
                } catch (\Exception $e) {
                    // Fallback if shared data fails during exception
                    return $response;
                }
            }
        });
        */
    }
}
