<?php

namespace DolmatovDev\X12Parser\Tests;

use Orchestra\Testbench\TestCase;
use DolmatovDev\X12Parser\X12ParserServiceProvider;
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\Builder\X12Builder;
use DolmatovDev\X12Parser\Services\ValidatorFactory;
use DolmatovDev\X12Parser\Services\FileNamingService;
use DolmatovDev\X12Parser\Services\InputSanitizerService;
use DolmatovDev\X12Parser\Repositories\FileRepository;

/**
 * Test the service provider registration and bindings.
 */
class ServiceProviderTest extends TestCase
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
     * Test that the service provider registers all services correctly.
     */
    public function test_service_provider_registers_services(): void
    {
        // Test that all services are registered as singletons
        $this->assertInstanceOf(Parser::class, app(Parser::class));
        $this->assertInstanceOf(X12Builder::class, app(X12Builder::class));
        $this->assertInstanceOf(ValidatorFactory::class, app(ValidatorFactory::class));
        $this->assertInstanceOf(FileRepository::class, app(FileRepository::class));
        $this->assertInstanceOf(FileNamingService::class, app(FileNamingService::class));
        $this->assertInstanceOf(InputSanitizerService::class, app(InputSanitizerService::class));
        $this->assertInstanceOf(AnsiParserService::class, app(AnsiParserService::class));
    }

    /**
     * Test that the facade binding works correctly.
     */
    public function test_facade_binding_works(): void
    {
        $service = app('x12-parser');
        
        $this->assertInstanceOf(AnsiParserService::class, $service);
    }

    /**
     * Test that services are singletons.
     */
    public function test_services_are_singletons(): void
    {
        $parser1 = app(Parser::class);
        $parser2 = app(Parser::class);
        
        $this->assertSame($parser1, $parser2);
    }

    /**
     * Test that configuration is merged correctly.
     */
    public function test_configuration_is_merged(): void
    {
        $config = config('x12-parser');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('transaction_types', $config);
        $this->assertArrayHasKey('delimiters', $config);
        $this->assertArrayHasKey('file_naming', $config);
    }

    /**
     * Test that supported transaction types are available.
     */
    public function test_supported_transaction_types(): void
    {
        $service = app(AnsiParserService::class);
        $supportedTypes = $service->getSupportedTransactionTypes();
        
        $this->assertContains(270, $supportedTypes);
        // Note: 271, 837, 835 are commented out in config for now
        // $this->assertContains('271', $supportedTypes);
        // $this->assertContains('837', $supportedTypes);
        // $this->assertContains('835', $supportedTypes);
    }
} 