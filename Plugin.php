<?php namespace Viamage\RealTime;

use Backend;
use System\Classes\PluginBase;
use Viamage\RealTime\Console\RunServer;
use Viamage\RealTime\Console\TestPush;
use Viamage\RealTime\Models\Settings;
use Viamage\RealTime\Components\AutoBahn;

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
    public function pluginDetails()
    {
        return [
            'name'        => 'RealTime',
            'description' => 'No description provided yet...',
            'author'      => 'Viamage',
            'icon'        => 'icon-leaf'
        ];
    }
    public function registerSettings()
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
    public function register()
    {
        $this->commands(RunServer::class, TestPush::class);
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
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
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'viamage.realtime.some_permission' => [
                'tab' => 'RealTime',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'realtime' => [
                'label'       => 'RealTime',
                'url'         => Backend::url('viamage/realtime/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['viamage.realtime.*'],
                'order'       => 500,
            ],
        ];
    }
}
