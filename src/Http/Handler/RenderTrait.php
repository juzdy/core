<?php

namespace Juzdy\Http\Handler;

use Juzdy\Http\ResponseInterface;
use Juzdy\Layout\Layout;
use Juzdy\Layout\LayoutInterface;

trait RenderTrait
{

    protected function getLayout(string $layout, string $template, array $params = []): LayoutInterface
    {
        return new Layout($layout, $template, $params);
    }

    protected function layout(string $layout, string $template, array $params = []): ResponseInterface
    {
        return $this->render($template, $params, $layout);
    }

    protected function render(string $template, array $params = [], ?string $layout = null): ResponseInterface
    { 
        $layoutObj = $this->getLayout($layout, $template, $params);
        $this->response()
                ->status(200)
                ->body(
                    $layoutObj->render()
                );

        return $this->response();
    }

    protected function raw(string $content, int $status = 200, array $headers = []): ResponseInterface
    {
        $this->response()
                ->status($status)
                ->body($content);
        
        foreach ($headers as $name => $value) {
            $this->response()->header($name, $value);
        }

        return $this->response();
    }

    protected function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $jsonContent = json_encode($data);

        $this->response()
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body($jsonContent);
        
        foreach ($headers as $name => $value) {
            $this->response()->header($name, $value);
        }

        return $this->response();
    }
}