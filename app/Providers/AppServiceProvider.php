<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\AppLink;

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
        // Force HTTPS in production
        if (env('FORCE_HTTPS', false)) {
            \URL::forceScheme('https');
        }
        
        // Trust all proxies for HTTPS headers
        request()->setTrustedProxies(['*'], \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        // Share app links for the topbar dropdown
        View::composer('layouts.partials.topbar', function ($view) {
            $links = collect();
            try {
                if (Schema::hasTable('app_links')) {
                    $links = Cache::remember('topbar.app_links', 60, function () {
                        return AppLink::query()
                            ->where('is_active', true)
                            ->orderBy('position')
                            ->orderBy('id')
                            ->limit(9)
                            ->get();
                    });
                }
            } catch (\Throwable $e) {
                // fail closed: no links if DB not ready
                $links = collect();
            }
            $view->with('appLinks', $links);
        });

        // Simple help directive: @help('git.strategy')
        \Blade::directive('help', function($key){
            return "<?php echo view('components.help-icon', ['text' => config('ops_help.' . trim($key, '\'\"'), 'Nápověda není dostupná')]); ?>";
        });
    }
}
