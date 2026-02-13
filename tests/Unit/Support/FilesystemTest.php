<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Support;

use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Tests\TestCase;
use RuntimeException;

/**
 * Unit tests for Filesystem class.
 *
 * Tests filesystem operations:
 * - File existence checking
 * - Reading and writing files
 * - Directory operations
 * - File listing and globbing
 * - Error handling
 */
class FilesystemTest extends TestCase
{
    private Filesystem $filesystem;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Filesystem::make();
        $this->testDir = sys_get_temp_dir() . '/phive-fs-test-' . uniqid();
        $this->filesystem->makeDirectory($this->testDir);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->deleteDirectory($this->testDir);
        }

        parent::tearDown();
    }

    /**
     * Test filesystem can be instantiated.
     */
    public function test_can_instantiate_filesystem(): void
    {
        $this->assertInstanceOf(Filesystem::class, $this->filesystem);
    }

    /**
     * Test exists returns true for existing path.
     */
    public function test_exists_returns_true_for_existing_path(): void
    {
        $this->assertTrue($this->filesystem->exists($this->testDir));
    }

    /**
     * Test exists returns false for non-existent path.
     */
    public function test_exists_returns_false_for_non_existent_path(): void
    {
        $this->assertFalse($this->filesystem->exists($this->testDir . '/nonexistent'));
    }

    /**
     * Test isFile returns true for files.
     */
    public function test_is_file_returns_true_for_files(): void
    {
        $file = $this->testDir . '/test.txt';
        $this->filesystem->write($file, 'content');

        $this->assertTrue($this->filesystem->isFile($file));
    }

    /**
     * Test isFile returns false for directories.
     */
    public function test_is_file_returns_false_for_directories(): void
    {
        $this->assertFalse($this->filesystem->isFile($this->testDir));
    }

    /**
     * Test isDirectory returns true for directories.
     */
    public function test_is_directory_returns_true_for_directories(): void
    {
        $this->assertTrue($this->filesystem->isDirectory($this->testDir));
    }

    /**
     * Test isDirectory returns false for files.
     */
    public function test_is_directory_returns_false_for_files(): void
    {
        $file = $this->testDir . '/test.txt';
        $this->filesystem->write($file, 'content');

        $this->assertFalse($this->filesystem->isDirectory($file));
    }

    /**
     * Test can write and read file.
     */
    public function test_can_write_and_read_file(): void
    {
        $file = $this->testDir . '/test.txt';
        $content = 'Hello, World!';

        $this->filesystem->write($file, $content);
        $read = $this->filesystem->read($file);

        $this->assertSame($content, $read);
    }

    /**
     * Test write creates parent directories.
     */
    public function test_write_creates_parent_directories(): void
    {
        $file = $this->testDir . '/nested/dir/test.txt';

        $this->filesystem->write($file, 'content');

        $this->assertTrue($this->filesystem->exists($file));
        $this->assertTrue($this->filesystem->isDirectory($this->testDir . '/nested/dir'));
    }

    /**
     * Test read throws exception for non-existent file.
     */
    public function test_read_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->filesystem->read($this->testDir . '/nonexistent.txt');
    }

    /**
     * Test read throws exception for directory.
     */
    public function test_read_throws_exception_for_directory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path is not a file');

        $this->filesystem->read($this->testDir);
    }

    /**
     * Test makeDirectory creates directory.
     */
    public function test_make_directory_creates_directory(): void
    {
        $dir = $this->testDir . '/newdir';

        $this->filesystem->makeDirectory($dir);

        $this->assertTrue($this->filesystem->exists($dir));
        $this->assertTrue($this->filesystem->isDirectory($dir));
    }

    /**
     * Test makeDirectory with recursive creates nested directories.
     */
    public function test_make_directory_with_recursive_creates_nested_directories(): void
    {
        $dir = $this->testDir . '/nested/deep/dir';

        $this->filesystem->makeDirectory($dir, 0755, true);

        $this->assertTrue($this->filesystem->exists($dir));
    }

    /**
     * Test makeDirectory is idempotent.
     */
    public function test_make_directory_is_idempotent(): void
    {
        $dir = $this->testDir . '/existingdir';

        $this->filesystem->makeDirectory($dir);
        $this->filesystem->makeDirectory($dir); // Should not throw

        $this->assertTrue($this->filesystem->exists($dir));
    }

    /**
     * Test delete removes file.
     */
    public function test_delete_removes_file(): void
    {
        $file = $this->testDir . '/test.txt';
        $this->filesystem->write($file, 'content');

        $this->filesystem->delete($file);

        $this->assertFalse($this->filesystem->exists($file));
    }

    /**
     * Test delete is idempotent.
     */
    public function test_delete_is_idempotent(): void
    {
        $file = $this->testDir . '/test.txt';
        $this->filesystem->write($file, 'content');

        $this->filesystem->delete($file);
        $this->filesystem->delete($file); // Should not throw

        $this->assertFalse($this->filesystem->exists($file));
    }

    /**
     * Test delete throws exception for directory.
     */
    public function test_delete_throws_exception_for_directory(): void
    {
        $dir = $this->testDir . '/subdir';
        $this->filesystem->makeDirectory($dir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path is not a file');

        $this->filesystem->delete($dir);
    }

    /**
     * Test deleteDirectory removes directory recursively.
     */
    public function test_delete_directory_removes_directory_recursively(): void
    {
        $dir = $this->testDir . '/subdir';
        $this->filesystem->makeDirectory($dir);
        $this->filesystem->write($dir . '/file.txt', 'content');

        $this->filesystem->deleteDirectory($dir);

        $this->assertFalse($this->filesystem->exists($dir));
    }

    /**
     * Test files returns list of files.
     */
    public function test_files_returns_list_of_files(): void
    {
        $this->filesystem->write($this->testDir . '/file1.txt', 'content');
        $this->filesystem->write($this->testDir . '/file2.txt', 'content');
        $this->filesystem->makeDirectory($this->testDir . '/subdir');

        $files = $this->filesystem->files($this->testDir);

        $this->assertCount(2, $files);
        $this->assertContains('file1.txt', $files);
        $this->assertContains('file2.txt', $files);
    }

    /**
     * Test directories returns list of directories.
     */
    public function test_directories_returns_list_of_directories(): void
    {
        $this->filesystem->makeDirectory($this->testDir . '/dir1');
        $this->filesystem->makeDirectory($this->testDir . '/dir2');
        $this->filesystem->write($this->testDir . '/file.txt', 'content');

        $dirs = $this->filesystem->directories($this->testDir);

        $this->assertCount(2, $dirs);
        $this->assertContains('dir1', $dirs);
        $this->assertContains('dir2', $dirs);
    }

    /**
     * Test glob finds matching files.
     */
    public function test_glob_finds_matching_files(): void
    {
        $this->filesystem->write($this->testDir . '/test1.txt', 'content');
        $this->filesystem->write($this->testDir . '/test2.txt', 'content');
        $this->filesystem->write($this->testDir . '/other.md', 'content');

        $matches = $this->filesystem->glob($this->testDir . '/*.txt');

        $this->assertCount(2, $matches);
    }

    /**
     * Test lastModified returns modification time.
     */
    public function test_last_modified_returns_modification_time(): void
    {
        $file = $this->testDir . '/test.txt';
        $this->filesystem->write($file, 'content');

        $mtime = $this->filesystem->lastModified($file);

        $this->assertIsInt($mtime);
        $this->assertGreaterThan(0, $mtime);
    }

    /**
     * Test lastModified throws exception for non-existent file.
     */
    public function test_last_modified_throws_exception_for_non_existent_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->filesystem->lastModified($this->testDir . '/nonexistent.txt');
    }

    /**
     * Test allFiles returns all files recursively.
     */
    public function test_all_files_returns_all_files_recursively(): void
    {
        $this->filesystem->write($this->testDir . '/file1.txt', 'content');
        $this->filesystem->makeDirectory($this->testDir . '/subdir');
        $this->filesystem->write($this->testDir . '/subdir/file2.txt', 'content');
        $this->filesystem->makeDirectory($this->testDir . '/subdir/nested');
        $this->filesystem->write($this->testDir . '/subdir/nested/file3.txt', 'content');

        $files = $this->filesystem->allFiles($this->testDir);

        $this->assertCount(3, $files);
        $this->assertContains('file1.txt', $files);
        $this->assertContains('subdir/file2.txt', $files);
        $this->assertContains('subdir/nested/file3.txt', $files);
    }

    /**
     * Test allFiles returns empty array for non-existent directory.
     */
    public function test_all_files_returns_empty_array_for_non_existent_directory(): void
    {
        $files = $this->filesystem->allFiles($this->testDir . '/nonexistent');

        $this->assertSame([], $files);
    }
}
