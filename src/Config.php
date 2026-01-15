<?php
namespace Juzdy;

/**
 * Class Config
 *
 * A simple configuration management class that allows loading, retrieving,
 * and setting configuration values from PHP files. Supports dynamic references
 * within configuration values.
 * No overengineering, just straightforward configuration handling.
 */
class Config
{
    /** @var array The configuration data storage */
    private static array $data = [];

    /** @var bool Flag to indicate if configuration has been loaded */
    private static $isLoaded = false;

    /** Prevent direct instantiation */
    private function __construct(){}

    /**
     * Initialize configuration from a file.
     *
     * @param string $files glob pattern to the configuration files
     */
    public static function init(string $files): void
    {
        foreach(glob($files) as $configFile) {
            static::load($configFile);
        }
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $file Path to the configuration file.
     * @return array The loaded configuration data.
     * @throws \RuntimeException If the file does not exist or cannot be loaded.
     */
    public static function load(string $file): array
    {
    
        if (!file_exists($file)) {
            throw new \RuntimeException("Config file not found: " . $file);
        }
        $data = require $file;

        if (!is_array($data)) {
            throw new \RuntimeException("Config file must return an array: " . $file);
        }

        static::$data = array_merge(static::$data, $data);
        static::$isLoaded = true;

        static::parse();

        return static::$data;
    }

    /**
     * Parse configuration values for dynamic references.
     *
     * This method scans through the configuration data and replaces any
     * placeholders in the format @{key} with their corresponding configuration
     * values.
     * 
     * @todo refacor to use a more robust templating approach: e.g., using a plugin system
     * @todo Improve performance by caching parsed values
     * @todo Handle circular references
     * @todo Add support for default values in references
     * @todo Add support for nested references
     */
    protected static function parse(): void
    {
        array_walk_recursive(static::$data, function (&$value) {
            if (is_string($value)) {
                $value = preg_replace_callback('/@{([^}]+)}/', function ($matches) {
                    $key = $matches[1];
                    return static::get($key, $matches[0]);
                }, $value);
            }
        });
    }

    /**
     * Get a configuration value by key.
     *
     * This method allows you to retrieve a configuration value using a dot notation key.
     * For example, if the configuration is structured as ['database' => ['host' => 'localhost']],
     * you can retrieve the host with Config::get('database.host').
     *
     * @param string $key The configuration key in dot notation.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The configuration value or the default value if not found.
     */
    public static function get(string $key, $default = null)
    {
        if (!static::$isLoaded) {
            throw new \RuntimeException("Configuration not loaded. Please call Config::init() first.");
        }
        $parts = explode('.', $key);
        $value = static::$data;
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        return $value;
    }

    /**
     * Set a configuration value by key.
     *
     * @param string $key The configuration key.
     * @param mixed $value The value to set.
     */
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $data = &static::$data;
        
        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $data[$part] = $value;
            } else {
                if (!isset($data[$part]) || !is_array($data[$part])) {
                    $data[$part] = [];
                }
                $data = &$data[$part];
            }
        }
        
    }

    public static function all(): array
    {
        return static::$data;
    }

    /**
     * Clear the configuration data.
     */
    public static function clear(): void
    {
        static::$data = [];
    }

}