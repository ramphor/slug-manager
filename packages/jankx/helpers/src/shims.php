<?php
/**
 * WordPress shims.
 *
 * @package jankx
 * @subpackage helpers
 */


if (! function_exists('wp_body_open')) {
    /**
     * Adds backwards compatibility for wp_body_open() introduced with WordPress 5.2
     *
     * @since 1.0.0
     * @see https://developer.wordpress.org/reference/functions/wp_body_open/
     * @return void
     */
    function wp_body_open()
    {
        do_action('wp_body_open');
    }
}
