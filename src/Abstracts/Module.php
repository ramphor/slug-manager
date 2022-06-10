<?php
namespace Ramphor\Slug\Manager\Abstracts;

use Ramphor\Slug\Manager\Interfaces\ModuleInterface;

abstract class Module implements ModuleInterface
{
    public function priority()
    {
        return 10;
    }
}
