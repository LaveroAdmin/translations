<?php

namespace LaveroAdmin\Admin\Translations;

use Illuminate\Support\ServiceProvider;

class TranslationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lavero-admin-translations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lavero-admin-translations');

        Translations::boot();
    }
}
