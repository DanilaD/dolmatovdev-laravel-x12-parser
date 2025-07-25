<?php

namespace DolmatovDev\X12Parser\Tests;

use Orchestra\Testbench\TestCase;
use DolmatovDev\X12Parser\X12ParserServiceProvider;

/**
 * Test configuration functionality.
 */
class ConfigurationTest extends TestCase
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            X12ParserServiceProvider::class,
        ];
    }

    /**
     * Test that configuration is properly structured.
     */
    public function test_configuration_structure(): void
    {
        $config = config('x12-parser');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('transaction_types', $config);
        $this->assertArrayHasKey('delimiters', $config);
        $this->assertArrayHasKey('transaction_delimiters', $config);
        $this->assertArrayHasKey('file_naming', $config);
        $this->assertArrayHasKey('validation', $config);
    }

    /**
     * Test that default delimiters are correct.
     */
    public function test_default_delimiters(): void
    {
        $config = config('x12-parser');
        $delimiters = $config['delimiters'];
        
        $this->assertEquals('~', $delimiters['segment']);
        $this->assertEquals('*', $delimiters['element']);
        $this->assertEquals('>', $delimiters['sub_element']);
    }

    /**
     * Test that transaction types are properly configured.
     */
    public function test_transaction_types_configuration(): void
    {
        $config = config('x12-parser');
        $transactionTypes = $config['transaction_types'];
        
        $this->assertArrayHasKey('270', $transactionTypes);
        // Note: 271, 837, 835 are commented out in config for now
        // $this->assertArrayHasKey('271', $transactionTypes);
        // $this->assertArrayHasKey('837', $transactionTypes);
        // $this->assertArrayHasKey('835', $transactionTypes);
        
        $this->assertEquals(
            \DolmatovDev\X12Parser\Validators\Validator270::class,
            $transactionTypes['270']
        );
    }

    /**
     * Test that file naming configuration is present.
     */
    public function test_file_naming_configuration(): void
    {
        $config = config('x12-parser');
        $fileNaming = $config['file_naming'];
        
        $this->assertArrayHasKey('default_pattern', $fileNaming);
        $this->assertArrayHasKey('extension', $fileNaming);
        $this->assertArrayHasKey('placeholders', $fileNaming);
        $this->assertArrayHasKey('use_custom_naming', $fileNaming);
    }

    /**
     * Test that validation configuration is present.
     */
    public function test_validation_configuration(): void
    {
        $config = config('x12-parser');
        $validation = $config['validation'];
        
        $this->assertArrayHasKey('strict_mode', $validation);
        $this->assertArrayHasKey('allow_warnings', $validation);
        $this->assertArrayHasKey('max_segments', $validation);
        $this->assertArrayHasKey('max_segment_length', $validation);
    }
} 