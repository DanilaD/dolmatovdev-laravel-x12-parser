<?php

namespace DolmatovDev\X12Parser\Console;

use Illuminate\Console\Command;
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;

/**
 * Artisan command to build X12 files from JSON data.
 */
class BuildX12Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x12:build {json-file : Path to the JSON file with X12 data} 
                           {--output= : Output file path for X12 (optional)}
                           {--type=270 : Transaction type (270, 271, 837, 835)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build an X12 file from JSON data';

    /**
     * Execute the console command.
     */
    public function handle(AnsiParserService $parserService): int
    {
        $jsonFilePath = $this->argument('json-file');
        $outputPath = $this->option('output');
        $transactionType = $this->option('type');

        if (!file_exists($jsonFilePath)) {
            $this->error("JSON file not found: {$jsonFilePath}");
            return 1;
        }

        try {
            $this->info("Building X12 file from JSON: {$jsonFilePath}");

            // Read and decode JSON
            $jsonContent = file_get_contents($jsonFilePath);
            $jsonData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON: " . json_last_error_msg());
                return 1;
            }

            // Build X12 content
            $x12Content = $parserService->buildFromJson($jsonData, $transactionType);

            if ($outputPath) {
                // Save to file
                $result = $parserService->saveToFile($x12Content, $outputPath);
                if ($result) {
                    $this->info("Successfully built and saved to: {$outputPath}");
                    return 0;
                } else {
                    $this->error("Failed to save output to: {$outputPath}");
                    return 1;
                }
            } else {
                // Output to console
                $this->line($x12Content);
                return 0;
            }
        } catch (AnsiFileException $e) {
            $this->error("Building failed: {$e->getMessage()}");
            return 1;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return 1;
        }
    }
} 