<?php
namespace Juzdy\Layout;

use Stringable;

interface LayoutInterface //extends Stringable
{
    
    /**
     * Getter and setter for view parameters.
     *
     * @param string $var The parameter name
     * @param mixed|null $value The value to set (if null, acts as a getter)
     * @return mixed The parameter value when acting as a getter, or the View instance when acting as a setter
     */
    public function __invoke(string $var, mixed $value = null): mixed;


    /**
     * Get or set the context array.
     * 
     * @param array|null $context The context array to set (optional)
     * @param bool $merge Whether to merge with existing context (default: false)
     * 
     * @return array|static The context array if getting, or $this if setting
     */
    public function context(?array $context = null, bool $merge = false): array|static;

    /**
     * Get or set the layout name.
     * 
     * @param string|null $name The layout name to set (optional)
     * 
     * @return string|static The layout name if getting, or $this if setting
     */
    public function layout(?string $name = null): string|static;

    /**
     * Get the rendered content.
     * 
     * @return string The rendered content
     */
    public function content(): string;

    /**
     * Get or set the index template name.
     * 
     * @param string|null $template The index template name to set (optional)
     * 
     * @return string|static The index template name if getting, or $this if setting
     */
    public function index(?string $template = null): string|static;

    /**
     * Get or set the content template name.
     * 
     * @param string|null $template The content template name to set (optional)
     * 
     * @return string|static The content template name if getting, or $this if setting
     */
    public function template(?string $template = null): string|static;

    /**
     * Add an asset to the layout.
     * 
     * @param string $type The type of the asset (e.g., 'css', 'js', 'meta')
     * @param array $attributes The attributes of the asset (e.g., 'href', 'src', 'rel')
     * 
     * @return static The Layout instance for method chaining
     */
    public function asset(string $type, array $attributes = []): static;

    /**
     * Render and return the layout as a string.
     * 
     * @return string The rendered layout
     */
    public function assets(): string;
}