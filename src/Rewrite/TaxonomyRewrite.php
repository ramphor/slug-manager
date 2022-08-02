<?php
namespace Ramphor\Slug\Manager\Rewrite;

class TaxonomyRewrite extends RewriteAbstract
{
    protected $currentMatches;

    public function rewrite()
    {
        // Reset currentMatches
        $this->currentMatches = null;

        add_filter('term_link', [$this, 'customTermUrl'], 10, 3);
        add_action('parse_request', [$this, 'parseRequest'], 30);
    }

    protected function getSlugFromTerm($term, $taxonomy)
    {
        $term = get_term($term, $taxonomy);
        if ($term->parent <= 0) {
            return $term->slug;
        }

        return sprintf('%s/%s', $this->getSlugFromTerm($term->parent, $taxonomy), $term->slug);
    }

    public function customTermUrl($url, $term, $taxonomy)
    {
        if (isset($this->rules[$taxonomy])) {
            $rule = $this->rules[$taxonomy];
            $format =array_get($rule, 'format');

            $path = str_replace(array(
                '%slug%'
            ), array(
                $this->getSlugFromTerm($term, $taxonomy)
            ), $format);

            return site_url($path);
        }
        return $url;
    }


    protected function matchingTaxonomyFromPageName($pagename)
    {
        foreach ($this->rules as $taxonomy => $rule) {
            if (!isset($rule['regex'])) {
                continue;
            }

            if (preg_match($rule['regex'], $pagename, $this->currentMatches)) {
                return $taxonomy;
            }
        }
        return false;
    }

    protected function getPageByPath($page_path, $output = OBJECT, $post_type = 'page')
    {
        global $wpdb;

        $last_changed = wp_cache_get_last_changed('posts');

        $hash      = md5($page_path . serialize($post_type));
        $cache_key = "get_page_by_path_exclude_attachment:$hash:$last_changed";
        wp_cache_delete('get_page_by_path_exclude_attachment');

        $cached    = wp_cache_get($cache_key, 'posts');
        if (false !== $cached) {
            // Special case: '0' is a bad `$page_path`.
            if ('0' === $cached || 0 === $cached) {
                return;
            } else {
                return get_post($cached, $output);
            }
        }

        $page_path     = rawurlencode(urldecode($page_path));
        $page_path     = str_replace('%2F', '/', $page_path);
        $page_path     = str_replace('%20', ' ', $page_path);
        $parts         = explode('/', trim($page_path, '/'));
        $parts         = array_map('sanitize_title_for_query', $parts);
        $escaped_parts = esc_sql($parts);

        $in_string = "'" . implode("','", $escaped_parts) . "'";

        if (is_array($post_type)) {
            $post_types = $post_type;
        } else {
            $post_types = array( $post_type );
        }

        $post_types          = esc_sql($post_types);
        $post_type_in_string = "'" . implode("','", $post_types) . "'";
        $sql                 = "
            SELECT ID, post_name, post_parent, post_type
            FROM $wpdb->posts
            WHERE post_name IN ($in_string)
            AND post_type IN ($post_type_in_string)
        ";

        $pages = $wpdb->get_results($sql, OBJECT_K);

        $revparts = array_reverse($parts);

        $foundid = 0;
        foreach ((array) $pages as $page) {
            if ($page->post_name == $revparts[0]) {
                $count = 0;
                $p     = $page;

                /*
                 * Loop through the given path parts from right to left,
                 * ensuring each matches the post ancestry.
                 */
                while (0 != $p->post_parent && isset($pages[ $p->post_parent ])) {
                    $count++;
                    $parent = $pages[ $p->post_parent ];
                    if (! isset($revparts[ $count ]) || $parent->post_name != $revparts[ $count ]) {
                        break;
                    }
                    $p = $parent;
                }

                if (0 == $p->post_parent && count($revparts) == $count + 1 && $p->post_name == $revparts[ $count ]) {
                    $foundid = $page->ID;
                    if ($page->post_type == $post_type) {
                        break;
                    }
                }
            }
        }

        // We cache misses as well as hits.
        wp_cache_set($cache_key, $foundid, 'posts');

        if ($foundid) {
            return get_post($foundid, $output);
        }

        return null;
    }

    public function parseRequest($wp)
    {
        $error = isset($wp->query_vars['error']) && $wp->query_vars['error'] === '404';
        if ($error || (isset($wp->query_vars['pagename']) && is_null($this->getPageByPath($wp->query_vars['pagename'])))) {
            $rawSlug  = $error ? $wp->request : $wp->query_vars['pagename'];
            $taxonomy = $this->matchingTaxonomyFromPageName('/' . $rawSlug);

            if ($taxonomy === false) {
                return;
            }

            $slugArr = $this->parseQuerySlug('/' . $rawSlug, $taxonomy);
            $slug    = $slugArr[0];
            if (isset($slugArr[1]) && $slugArr[1] === 'page') {
                $wp->query_vars['paged'] = isset($slugArr[2]) ? $slugArr[2] : 1;
            }

            $wp->query_vars[$taxonomy] = $slug;
            $wp->query[$taxonomy]      = $slug;
            $wp->query['ramphor_slug'] = $slug;
            $wp->ramphorCustomValue    = $slug;
            $wp->ramphorCustomSlug     = true;
            $wp->ramphorCustomKey      = $taxonomy;

            unset($wp->query_vars['pagename'], $wp->query['pagename'], $wp->query_vars['error']);
        }
    }
}
