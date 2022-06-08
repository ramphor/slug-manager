<?php
namespace Ramphor\Slug\Remover;

class Plugin
{
    protected static $instance;

    protected function __construct() {
    }

    public static function instance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}
