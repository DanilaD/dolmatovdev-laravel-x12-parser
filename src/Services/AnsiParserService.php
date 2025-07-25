<?php

namespace DolmatovDev\X12Parser\Services;

use DolmatovDev\X12Parser\Parser;
use DolmatovDev\X12Parser\Builder\X12Builder;
use DolmatovDev\X12Parser\DTO\ParseResult;
use DolmatovDev\X12Parser\DTO\ValidationResult;
use DolmatovDev\X12Parser\DTO\Eligibility270DTO;
use DolmatovDev\X12Parser\Repositories\FileRepository;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;
use DolmatovDev\X12Parser\Traits\HasConfig;

/**
 * Main service for ANSI file processing operations.
 * 
 * This service orchestrates parsing, validation, and building
 * operations for ANSI X12 files.
 */
class AnsiParserService
{
    use HasConfig;

    /**
     * Create a new AnsiParserService instance.
     */
    public function __construct(
        private Parser $parser,
        private ValidatorFactory $validatorFactory,
        private X12Builder $builder,
        private FileRepository $fileRepository,
        private ?FileNamingService $fileNamingService = null
    ) {
        // Initialize FileNamingService if not provided
        if ($this->fileNamingService === null) {
            $this->fileNamingService = new FileNamingService();
        }
    }

    /**
     * Parse an ANSI file and return structured data.
     * 
     * @param string $filePath Path to the ANSI file
     * @return array Parsed and validated data
     * @throws AnsiFileException
     */
    public function parseFile(string $filePath): array
    {
        // Parse the file
        $parseResult = $this->parser->parseFile($filePath);
        
        if (!$parseResult->isSuccessful()) {
            throw new AnsiFileException('Failed to parse file: ' . implode(', ', $parseResult->errors));
        }

        // Get the appropriate validator
        $validator = $this->validatorFactory->make($parseResult->transactionType);

        // Set delimiters on validator if it supports it
        if (method_exists($validator, 'setDelimiters')) {
            $validator->setDelimiters($this->parser->getDelimiters());
        }

        // Validate the parsed segments
        $validationResult = $validator->validate($parseResult->segments);

        if (!$validationResult->isSuccessful()) {
            throw new AnsiFileException('Validation failed: ' . implode(', ', $validationResult->errors));
        }

        return $validationResult->data;
    }

    /**
     * Parse an ANSI file and return JSON.
     * 
     * @param string $filePath Path to the ANSI file
     * @param bool $prettyPrint Whether to pretty print the JSON
     * @return string JSON representation
     * @throws AnsiFileException
     */
    public function parseFileToJson(string $filePath, bool $prettyPrint = true): string
    {
        $data = $this->parseFile($filePath);
        
        $options = $prettyPrint ? JSON_PRETTY_PRINT : 0;
        
        return json_encode($data, $options);
    }

    /**
     * Build an ANSI file from JSON data.
     * 
     * @param array $jsonData JSON data to build from
     * @param string $transactionType Transaction type (e.g., '270')
     * @return string ANSI formatted content
     * @throws AnsiFileException
     */
    public function buildFromJson(array $jsonData, string $transactionType = '270'): string
    {
        return $this->builder->buildFromArray($jsonData, $transactionType);
    }

    /**
     * Build an ANSI file from Eligibility270DTO.
     * 
     * @param Eligibility270DTO $dto The DTO to build from
     * @return string ANSI formatted content
     * @throws AnsiFileException
     */
    public function buildFrom270DTO(Eligibility270DTO $dto): string
    {
        return $this->builder->buildFrom270DTO($dto);
    }

    /**
     * Save content to a file.
     * 
     * @param string $content Content to save
     * @param string $filePath Path to save the file
     * @return bool True if successful
     */
    public function saveToFile(string $content, string $filePath): bool
    {
        return $this->fileRepository->save($content, $filePath);
    }

    /**
     * Build ANSI file from JSON and save to file.
     * 
     * @param array $jsonData JSON data
     * @param string $outputPath Output file path
     * @param string $transactionType Transaction type
     * @return bool True if successful
     */
    public function buildAndSave(array $jsonData, string $outputPath, string $transactionType = '270'): bool
    {
        $content = $this->buildFromJson($jsonData, $transactionType);
        return $this->saveToFile($content, $outputPath);
    }

