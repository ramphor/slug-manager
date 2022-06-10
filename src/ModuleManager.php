<?php
namespace Ramphor\Slug\Manager;

use Ramphor\Slug\Manager\Interfaces\ModuleInterface;
use Ramphor\Slug\Manager\Modules\Redirection;

class ModuleManager
{
    protected static $instance;

    /**
     * @var \Ramphor\Slug\Manager\Interfaces\ModuleInterface[]
     */
    protected $moduleInstances = [];

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    protected function builtinModules()
    {
        return [
            'redirection' => Redirection::class,
        ];
    }


    public function getAllModules()
    {
        $modules = $this->builtinModules();

        return apply_filters(
            'ramphor/slug/manager/modules',
            $modules
        );
    }

    public function getActiveModules()
    {
        $modules = $this->getAllModules();

        foreach ($modules as $moduleCls) {
            $module = new $moduleCls();
            if (!$module instanceof ModuleInterface) {
                unset($module);
                continue;
            }
            array_push($this->moduleInstances, $module);
        }

        return $this->moduleInstances;
    }

    public function loadModules()
    {
        foreach ($this->moduleInstances as $module) {
            if (method_exists($module, 'bootstrap')) {
                add_action('after_setup_theme', [$module, 'bootstrap'], $module->priority());
            }

            if (method_exists($module, 'init')) {
                add_action('init', [$module, 'init'], $module->priority());
            }

            if (method_exists($module, 'load')) {
                add_action('wp', [$module, 'load'], $module->priority());
            }

            if (method_exists($module, 'template')) {
                add_action('template_redirect', [$module, 'template'], $module->priority());
            }
        }
    }
}
