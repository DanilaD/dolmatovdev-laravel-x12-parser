<?php

namespace DolmatovDev\X12Parser\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for ANSI file operations.
 * 
 * This facade provides easy access to ANSI file parsing, validation,
 * and building operations.
 * 
 * @method static array parseFile(string $filePath)
 * @method static string parseFileToJson(string $filePath, bool $prettyPrint = true)
 * @method static string buildFromJson(array $jsonData, string $transactionType = '270')
 * @method static string buildFrom270DTO(\DolmatovDev\X12Parser\DTO\Eligibility270DTO $dto)
 * @method static bool saveToFile(string $content, string $filePath)
 * @method static bool buildAndSave(array $jsonData, string $outputPath, string $transactionType = '270')
 * @method static bool parseAndSaveAsJson(string $inputPath, string $outputPath, bool $prettyPrint = true)
 * @method static \DolmatovDev\X12Parser\DTO\ValidationResult validateFile(string $filePath)
 * @method static array getSupportedTransactionTypes()
 * @method static bool isTransactionTypeSupported(string $transactionType)
 * @method static array getFileInfo(string $filePath)
 */
class X12Parser extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'x12-parser';
    }
} 