<?php

namespace DolmatovDev\X12Parser\Exceptions;

use Exception;

/**
 * Base exception class for ANSI file processing errors.
 * 
 * This exception is thrown when there are general errors during
 * ANSI file parsing, validation, or building operations.
 */
class AnsiFileException extends Exception
{
    /**
     * Create a new ANSI file exception instance.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 