<?php
namespace Juzdy\View;

use Closure;

class Phtml
{

    public function __construct(protected string $template)
    {
        
    }

    public function render(object $scope, string $template): string
    {
        return Closure::bind(function() use ($template) {
            ob_start();
            include $template;
            return ob_get_clean();
        }, $scope)();
    }
}