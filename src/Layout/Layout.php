<?php
namespace Juzdy\Layout;

use Juzdy\Config;
use Juzdy\Layout\Asset\Asset;

final class Layout implements LayoutInterface
{
    private ?string $layout = null;
    private array $context = [];
    private string $index = 'index';
    private ?string $template = null;

    private array $assets = [];


    public function __construct() 
    {
    }

    /**
     * Get or set a context variable.
     * 
     * @param string $name The variable name
     * @param mixed|null $value The value to set (optional)
     * 
     * @return mixed The variable value if getting, or $this if setting
     */
    public function __invoke(string $name, mixed $value = null): mixed
    {
        if ($value === null) {
            return $this->context[$name] ?? null;
        }

        $this->context[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function context(?array $context = null, bool $merge = false): array|static
    {
        if ($context === null) {
            return $this->context;
        }

        if ($merge) {
            $this->context = array_merge($this->context, $context);
        } else {
            $this->context = $context;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function layout(?string $name = null): string|static
    {
        if ($name === null) {
            return $this->layout;
        }

        $this->layout = $name;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function index(?string $template = null): string|static
    {
        if ($template === null) {
            return $this->index;
        }

        $this->index = $template;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function template(?string $template = null): string|static
    {
        if ($template === null) {
            return $this->template;
        }

        $this->template = $template;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function asset(string $type, array $attributes = []): static
    {
        $this->assets[] = $this->createAsset($type, $attributes);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function assets(): string
    {
        $str = '';
        foreach ($this->assets as $asset) {
            $str .= (string)$asset . "\n";
        }
        return $str;
    }

    

    

    
    public function render(
        ?string $template = null
        ): string
    {
        $str = '';
        ob_start();
        try{
            
            $resource = $this->resolve($template);
            
            if (!file_exists($resource)) {
                throw new \RuntimeException("Layout resourse not found: $resource");
            }

            include $resource;

            $str = ob_get_clean();

        } catch (\Throwable $e) {
            
            throw $e;
        }

        return $str;
    }

    /**
     * {@inheritDoc}
     */
    public function content(): string
    {
        return $this->render($this->getTemplate());
    }

    /**
     * Create an asset instance.
     * 
     * @param string $type The asset type
     * @param array $attributes The asset attributes
     * 
     * @return Asset The created asset
     */
    protected function createAsset(string $type, array $attributes = []): Asset
    {
        return (new Asset())
        ->type($type)
        ->attributes($attributes);
    }

    /**
     * Resolve the full path of a template.
     * 
     * @param string|null $template The template name (optional)
     * 
     * @return string The resolved template path
     */
    protected function resolve(?string $template = null): string
    {
        if ($template && str_starts_with($template, DIRECTORY_SEPARATOR)) {
            // Absolute path provided
            return $template;
        }

        $resource = Config::get('layout.path') . DIRECTORY_SEPARATOR;

        if ($this->layout !== null) {
            $resource .= $this->layout . DIRECTORY_SEPARATOR;

        }

        $resource .= $template ?: $this->index;

        // $source = sprintf(
        //     '%s%s%s%s%s',
        //     Config::get('layout.path'),
        //     DIRECTORY_SEPARATOR,
        //     $this->layout ?? '',
        //     DIRECTORY_SEPARATOR,
        //     ($template)
        // );

        // $source = preg_replace('#/+#', '/', $source);

        if (pathinfo($resource, PATHINFO_EXTENSION) === '') {
            $resource .= '.phtml';
        }

        return $resource;
    }

     /**
     * @return string The template name
     */
    protected function getTemplate(): string
    {
        return $this->template;
    }

    // public function __toString(): string
    // {
    //     return $this->content();
    //     //return $this->render();
    // }

    
}