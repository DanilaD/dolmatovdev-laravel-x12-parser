<?php

namespace DolmatovDev\X12Parser;

use Illuminate\Support\ServiceProvider;
use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\Builder\X12Builder;
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Services\ValidatorFactory;
use DolmatovDev\X12Parser\Services\FileNamingService;
use DolmatovDev\X12Parser\Services\InputSanitizerService;
use DolmatovDev\X12Parser\Repositories\FileRepository;

/**
 * Service provider for the Laravel ANSI package.
 * 
 * This provider registers all the necessary services and bindings
 * for the ANSI file processing package.
 */
class X12ParserServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__.'/../config/x12-parser.php', 'x12-parser');

        // Register core services
        $this->app->singleton(Parser::class);
        $this->app->singleton(X12Builder::class);
        $this->app->singleton(ValidatorFactory::class);
        $this->app->singleton(FileRepository::class);
        $this->app->singleton(FileNamingService::class);
        $this->app->singleton(InputSanitizerService::class);
        $this->app->singleton(AnsiParserService::class);

        // Bind the main service to the facade
        $this->app->bind('x12-parser', function ($app) {
            return new AnsiParserService(
                $app->make(Parser::class),
                $app->make(ValidatorFactory::class),
                $app->make(X12Builder::class),
                $app->make(FileRepository::class),
                $app->make(FileNamingService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/x12-parser.php' => config_path('x12-parser.php'),
            ], 'x12-parser-config');

            // Publish stubs
            $this->publishes([
                __DIR__.'/../resources/stubs' => resource_path('stubs/x12-parser'),
            ], 'x12-parser-stubs');

            // Register Artisan commands
            $this->commands([
                \DolmatovDev\X12Parser\Console\ParseX12Command::class,
                \DolmatovDev\X12Parser\Console\ValidateX12Command::class,
                \DolmatovDev\X12Parser\Console\BuildX12Command::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     * 
     * @return array
     */
    public function provides(): array
    {
        return [
            'x12-parser',
            Parser::class,
            X12Builder::class,
            ValidatorFactory::class,
            FileRepository::class,
            FileNamingService::class,
            InputSanitizerService::class,
            AnsiParserService::class,
        ];
    }
} 