<?php

namespace Arc\Hooks;

use Arc\Application;

class Activation
{
    public function __construct(Application $plugin)
    {
        $this->app = $plugin;
    }

    /**
     * Register an activation hook with WordPress to Execute the callable when the plugin
     * is activated.
     **/
    public function whenPluginIsActivated($callable)
    {
        register_activation_hook(
            $this->app->filename,
            $callable
        );
    }

    /**
     * Register a deactivation hook with Wordpress to execture the callable when the plugin
     * is deactivated.
     **/
    public function whenPluginIsDeactivated($callable)
    {
        register_deactivation_hook(
            $this->app->filename,
            $callable
        );
    }
}
