<?php

namespace DolmatovDev\X12Parser\DTO;

/**
 * Result object for ANSI validation operations.
 * 
 * This class provides a structured way to handle validation results,
 * including success status, errors, warnings, and parsed data.
 */
class ValidationResult
{
    /**
     * Create a new validation result instance.
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?array $data = null,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly ?string $transactionType = null
    ) {}

    /**
     * Check if the validation was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success && empty($this->errors);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get the first error message.
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Create a successful validation result.
     */
    public static function success(array $data, ?string $transactionType = null): self
    {
        return new self(true, $data, [], [], $transactionType);
    }

    /**
     * Create a failed validation result.
     */
    public static function failure(array $errors, array $warnings = [], ?string $transactionType = null): self
    {
        return new self(false, null, $errors, $warnings, $transactionType);
    }
} 