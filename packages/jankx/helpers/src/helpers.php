<?php
define('JANKX_HELPER_LOADED', true);

if (!function_exists('array_get')) {
    function array_get($array, $key, $defaultValue = false)
    {
        $keys = explode('.', $key);
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return $defaultValue;
            }
            $value = $array = $array[$key];
        }
        return $value;
    }
}

if (!function_exists('jankx_get_device_detector')) {
    function jankx_get_device_detector()
    {
        global $detector;
        if (is_null($detector)) {
            // Init Mobile Detect Library 2.8.34
            $detector = new Mobile_Detect();
        }
        if (class_exists(Jankx::class)) {
            // Create Jankx::device() method
            $jankxInstance = Jankx::instance();
            $jankxInstance->device = function () use ($detector) {
                return $detector;
            };
        }

        return $detector;
    }
}

if (!function_exists('jankx_is_mobile')) {
    function jankx_is_mobile()
    {
        $detector = jankx_get_device_detector();
        $pre = apply_filters('jankx/device/is_mobile/pre', null, $detector);
        if (!is_null($pre)) {
            return $pre;
        }

        return apply_filters(
            'jankx/device/is_mobile',
            $detector->isMobile()
        );
    }
}

if (!function_exists('jankx_is_tablet')) {
    function jankx_is_tablet()
    {
        $detector = jankx_get_device_detector();
        $pre = apply_filters('jankx/device/is_tablet/pre', null, $detector);
        if (!is_null($pre)) {
            return $pre;
        }

        return apply_filters(
            'jankx/device/is_tablet',
            $detector->isTablet()
        );
    }
}

if (!function_exists('jankx/device/is_mobile/template')) {
    function jankx_is_mobile_template()
    {
        $pre = apply_filters(
            'jankx/device/is_mobile/template',
            null
        );
        if (!is_null($pre)) {
            return boolval($pre);
        }
        return jankx_is_mobile();
    }
}

if (!function_exists('jankx_generate_html_attributes')) {
    function jankx_generate_html_attributes($attributes)
    {
        if (!is_array($attributes)) {
            return '';
        }
        $attributesStr = '';
        foreach ($attributes as $attribute => $value) {
            $attributesStr .= sprintf(
                '%s="%s" ',
                $attribute,
                esc_attr(is_array($value) ? implode(' ', $value) : $value)
            );
        }
        return rtrim($attributesStr);
    }
}

if (!function_exists('jankx_get_wp_image_sizes')) {
    function jankx_get_wp_image_sizes($size)
    {
        if (in_array($size, array( 'thumbnail', 'medium', 'large', 'medium_large' ))) {
            return array(
                'width'  => get_option($size . '_size_w'),
                'height' => get_option($size . '_size_h'),
            );
        }

        $get_intermediate_image_sizes = get_intermediate_image_sizes();
        if (! $size || ! in_array($size, $get_intermediate_image_sizes)) {
            return false;
        }
        // Get additional image sizes;
        $wp_additional_image_sizes = wp_get_additional_image_sizes();

        return $wp_additional_image_sizes[ $size ];
    }
}

if (!function_exists('jankx_get_image_numeric_size')) {
    function jankx_get_image_numeric_size($textSize)
    {
        if (empty($textSize)) {
            return false;
        }

        $height = 0;
        $width  = 0;
        if (is_array($textSize)) {
            $width  = array_get($textSize, 0);
            $height = array_get($textSize, 1);

            return array(
                'width'  => $width,
                'height' => $height,
            );
        }

        return jankx_get_wp_image_sizes($textSize);
    }
}

if (!function_exists('jankx_placeholder_image')) {
    function jankx_placeholder_image($imageSize, $placeholder = '')
    {
        if (in_array($imageSize, array('medium_large'))) {
            $imageSize = 'medium';
        }
        $imageSize = jankx_get_image_numeric_size($imageSize);
        if (empty($imageSize)) {
            $imageSize = array(150, 150);
        }

        $siteName = urlencode(get_bloginfo('name'));
        return call_user_func_array('sprintf', apply_filters(
            'jankx_placeholder_image_args',
            array(
                '<img src="https://placeskull.com/%1$s/%2$s/%3$d" alt="%4$s" />',
                implode('/', array_values($imageSize)),
                '4a90e2',
                40,
                urldecode($siteName)
            )
        ));
    }
}

if (!function_exists('jankx_get_post_thumbnail')) {
    function jankx_get_post_thumbnail($size = 'thumbnail', $attr = array(), $post = null)
    {
        if (has_post_thumbnail($post)) {
            return get_the_post_thumbnail($post, $size, $attr);
        } else {
            return jankx_placeholder_image($size);
        }
    }
}

if (!function_exists('jankx_the_post_thumbnail')) {
    function jankx_the_post_thumbnail($size = 'thumbnail', $attr = array(), $post = null)
    {
        echo jankx_get_post_thumbnail($size, $attr, $post);
    }
}

if (!function_exists('jankx_template_has_footer')) {
    function jankx_template_has_footer()
    {
        return apply_filters('jankx_template_has_footer', true);
    }
}

if (!function_exists('wp_set_cookie')) {
    function wp_set_cookie($cookie_name, $cookie_value = false, $secure = '')
    {
        $cookie_lifetime = apply_filters('auth_cookie_expiration', 14 * DAY_IN_SECONDS );
        $expired_at = time() + $cookie_lifetime;

        if ('' === $secure) {
            $secure = is_ssl();
        }
        $secure = apply_filters('secure_wp_cookie', $secure, $cookie_name, $cookie_value);

        do_action('set_wp_cookie', $cookie_name, $expired_at, $cookie_value);

        setcookie($cookie_name, $cookie_value, $expired_at, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
        setcookie($cookie_name, $cookie_value, $expired_at, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true);
    }
}

if (!function_exists('wp_get_cookie')) {
    function wp_get_cookie($cookie_name, $defaultValue = null)
    {
        if (!isset($_COOKIE[$cookie_name])) {
            return $defaultValue;
        }
        return $_COOKIE[$cookie_name];
    }
}

if (!function_exists('array_elements_in_array')) {
    function array_elements_in_array($elements, $arr)
    {
        if (!is_array($elements)) {
            $elements = array($elements);
        }
        foreach ($elements as $element) {
            if (in_array($element, $arr)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('wp_is_request')) {
    function wp_is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return ( ! is_admin() || defined('DOING_AJAX') ) && ! defined('DOING_CRON');
        }
    }
}

function wp_get_client_ip_address()
{
    $ip_headers = apply_filters('wp_get_client_ip_address', array(
        'HTTP_CF_IPCOUNTRY',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ));

    foreach ($ip_headers as $ip_header) {
        if (!empty($_SERVER[$ip_header])) {
            return $_SERVER[$ip_header];
        }
    }
    return '127.0.0.1';
}

if (! function_exists('wp_request_is_xhr')) {
    function wp_request_is_xhr()
    {
        return array_get($_SERVER, 'HTTP_X_REQUESTED_WITH', false) === 'XMLHttpRequest';
    }
}

if (!function_exists('jankx_get_path_url')) {
    function jankx_get_path_url($path)
    {
        $abspath = constant('ABSPATH');
        if (PHP_OS === 'WINNT') {
            $abspath = str_replace('\\', '/', $abspath);
            $path = str_replace('\\', '/', $path);
        }
        return str_replace($abspath, site_url('/'), $path);
    }
}

if (!function_exists('jankx_trim_is_not_number')) {
    function jankx_trim_is_not_number($str)
    {
        return preg_replace('/[^\d]/', '', $str);
    }
}
