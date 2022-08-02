<?php
namespace Ramphor\Slug\Manager\Rewrite;

abstract class RewriteAbstract
{
    protected $rules;

    protected $currentMatches;

    public function __construct($rules)
    {
        if (is_array($rules) && count($rules) > 0) {
            $this->rules = array_filter($rules, function ($rule) {
                return $rule['rewrite'] == true;
            });
        }
    }

    abstract public function rewrite();

    protected function searchSlugOrPostNameIndex($formatArr)
    {
        foreach ($formatArr as $index => $format) {
            if (preg_match('/\%(slug|postname)\%/', $format)) {
                return $index;
            }
        }
        return false;
    }


    protected function parseQuerySlug($slug, $dataType)
    {
        $rule = $this->rules[$dataType];
        $format = array_get($rule, 'format');
        $regex = array_get($rule, 'regex');

        $formatArr = explode('/', ltrim($format, '/'));
        $slugArr   = explode('/', $slug);

        if (!$regex || count($formatArr) === 1 && $this->currentMatches) {
            return explode('/', ltrim($slug, '/'));
        } elseif (count($formatArr) > 1) {
            $index = $this->searchSlugOrPostNameIndex($formatArr);
            return explode('/', ltrim($this->currentMatches[$index], '/'));
        }

        foreach ($formatArr as $index => $tag) {
            if (!preg_match('/\%[^\%]+\%/', $tag, $matches)) {
                continue;
            }
            $dirtyString = str_replace($matches[0], '', $tag);
            $dirtyString = str_replace($dirtyString, '', $slugArr[$index]);

            return explode('/', $dirtyString);
        }
    }
}
