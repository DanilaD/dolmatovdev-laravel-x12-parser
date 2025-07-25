<?php

namespace DolmatovDev\X12Parser\Console;

use Illuminate\Console\Command;
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;

/**
 * Artisan command to parse X12 files.
 */
class ParseX12Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x12:parse {file : Path to the X12 file to parse} 
                            {--output= : Output file path for JSON (optional)}
                            {--pretty : Pretty print JSON output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse an X12 file and output JSON data';

    /**
     * Execute the console command.
     */
    public function handle(AnsiParserService $parserService): int
    {
        $filePath = $this->argument('file');
        $outputPath = $this->option('output');
        $prettyPrint = $this->option('pretty');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        try {
            $this->info("Parsing X12 file: {$filePath}");

            if ($outputPath) {
                // Parse and save to file
                $result = $parserService->parseAndSaveAsJson($filePath, $outputPath, $prettyPrint);
                if ($result) {
                    $this->info("Successfully parsed and saved to: {$outputPath}");
                    return 0;
                } else {
                    $this->error("Failed to save output to: {$outputPath}");
                    return 1;
                }
            } else {
                // Parse and output to console
                $jsonData = $parserService->parseFileToJson($filePath, $prettyPrint);
                $this->line($jsonData);
                return 0;
            }
        } catch (AnsiFileException $e) {
            $this->error("Parsing failed: {$e->getMessage()}");
            return 1;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return 1;
        }
    }
} 