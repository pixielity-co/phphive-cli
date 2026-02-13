<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Services;

use PhpHive\Cli\Services\NameSuggestionService;
use PhpHive\Cli\Support\Filesystem;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for NameSuggestionService class.
 *
 * Tests name suggestion functionality:
 * - Generating alternative names
 * - Selecting best suggestions
 * - Formatting suggestions for display
 */
class NameSuggestionServiceTest extends TestCase
{
    private NameSuggestionService $service;

    private Filesystem $filesystem;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = Filesystem::make();
        $this->testDir = sys_get_temp_dir() . '/phive-test-' . uniqid();
        $this->filesystem->makeDirectory($this->testDir);

        $this->service = NameSuggestionService::make();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->deleteDirectory($this->testDir);
        }

        parent::tearDown();
    }

    /**
     * Test service can be instantiated.
     */
    public function test_can_instantiate_service(): void
    {
        $this->assertInstanceOf(NameSuggestionService::class, $this->service);
    }

    /**
     * Test service can be created using static factory.
     */
    public function test_can_create_service_using_static_factory(): void
    {
        $service = NameSuggestionService::make();

        $this->assertInstanceOf(NameSuggestionService::class, $service);
    }

    /**
     * Test suggest returns array of suggestions.
     */
    public function test_suggest_returns_array_of_suggestions(): void
    {
        $suggestions = $this->service->suggest(
            'my-app',
            'app',
            fn (string $name): bool => true // All names available
        );

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);
    }

    /**
     * Test suggest filters unavailable names.
     */
    public function test_suggest_filters_unavailable_names(): void
    {
        // Create some directories to make names unavailable
        $this->filesystem->makeDirectory($this->testDir . '/my-app-app');
        $this->filesystem->makeDirectory($this->testDir . '/my-app-dev');

        $suggestions = $this->service->suggest(
            'my-app',
            'app',
            fn (string $name): bool => ! $this->filesystem->exists($this->testDir . '/' . $name)
        );

        // Should not include my-app-app or my-app-dev
        $this->assertNotContains('my-app-app', $suggestions);
        $this->assertNotContains('my-app-dev', $suggestions);
    }

    /**
     * Test suggest returns limited number of suggestions.
     */
    public function test_suggest_returns_limited_number_of_suggestions(): void
    {
        $suggestions = $this->service->suggest(
            'my-app',
            'app',
            fn (string $name): bool => true
        );

        // Should return at most 5 suggestions
        $this->assertLessThanOrEqual(5, count($suggestions));
    }

    /**
     * Test suggest includes suffix-based suggestions.
     */
    public function test_suggest_includes_suffix_based_suggestions(): void
    {
        $suggestions = $this->service->suggest(
            'my-app',
            'app',
            fn (string $name): bool => true
        );

        // Should include at least one suffix-based suggestion
        $hasSuffixSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_starts_with($suggestion, 'my-app-')) {
                $hasSuffixSuggestion = true;

                break;
            }
        }

        $this->assertTrue($hasSuffixSuggestion);
    }

    /**
     * Test suggest includes prefix-based suggestions.
     */
    public function test_suggest_includes_prefix_based_suggestions(): void
    {
        $suggestions = $this->service->suggest(
            'app',
            'app',
            fn (string $name): bool => true
        );

        // Should include at least one prefix-based suggestion
        $hasPrefixSuggestion = false;
        foreach ($suggestions as $suggestion) {
            if (str_ends_with($suggestion, '-app')) {
                $hasPrefixSuggestion = true;

                break;
            }
        }

        $this->assertTrue($hasPrefixSuggestion);
    }

    /**
     * Test getBestSuggestion returns best option.
     */
    public function test_get_best_suggestion_returns_best_option(): void
    {
        $suggestions = ['my-app-app', 'my-app-dev', 'my-app-abc'];

        $best = $this->service->getBestSuggestion($suggestions);

        $this->assertIsString($best);
        $this->assertContains($best, $suggestions);
    }

    /**
     * Test getBestSuggestion returns null for empty array.
     */
    public function test_get_best_suggestion_returns_null_for_empty_array(): void
    {
        $best = $this->service->getBestSuggestion([]);

        $this->assertNull($best);
    }

    /**
     * Test getBestSuggestion prefers meaningful suffixes.
     */
    public function test_get_best_suggestion_prefers_meaningful_suffixes(): void
    {
        $suggestions = ['my-app-abc', 'my-app-app', 'my-app-xyz'];

        $best = $this->service->getBestSuggestion($suggestions);

        // Should prefer 'my-app-app' over hash-based suggestions
        $this->assertSame('my-app-app', $best);
    }

    /**
     * Test formatForDisplay returns formatted array.
     */
    public function test_format_for_display_returns_formatted_array(): void
    {
        $suggestions = ['my-app-app', 'my-app-dev', 'my-app-kit'];

        $formatted = $this->service->formatForDisplay($suggestions);

        $this->assertIsArray($formatted);
        $this->assertCount(3, $formatted);
    }

    /**
     * Test formatForDisplay includes recommended marker.
     */
    public function test_format_for_display_includes_recommended_marker(): void
    {
        $suggestions = ['my-app-app', 'my-app-dev'];

        $formatted = $this->service->formatForDisplay($suggestions);

        // At least one suggestion should be marked as recommended
        $hasRecommended = false;
        foreach ($formatted as $display) {
            if (str_contains($display, '(recommended)')) {
                $hasRecommended = true;

                break;
            }
        }

        $this->assertTrue($hasRecommended);
    }

    /**
     * Test formatForDisplay uses numeric keys.
     */
    public function test_format_for_display_uses_numeric_keys(): void
    {
        $suggestions = ['my-app-app', 'my-app-dev'];

        $formatted = $this->service->formatForDisplay($suggestions);

        $this->assertArrayHasKey('1', $formatted);
        $this->assertArrayHasKey('2', $formatted);
    }

    /**
     * Test suggest returns unique suggestions.
     */
    public function test_suggest_returns_unique_suggestions(): void
    {
        $suggestions = $this->service->suggest(
            'my-app',
            'app',
            fn (string $name): bool => true
        );

        // All suggestions should be unique
        $unique = array_unique($suggestions);
        $this->assertSame(count($suggestions), count($unique));
    }
}
