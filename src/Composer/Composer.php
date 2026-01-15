<?php
namespace Juzdy\Composer;

use Composer\Autoload\ClassLoader;
use ReflectionClass;

class Composer
{

    /**
     * The vendor directory
     *
     * @var string|null
     */
    protected static ?string $vendorDir = null;

    /**
     * Get path inside vendor directory
     * 
     * @param string|null $vendor Optional subpath inside vendor directory
     * 
     * @return string
     */
    public static function vendor(?string $vendor = null): string
    {
        static::$vendorDir ??= 
            class_exists(ClassLoader::class) 
                ? dirname(dirname((new ReflectionClass(ClassLoader::class))->getFileName())) // assume Composer still has same structure
                : throw new \RuntimeException('Composer is not loaded');
        return 
            sprintf(
                '%s%s%s',
                rtrim(static::$vendorDir, DIRECTORY_SEPARATOR),
                DIRECTORY_SEPARATOR,
                ltrim($vendor ?? '', DIRECTORY_SEPARATOR)
            );
    }

    /**
     * Get all registered Composer autoload namespaces
     * 
     * @return array<string>
     */
    public static function namespaces(): array
    {
        // Load the Composer autoload namespaces
        $namespaces = include static::vendor('composer/autoload_psr4.php');

        // Return only the namespace names
        $namespaces = array_keys($namespaces);

        return $namespaces;
    }
}
        