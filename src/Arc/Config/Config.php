<?php

namespace Arc\Config;

use Arc\Application;
use ArrayAccess;

class Config implements ArrayAccess
{
    protected $plugin;
    protected $testConfig;

    public function __construct(Application $plugin)
    {
        $this->app = $plugin;
    }

    public function useTestConfig($testConfig)
    {
        $this->testConfig = $testConfig;
    }

    public function get($key)
    {
        // If a test config value has been set for the given key we'll use that
        if (isset($this->testConfig[$key])) {
            return $this->testConfig[$key];
        }

        $configPath = $this->app->path.'config/app.php';
        $configValues = (file_exists($configPath)) ? include($configPath) : [];

        if (!isset($configValues[$key])) {
            $configValues = $this->values;
        }
        if (!isset($configValues[$key])) {
            return;
        }

        return $configValues[$key];
    }

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function has($key)
    {
        return !empty($this->get($key));
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->unset($offset);
    }
}
