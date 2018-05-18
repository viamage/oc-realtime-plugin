<?php namespace Viamage\RealTime;

use System\Classes\PluginBase;
use Viamage\RealTime\Console\RunServer;
use Viamage\RealTime\Console\TestPush;
use Viamage\RealTime\Models\Settings;
use Viamage\RealTime\Components\AutoBahn;
use Viamage\RealTime\Models\Token;

/**
 * RealTime Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'RealTime',
            'description' => 'Ratchet Broadcasting for OctoberCMS',
            'author'      => 'Viamage',
            'icon'        => 'icon-cogs'
        ];
    }
    public function registerSettings(): array
    {
        return [
            'realtime' => [
                'label'       => 'viamage.realtime::lang.plugin.title',
                'description' => 'viamage.realtime::lang.plugin.description',
                'icon'        => 'icon-phone',
                'order'       => 550,
                'class'       => Settings::class,
                'category'    => 'viamage.realtime::lang.plugin.title',
                'permissions' => ['viamage.realtime.settings'],
            ],
        ];
    }
    
    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register(): void
    {
        $this->commands(RunServer::class, TestPush::class);
    }

    /**
     * Boot method, called right before the request route.
     */
    public function boot(): void
    {
        /** @noinspection ClassConstantCanBeUsedInspection */
        if(class_exists('RainLab\User\Models\User')){
            \RainLab\User\Models\User::extend(
                function ($model) {
                    $model->hasOne['realtimeToken'] = [Token::class];
                }
            );
            Token::extend(
                function(Token $model){
                    $model->belongsTo['user'] = \RainLab\User\Models\User::class;
                }
            );
        }
        /** @noinspection ClassConstantCanBeUsedInspection */
        if(class_exists('Keios\ProUser\Models\User')){
            \Keios\ProUser\Models\User::extend(
                function ($model) {
                    $model->hasOne['realtimeToken'] = [Token::class];
                }
            );
            Token::extend(
                function(Token $model){
                    $model->belongsTo['user'] = \Keios\ProUser\Models\User::class;
                }
            );
        }

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents(): array
    {
        return [
            AutoBahn::class => 'vm_autobahn',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions(): array
    {
        return [];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation(): array
    {
        return [];
    }
}
