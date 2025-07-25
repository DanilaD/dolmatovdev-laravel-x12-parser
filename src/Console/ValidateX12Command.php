<?php

namespace DolmatovDev\X12Parser\Console;

use Illuminate\Console\Command;
use DolmatovDev\X12Parser\Services\AnsiParserService;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;

/**
 * Artisan command to validate X12 files.
 */
class ValidateX12Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x12:validate {file : Path to the X12 file to validate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate an X12 file structure and content';

    /**
     * Execute the console command.
     */
    public function handle(AnsiParserService $parserService): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        try {
            $this->info("Validating X12 file: {$filePath}");

            $validationResult = $parserService->validateFile($filePath);

            if ($validationResult->isSuccessful()) {
                $this->info("✅ File is valid!");
                $this->info("Transaction Type: " . ($validationResult->data['transaction_type'] ?? 'Unknown'));
                $this->info("Segments Count: " . count($validationResult->data['segments'] ?? []));
                return 0;
            } else {
                $this->error("❌ File validation failed:");
                foreach ($validationResult->errors as $error) {
                    $this->error("  - {$error}");
                }
                return 1;
            }
        } catch (AnsiFileException $e) {
            $this->error("Validation failed: {$e->getMessage()}");
            return 1;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return 1;
        }
    }
} 