<?php

declare(strict_types=1);

/**
 * Testing the Kronolith_Geo_Null class.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @link       http://www.horde.org/apps/kronolith
 * @license    http://www.horde.org/licenses/gpl GPL
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Kronolith_Geo_Null::class)]
class Kronolith_Unit_Geo_NullTest extends TestCase
{
    private Kronolith_Geo_Null $driver;

    protected function setUp(): void
    {
        $this->driver = new Kronolith_Geo_Null();
    }

    public function testConstructorWithoutAdapter(): void
    {
        $driver = new Kronolith_Geo_Null();
        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
        $this->assertInstanceOf(Kronolith_Geo_Base::class, $driver);
    }

    public function testConstructorWithAdapter(): void
    {
        $adapter = $this->createMock(Horde_Db_Adapter::class);
        $driver = new Kronolith_Geo_Null($adapter);
        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
    }

    public function testSetLocationIsNoOp(): void
    {
        $point = ['lat' => 37.7749, 'lon' => -122.4194];

        // Should not throw exception
        $this->driver->setLocation('event123', $point);

        // Verify location was not actually stored
        $this->assertNull($this->driver->getLocation('event123'));
    }

    public function testGetLocationAlwaysReturnsNull(): void
    {
        $this->assertNull($this->driver->getLocation('event123'));
        $this->assertNull($this->driver->getLocation('nonexistent'));
        $this->assertNull($this->driver->getLocation(''));
    }

    public function testDeleteLocationIsNoOp(): void
    {
        // Should not throw exception
        $this->driver->deleteLocation('event123');
        $this->driver->deleteLocation('nonexistent');

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testSearchAlwaysReturnsEmptyArray(): void
    {
        $criteria = [
            'point' => ['lat' => 37.7749, 'lon' => -122.4194],
            'radius' => 10,
            'limit' => 50,
        ];

        $result = $this->driver->search($criteria);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSearchWithEmptyCriteria(): void
    {
        $result = $this->driver->search([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCompleteWorkflow(): void
    {
        $eventId = 'test-event-456';
        $point = ['lat' => 40.7128, 'lon' => -74.0060];

        // Set location (no-op)
        $this->driver->setLocation($eventId, $point);

        // Retrieve location (should be null)
        $location = $this->driver->getLocation($eventId);
        $this->assertNull($location);

        // Search (should return empty)
        $results = $this->driver->search([
            'point' => $point,
            'radius' => 5,
        ]);
        $this->assertEmpty($results);

        // Delete (no-op)
        $this->driver->deleteLocation($eventId);

        // Verify still null after delete
        $this->assertNull($this->driver->getLocation($eventId));
    }
}
