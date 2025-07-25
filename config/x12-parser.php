<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ANSI File Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the ANSI file processing
    | package, including delimiters, supported transaction types, and
    | file storage settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Delimiters
    |--------------------------------------------------------------------------
    |
    | These are the default delimiters used for ANSI X12 files.
    | You can override these if your files use different delimiters.
    |
    */
    'delimiters' => [
        'segment' => '~',
        'element' => '*',
        'sub_element' => '>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction-Specific Delimiters
    |--------------------------------------------------------------------------
    |
    | Custom delimiters for specific transaction types.
    | If a transaction type is not listed here, the default delimiters will be used.
    | This allows for different delimiter configurations per transaction type.
    |
    */
    'transaction_delimiters' => [
        // Example: Custom delimiters for 270 transactions
        // '270' => [
        //     'segment' => '~',
        //     'element' => '*',
        //     'sub_element' => '>',
        // ],
        
        // Example: Different delimiters for 837 transactions
        // '837' => [
        //     'segment' => '|',
        //     'element' => '^',
        //     'sub_element' => '&',
        // ],
        
        // Example: Legacy format with different delimiters
        // 'legacy_270' => [
        //     'segment' => '\r\n',
        //     'element' => ',',
        //     'sub_element' => ';',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Transaction Types
    |--------------------------------------------------------------------------
    |
    | List of transaction types that are supported by this package.
    | Each type should have a corresponding validator class.
    |
    */
    'transaction_types' => [
        '270' => \DolmatovDev\X12Parser\Validators\Validator270::class,
        // '271' => \DolmatovDev\X12Parser\Validators\Validator271::class,
        // '837' => \DolmatovDev\X12Parser\Validators\Validator837::class,
        // '835' => \DolmatovDev\X12Parser\Validators\Validator835::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file storage operations.
    |
    */
    'file_storage' => [
        'default_path' => storage_path('ansi'),
        'permissions' => 0644,
        'backup_enabled' => true,
        'backup_suffix' => '.backup',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Naming Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file naming patterns and conventions.
    | You can specify custom naming patterns for different transaction types.
    |
    */
    'file_naming' => [
        // Default file naming pattern
        'default_pattern' => 'x12_{transaction_type}_{timestamp}.txt',
        
        // Custom naming patterns for specific transaction types
        'transaction_patterns' => [
            // Example: Custom naming for 270 transactions
            // '270' => 'eligibility_inquiry_{timestamp}_{random}.txt',
            
            // Example: Custom naming for 837 transactions
            // '837' => 'claim_{provider_id}_{date}_{sequence}.txt',
            
            // Example: Custom naming for 835 transactions
            // '835' => 'payment_remittance_{payer_id}_{date}.txt',
        ],
        
        // Available placeholders for file naming
        'placeholders' => [
            '{transaction_type}' => 'The transaction type (e.g., 270, 837)',
            '{timestamp}' => 'Current timestamp in Y-m-d_H-i-s format',
            '{date}' => 'Current date in Y-m-d format',
            '{time}' => 'Current time in H-i-s format',
            '{random}' => 'Random 6-digit number',
            '{sequence}' => 'Sequential number (increments per file)',
            '{provider_id}' => 'Provider ID from the data (if available)',
            '{payer_id}' => 'Payer ID from the data (if available)',
            '{member_id}' => 'Member ID from the data (if available)',
        ],
        
        // File extension
        'extension' => '.txt',
        
        // Whether to use custom naming by default
        'use_custom_naming' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for validation behavior.
    |
    */
    'validation' => [
        'strict_mode' => true,
        'allow_warnings' => true,
        'max_segments' => 10000,
        'max_segment_length' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for logging ANSI file operations.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('ANSI_LOG_CHANNEL', 'daily'),
        'level' => env('ANSI_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for caching validation rules and parsed data.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'ansi_',
    ],
]; 