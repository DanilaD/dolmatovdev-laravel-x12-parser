<?php

namespace DolmatovDev\X12Parser\Exceptions;

/**
 * Exception thrown when an unsupported transaction type is encountered.
 * 
 * This exception is thrown when the parser encounters a transaction
 * type that is not yet implemented in the package.
 */
class UnsupportedTransactionTypeException extends AnsiFileException
{
    /**
     * Create a new unsupported transaction type exception instance.
     */
    public function __construct(string $transactionType)
    {
        $message = "Unsupported transaction type: {$transactionType}. " .
                   "Currently supported types: 270, 271, 837, 835";
        
        parent::__construct($message);
    }
} 