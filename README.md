# Laravel X12 Parser Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%2B-red.svg)](https://laravel.com)

A comprehensive Laravel package for processing X12 files with validation, parsing, and JSON conversion capabilities. Built with clean architecture principles and optimized for maintainability.

**Author:** Danila Dolmatov (danila@autosport.by)

## 🚀 Features

- **ANSI X12 File Parsing**: Parse ANSI files into structured data
- **Validation**: Comprehensive validation for different transaction types
- **JSON Conversion**: Convert ANSI files to/from JSON format
- **File Building**: Generate ANSI files from structured data
- **Transaction Support**: Currently supports 270 (Eligibility/Benefit Inquiry)
- **Extensible**: Easy to add support for other transaction types
- **Laravel Integration**: Full Laravel integration with facades and service providers
- **Console Commands**: Artisan commands for file processing operations
- **Input Sanitization**: Advanced security with input validation and sanitization
- **Custom File Naming**: Dynamic file naming with placeholders and patterns
- **Clean Architecture**: Optimized codebase with shared traits and reduced duplication
- **Configuration-Driven**: Flexible configuration system with fallbacks

## 📋 Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+

## 📦 Installation

1. **Install via Composer:**

```bash
composer require dolmatovdev/laravel-x12-parser
```

2. **Publish Configuration (Optional):**

```bash
php artisan vendor:publish --tag=x12-parser-config
```

3. **Publish Stubs (Optional):**

```bash
php artisan vendor:publish --tag=x12-parser-stubs
```

## 🔧 Configuration

The package configuration is located in `config/x12-parser.php`:

```php
return [
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
    
    'transaction_types' => [
        '270' => \DolmatovDev\X12Parser\Validators\Validator270::class,
    ],
    
    'file_storage' => [
        'default_path' => storage_path('ansi'),
        'permissions' => 0644,
        'backup_enabled' => true,
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
    
    'validation' => [
        'strict_mode' => true,
        'allow_warnings' => true,
        'max_segments' => 10000,
        'max_segment_length' => 1000,
    ],

    'logging' => [
        'enabled' => false,
        'level' => 'info',
    ],

    'cache' => [
        'enabled' => false,
        'ttl' => 3600,
    ],
];
```

## 🎯 Usage

### Using the Facade

```php
use DolmatovDev\X12Parser\Facades\X12Parser;

// Parse X12 file to array
$data = X12Parser::parseFile('path/to/file.txt');

// Parse X12 file to JSON
$json = X12Parser::parseFileToJson('path/to/file.txt');

// Build X12 file from JSON
$x12Content = X12Parser::buildFromJson($jsonData);

// Save X12 content to file
X12Parser::saveToFile($x12Content, 'output.txt');

// Validate file without parsing
$result = X12Parser::validateFile('path/to/file.txt');
if ($result->isSuccessful()) {
    echo "File is valid!";
} else {
    echo "Validation errors: " . implode(', ', $result->errors);
}

// Build and save with auto-generated filename
$filePath = X12Parser::buildAndSaveWithAutoName($jsonData, '270');

// Get supported transaction types
$types = X12Parser::getSupportedTransactionTypes();
```

### Using Dependency Injection

```php
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;

class X12Controller extends Controller
{
    public function __construct(
        private AnsiParserService $ansiService
    ) {}

    public function parseFile(Request $request)
    {
        $filePath = $request->file('x12_file')->getPathname();
        
        try {
            $data = $this->ansiService->parseFile($filePath);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function buildFile(Request $request)
    {
        $jsonData = $request->input('data');
        
        try {
            $ansiContent = $this->ansiService->buildFromJson($jsonData);
            return response($ansiContent)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Using DTOs

```php
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use DolmatovDev\X12Parser\Builder\X12Builder;

// Create DTO from array
$dto = Eligibility270DTO::fromArray([
    'subscriber_id' => '123456789',
    'subscriber_first_name' => 'John',
    'subscriber_last_name' => 'Doe',
    'subscriber_date_of_birth' => '19800101',
    'subscriber_gender' => 'M',
    'inquiries' => [
        ['service_type_code' => '30']
    ]
]);

