<?php

namespace DolmatovDev\X12Parser\Services;

use DolmatovDev\X12Parser\Traits\HasConfig;

/**
 * Service for generating file names with custom patterns and placeholders.
 * 
 * This service provides functionality to create dynamic file names
 * based on transaction types, timestamps, and custom data.
 */
class FileNamingService
{
    use HasConfig;

    /**
     * Current sequence number for file naming.
     */
    private int $sequence = 1;

    /**
     * Generate a filename for a transaction type.
     * 
     * @param string $transactionType Transaction type
     * @param array $data Optional data for placeholders
     * @param string|null $customPattern Optional custom pattern
     * @return string Generated filename
     */
    public function generateFileName(string $transactionType, array $data = [], ?string $customPattern = null): string
    {
        $pattern = $this->getPattern($transactionType, $customPattern);
        $fileName = $this->replacePlaceholders($pattern, $transactionType, $data);
        
        // Increment sequence for next file
        $this->sequence++;
        
        return $fileName;
    }

    /**
     * Get the naming pattern for a transaction type.
     * 
     * @param string $transactionType Transaction type
     * @param string|null $customPattern Optional custom pattern
     * @return string Pattern to use
     */
    private function getPattern(string $transactionType, ?string $customPattern = null): string
    {
        if ($customPattern) {
            return $customPattern;
        }

        $config = $this->getConfig('x12-parser.file_naming', []);
        $transactionPatterns = $config['transaction_patterns'] ?? [];
        
        if (isset($transactionPatterns[$transactionType])) {
            return $transactionPatterns[$transactionType];
        }

        return $config['default_pattern'] ?? 'x12_{transaction_type}_{timestamp}.txt';
    }

    /**
     * Replace placeholders in a pattern with actual values.
     * 
     * @param string $pattern Pattern with placeholders
     * @param string $transactionType Transaction type
     * @param array $data Optional data for placeholders
     * @return string Pattern with replaced placeholders
     */
    private function replacePlaceholders(string $pattern, string $transactionType, array $data = []): string
    {
        $replacements = [
            '{transaction_type}' => $transactionType,
            '{timestamp}' => date('Y-m-d_H-i-s'),
            '{date}' => date('Y-m-d'),
            '{time}' => date('H-i-s'),
            '{random}' => str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT),
            '{sequence}' => str_pad((string) $this->sequence, 6, '0', STR_PAD_LEFT),
            '{provider_id}' => $data['provider_id'] ?? '',
            '{payer_id}' => $data['payer_id'] ?? '',
            '{member_id}' => $data['member_id'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Reset the sequence counter.
     * 
     * @return void
     */
    public function resetSequence(): void
    {
        $this->sequence = 1;
    }

    /**
     * Get the current sequence number.
     * 
     * @return int Current sequence number
     */
    public function getCurrentSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Set the sequence number.
     * 
     * @param int $sequence Sequence number to set
     * @return void
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * Get available placeholders for file naming.
     * 
     * @return array Array of available placeholders
     */
    public function getAvailablePlaceholders(): array
    {
        $config = $this->getConfig('x12-parser.file_naming', []);
        return $config['placeholders'] ?? [
            '{transaction_type}' => 'The transaction type (e.g., 270, 837)',
            '{timestamp}' => 'Current timestamp in Y-m-d_H-i-s format',
            '{date}' => 'Current date in Y-m-d format',
            '{time}' => 'Current time in H-i-s format',
            '{random}' => 'Random 6-digit number',
            '{sequence}' => 'Sequential number (increments per file)',
            '{provider_id}' => 'Provider ID from the data (if available)',
            '{payer_id}' => 'Payer ID from the data (if available)',
            '{member_id}' => 'Member ID from the data (if available)',
        ];
    }
} 