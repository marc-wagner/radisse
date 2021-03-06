<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set the default string length for database migrations.
        // @see https://laravel.com/docs/5.4/migrations#indexes
        Schema::defaultStringLength(191);

        // Configure the Bcrypt hasher to use a custom cost factor.
        app('hash')->setRounds(config('hashing.bcrypt_cost'));

        // Register custom Blade directives.
        $this->registerBladeDirectives();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register custom Blade directives.
     *
     * @return void
     */
    public function registerBladeDirectives()
    {
        // Register a directive to display form validation errors.
        \Illuminate\Support\Facades\Blade::directive('errorhandling', function ($expression) {
            return "<?php echo \$__env->make(
                'errors.handling',
                ['item' => {$expression}]
            )->render(); ?>";
        });

        // Register a directive for the custom `breadcrumbs` Blade component.
        \Illuminate\Support\Facades\Blade::directive('breadcrumbs', function ($expression) {
            return "<?php echo \$__env->make(
                'components.breadcrumbs',
                ['breadcrumbs' => {$expression}]
            )->render(); ?>";
        });
    }
}
