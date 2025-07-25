<?php

namespace DolmatovDev\X12Parser\Repositories;

use DolmatovDev\X12Parser\Exceptions\AnsiFileException;

/**
 * Repository for handling file operations.
 * 
 * This class abstracts file system operations for ANSI files,
 * providing a clean interface for saving and loading files.
 */
class FileRepository
{
    /**
     * Save content to a file.
     * 
     * @param string $content Content to save
     * @param string $filePath Path to save the file
     * @return bool True if successful
     * @throws AnsiFileException
     */
    public function save(string $content, string $filePath): bool
    {
        try {
            // Ensure directory exists
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Write content to file
            $bytesWritten = file_put_contents($filePath, $content);
            
            if ($bytesWritten === false) {
                throw new AnsiFileException("Failed to write to file: {$filePath}");
            }

            return true;
        } catch (\Exception $e) {
            throw new AnsiFileException("Error saving file: {$e->getMessage()}");
        }
    }

    /**
     * Load content from a file.
     * 
     * @param string $filePath Path to the file
     * @return string File content
     * @throws AnsiFileException
     */
    public function load(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new AnsiFileException("File not found: {$filePath}");
        }

        try {
            $content = file_get_contents($filePath);
            
            if ($content === false) {
                throw new AnsiFileException("Failed to read file: {$filePath}");
            }

            return $content;
        } catch (\Exception $e) {
            throw new AnsiFileException("Error loading file: {$e->getMessage()}");
        }
    }

    /**
     * Check if a file exists.
     * 
     * @param string $filePath Path to the file
     * @return bool True if file exists
     */
    public function exists(string $filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * Get file size.
     * 
     * @param string $filePath Path to the file
     * @return int File size in bytes
     * @throws AnsiFileException
     */
    public function getSize(string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new AnsiFileException("File not found: {$filePath}");
        }

        $size = filesize($filePath);
        
        if ($size === false) {
            throw new AnsiFileException("Failed to get file size: {$filePath}");
        }

        return $size;
    }

    /**
     * Delete a file.
     * 
     * @param string $filePath Path to the file
     * @return bool True if successful
     * @throws AnsiFileException
     */
    public function delete(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new AnsiFileException("File not found: {$filePath}");
        }

        try {
            $deleted = unlink($filePath);
            
            if (!$deleted) {
                throw new AnsiFileException("Failed to delete file: {$filePath}");
            }

            return true;
        } catch (\Exception $e) {
            throw new AnsiFileException("Error deleting file: {$e->getMessage()}");
        }
    }

    /**
     * Get file information.
     * 
     * @param string $filePath Path to the file
     * @return array File information
     * @throws AnsiFileException
     */
    public function getInfo(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new AnsiFileException("File not found: {$filePath}");
        }

        try {
            $stat = stat($filePath);
            
            if ($stat === false) {
                throw new AnsiFileException("Failed to get file info: {$filePath}");
            }

            return [
                'path' => $filePath,
                'size' => $stat['size'],
                'modified' => $stat['mtime'],
                'created' => $stat['ctime'],
                'permissions' => $stat['mode'],
                'readable' => is_readable($filePath),
                'writable' => is_writable($filePath),
            ];
        } catch (\Exception $e) {
            throw new AnsiFileException("Error getting file info: {$e->getMessage()}");
        }
    }

    /**
     * Create a backup of a file.
     * 
     * @param string $filePath Path to the file
     * @param string|null $backupPath Backup path (optional)
     * @return string Backup file path
     * @throws AnsiFileException
     */
    public function backup(string $filePath, ?string $backupPath = null): string
    {
        if (!file_exists($filePath)) {
            throw new AnsiFileException("File not found: {$filePath}");
        }

        if ($backupPath === null) {
            $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
        }

        try {
            $content = $this->load($filePath);
            $this->save($content, $backupPath);
            
            return $backupPath;
        } catch (\Exception $e) {
            throw new AnsiFileException("Error creating backup: {$e->getMessage()}");
        }
    }
} 