// Build ANSI content from DTO
$builder = new X12Builder();
$ansiContent = $builder->buildFrom270DTO($dto);
```

### Using Transaction-Specific Delimiters

```php
use DolmatovDev\X12Parser\Facades\X12Parser;

// Parse with custom delimiters for a specific transaction type
$result = X12Parser::parseFromFile('path/to/legacy_file.txt', '270');

// Build with custom delimiters
$ansiContent = X12Parser::buildFromJson($jsonData, '270', '270');

// Using the Parser directly with custom delimiters
use DolmatovDev\X12Parser\Parser;

$parser = new Parser();
$parser->setDelimitersForTransaction('270'); // Uses config from x12-parser.transaction_delimiters.270
$result = $parser->parseContent($content, '270');

// Set custom delimiters manually
$parser->setDelimiters([
    'segment' => '|',
    'element' => '^',
    'sub_element' => '&',
]);
```

### Console Commands

The package provides several Artisan commands for X12 file processing:

```bash
# Parse an X12 file and output JSON
php artisan x12:parse path/to/file.txt

# Parse and save to file with pretty printing
php artisan x12:parse path/to/file.txt --output=output.json --pretty

# Validate an X12 file structure
php artisan x12:validate path/to/file.txt

# Build X12 file from JSON
php artisan x12:build path/to/data.json --output=output.txt --type=270
```

**Available Commands:**

- `x12:parse {file}` - Parse X12 file to JSON
  - `--output=` - Save output to file (optional)
  - `--pretty` - Pretty print JSON output

- `x12:validate {file}` - Validate X12 file structure and content

- `x12:build {json-file}` - Build X12 file from JSON data
  - `--output=` - Save output to file (optional)
  - `--type=270` - Transaction type (default: 270)

### Using Custom File Naming

```php
use DolmatovDev\X12Parser\Facades\X12Parser;

// Build and save with auto-generated filename
$filePath = X12Parser::buildAndSaveWithAutoName($jsonData, '270');

// Build and save with custom naming pattern
$filePath = X12Parser::buildAndSaveWithAutoName(
    $jsonData, 
    '270', 
    ['provider_id' => '12345', 'member_id' => '67890'],
    'claim_{provider_id}_{member_id}_{timestamp}.txt'
);

// Generate filename only
$fileName = X12Parser::generateFileName('270', ['provider_id' => '12345']);

// Get available placeholders
$placeholders = X12Parser::getAvailablePlaceholders();

// Reset sequence counter
X12Parser::resetSequence();

// Set sequence counter
X12Parser::setSequence(1000);
```

## 📄 Supported Transaction Types

### 270 - Eligibility/Benefit Inquiry

The 270 transaction is used to inquire about a subscriber's eligibility and benefits.

#### Required Segments:
- `ISA` - Interchange Control Header
- `GS` - Functional Group Header
- `ST` - Transaction Set Header
- `BHT` - Beginning of Hierarchical Transaction
- `HL` - Hierarchical Level
- `NM1` - Subscriber Name
- `SE` - Transaction Set Trailer
- `GE` - Functional Group Trailer
- `IEA` - Interchange Control Trailer

#### Optional Segments:
- `TRN` - Subscriber Trace Number
- `DMG` - Subscriber Demographics
- `DTP` - Date/Time Reference
- `EQ` - Subscriber Eligibility or Benefit Inquiry
- `QTY` - Quantity

#### Sample 270 File:

```
ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *240101*1200*U*00401*000000001*0*P*>~
GS*HS*SENDER*RECEIVER*20240101*1200*1*X*005010X279A1~
ST*270*0001~
BHT*0022*13*123456789*20240101*1200*RT~
HL*1**20*1~
NM1*IL*1*DOE*JOHN*A*DR*MI*123456789~
TRN*1*987654321*9123456789~
DMG*D8*19800101*M~
EQ*30~
SE*8*0001~
GE*1*1~
IEA*1*000000001~
```

## 🧪 Testing

Run the test suite:

```bash
composer test
```

### Test Examples

```php
use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\Validators\Validator270;

