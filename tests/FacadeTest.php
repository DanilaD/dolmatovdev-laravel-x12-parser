<?php

namespace DolmatovDev\X12Parser\Tests;

use Orchestra\Testbench\TestCase;
use DolmatovDev\X12Parser\X12ParserServiceProvider;
use DolmatovDev\X12Parser\Facades\X12Parser;

/**
 * Test the X12Parser facade functionality.
 */
class FacadeTest extends TestCase
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
     * Get package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'X12Parser' => \DolmatovDev\X12Parser\Facades\X12Parser::class,
        ];
    }

    /**
     * Test that the facade can be accessed.
     */
    public function test_facade_can_be_accessed(): void
    {
        $this->assertInstanceOf(
            \DolmatovDev\X12Parser\Services\AnsiParserService::class,
            X12Parser::getFacadeRoot()
        );
    }

    /**
     * Test that the facade can call service methods.
     */
    public function test_facade_can_call_service_methods(): void
    {
        $supportedTypes = X12Parser::getSupportedTransactionTypes();
        
        $this->assertIsArray($supportedTypes);
        $this->assertContains(270, $supportedTypes);
        // Note: 271, 837, 835 are commented out in config for now
        // $this->assertContains('271', $supportedTypes);
        // $this->assertContains('837', $supportedTypes);
        // $this->assertContains('835', $supportedTypes);
    }

    /**
     * Test that the facade can validate transaction types.
     */
    public function test_facade_can_validate_transaction_types(): void
    {
        $this->assertTrue(X12Parser::isTransactionTypeSupported(270));
        // Note: 271, 837, 835 are commented out in config for now
        // $this->assertTrue(X12Parser::isTransactionTypeSupported('271'));
        // $this->assertTrue(X12Parser::isTransactionTypeSupported('837'));
        // $this->assertTrue(X12Parser::isTransactionTypeSupported('835'));
        $this->assertFalse(X12Parser::isTransactionTypeSupported('999'));
    }

    /**
     * Test that the facade can build from JSON.
     */
    public function test_facade_can_build_from_json(): void
    {
        $jsonData = [
            'transaction_type' => '270',
            'sender_id' => 'SENDER',
            'receiver_id' => 'RECEIVER',
            'subscriber_id' => '12345678901',
            'subscriber_first_name' => 'JOHN',
            'subscriber_last_name' => 'DOE',
            'subscriber_date_of_birth' => '19800101',
            'inquiries' => [
                ['service_type_code' => '30']
            ]
        ];

        $x12Content = X12Parser::buildFromJson($jsonData, '270');
        
        $this->assertIsString($x12Content);
        $this->assertStringContainsString('ISA*', $x12Content);
        $this->assertStringContainsString('ST*270*', $x12Content);
        $this->assertStringContainsString('NM1*IL*1*DOE*JOHN', $x12Content);
    }
} 