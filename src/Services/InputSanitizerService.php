<?php

namespace DolmatovDev\X12Parser\Services;

/**
 * Service for sanitizing and validating X12 input data
 * 
 * This service handles:
 * - Removal of unwanted characters (line breaks, tabs, etc.)
 * - Validation of allowed X12 characters
 * - Normalization of delimiters
 * - Input length validation
 */
class InputSanitizerService
{
    /**
     * Allowed characters in X12 standard (excluding delimiters)
     */
    private const ALLOWED_CHARS = '/^[A-Za-z0-9\s\-\.\/\*\+\=\&\$\%\#\@\!\?\(\)\[\]\{\}\|\:\;\<\>\"\']*$/';

    /**
     * Maximum segment length according to X12 standard
     */
    private const MAX_SEGMENT_LENGTH = 105;

    /**
     * Maximum element length according to X12 standard
     */
    private const MAX_ELEMENT_LENGTH = 80;

    /**
     * Sanitize raw input content
     * 
     * @param string $content Raw input content
     * @return string Sanitized content
     */
    public function sanitizeContent(string $content): string
    {
        // Remove null bytes and control characters (except tab and newline for now)
        $content = $this->removeControlCharacters($content);
        
        // Normalize line endings
        $content = $this->normalizeLineEndings($content);
        
        // Remove extra whitespace
        $content = $this->normalizeWhitespace($content);
        
        // Trim content
        $content = trim($content);
        
        return $content;
    }

    /**
     * Validate and sanitize segment content
     * 
     * @param string $segment Raw segment content
     * @param array $delimiters Current delimiters
     * @return string Sanitized segment
     * @throws \InvalidArgumentException If segment is invalid
     */
    public function sanitizeSegment(string $segment, array $delimiters): string
    {
        // Remove control characters
        $segment = $this->removeControlCharacters($segment);
        
        // Normalize whitespace
        $segment = $this->normalizeWhitespace($segment);
        
        // Trim segment
        $segment = trim($segment);
        
        // Validate segment length
        if (strlen($segment) > self::MAX_SEGMENT_LENGTH) {
            throw new \InvalidArgumentException(
                "Segment length exceeds maximum allowed length of " . self::MAX_SEGMENT_LENGTH . " characters"
            );
        }
        
        // Validate segment format
        $this->validateSegmentFormat($segment, $delimiters);
        
        return $segment;
    }

    /**
     * Validate and sanitize element content
     * 
     * @param string $element Raw element content
     * @return string Sanitized element
     * @throws \InvalidArgumentException If element is invalid
     */
    public function sanitizeElement(string $element): string
    {
        // Remove control characters
        $element = $this->removeControlCharacters($element);
        
        // Normalize whitespace
        $element = $this->normalizeWhitespace($element);
        
        // Trim element
        $element = trim($element);
        
        // Validate element length
        if (strlen($element) > self::MAX_ELEMENT_LENGTH) {
            throw new \InvalidArgumentException(
                "Element length exceeds maximum allowed length of " . self::MAX_ELEMENT_LENGTH . " characters"
            );
        }
        
        return $element;
    }

    /**
     * Validate JSON input for building X12
     * 
     * @param array $data JSON data
     * @return array Sanitized data
     * @throws \InvalidArgumentException If data is invalid
     */
    public function sanitizeJsonData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeElement($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeJsonData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Remove control characters from string
     * 
     * @param string $input Input string
     * @return string Cleaned string
     */
    private function removeControlCharacters(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove other control characters except tab, newline, carriage return
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        return $input;
    }

    /**
     * Normalize line endings
     * 
     * @param string $input Input string
     * @return string Normalized string
     */
    private function normalizeLineEndings(string $input): string
    {
        // Convert all line endings to \n
        $input = str_replace(["\r\n", "\r"], "\n", $input);
        
        return $input;
    }

    /**
     * Normalize whitespace
     * 
     * @param string $input Input string
     * @return string Normalized string
     */
    private function normalizeWhitespace(string $input): string
    {
        // Replace multiple spaces with single space
        $input = preg_replace('/\s+/', ' ', $input);
        
        return $input;
    }

    /**
     * Validate segment format
     * 
     * @param string $segment Segment content
     * @param array $delimiters Current delimiters
     * @throws \InvalidArgumentException If segment format is invalid
     */
    private function validateSegmentFormat(string $segment, array $delimiters): void
    {
        // Check if segment is empty
        if (empty($segment)) {
            throw new \InvalidArgumentException("Segment cannot be empty");
        }
        
        // Check if segment starts with valid segment identifier (2-3 characters)
        if (!preg_match('/^[A-Z0-9]{2,3}/', $segment)) {
            throw new \InvalidArgumentException("Segment must start with 2-3 alphanumeric identifier");
        }
        
        // Check for invalid characters (excluding delimiters)
        $allowedPattern = $this->getAllowedPattern($delimiters);
        

        
        if (!preg_match($allowedPattern, $segment)) {
            throw new \InvalidArgumentException("Segment contains invalid characters");
        }
    }

    /**
     * Get allowed character pattern excluding current delimiters
     * 
     * @param array $delimiters Current delimiters
     * @return string Regex pattern
     */
    private function getAllowedPattern(array $delimiters): string
    {
        // Start with the base allowed characters
        $baseChars = 'A-Za-z0-9\s\-\.\/\*\+\=\&\$\%\#\@\!\?\(\)\[\]\{\}\|\:\;\<\>\"\'';
        
        // Add delimiter characters, properly escaped for regex
        $delimiterChars = array_unique(array_values($delimiters));
        

        
        foreach ($delimiterChars as $char) {
            // Escape the character for regex and add it if not already present
            $escapedChar = preg_quote($char, '/');
            if (strpos($baseChars, $escapedChar) === false) {
                $baseChars .= $escapedChar;
            }
        }
        
        // Special handling for ^ character (regex anchor)
        if (in_array('^', $delimiterChars)) {
            $baseChars = str_replace('\^', '', $baseChars);
            $baseChars .= '\^';
        }
        
        return '/^[' . $baseChars . ']*$/';
    }

    /**
     * Check if content contains potentially dangerous characters
     * 
     * @param string $content Content to check
     * @return bool True if content is safe
     */
    public function isContentSafe(string $content): bool
    {
        // Check for null bytes
        if (strpos($content, "\0") !== false) {
            return false;
        }
        
        // Check for other dangerous control characters
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get validation errors for content
     * 
     * @param string $content Content to validate
     * @return array Array of validation errors
     */
    public function getValidationErrors(string $content): array
    {
        $errors = [];
        
        if (empty($content)) {
            $errors[] = "Content cannot be empty";
        }
        
        if (!$this->isContentSafe($content)) {
            $errors[] = "Content contains dangerous characters";
        }
        
        if (strlen($content) > 10000) { // Reasonable limit for X12 files
            $errors[] = "Content is too large (max 10KB)";
        }
        
        return $errors;
    }
} 