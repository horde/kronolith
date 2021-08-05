<?php
declare(strict_types=1);

namespace Horde\Kronolith\Calendar;

use Horde_Icalendar_Vevent as Vevent;

/**
 * Writable calendar interface
 * 
 * Methods a writable calendar must implement
 * 
 */
interface WritableCalendarInterface extends CalendarInterface
{
    /**
     * Create a new event
     * 
     * The event must not yet exist in that calendar
     *
     * @return void
     */
    public function createEvent(Vevent $event);

    /**
     * Update an existing event
     * 
     * The event must already exist in that calendar
     *
     * @return void
     */
    public function updateEvent(Vevent $event);

    /**
     * Create or update as appropriate
     * 
     * This completely replaces the existing event
     *
     * @return void
     */
    public function putEvent(Vevent $event);

}