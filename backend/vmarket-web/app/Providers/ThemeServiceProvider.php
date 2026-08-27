<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $theme = env('WEB_THEME') == null ? 'theme_aster' : env('WEB_THEME');
        $path = base_path('resources/themes/' . $theme);
        if (!is_dir($path)) {
            $path = base_path('resources/themes/theme_aster');
        }
        if (!defined('VIEW_FILE_NAMES') && file_exists($path . '/file_names.php')) {
            define("VIEW_FILE_NAMES", include($path . '/file_names.php'));
        }
        view()->addLocation($path);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