    /**
     * Parse ANSI file and save as JSON.
     * 
     * @param string $inputPath Input ANSI file path
     * @param string $outputPath Output JSON file path
     * @param bool $prettyPrint Whether to pretty print JSON
     * @return bool True if successful
     */
    public function parseAndSaveAsJson(string $inputPath, string $outputPath, bool $prettyPrint = true): bool
    {
        $jsonData = $this->parseFileToJson($inputPath, $prettyPrint);
        return $this->saveToFile($jsonData, $outputPath);
    }

    /**
     * Validate an ANSI file without parsing to structured data.
     * 
     * @param string $filePath Path to the ANSI file
     * @return ValidationResult Validation result
     */
    public function validateFile(string $filePath): ValidationResult
    {
        // Parse the file
        $parseResult = $this->parser->parseFile($filePath);
        
        if (!$parseResult->isSuccessful()) {
            return ValidationResult::failure($parseResult->errors);
        }

        // Get the appropriate validator
        $validator = $this->validatorFactory->make($parseResult->transactionType);

        // Set delimiters on validator if it supports it
        if (method_exists($validator, 'setDelimiters')) {
            $validator->setDelimiters($this->parser->getDelimiters());
        }

        // Validate the parsed segments
        return $validator->validate($parseResult->segments);
    }

    /**
     * Get supported transaction types.
     * 
     * @return array Array of supported transaction types
     */
    public function getSupportedTransactionTypes(): array
    {
        return $this->validatorFactory->getSupportedTransactionTypes();
    }

    /**
     * Check if a transaction type is supported.
     * 
     * @param string $transactionType Transaction type to check
     * @return bool True if supported
     */
    public function isTransactionTypeSupported(string $transactionType): bool
    {
        return $this->validatorFactory->isSupported($transactionType);
    }

    /**
     * Get file information without full parsing.
     * 
     * @param string $filePath Path to the ANSI file
     * @return array File information
     */
    public function getFileInfo(string $filePath): array
    {
        $parseResult = $this->parser->parseFile($filePath);
        
        return [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
            'transaction_type' => $parseResult->transactionType,
            'segment_count' => $parseResult->getSegmentCount(),
            'is_valid' => $parseResult->isSuccessful(),
            'errors' => $parseResult->errors,
            'warnings' => $parseResult->warnings,
        ];
    }

    /**
     * Build ANSI file from JSON and save with auto-generated filename.
     * 
     * @param array $jsonData JSON data
     * @param string $transactionType Transaction type
     * @param array $data Optional data for filename placeholders
     * @param string|null $customPattern Optional custom naming pattern
     * @param string|null $outputDirectory Optional output directory
     * @return string Generated file path
     * @throws AnsiFileException
     */
    public function buildAndSaveWithAutoName(
        array $jsonData, 
        string $transactionType = '270', 
        array $data = [], 
        ?string $customPattern = null,
        ?string $outputDirectory = null
    ): string {
        $content = $this->buildFromJson($jsonData, $transactionType);
        
        // Generate filename
        $fileName = $this->fileNamingService->generateFileName($transactionType, $data, $customPattern);
        
        // Determine output directory
        if ($outputDirectory === null) {
            $outputDirectory = $this->getConfig('x12-parser.file_storage.default_path', storage_path('ansi'));
        }
        
        // Ensure directory exists
        if (!is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0755, true);
        }
        
        $filePath = rtrim($outputDirectory, '/') . '/' . $fileName;
        
        // Save the file
        if (!$this->saveToFile($content, $filePath)) {
            throw new AnsiFileException("Failed to save file to: {$filePath}");
        }
        
        return $filePath;
    }

    /**
     * Generate a filename for a transaction type.
     * 
     * @param string $transactionType Transaction type
     * @param array $data Optional data for placeholders
     * @param string|null $customPattern Optional custom pattern
     * @return string Generated filename
     */
    public function generateFileName(string $transactionType, array $data = [], ?string $customPattern = null): string
    {
        return $this->fileNamingService->generateFileName($transactionType, $data, $customPattern);
    }

    /**
     * Get available placeholders for file naming.
     * 
     * @return array Array of available placeholders
     */
    public function getAvailablePlaceholders(): array
    {
        return $this->fileNamingService->getAvailablePlaceholders();
    }

    /**
     * Reset the sequence counter for file naming.
     * 
     * @return void
     */
    public function resetSequence(): void
    {
        $this->fileNamingService->resetSequence();
    }

    /**
     * Set the sequence number for file naming.
     * 
     * @param int $sequence Sequence number to set
     * @return void
     */
    public function setSequence(int $sequence): void
    {
        $this->fileNamingService->setSequence($sequence);
    }
} 