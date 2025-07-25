<?php

namespace DolmatovDev\X12Parser\Traits;

/**
 * Trait for classes that need delimiter functionality.
 * 
 * This trait provides common delimiter management methods
 * to eliminate code duplication between Parser and X12Builder.
 */
trait HasDelimiters
{
    use HasConfig;
    /**
     * Default ANSI delimiters.
     */
    private const DEFAULT_DELIMITERS = [
        'segment' => '~',
        'element' => '*',
        'sub_element' => '>',
    ];

    /**
     * Current delimiters being used.
     */
    protected array $currentDelimiters;

    /**
     * Initialize delimiters with default values.
     */
    protected function initializeDelimiters(): void
    {
        $this->currentDelimiters = self::DEFAULT_DELIMITERS;
    }

    /**
     * Set delimiters for a specific transaction type.
     * 
     * @param string $transactionType Transaction type
     * @return void
     */
    public function setDelimitersForTransaction(string $transactionType): void
    {
        $config = $this->getConfig('x12-parser.transaction_delimiters.' . $transactionType);
        
        if ($config && is_array($config)) {
            $this->currentDelimiters = array_merge(self::DEFAULT_DELIMITERS, $config);
            return;
        }
        
        $this->currentDelimiters = self::DEFAULT_DELIMITERS;
    }

    /**
     * Set custom delimiters.
     * 
     * @param array $delimiters Custom delimiters
     * @return void
     */
    public function setDelimiters(array $delimiters): void
    {
        $this->currentDelimiters = array_merge(self::DEFAULT_DELIMITERS, $delimiters);
    }

    /**
     * Get the delimiters currently being used.
     * 
     * @return array Delimiters configuration
     */
    public function getDelimiters(): array
    {
        return $this->currentDelimiters;
    }

    /**
     * Get the default delimiters.
     * 
     * @return array Default delimiters configuration
     */
    public function getDefaultDelimiters(): array
    {
        return self::DEFAULT_DELIMITERS;
    }

    /**
     * Check if current delimiters are the default ones.
     * 
     * @return bool True if using default delimiters
     */
    protected function isUsingDefaultDelimiters(): bool
    {
        return $this->currentDelimiters === self::DEFAULT_DELIMITERS;
    }
} 