<?php
namespace Ramphor\Slug\Manager\Rewrite;

class PostRewrite extends RewriteAbstract
{
    protected $rules;

    protected $currentMatches;

    public function rewrite()
    {
        if (!$this->rules) {
            return;
        }
        add_filter('post_type_link', array($this, 'customPostUrl'), 10, 2);
        add_filter('get_sample_permalink', array($this, 'customSamplePostUrl'), 10, 5);
        add_action('pre_get_posts', array($this, 'parseRequest'), 10);
    }

    protected function findPostName($post)
    {
        $post = get_post($post);
        if (empty($post->post_parent)) {
            return $post->post_name;
        }
        return sprintf('%s/%s', $this->findPostName($post->post_parent), $post->post_name);
    }

    public function customSamplePostUrl($permalink, $postID, $title, $name, $post)
    {
        $rule = array_get($this->rules, $post->post_type);
        $format = array_get($rule, 'format');

        if (is_array($permalink)) {
            $permalink[0] = site_url($format);
        }
        return $permalink;
    }

    public function customPostUrl($post_link, $post)
    {
        $post = get_post($post);
        $postType = $post->post_type;
        if (!isset($postType)) {
            return $post_link;
        }

        $rule = array_get($this->rules, $postType);
        $format = array_get($rule, 'format');
        $tags = array(
            '%postname%'
        );
        $tags_values = array(
            $this->findPostName($post)
        );
        $path = str_replace($tags, $tags_values, $format);

        return site_url($path);
    }

    protected function matchingPostTypeFromSlug($slug)
    {
        foreach ($this->rules as $postType => $args) {
            if (!isset($args['regex'])) {
                continue;
            }

            if (preg_match($args['regex'], $slug, $this->currentMatches)) {
                return $postType;
            }
        }
        return false;
    }

    protected function cleanCustomTaxonomyBefore(&$query, $key)
    {
        if (!isset($query->tax_query->queries) || !is_array($query->tax_query->queries)) {
            return;
        }
        $queries = $query->tax_query->queries;
        foreach ($query->tax_query->queries as $index => $args) {
            if ($key === $args['taxonomy']) {
                unset($queries[$index]);
            }
        }
        $query->tax_query->queries = array_values($queries);
        $query->tax_query->queried_terms = [];

        $query->is_archive  = false;
        $query->is_tax      = false;
        $query->is_single   = true;
        $query->is_singular = true;

        unset($query->query[$key], $query->query_vars[$key]);
    }

    public function parseRequest($query)
    {
        // Only support main query
        if (!$query->is_main_query() || isset($query->query['post_type'])) {
            return;
        }

        global $wp;
        if (!isset($wp->ramphorCustomSlug)) {
            $wp->ramphorCustomSlug = false;
        }

        $error = isset($wp->query_vars['error']) && $wp->query_vars['error'] === '404';
        if ($error || (isset($query->query['pagename']) || $wp->ramphorCustomSlug)) {
            $requestSlug = '/' . $wp->request;
            if ($wp->ramphorCustomSlug) {
                $requestSlug = '/' . $wp->ramphorCustomValue;
            }
            $postType    = $this->matchingPostTypeFromSlug($requestSlug);

            if ($postType !== false) {
                $slugArr = $this->parseQuerySlug($requestSlug, $postType);
                if (isset($slugArr[1]) && $slugArr[1] === 'page') {
                    $wp->query_vars['paged'] = isset($slugArr[2]) ? $slugArr[2] : 1;
                } else {
                    $slug = implode('/', $slugArr);
                }

                $query->query[$postType]   = $slug;
                $query->query['name']      = $slug;
                $query->query['post_type'] = $postType;

                $query->query_vars[$postType]   = $slug;
                $query->query_vars['name']      = $slug;
                $query->query_vars['post_type'] = $postType;


                if ($wp->ramphorCustomSlug) {
                    $this->cleanCustomTaxonomyBefore($query, $wp->ramphorCustomKey);
                }
                // Unset page params
                unset($query->query['pagename'], $query->query_vars['pagename'], $query->query['error'], $query->query_vars['error']);
            }
        }
    }
}
