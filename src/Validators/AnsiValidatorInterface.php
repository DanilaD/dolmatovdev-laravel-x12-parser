<?php

namespace DolmatovDev\X12Parser\Validators;

use DolmatovDev\X12Parser\DTO\ValidationResult;

/**
 * Interface for ANSI transaction validators.
 * 
 * This interface defines the contract for validating different
 * ANSI X12 transaction types (270, 271, 837, etc.).
 */
interface AnsiValidatorInterface
{
    /**
     * Validate the structure and content of ANSI segments.
     * 
     * @param array $segments Array of ANSI segments (e.g., ['ISA*...', 'GS*...'])
     * @return ValidationResult Contains validation status, errors, and parsed data
     */
    public function validate(array $segments): ValidationResult;

    /**
     * Get the transaction type this validator handles.
     * 
     * @return string Transaction type (e.g., '270', '271', '837')
     */
    public function getTransactionType(): string;

    /**
     * Get the required segments for this transaction type.
     * 
     * @return array Array of required segment IDs
     */
    public function getRequiredSegments(): array;

    /**
     * Get the optional segments for this transaction type.
     * 
     * @return array Array of optional segment IDs
     */
    public function getOptionalSegments(): array;
} 