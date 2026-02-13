<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Support;

use PhpHive\Cli\Support\Arr;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for Arr helper class.
 *
 * Tests array manipulation functionality:
 * - Array transformations
 * - Array filtering and mapping
 * - Array key/value operations
 * - Array sorting
 * - Array searching and checking
 */
class ArrTest extends TestCase
{
    /**
     * Test build method creates new array using callback.
     */
    public function test_build_creates_new_array_using_callback(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $result = Arr::build($array, fn ($key, $value) => [strtoupper($key), $value * 2]);

        $this->assertSame(['A' => 2, 'B' => 4, 'C' => 6], $result);
    }

    /**
     * Test keys returns array keys.
     */
    public function test_keys_returns_array_keys(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $keys = Arr::keys($array);

        $this->assertSame(['a', 'b', 'c'], $keys);
    }

    /**
     * Test keys can filter by value.
     */
    public function test_keys_can_filter_by_value(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 1];

        $keys = Arr::keys($array, 1);

        $this->assertSame(['a', 'c'], $keys);
    }

    /**
     * Test values returns array values.
     */
    public function test_values_returns_array_values(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $values = Arr::values($array);

        $this->assertSame([1, 2, 3], $values);
    }

    /**
     * Test flip swaps keys and values.
     */
    public function test_flip_swaps_keys_and_values(): void
    {
        $array = ['a' => 'x', 'b' => 'y', 'c' => 'z'];

        $flipped = Arr::flip($array);

        $this->assertSame(['x' => 'a', 'y' => 'b', 'z' => 'c'], $flipped);
    }

    /**
     * Test flip filters non-string/int values.
     */
    public function test_flip_filters_non_string_int_values(): void
    {
        $array = ['a' => 'x', 'b' => ['nested'], 'c' => 'z'];

        $flipped = Arr::flip($array);

        $this->assertSame(['x' => 'a', 'z' => 'c'], $flipped);
    }

    /**
     * Test combine creates array from keys and values.
     */
    public function test_combine_creates_array_from_keys_and_values(): void
    {
        $keys = ['a', 'b', 'c'];
        $values = [1, 2, 3];

        $combined = Arr::combine($keys, $values);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $combined);
    }

    /**
     * Test keyExists checks if key exists.
     */
    public function test_key_exists_checks_if_key_exists(): void
    {
        $array = ['a' => 1, 'b' => null];

        $this->assertTrue(Arr::keyExists('a', $array));
        $this->assertTrue(Arr::keyExists('b', $array));
        $this->assertFalse(Arr::keyExists('c', $array));
    }

    /**
     * Test reduce accumulates array to single value.
     */
    public function test_reduce_accumulates_array_to_single_value(): void
    {
        $array = [1, 2, 3, 4, 5];

        $sum = Arr::reduce($array, fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    /**
     * Test fillKeys fills array with value.
     */
    public function test_fill_keys_fills_array_with_value(): void
    {
        $keys = ['a', 'b', 'c'];

        $filled = Arr::fillKeys($keys, 'value');

        $this->assertSame(['a' => 'value', 'b' => 'value', 'c' => 'value'], $filled);
    }

    /**
     * Test slice extracts portion of array.
     */
    public function test_slice_extracts_portion_of_array(): void
    {
        $array = ['a', 'b', 'c', 'd', 'e'];

        $sliced = Arr::slice($array, 1, 3);

        $this->assertSame(['b', 'c', 'd'], $sliced);
    }

    /**
     * Test slice preserves keys when specified.
     */
    public function test_slice_preserves_keys_when_specified(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];

        $sliced = Arr::slice($array, 1, 2, true);

        $this->assertSame(['b' => 2, 'c' => 3], $sliced);
    }

    /**
     * Test filter removes elements based on callback.
     */
    public function test_filter_removes_elements_based_on_callback(): void
    {
        $array = [1, 2, 3, 4, 5];

        $filtered = Arr::filter($array, fn ($value) => $value > 2);

        $this->assertSame([2 => 3, 3 => 4, 4 => 5], $filtered);
    }

    /**
     * Test reverse reverses array order.
     */
    public function test_reverse_reverses_array_order(): void
    {
        $array = ['a', 'b', 'c'];

        $reversed = Arr::reverse($array);

        $this->assertSame(['c', 'b', 'a'], $reversed);
    }

    /**
     * Test pad pads array to specified length.
     */
    public function test_pad_pads_array_to_specified_length(): void
    {
        $array = ['a', 'b'];

        $padded = Arr::pad($array, 5, 'x');

        $this->assertSame(['a', 'b', 'x', 'x', 'x'], $padded);
    }

    /**
     * Test replace replaces array elements.
     */
    public function test_replace_replaces_array_elements(): void
    {
        $array = ['a' => 1, 'b' => 2];
        $replacement = ['b' => 3, 'c' => 4];

        $replaced = Arr::replace($array, $replacement);

        $this->assertSame(['a' => 1, 'b' => 3, 'c' => 4], $replaced);
    }

    /**
     * Test unique removes duplicate values.
     */
    public function test_unique_removes_duplicate_values(): void
    {
        $array = [1, 2, 2, 3, 3, 3];

        $unique = Arr::unique($array);

        $this->assertSame([0 => 1, 1 => 2, 3 => 3], $unique);
    }

    /**
     * Test diff computes array difference.
     */
    public function test_diff_computes_array_difference(): void
    {
        $array1 = [1, 2, 3, 4];
        $array2 = [2, 4];

        $diff = Arr::diff($array1, $array2);

        $this->assertSame([0 => 1, 2 => 3], $diff);
    }

    /**
     * Test merge combines arrays.
     */
    public function test_merge_combines_arrays(): void
    {
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['b' => 3, 'c' => 4];

        $merged = Arr::merge($array1, $array2);

        $this->assertSame(['a' => 1, 'b' => 3, 'c' => 4], $merged);
    }

    /**
     * Test sum calculates array sum.
     */
    public function test_sum_calculates_array_sum(): void
    {
        $array = [1, 2, 3, 4, 5];

        $sum = Arr::sum($array);

        $this->assertSame(15, $sum);
    }

    /**
     * Test product calculates array product.
     */
    public function test_product_calculates_array_product(): void
    {
        $array = [2, 3, 4];

        $product = Arr::product($array);

        $this->assertSame(24, $product);
    }

    /**
     * Test count returns element count.
     */
    public function test_count_returns_element_count(): void
    {
        $array = ['a', 'b', 'c'];

        $count = Arr::count($array);

        $this->assertSame(3, $count);
    }

    /**
     * Test chunk splits array into chunks.
     */
    public function test_chunk_splits_array_into_chunks(): void
    {
        $array = [1, 2, 3, 4, 5];

        $chunked = Arr::chunk($array, 2);

        $this->assertSame([[1, 2], [3, 4], [5]], $chunked);
    }

    /**
     * Test isList checks if array is a list.
     */
    public function test_is_list_checks_if_array_is_list(): void
    {
        $list = [1, 2, 3];
        $assoc = ['a' => 1, 'b' => 2];

        $this->assertTrue(Arr::isList($list));
        $this->assertFalse(Arr::isList($assoc));
    }

    /**
     * Test keyFirst returns first key.
     */
    public function test_key_first_returns_first_key(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $firstKey = Arr::keyFirst($array);

        $this->assertSame('a', $firstKey);
    }

    /**
     * Test keyLast returns last key.
     */
    public function test_key_last_returns_last_key(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $lastKey = Arr::keyLast($array);

        $this->assertSame('c', $lastKey);
    }

    /**
     * Test search finds value in array.
     */
    public function test_search_finds_value_in_array(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $key = Arr::search(2, $array);

        $this->assertSame('b', $key);
    }

    /**
     * Test search returns false when value not found.
     */
    public function test_search_returns_false_when_value_not_found(): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $key = Arr::search(99, $array);

        $this->assertFalse($key);
    }

    /**
     * Test inArray checks if value exists.
     */
    public function test_in_array_checks_if_value_exists(): void
    {
        $array = [1, 2, 3];

        $this->assertTrue(Arr::inArray(2, $array));
        $this->assertFalse(Arr::inArray(99, $array));
    }

    /**
     * Test range creates array of sequential values.
     */
    public function test_range_creates_array_of_sequential_values(): void
    {
        $range = Arr::range(1, 5);

        $this->assertSame([1, 2, 3, 4, 5], $range);
    }

    /**
     * Test range with step.
     */
    public function test_range_with_step(): void
    {
        $range = Arr::range(0, 10, 2);

        $this->assertSame([0, 2, 4, 6, 8, 10], $range);
    }

    /**
     * Test each applies callback to array.
     */
    public function test_each_applies_callback_to_array(): void
    {
        $array = [1, 2, 3];

        $result = Arr::each(fn ($value) => $value * 2, $array);

        $this->assertSame([2, 4, 6], $result);
    }

    /**
     * Test column extracts column from multidimensional array.
     */
    public function test_column_extracts_column_from_multidimensional_array(): void
    {
        $array = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Charlie'],
        ];

        $names = Arr::column($array, 'name');

        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    /**
     * Test fill creates array with repeated value.
     */
    public function test_fill_creates_array_with_repeated_value(): void
    {
        $filled = Arr::fill(0, 3, 'x');

        $this->assertSame(['x', 'x', 'x'], $filled);
    }
}
