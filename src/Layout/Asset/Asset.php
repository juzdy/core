<?php
namespace Juzdy\Layout\Asset;

class Asset
{
    /** @var string  The type of the asset (e.g., 'css', 'js', 'meta') */
    private string $type;

    /** @var array The attributes of the asset (e.g., 'href', 'src', 'rel') */
    private array $attributes;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Get or set the asset type.
     * 
     * @param string|null $type The asset type to set (optional)
     * 
     * @return string|static The asset type if getting, or $this if setting
     */
    public function type(?string $type = null): string|static
    {
        if ($type === null) {
            return $this->type;
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get or set the asset attributes.
     * 
     * @param array|null $attributes The attributes to set (optional)
     * 
     * @return array|static The attributes if getting, or $this if setting
     */
    public function attributes(?array $attributes = null): array|static
    {
        if ($attributes === null) {
            return $this->attributes;
        }

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Convert the asset to its HTML representation.
     * 
     * @return string The HTML string of the asset
     */
    public function __toString(): string
    {
        $attributes = array_merge(
            $this->attributes,
            match ($this->type) {
                'css' => ['rel' => 'stylesheet'],
                'js'  => [],
                default => [],
            }
        );

        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= ' ' . htmlspecialchars($key, ENT_QUOTES) . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
        }

        $asset = match ($this->type) {
            'css' => '<link' . $attrString . ' />',
            'js'  => '<script' . $attrString . '></script>',
            'meta' => '<meta' . $attrString . ' />',

            default => '',
        };

        return $asset;
    }
}