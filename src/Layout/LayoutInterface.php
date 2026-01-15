<?php
namespace Juzdy\Layout;

use Stringable;

interface LayoutInterface extends RenderableInterface, Stringable
{
    
    /**
     * Getter and setter for view parameters.
     *
     * @param string $var The parameter name
     * @param mixed|null $value The value to set (if null, acts as a getter)
     * @return mixed The parameter value when acting as a getter, or the View instance when acting as a setter
     */
    public function __invoke(string $var, mixed $value = null): mixed;


    public function render(?string $template = null, ?string $layout = null): string;

    //public function content(): string;
}