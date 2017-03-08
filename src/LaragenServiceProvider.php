<?php

namespace Laragen\Laragen;

use Illuminate\Support\ServiceProvider;
use Laragen\Laragen\Commands\ApiCommand;
use Laragen\Laragen\Commands\ModelCommand;

/**
 * Created by PhpStorm.
 * User: wxs77577 <wxs77577@gmail.com>
 * Date: 2017/3/8
 * Time: 14:30
 */
class LaragenServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/laragen.php' => config_path('laragen.php'),
        ], 'laragen.config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModelCommand::class,
                ApiCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/laragen.php', 'laragen'
        );
    }
}