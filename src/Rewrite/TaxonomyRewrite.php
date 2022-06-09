<?php
namespace Ramphor\Slug\Manager\Rewrite;

class TaxonomyRewrite extends RewriteAbstract
{
    public function rewrite()
    {
        add_filter('term_link', [$this, 'customTermUrl'], 10, 3);
        add_action('parse_request', [$this, 'parseRequest'], 10);
    }

    protected function getSlugFromTerm($term, $taxonomy)
    {
        $term = get_term($term, $taxonomy);
        if ($term->parent <= 0) {
            return $term->slug;
        }

        return sprintf('%s/%s', $term->slug, $this->getSlugFromTerm($term->parent, $taxonomy));
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

            if (preg_match($rule['regex'], $pagename)) {
                return $taxonomy;
            }
        }
        return false;
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars['pagename']) && is_null(get_page_by_path($wp->query_vars['pagename']))) {
            $taxonomy = $this->matchingTaxonomyFromPageName('/' . $wp->query_vars['pagename']);
            if ($taxonomy === false) {
                return;
            }

            $slug = $this->parseQuerySlug($wp->query_vars['pagename'], $taxonomy);

            $wp->query_vars[$taxonomy] = $slug;
            $wp->query[$taxonomy] = $slug;

            unset($wp->query_vars['pagename'], $wp->query['pagename']);
        }
    }
}
