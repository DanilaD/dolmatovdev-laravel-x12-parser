<?php

namespace DolmatovDev\X12Parser;

use DolmatovDev\X12Parser\DTO\ParseResult;
use DolmatovDev\X12Parser\Exceptions\InvalidSegmentException;
use DolmatovDev\X12Parser\Exceptions\UnsupportedTransactionTypeException;
use DolmatovDev\X12Parser\Services\InputSanitizerService;
use DolmatovDev\X12Parser\Traits\HasDelimiters;

/**
 * Main parser for ANSI X12 files.
 * 
 * This class handles parsing ANSI files into segments and detecting
 * transaction types for further processing.
 */
class Parser
{
    use HasDelimiters;

    /**
     * Supported transaction types.
     */
    private const SUPPORTED_TRANSACTIONS = ['270', '271', '837', '835'];

    /**
     * Input sanitizer service for cleaning input data.
     */
    private InputSanitizerService $sanitizer;

    /**
     * Constructor to initialize with default delimiters.
     */
    public function __construct()
    {
        $this->initializeDelimiters();
        $this->sanitizer = new InputSanitizerService();
    }

    /**
     * Parse an ANSI file from the given file path.
     * 
     * @param string $filePath Path to the ANSI file
     * @return ParseResult Contains parsed segments and transaction type
     */
    public function parseFile(string $filePath): ParseResult
    {
        if (!file_exists($filePath)) {
            return ParseResult::failure(["File not found: {$filePath}"]);
        }

        try {
            $content = file_get_contents($filePath);
            return $this->parseContent($content);
        } catch (\Exception $e) {
            return ParseResult::failure(["Error reading file: {$e->getMessage()}"]);
        }
    }

    /**
     * Parse ANSI content string.
     * 
     * @param string $content ANSI file content
     * @param string|null $transactionType Optional transaction type for custom delimiters
     * @return ParseResult Contains parsed segments and transaction type
     */
    public function parseContent(string $content, ?string $transactionType = null): ParseResult
    {
        try {
            // Sanitize input content first
            $content = $this->sanitizer->sanitizeContent($content);
            
            // Validate content safety
            if (!$this->sanitizer->isContentSafe($content)) {
                return ParseResult::failure(["Content contains dangerous characters"]);
            }
            
            // Get validation errors
            $validationErrors = $this->sanitizer->getValidationErrors($content);
            if (!empty($validationErrors)) {
                return ParseResult::failure($validationErrors);
            }

            // Set delimiters based on transaction type if provided
            // Only if custom delimiters haven't been set
            if ($transactionType && $this->isUsingDefaultDelimiters()) {
                $this->setDelimitersForTransaction($transactionType);
            }

            $segments = $this->extractSegments($content);
            $detectedTransactionType = $this->detectTransactionType($segments);

            // If transaction type was provided, verify it matches detected type
            if ($transactionType && $transactionType !== $detectedTransactionType) {
                return ParseResult::failure([
                    "Transaction type mismatch: expected '{$transactionType}', detected '{$detectedTransactionType}'"
                ]);
            }

            return ParseResult::success($segments, $detectedTransactionType);
        } catch (\Exception $e) {
            return ParseResult::failure([$e->getMessage()]);
        }
    }

    /**
     * Extract segments from ANSI content.
     * 
     * @param string $content ANSI file content
     * @return array Array of segments
     */
    private function extractSegments(string $content): array
    {
        // Remove any whitespace and split by segment delimiter
        $content = trim($content);
        $segments = explode($this->currentDelimiters['segment'], $content);

        // Filter out empty segments and sanitize each segment
        // All segment validation is handled by InputSanitizerService::sanitizeSegment
        $sanitizedSegments = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if (!empty($segment)) {

                try {
                    
                    $sanitizedSegment = $this->sanitizer->sanitizeSegment($segment, $this->currentDelimiters);
                    $sanitizedSegments[] = $sanitizedSegment;
                } catch (\InvalidArgumentException $e) {
                    throw new InvalidSegmentException($segment, $e->getMessage());
                }
            }
        }

        return array_values($sanitizedSegments);
    }

    /**
     * Detect the transaction type from segments.
     * 
     * @param array $segments Array of segments
     * @return string Transaction type
     * @throws UnsupportedTransactionTypeException
     */
    private function detectTransactionType(array $segments): string
    {
        foreach ($segments as $segment) {
            if (str_starts_with($segment, 'ST' . $this->currentDelimiters['element'])) {
                $elements = explode($this->currentDelimiters['element'], $segment);
                $transactionType = $elements[1] ?? null;

                if (!$transactionType) {
                    throw new InvalidSegmentException($segment, 'Missing transaction type in ST segment');
                }

                if (!in_array($transactionType, self::SUPPORTED_TRANSACTIONS)) {
                    throw new UnsupportedTransactionTypeException($transactionType);
                }

                return $transactionType;
            }
        }

        throw new InvalidSegmentException('', 'No ST segment found to determine transaction type');
    }



    /**
     * Get supported transaction types.
     * 
     * @return array Array of supported transaction types
     */
    public function getSupportedTransactionTypes(): array
    {
        return self::SUPPORTED_TRANSACTIONS;
    }
} 