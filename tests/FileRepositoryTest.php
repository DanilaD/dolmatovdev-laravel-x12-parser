<?php

namespace DolmatovDev\X12Parser\Tests;

use DolmatovDev\X12Parser\Repositories\FileRepository;
use DolmatovDev\X12Parser\Exceptions\AnsiFileException;
use PHPUnit\Framework\TestCase;

class FileRepositoryTest extends TestCase
{
    private FileRepository $repository;
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new FileRepository();
        $this->testDir = sys_get_temp_dir() . '/x12_test';
        $this->testFile = $this->testDir . '/test.txt';
        
        // Create test directory
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files and directories recursively
        $this->removeDirectoryRecursively($this->testDir);
        
        parent::tearDown();
    }

    /**
     * Recursively remove a directory and all its contents
     */
    private function removeDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectoryRecursively($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    public function test_save_and_load_file(): void
    {
        $content = 'ISA*00*          *00*          *ZZ*SENDER*ZZ*RECEIVER*240101*1200*U*00401*000000001*0*P*>~ST*270*0001~SE*2*0001~';
        
        $result = $this->repository->save($content, $this->testFile);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->exists($this->testFile));
        
        $loadedContent = $this->repository->load($this->testFile);
        
        $this->assertEquals($content, $loadedContent);
    }

    public function test_save_file_creates_directory(): void
    {
        $nestedDir = $this->testDir . '/nested/dir';
        $nestedFile = $nestedDir . '/test.txt';
        $content = 'Test content';
        
        $result = $this->repository->save($content, $nestedFile);
        
        $this->assertTrue($result);
        $this->assertTrue(is_dir($nestedDir));
        $this->assertTrue($this->repository->exists($nestedFile));
        
        // Cleanup is handled by tearDown()
    }

    public function test_load_nonexistent_file_throws_exception(): void
    {
        $this->expectException(AnsiFileException::class);
        
        $this->repository->load('nonexistent.txt');
    }

    public function test_exists_returns_false_for_nonexistent_file(): void
    {
        $this->assertFalse($this->repository->exists('nonexistent.txt'));
    }

    public function test_get_size(): void
    {
        $content = 'Test content for size calculation';
        $this->repository->save($content, $this->testFile);
        
        $size = $this->repository->getSize($this->testFile);
        
        $this->assertEquals(strlen($content), $size);
    }

    public function test_get_size_nonexistent_file_throws_exception(): void
    {
        $this->expectException(AnsiFileException::class);
        
        $this->repository->getSize('nonexistent.txt');
    }

    public function test_delete_file(): void
    {
        $content = 'Test content';
        $this->repository->save($content, $this->testFile);
        
        $this->assertTrue($this->repository->exists($this->testFile));
        
        $result = $this->repository->delete($this->testFile);
        
        $this->assertTrue($result);
        $this->assertFalse($this->repository->exists($this->testFile));
    }

    public function test_delete_nonexistent_file_throws_exception(): void
    {
        $this->expectException(AnsiFileException::class);
        
        $this->repository->delete('nonexistent.txt');
    }

    public function test_get_info(): void
    {
        $content = 'Test content for info';
        $this->repository->save($content, $this->testFile);
        
        $info = $this->repository->getInfo($this->testFile);
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('modified', $info);
        $this->assertArrayHasKey('permissions', $info);
        $this->assertEquals(strlen($content), $info['size']);
    }

    public function test_get_info_nonexistent_file_throws_exception(): void
    {
        $this->expectException(AnsiFileException::class);
        
        $this->repository->getInfo('nonexistent.txt');
    }

    public function test_backup_file(): void
    {
        $content = 'Original content';
        $this->repository->save($content, $this->testFile);
        
        $backupPath = $this->repository->backup($this->testFile);
        
        $this->assertTrue(file_exists($backupPath));
        $this->assertEquals($content, file_get_contents($backupPath));
        
        // Clean up backup
        unlink($backupPath);
    }

    public function test_backup_nonexistent_file_throws_exception(): void
    {
        $this->expectException(AnsiFileException::class);
        
        $this->repository->backup('nonexistent.txt');
    }

    public function test_save_with_custom_permissions(): void
    {
        $content = 'Test content';
        $customPermissions = 0644;
        
        $result = $this->repository->save($content, $this->testFile, $customPermissions);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->exists($this->testFile));
        
        $info = $this->repository->getInfo($this->testFile);
        $this->assertEquals($customPermissions, $info['permissions'] & 0777);
    }

    public function test_save_empty_content(): void
    {
        $result = $this->repository->save('', $this->testFile);
        
        $this->assertTrue($result);
        $this->assertTrue($this->repository->exists($this->testFile));
        
        $loadedContent = $this->repository->load($this->testFile);
        $this->assertEquals('', $loadedContent);
    }

    public function test_save_large_content(): void
    {
        $largeContent = str_repeat('A', 10000); // 10KB content
        
        $result = $this->repository->save($largeContent, $this->testFile);
        
        $this->assertTrue($result);
        $this->assertEquals(10000, $this->repository->getSize($this->testFile));
    }
} 