<?php

namespace DolmatovDev\X12Parser\Services;

use DolmatovDev\X12Parser\Validators\AnsiValidatorInterface;
use DolmatovDev\X12Parser\Validators\Validator270;
use DolmatovDev\X12Parser\Exceptions\UnsupportedTransactionTypeException;
use DolmatovDev\X12Parser\Traits\HasConfig;

/**
 * Factory for creating ANSI validators based on transaction type.
 * 
 * This factory provides a centralized way to create appropriate
 * validators for different ANSI X12 transaction types.
 */
class ValidatorFactory
{
    use HasConfig;

    /**
     * Supported transaction types and their validator classes.
     */
    private array $validators;

    /**
     * Constructor to initialize validators from configuration.
     */
    public function __construct()
    {
        $this->validators = $this->loadValidatorsFromConfig();
    }

    /**
     * Load validators from configuration.
     */
    private function loadValidatorsFromConfig(): array
    {
        return $this->getConfig('x12-parser.transaction_types', [
            '270' => Validator270::class,
        ]);
    }

    /**
     * Create a validator for the specified transaction type.
     * 
     * @param string $transactionType Transaction type (e.g., '270', '271')
     * @return AnsiValidatorInterface Validator instance
     * @throws UnsupportedTransactionTypeException
     */
    public function make(string $transactionType): AnsiValidatorInterface
    {
        if (!isset($this->validators[$transactionType])) {
            throw new UnsupportedTransactionTypeException($transactionType);
        }

        $validatorClass = $this->validators[$transactionType];
        
        return new $validatorClass();
    }

    /**
     * Get all supported transaction types.
     * 
     * @return array Array of supported transaction types
     */
    public function getSupportedTransactionTypes(): array
    {
        return array_keys($this->validators);
    }

    /**
     * Check if a transaction type is supported.
     * 
     * @param string $transactionType Transaction type to check
     * @return bool True if supported
     */
    public function isSupported(string $transactionType): bool
    {
        return isset($this->validators[$transactionType]);
    }

    /**
     * Get the validator class for a transaction type.
     * 
     * @param string $transactionType Transaction type
     * @return string|null Validator class name or null if not supported
     */
    public function getValidatorClass(string $transactionType): ?string
    {
        return $this->validators[$transactionType] ?? null;
    }
} 