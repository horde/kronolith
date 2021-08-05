<?php
declare(strict_types=1);

namespace Horde\Kronolith\Calendar;

/**
 * Base calendar interface
 *
 * Methods any calendar must implement
 * 
 * Writable calendars must use the extended WritableCalendarInterface
 * 
 * 
 */
interface CalendarInterface
{
    public function getOwner();
}