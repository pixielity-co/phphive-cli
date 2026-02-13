<?php

declare(strict_types=1);

namespace PhpHive\Cli\Tests\Unit\Support;

use PhpHive\Cli\Support\Emitter;
use PhpHive\Cli\Tests\TestCase;

/**
 * Unit tests for Emitter trait.
 *
 * Tests event emitter functionality:
 * - Binding events
 * - Firing events
 * - One-time events
 * - Event priorities
 * - Unbinding events
 */
class EmitterTest extends TestCase
{
    private object $emitter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class using Emitter trait
        $this->emitter = new class()
        {
            use Emitter;
        };
    }

    /**
     * Test can bind and fire event.
     */
    public function test_can_bind_and_fire_event(): void
    {
        $called = false;

        $this->emitter->bindEvent('test.event', function () use (&$called): void {
            $called = true;
        });

        $this->emitter->fireEvent('test.event');

        $this->assertTrue($called);
    }

    /**
     * Test event receives parameters.
     */
    public function test_event_receives_parameters(): void
    {
        $receivedValue = null;

        $this->emitter->bindEvent('test.event', function ($value) use (&$receivedValue): void {
            $receivedValue = $value;
        });

        $this->emitter->fireEvent('test.event', ['test-value']);

        $this->assertSame('test-value', $receivedValue);
    }

    /**
     * Test can bind multiple listeners to same event.
     */
    public function test_can_bind_multiple_listeners_to_same_event(): void
    {
        $calls = [];

        $this->emitter->bindEvent('test.event', function () use (&$calls): void {
            $calls[] = 'first';
        });

        $this->emitter->bindEvent('test.event', function () use (&$calls): void {
            $calls[] = 'second';
        });

        $this->emitter->fireEvent('test.event');

        $this->assertSame(['first', 'second'], $calls);
    }

    /**
     * Test event priority determines execution order.
     */
    public function test_event_priority_determines_execution_order(): void
    {
        $calls = [];

        $this->emitter->bindEvent('test.event', function () use (&$calls): void {
            $calls[] = 'low';
        }, 0);

        $this->emitter->bindEvent('test.event', function () use (&$calls): void {
            $calls[] = 'high';
        }, 10);

        $this->emitter->fireEvent('test.event');

        $this->assertSame(['high', 'low'], $calls);
    }

    /**
     * Test bindEventOnce fires only once.
     */
    public function test_bind_event_once_fires_only_once(): void
    {
        $callCount = 0;

        $this->emitter->bindEventOnce('test.event', function () use (&$callCount): void {
            $callCount++;
        });

        $this->emitter->fireEvent('test.event');
        $this->emitter->fireEvent('test.event');

        $this->assertSame(1, $callCount);
    }

    /**
     * Test unbindEvent removes event listeners.
     */
    public function test_unbind_event_removes_event_listeners(): void
    {
        $called = false;

        $this->emitter->bindEvent('test.event', function () use (&$called): void {
            $called = true;
        });

        $this->emitter->unbindEvent('test.event');
        $this->emitter->fireEvent('test.event');

        $this->assertFalse($called);
    }

    /**
     * Test unbindEvent with null removes all events.
     */
    public function test_unbind_event_with_null_removes_all_events(): void
    {
        $called1 = false;
        $called2 = false;

        $this->emitter->bindEvent('event1', function () use (&$called1): void {
            $called1 = true;
        });

        $this->emitter->bindEvent('event2', function () use (&$called2): void {
            $called2 = true;
        });

        $this->emitter->unbindEvent();

        $this->emitter->fireEvent('event1');
        $this->emitter->fireEvent('event2');

        $this->assertFalse($called1);
        $this->assertFalse($called2);
    }

    /**
     * Test unbindEvent with array removes multiple events.
     */
    public function test_unbind_event_with_array_removes_multiple_events(): void
    {
        $called1 = false;
        $called2 = false;
        $called3 = false;

        $this->emitter->bindEvent('event1', function () use (&$called1): void {
            $called1 = true;
        });

        $this->emitter->bindEvent('event2', function () use (&$called2): void {
            $called2 = true;
        });

        $this->emitter->bindEvent('event3', function () use (&$called3): void {
            $called3 = true;
        });

        $this->emitter->unbindEvent(['event1', 'event2']);

        $this->emitter->fireEvent('event1');
        $this->emitter->fireEvent('event2');
        $this->emitter->fireEvent('event3');

        $this->assertFalse($called1);
        $this->assertFalse($called2);
        $this->assertTrue($called3);
    }

    /**
     * Test fireEvent returns array of results.
     */
    public function test_fire_event_returns_array_of_results(): void
    {
        $this->emitter->bindEvent('test.event', fn () => 'result1');
        $this->emitter->bindEvent('test.event', fn () => 'result2');

        $results = $this->emitter->fireEvent('test.event');

        $this->assertSame(['result1', 'result2'], $results);
    }

    /**
     * Test fireEvent with halt returns first non-null result.
     */
    public function test_fire_event_with_halt_returns_first_non_null_result(): void
    {
        $this->emitter->bindEvent('test.event', fn () => null);
        $this->emitter->bindEvent('test.event', fn () => 'first-result');
        $this->emitter->bindEvent('test.event', fn () => 'second-result');

        $result = $this->emitter->fireEvent('test.event', [], true);

        $this->assertSame('first-result', $result);
    }

    /**
     * Test fireEvent stops on false return.
     */
    public function test_fire_event_stops_on_false_return(): void
    {
        $calls = [];

        $this->emitter->bindEvent('test.event', function () use (&$calls) {
            $calls[] = 'first';

            return false;
        });

        $this->emitter->bindEvent('test.event', function () use (&$calls): void {
            $calls[] = 'second';
        });

        $this->emitter->fireEvent('test.event');

        $this->assertSame(['first'], $calls);
    }

    /**
     * Test fireEvent ignores null results in array.
     */
    public function test_fire_event_ignores_null_results_in_array(): void
    {
        $this->emitter->bindEvent('test.event', fn () => 'result1');
        $this->emitter->bindEvent('test.event', fn () => null);
        $this->emitter->bindEvent('test.event', fn () => 'result2');

        $results = $this->emitter->fireEvent('test.event');

        $this->assertSame(['result1', 'result2'], $results);
    }

    /**
     * Test fireEvent returns empty array for unbound event.
     */
    public function test_fire_event_returns_empty_array_for_unbound_event(): void
    {
        $results = $this->emitter->fireEvent('nonexistent.event');

        $this->assertSame([], $results);
    }

    /**
     * Test fireEvent with halt returns null for unbound event.
     */
    public function test_fire_event_with_halt_returns_null_for_unbound_event(): void
    {
        $result = $this->emitter->fireEvent('nonexistent.event', [], true);

        $this->assertNull($result);
    }

    /**
     * Test event parameters can be array or single value.
     */
    public function test_event_parameters_can_be_array_or_single_value(): void
    {
        $receivedValue = null;

        $this->emitter->bindEvent('test.event', function ($value) use (&$receivedValue): void {
            $receivedValue = $value;
        });

        // Test with single value (converted to array internally)
        $this->emitter->fireEvent('test.event', 'single-value');

        $this->assertSame('single-value', $receivedValue);
    }
}
