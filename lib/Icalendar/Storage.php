<?php

use Horde\Injector\Attribute\Factory;

#[Factory(factory: Kronolith_Factory_IcalendarStorage::class, method: 'create')]
interface Kronolith_Icalendar_Storage
{
    public function put(string $calendarId, string $eventUid, string $data): void;
    public function get(string $calendarId, string $eventUid): string;
    public function remove(string $calendarId, string $eventUid): void;
}
