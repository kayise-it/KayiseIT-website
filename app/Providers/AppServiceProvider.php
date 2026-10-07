<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->ensureBladeCompiledPathIsWritable();

        View::composer('layouts.app', function ($view) {
            $showWhatsappFloating = true;
            $showChatbotFloating = true;
            $whatsappDisplay = null;

            try {
                if (Schema::hasTable('site_settings')) {
                    try {
                        $settings = SiteSetting::current();
                        $showWhatsappFloating = $settings->show_whatsapp_floating;
                        $showChatbotFloating = $settings->show_chatbot_floating;
                        $whatsappDisplay = $settings->whatsappDisplay();
                    } catch (\Throwable $e) {
                        // Table exists but row unreadable — keep defaults.
                    }
                }
            } catch (\Throwable $e) {
                // Wrong DB credentials, ConnectionException, server down, etc. — still render pages.
            }

            $view->with([
                'showWhatsappFloating' => $showWhatsappFloating,
                'showChatbotFloating' => $showChatbotFloating,
                'whatsappDisplay' => $whatsappDisplay,
            ]);
        });
    }

    /**
     * Disk quota or deleted files under storage/framework/views cause "compiled view missing"
     * 500s. Prefer the default path; fall back to a deterministic temp directory when needed.
     */
    private function ensureBladeCompiledPathIsWritable(): void
    {
        $configured = config('view.compiled');
        if (! is_string($configured) || $configured === '') {
            config(['view.compiled' => storage_path('framework/views')]);
            $configured = storage_path('framework/views');
        }

        if ($this->bladeCompiledDirectoryIsWritable($configured)) {
            return;
        }

        $fallback = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'laravel-blade-' . md5(base_path());
        if (! is_dir($fallback)) {
            @mkdir($fallback, 0755, true);
        }
        if ($this->bladeCompiledDirectoryIsWritable($fallback)) {
            config(['view.compiled' => $fallback]);
        }
    }

    private function bladeCompiledDirectoryIsWritable(string $dir): bool
    {
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }

        $probe = $dir . DIRECTORY_SEPARATOR . '__probe_' . bin2hex(random_bytes(5)) . '.php';
        if (@file_put_contents($probe, '<?php') === false) {
            return false;
        }
        @unlink($probe);

        return true;
    }
}
