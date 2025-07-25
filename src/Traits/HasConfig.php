<?php

namespace DolmatovDev\X12Parser\Traits;

/**
 * Trait for classes that need configuration functionality.
 * 
 * This trait provides common configuration access methods
 * to eliminate code duplication across services.
 */
trait HasConfig
{
    /**
     * Get configuration value with fallback.
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if config not available
     * @return mixed Configuration value or default
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        try {
            if (function_exists('config')) {
                return config($key, $default);
            }
        } catch (\Exception $e) {
            // Fall back to default if config is not available
        }

        return $default;
    }

    /**
     * Check if Laravel configuration is available.
     * 
     * @return bool True if config function exists
     */
    protected function isLaravelAvailable(): bool
    {
        return function_exists('config');
    }
} 