// Test parser
$parser = new Parser();
$result = $parser->parseContent('ST*270*0001~');
$this->assertTrue($result->isSuccessful());

// Test validator
$validator = new Validator270();
$segments = ['ST*270*0001', 'SE*2*0001'];
$result = $validator->validate($segments);
$this->assertFalse($result->isSuccessful()); // Missing required segments
```

## 🔍 Validation

The package provides comprehensive validation for ANSI files:

### Validation Features:
- **Segment Order**: Ensures segments appear in the correct order
- **Required Segments**: Validates all required segments are present
- **Element Format**: Validates element formats and lengths
- **Business Rules**: Enforces business logic specific to each transaction type
- **Input Sanitization**: Removes control characters and validates X12-safe content

### Validation Result:

```php
$result = X12Parser::validateFile('file.txt');

if ($result->isSuccessful()) {
    echo "File is valid!";
} else {
    echo "Errors: " . implode(', ', $result->errors);
    echo "Warnings: " . implode(', ', $result->warnings);
}
```

## 🏗️ Architecture

The package follows clean architecture principles with optimized code organization:

```
src/
├── Services/           # Business logic services
│   ├── AnsiParserService.php
│   ├── ValidatorFactory.php
│   ├── FileNamingService.php
│   └── InputSanitizerService.php
├── Validators/         # Transaction validators
│   ├── AnsiValidatorInterface.php
│   └── Validator270.php
├── DTO/               # Data Transfer Objects
│   ├── ValidationResult.php
│   ├── ParseResult.php
│   └── Eligibility270DTO.php
├── Repositories/      # File operations
│   └── FileRepository.php
├── Builder/           # ANSI file building
│   └── X12Builder.php
├── Traits/            # Shared functionality
│   ├── HasDelimiters.php
│   └── HasConfig.php
├── Facades/           # Laravel facades
│   └── X12Parser.php
├── Exceptions/        # Custom exceptions
│   ├── AnsiFileException.php
│   ├── InvalidSegmentException.php
│   └── UnsupportedTransactionTypeException.php
├── Console/           # Artisan commands
│   ├── ParseX12Command.php
│   ├── ValidateX12Command.php
│   └── BuildX12Command.php
└── Parser.php         # Main parser
```

### Key Architectural Improvements:

- **Shared Traits**: `HasDelimiters` and `HasConfig` traits eliminate code duplication
- **Configuration-Driven**: All services use centralized configuration with fallbacks
- **Clean Separation**: Clear separation between parsing, validation, and building
- **Extensible Design**: Easy to add new transaction types and validators
- **Optimized Performance**: Reduced memory footprint and improved execution speed

## 🔧 Extending the Package

### Adding New Transaction Types

1. **Create Validator:**

```php
namespace DolmatovDev\X12Parser\Validators;

use DolmatovDev\X12Parser\DTO\ValidationResult;

class Validator271 implements AnsiValidatorInterface
{
    public function validate(array $segments): ValidationResult
    {
        // Implement validation logic
    }

    public function getTransactionType(): string
    {
        return '271';
    }

    public function getRequiredSegments(): array
    {
        return ['ISA', 'GS', 'ST', 'SE', 'GE', 'IEA'];
    }

    public function getOptionalSegments(): array
    {
        return [];
    }
}
```

2. **Update Configuration:**

```php
// In config/x12-parser.php
    'transaction_types' => [
        '270' => \DolmatovDev\X12Parser\Validators\Validator270::class,
        '271' => \DolmatovDev\X12Parser\Validators\Validator271::class,
    ],
