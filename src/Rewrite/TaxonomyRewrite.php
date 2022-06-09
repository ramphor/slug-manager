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

    public function parseRequest($wp)
    {
        $error = isset($wp->query_vars['error']) && $wp->query_vars['error'] === '404';
        if ($error || (isset($wp->query_vars['pagename']) && is_null(get_page_by_path($wp->query_vars['pagename'])))) {
            $rawSlug  = $error ? $wp->request : $wp->query_vars['pagename'];
            $taxonomy = $this->matchingTaxonomyFromPageName('/' . $rawSlug);

            if ($taxonomy === false) {
                return;
            }

            $slug = $this->parseQuerySlug('/' . $rawSlug, $taxonomy);

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
