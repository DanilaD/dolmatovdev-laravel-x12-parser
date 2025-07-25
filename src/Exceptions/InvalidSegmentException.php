<?php

namespace DolmatovDev\X12Parser\Exceptions;

/**
 * Exception thrown when an ANSI segment format is invalid.
 * 
 * This exception is thrown when a segment doesn't follow the expected
 * format or contains invalid characters.
 */
class InvalidSegmentException extends AnsiFileException
{
    /**
     * Create a new invalid segment exception instance.
     */
    public function __construct(string $segment, string $reason = '')
    {
        $message = "Invalid segment format: {$segment}";
        
        if (!empty($reason)) {
            $message .= " - {$reason}";
        }
        
        parent::__construct($message);
    }
} 