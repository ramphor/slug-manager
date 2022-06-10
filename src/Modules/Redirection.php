<?php
namespace Ramphor\Slug\Manager\Modules;

use WP_Post;
use WP_Term;
use Ramphor\Slug\Manager\Abstracts\Module;

class Redirection extends Module
{
    /**
     * @param \WP $wp
     */
    public function load($wp)
    {
        $queried_object = get_queried_object();
        if ($queried_object instanceof WP_Post) {
            $link = get_permalink($queried_object);
            if (site_url($wp->request) !== rtrim($link, '/')) {
                wp_safe_redirect($link, 301, 'Ramphor Slug Manager');
            }
        } elseif ($queried_object instanceof WP_Term) {
            $link = get_term_link($queried_object, $queried_object->taonomy);
            if (site_url($wp->request) !== rtrim($link, '/')) {
                wp_safe_redirect($link, 301, 'Ramphor Slug Manager');
            }
        }
    }
}
