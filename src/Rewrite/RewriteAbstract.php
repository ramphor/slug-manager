<?php
namespace Ramphor\Slug\Manager\Rewrite;

abstract class RewriteAbstract
{
    protected $rules;

    public function __construct($rules)
    {
        if (is_array($rules) && count($rules) > 0) {
            $this->rules = array_filter($rules, function ($rule) {
                return $rule['rewrite'] == true;
            });
        }
    }

    abstract public function rewrite();


    protected function parseQuerySlug($slug, $dataType)
    {
        $rule = $this->rules[$dataType];
        $format = array_get($rule, 'format');

        $formatArr = explode('/', ltrim($format, '/'));
        $slugArr = explode('/', $slug);

        foreach ($formatArr as $index => $tag) {
            if (!preg_match('/\%[^\%]+\%/', $tag, $matches)) {
                continue;
            }
            $dirtyString = str_replace($matches[0], '', $tag);

            return str_replace($dirtyString, '', $slugArr[$index]);
        }
    }
}
