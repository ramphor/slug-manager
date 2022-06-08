<?php
/**
 * Plugin Name: Ramphor Slug Manager
 * Descripton: This plugin will be remove slug of the posts, taxonomy (product_cat, category, ...) by the settings
 * Version: 0.0.1
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Tag: SEO, slug, URL
 */

if (!defined('ABSPATH')) {
    exit('Are you cheating huh?');
}

if (!defined('RAMPHOR_SLUG_MANAGER_PLUGIN_FILE')) {
    define('RAMPHOR_SLUG_MANAGER_PLUGIN_FILE', __FILE__);
}

$autoloader = dirname(RAMPHOR_SLUG_MANAGER_PLUGIN_FILE) . '/packages/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

if (class_exists(\Ramphor\Slug\Manager\Plugin::class)) {
    $GLOBALS['slug_Manager'] = \Ramphor\Slug\Manager\Plugin::instance();
}
