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
        add_filter('post_type_link', array($this, 'customPostUrl'), 10, 3);
        add_action('pre_get_posts', array($this, 'parseRequest'), 10);
    }

    protected function findPostName($post)
    {
        $post = get_post($post);
        if (empty($post->post_parent)) {
            return $post->post_name;
        }
        return sprintf('%s/%s', $post->post_name, $this->findPostName($post->post_parent));
    }

    public function customPostUrl($post_link, $post, $leavename)
    {
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

    public function parseRequest($query)
    {
        // Only support main query
        if (!$query->is_main_query() || isset($query->query['post_type'])) {
            return;
        }

        if (isset($query->query['pagename'])) {
            $requestSlug = '/' . $query->query['pagename'];

            $postType = $this->matchingPostTypeFromSlug($requestSlug);

            if ($postType !== false) {
                $slug = $this->parseQuerySlug($query->query['pagename'], $postType);
                $query->query[$postType]   = $slug;
                $query->query['name']      = $slug;
                $query->query['post_type'] = $postType;

                $query->query_vars[$postType]   = $slug;
                $query->query_vars['name']      = $slug;
                $query->query_vars['post_type'] = $postType;

                // Unset page params
                unset($query->query['pagename'], $query->query_vars['pagename']);
            }
        }
    }
}
