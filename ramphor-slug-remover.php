<?php
/**
 * Plugin Name: Ramphor Slug Remover
 * Descripton: This plugin will be remove slug of the posts, taxonomy (product_cat, category, ...) by the settings
 * Version: 0.0.1
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Tag: SEO, slug, URL
 */

if (!defined('RAMPHOR_SLUG_REMOVER_PLUGIN_FILE')) {
    define('RAMPHOR_SLUG_REMOVER_PLUGIN_FILE', __FILE__);
}

$autoloader = dirname(RAMPHOR_SLUG_REMOVER_PLUGIN_FILE) . '/packages/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

if (class_exists(\Ramphor\Slug\Remover\Plugin::class)) {
    $GLOBALS['slug_remover'] = \Ramphor\Slug\Remover\Plugin::instance();
}
