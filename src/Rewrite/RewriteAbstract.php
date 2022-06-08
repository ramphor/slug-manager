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
}
