<?php

/**
 * Example: Using Transaction-Specific Delimiters
 * 
 * This example demonstrates how to use custom delimiters for different
 * transaction types in the X12 Parser package.
 */

// Note: This example requires the package to be installed in a Laravel project
// or the autoloader to be available. For standalone usage, you would need to:
// require_once __DIR__ . '/../vendor/autoload.php';

// For demonstration purposes, we'll show the code structure
// In a real Laravel application, these classes would be auto-loaded

use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\Builder\X12Builder;

echo "=== X12 Parser - Transaction-Specific Delimiters Example ===\n\n";

// 1. Default Delimiters
echo "1. Using Default Delimiters:\n";
$parser = new Parser();
$builder = new X12Builder();

echo "Default delimiters: " . json_encode($parser->getDelimiters()) . "\n\n";

// 2. Custom Delimiters for Specific Transaction Type
echo "2. Setting Custom Delimiters for Transaction Type '270':\n";
$parser->setDelimitersForTransaction('270');
$builder->setDelimitersForTransaction('270');

echo "Current delimiters: " . json_encode($parser->getDelimiters()) . "\n\n";

// 3. Manual Custom Delimiters
echo "3. Setting Manual Custom Delimiters:\n";
$customDelimiters = [
    'segment' => '|',
    'element' => '^',
    'sub_element' => '&',
];

$parser->setDelimiters($customDelimiters);
$builder->setDelimiters($customDelimiters);

echo "Custom delimiters: " . json_encode($parser->getDelimiters()) . "\n\n";

// 4. Testing with Standard Delimiters (should work)
echo "4. Testing with Standard Delimiters:\n";
$standardContent = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *240101*1200*U*00401*000000001*0*P*>~ST*270*0001~BHT*0022*13*10001234*20240101*1200~HL*1**20*1~NM1*IL*1*DOE*JOHN****MI*123456789~EQ*30~SE*5*0001~GE*1*1~IEA*1*000000001~";

// Reset to default delimiters
$parser->setDelimiters($parser->getDefaultDelimiters());
$result = $parser->parseContent($standardContent, '270');

if ($result->isSuccessful()) {
    echo "✅ Successfully parsed with standard delimiters!\n";
    echo "Transaction type: " . $result->transactionType . "\n";
    echo "Number of segments: " . $result->getSegmentCount() . "\n";
} else {
    echo "❌ Failed to parse: " . implode(', ', $result->errors) . "\n";
}

echo "\n5. Testing Delimiter Methods:\n";
echo "Default delimiters: " . json_encode($parser->getDefaultDelimiters()) . "\n";
echo "Current delimiters: " . json_encode($parser->getDelimiters()) . "\n";
echo "Can set custom delimiters: " . ($parser->getDelimiters() !== $parser->getDefaultDelimiters() ? "Yes" : "No") . "\n";

echo "\n=== Example Complete ===\n"; 