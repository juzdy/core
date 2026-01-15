<?php
namespace Juzdy\Layout;

use Juzdy\Config;

class Layout implements LayoutInterface
{
    /**
     * @param Request $request The request object
     * @param string $template The template name
     * @param array $params The initial parameters for the view
     */
    public function __construct(
        private string $layout,
        private string $template,
        private array $params = [],
    ) 
    {
        //$this->layout = $layout ?? $this->layout;
    }

    /**
     * @return string The template name
     */
    protected function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Getter and setter for view parameters.
     *
     * @param string $var The parameter name
     * @param mixed|null $value The value to set (if null, acts as a getter)
     * @return mixed The parameter value when acting as a getter, or the View instance when acting as a setter
     */
    public function __invoke(string $var, mixed $value = null): mixed
    {
        if ($value === null) {
            return $this->params[$var] ?? null;
        }

        $this->params[$var] = $value;
        return $this;
    }

    

    /**
     * Render the view with the given parameters.
     * 
     * @return string The rendered output
     * @throws \RuntimeException If the view or layout file is not found
     */
    public function render(?string $template = null, ?string $layout = null): string
    {
        $str = '';
        ob_start();
        try{
            
            $layoutPath = $this->layout($template, $layout);
            
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout not found: $layoutPath");
            }

            include $layoutPath;

            $str = ob_get_clean();

        } catch (\Throwable $e) {
            
            throw $e;
        }

        return $str;
    }

    

    /**
     * Get the full path to the main layout file.
     * Builds the layout file path based on configuration settings.
     * 
     * @return string The full path to the layout file
     */
    protected function layout(?string $template = null, ?string $layout = null): string
    {
        if ($template && str_starts_with($template, DIRECTORY_SEPARATOR)) {
            return $template;
        }

        $source = sprintf(
            '%s%s%s%s%s',
            Config::get('layout.path'),
            DIRECTORY_SEPARATOR,
            $layout ?? $this->layout,
            DIRECTORY_SEPARATOR,
            ($template ?? Config::get('layout.main'))
        );

        if (pathinfo($source, PATHINFO_EXTENSION) === '') {
            $source .= '.phtml';
        }

        return $source;
    }

    protected function content(): string
    {

        return $this->render($this->getTemplate());
    }

    public function __toString(): string
    {
        return $this->content();
        return $this->render();
    }
}