<?php

namespace LaveroAdmin\Admin\Translations;

use LaveroAdmin\Admin\Admin;
use LaveroAdmin\Admin\Extension;

class Translations extends Extension
{
    /**
     * Bootstrap this package.
     *
     * @return void
     */
    public static function boot()
    {
        static::registerRoutes();

        Admin::extend('translations', __CLASS__);
    }

    /**
     * Register routes for LaveroAdmin.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('translations', 'LaveroAdmin\Admin\Translations\TranslationsController@index')->name('translations-index');
            $router->post('translations/save', 'LaveroAdmin\Admin\Translations\TranslationsController@save')->name('translations-save');
            $router->post('translations/add-key', 'LaveroAdmin\Admin\Translations\TranslationsController@addKey')->name('translations-add-key');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Translations', 'translations', 'icon-language');

        parent::createPermission('Translations', 'ext.translations', 'translations*');
    }
}
