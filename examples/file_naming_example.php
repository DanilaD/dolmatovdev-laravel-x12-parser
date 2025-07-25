<?php

/**
 * Example: Custom File Naming
 * 
 * This example demonstrates how to use custom file naming patterns
 * and placeholders in the X12 Parser package.
 */

// Note: This example requires the package to be installed in a Laravel project
// or the autoloader to be available. For standalone usage, you would need to:
// require_once __DIR__ . '/../vendor/autoload.php';

// For demonstration purposes, we'll show the code structure
// In a real Laravel application, these classes would be auto-loaded

use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Services\FileNamingService;

echo "=== X12 Parser - Custom File Naming Example ===\n\n";

// Initialize services
$fileNamingService = new FileNamingService();
$ansiService = new AnsiParserService(
    new \DolmatovDev\X12Parser\Parser(),
    new \DolmatovDev\X12Parser\Services\ValidatorFactory(),
    new \DolmatovDev\X12Parser\Builder\X12Builder(),
    new \DolmatovDev\X12Parser\Repositories\FileRepository(),
    $fileNamingService
);

// Sample data for 270 transaction
$jsonData = [
    'subscriber' => [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'memberId' => '123456789',
        'dateOfBirth' => '1980-01-01',
        'gender' => 'M'
    ],
    'inquiries' => [
        ['serviceTypeCode' => '30', 'medicalProcedureCode' => '99213']
    ],
    'provider_id' => 'PROVIDER123',
    'payer_id' => 'PAYER456',
    'member_id' => '123456789'
];

echo "1. Default File Naming:\n";
$defaultFileName = $fileNamingService->generateFileName('270');
echo "Default filename: {$defaultFileName}\n\n";

echo "2. Custom Pattern with Data:\n";
$customFileName = $fileNamingService->generateFileName('270', $jsonData, 'eligibility_{provider_id}_{member_id}_{timestamp}.txt');
echo "Custom filename: {$customFileName}\n\n";

echo "3. Using Different Patterns:\n";
$patterns = [
    'claim_{provider_id}_{date}_{sequence}.txt',
    'payment_{payer_id}_{time}_{random}.txt',
    'inquiry_{transaction_type}_{member_id}_{timestamp}.txt'
];

foreach ($patterns as $pattern) {
    $fileName = $fileNamingService->generateFileName('270', $jsonData, $pattern);
    echo "Pattern: {$pattern}\n";
    echo "Result: {$fileName}\n\n";
}

echo "4. Available Placeholders:\n";
$placeholders = $fileNamingService->getAvailablePlaceholders();
foreach ($placeholders as $placeholder => $description) {
    echo "  {$placeholder}: {$description}\n";
}

echo "\n5. Sequence Management:\n";
echo "Current sequence: " . \DolmatovDev\X12Parser\Services\FileNamingService::getCurrentSequence() . "\n";

// Generate a few files to see sequence increment
for ($i = 1; $i <= 3; $i++) {
    $fileName = $fileNamingService->generateFileName('270', [], 'test_{sequence}.txt');
    echo "File {$i}: {$fileName}\n";
}

echo "\n6. Reset Sequence:\n";
\DolmatovDev\X12Parser\Services\FileNamingService::resetSequence();
echo "Sequence after reset: " . \DolmatovDev\X12Parser\Services\FileNamingService::getCurrentSequence() . "\n";

echo "\n=== Example Complete ===\n"; 