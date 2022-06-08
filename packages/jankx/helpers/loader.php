<?php
/**
 * Jankx helper loader
 *
 * @package jankx
 * @subpackage helpers
 */


if (!defined('JANKX_HELPER_LOADED')) {
    if (!class_exists('Mobile_Detect')) {
        require_once dirname(__FILE__) . '/src/Mobile_Detect.php';
    }

    // Load WordPress shim to compatibility with all WordPress versions
    require_once dirname(__FILE__) . '/src/shims.php';

    // The Jankx helpers
    require_once dirname(__FILE__) . '/src/helpers.php';
}
