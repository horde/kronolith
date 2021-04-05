<?php
/**
 * Noop storage driver for CalDAV data
 *
 * Simplifies calling code which can just depend on storage interface
 */
class Kronolith_Icalendar_Storage_Null implements Kronolith_Icalendar_Storage
{
    public function put(string $calendarId, string $eventUid, string $data): void
    {
        return;
    }

    public function remove(string $calendarId, string $eventUid): void
    {
        return;
    }


    public function get(string $calendarId, string $eventUid): string
    {
        return '';
    }
}
