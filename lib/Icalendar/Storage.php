<?php
interface Kronolith_Icalendar_Storage
{
    public function put(string $calendarId, string $eventUid, string $data): void;
    public function get(string $calendarId, string $eventUid): string;
    public function remove(string $calendarId, string $eventUid): void;
}