```

The `ValidatorFactory` will automatically load the new validator from configuration.

## 📚 API Reference

### AnsiParserService

| Method | Description |
|--------|-------------|
| `parseFile(string $filePath): array` | Parse ANSI file to array |
| `parseFileToJson(string $filePath, bool $prettyPrint = true): string` | Parse ANSI file to JSON |
| `buildFromJson(array $jsonData, string $transactionType = '270'): string` | Build ANSI from JSON |
| `buildFrom270DTO(Eligibility270DTO $dto): string` | Build ANSI from DTO |
| `saveToFile(string $content, string $filePath): bool` | Save content to file |
| `buildAndSaveWithAutoName(array $jsonData, string $transactionType, array $data, ?string $customPattern, ?string $outputDirectory): string` | Build and save with auto-generated filename |
| `generateFileName(string $transactionType, array $data, ?string $customPattern): string` | Generate filename for transaction type |
| `getAvailablePlaceholders(): array` | Get available placeholders for file naming |
| `resetSequence(): void` | Reset sequence counter |
| `setSequence(int $sequence): void` | Set sequence counter |
| `validateFile(string $filePath): ValidationResult` | Validate file |
| `getSupportedTransactionTypes(): array` | Get supported types |
| `isTransactionTypeSupported(string $transactionType): bool` | Check if transaction type is supported |
| `getFileInfo(string $filePath): array` | Get file information without full parsing |

### Parser & X12Builder

Both classes use the `HasDelimiters` trait, providing these methods:

| Method | Description |
|--------|-------------|
| `setDelimitersForTransaction(string $transactionType): void` | Set delimiters from config |
| `setDelimiters(array $delimiters): void` | Set custom delimiters |
| `getDelimiters(): array` | Get current delimiters |
| `getDefaultDelimiters(): array` | Get default delimiters |

### ValidationResult

| Property | Type | Description |
|----------|------|-------------|
| `success` | bool | Whether validation was successful |
| `data` | array|null | Parsed data (if successful) |
| `errors` | array | Validation errors |
| `warnings` | array | Validation warnings |
| `transactionType` | string|null | Detected transaction type |

### Eligibility270DTO

| Property | Type | Description |
|----------|------|-------------|
| `subscriberId` | string | Subscriber's unique identifier |
| `subscriberFirstName` | string | Subscriber's first name |
| `subscriberLastName` | string | Subscriber's last name |
| `subscriberDateOfBirth` | string|null | Date of birth (YYYYMMDD) |
| `subscriberGender` | string|null | Gender (M/F) |
| `inquiries` | array | Benefit inquiries |

## 🚀 Performance & Optimization

### Recent Improvements:

- **Code Duplication Reduced**: ~7% reduction in total lines of code
- **Shared Traits**: Eliminated duplicate delimiter and configuration handling
- **Optimized Configuration**: Centralized config access with intelligent fallbacks
- **Improved Memory Usage**: Better resource management and reduced overhead
- **Enhanced Testability**: Instance-based services instead of static methods

### Best Practices:

- **Use Configuration**: Leverage the configuration system for customization
- **Leverage Traits**: Extend functionality using the provided traits
- **Input Sanitization**: Always use the built-in sanitization for security
- **Error Handling**: Implement proper exception handling for production use

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This package is open-sourced software licensed under the **MIT License**. See the [LICENSE](LICENSE) file for the full license text.

### MIT License

```
MIT License

Copyright (c) 2024 Danila Dolmatov

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

### License Summary

- **License Type**: MIT License
- **Copyright**: © 2024 Danila Dolmatov
- **Permissions**: 
  - ✅ Commercial use
  - ✅ Modification
  - ✅ Distribution
  - ✅ Private use
- **Limitations**: 
  - ❌ No liability
  - ❌ No warranty
- **Conditions**: Include license and copyright notice

For more information about the MIT License, visit [opensource.org/licenses/MIT](https://opensource.org/licenses/MIT).

## 🔗 Links

- [X12.org](https://x12.org) - Official X12 standards
- [270 Transaction Specification](https://x12.org/codes/270-eligibility-benefit-inquiry)
- [Laravel Documentation](https://laravel.com/docs)

## 🆘 Support

For support, please open an issue on GitHub or contact Danila Dolmatov (danila@autosport.by). 