<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category   Horde
 * @package    Kronolith
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/gpl GPL
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Kronolith_CalendarsManager::class)]
class Kronolith_Unit_CalendarsManagerTest extends TestCase
{
    private function createManager(): Kronolith_CalendarsManager_Testable
    {
        return new Kronolith_CalendarsManager_Testable();
    }

    public function testGetAllHolidaysReturnsEmptyWhenDisabled(): void
    {
        $GLOBALS['conf']['holidays']['enable'] = false;
        $manager = $this->createManager();

        $result = $manager->getAllHolidays();

        $this->assertSame([], $result);
    }

    public function testGetAllHolidaysReturnsEmptyWhenEnableNotSet(): void
    {
        unset($GLOBALS['conf']['holidays']);
        $manager = $this->createManager();

        $result = $manager->getAllHolidays();

        $this->assertSame([], $result);
    }

    public function testGetAllHolidaysSurvivesBrokenAutoload(): void
    {
        $GLOBALS['conf']['holidays']['enable'] = true;

        $autoloader = static function (string $class): never {
            if ($class === 'Date_Holidays') {
                throw new \Error('Failed opening required Date.php');
            }
            throw new \Exception('Unexpected class: ' . $class);
        };
        spl_autoload_register($autoloader, true, true);

        try {
            $manager = $this->createManager();
            $result = $manager->getAllHolidays();
            $this->assertSame([], $result);
        } finally {
            spl_autoload_unregister($autoloader);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['conf']);
    }
}

/**
 * Testable subclass that bypasses the constructor and exposes protected methods.
 */
class Kronolith_CalendarsManager_Testable extends Kronolith_CalendarsManager
{
    public function __construct()
    {
        // Skip parent constructor — it requires globals we don't have in unit tests.
    }

    public function getAllHolidays(): array
    {
        return $this->_getAllHolidays();
    }
}
