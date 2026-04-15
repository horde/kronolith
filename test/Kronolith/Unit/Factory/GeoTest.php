<?php

declare(strict_types=1);

/**
 * Testing the Kronolith_Factory_Geo class.
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

#[CoversClass(Kronolith_Factory_Geo::class)]
class Kronolith_Unit_Factory_GeoTest extends TestCase
{
    private Horde_Injector $injector;

    protected function setUp(): void
    {
        $this->injector = new Horde_Injector(new Horde_Injector_TopLevel());
    }

    protected function tearDown(): void
    {
        // Clean up globals
        unset($GLOBALS['conf']['maps']['geodriver']);
    }

    public function testCreateReturnsNullDriverWhenNotConfigured(): void
    {
        // Ensure geodriver is not set
        unset($GLOBALS['conf']['maps']['geodriver']);

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
        $this->assertInstanceOf(Kronolith_Geo_Base::class, $driver);
    }

    public function testCreateReturnsNullDriverWhenConfiguredAsNull(): void
    {
        $GLOBALS['conf']['maps']['geodriver'] = null;

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
    }

    public function testCreateReturnsNullDriverWhenConfiguredAsEmptyString(): void
    {
        $GLOBALS['conf']['maps']['geodriver'] = '';

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
    }

    public function testCreateReturnsSqlDriverWhenConfigured(): void
    {
        $GLOBALS['conf']['maps']['geodriver'] = 'Sql';

        // Mock database adapter
        $dbAdapter = $this->createMock(Horde_Db_Adapter::class);
        $this->injector->setInstance('Horde_Db_Adapter', $dbAdapter);

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Sql::class, $driver);
        $this->assertInstanceOf(Kronolith_Geo_Base::class, $driver);
    }

    public function testCreateReturnsMysqlDriverWhenConfigured(): void
    {
        $GLOBALS['conf']['maps']['geodriver'] = 'Mysql';

        // Mock database adapter
        $dbAdapter = $this->createMock(Horde_Db_Adapter::class);
        $this->injector->setInstance('Horde_Db_Adapter', $dbAdapter);

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Mysql::class, $driver);
        $this->assertInstanceOf(Kronolith_Geo_Base::class, $driver);
    }

    public function testNoCircularDependencyWhenNotConfigured(): void
    {
        // This was the original bug - ensure it doesn't happen
        unset($GLOBALS['conf']['maps']['geodriver']);

        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->bindFactory('Kronolith_Geo', 'Kronolith_Factory_Geo', 'create');

        // This should NOT throw a CircularDependencyException
        $driver = $injector->getInstance('Kronolith_Geo');

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
    }

    public function testNullDriverWorksWithoutDatabase(): void
    {
        // Ensure geodriver is not set
        unset($GLOBALS['conf']['maps']['geodriver']);

        // Create injector WITHOUT database adapter
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());

        $factory = new Kronolith_Factory_Geo($injector);

        // Should not throw exception about missing Horde_Db_Adapter
        $driver = $factory->create($injector);

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);

        // Verify driver works
        $driver->setLocation('test', ['lat' => 1.0, 'lon' => 2.0]);
        $this->assertNull($driver->getLocation('test'));
    }

    public function testCreateReturnsNullDriverWhenConfiguredAsStringFalse(): void
    {
        // conf.xml generates the string 'false' for the "None" geodriver option
        $GLOBALS['conf']['maps']['geodriver'] = 'false';

        $factory = new Kronolith_Factory_Geo($this->injector);
        $driver = $factory->create($this->injector);

        $this->assertInstanceOf(Kronolith_Geo_Null::class, $driver);
    }

    public function testCreateThrowsExceptionForInvalidDriver(): void
    {
        $GLOBALS['conf']['maps']['geodriver'] = 'NonExistentDriver';

        $dbAdapter = $this->createMock(Horde_Db_Adapter::class);
        $this->injector->setInstance('Horde_Db_Adapter', $dbAdapter);

        $factory = new Kronolith_Factory_Geo($this->injector);

        $this->expectException(Kronolith_Exception::class);
        $factory->create($this->injector);
    }
}
