<?php

namespace DolmatovDev\X12Parser\DTO;

/**
 * Result object for ANSI file parsing operations.
 * 
 * This class provides a structured way to handle parsing results,
 * including segments, transaction type, and any parsing errors.
 */
class ParseResult
{
    /**
     * Create a new parse result instance.
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $segments = [],
        public readonly ?string $transactionType = null,
        public readonly array $errors = [],
        public readonly array $warnings = []
    ) {}

    /**
     * Check if the parsing was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success && empty($this->errors);
    }

    /**
     * Get the number of segments parsed.
     */
    public function getSegmentCount(): int
    {
        return count($this->segments);
    }

    /**
     * Get segments by type (e.g., 'ISA', 'GS', 'ST').
     */
    public function getSegmentsByType(string $type): array
    {
        return array_filter($this->segments, function ($segment) use ($type) {
            return str_starts_with($segment, $type . '*');
        });
    }

    /**
     * Create a successful parse result.
     */
    public static function success(array $segments, ?string $transactionType = null): self
    {
        return new self(true, $segments, $transactionType);
    }

    /**
     * Create a failed parse result.
     */
    public static function failure(array $errors, array $warnings = []): self
    {
        return new self(false, [], null, $errors, $warnings);
    }
} 