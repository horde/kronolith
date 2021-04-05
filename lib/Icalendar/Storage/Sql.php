<?php
/**
 * Simple storage for icalendar objects
 */
declare (strict_types=1);
//use \InvalidArgumentException;
use \Kronolith_Icalendar_Storage_Entity as Entity;
/**
 * Simple storage for caldav related data
 */
class Kronolith_Icalendar_Storage_Sql extends \Horde_Rdo_Mapper implements Kronolith_Icalendar_Storage
{

    protected $_classname = Entity::class;
    protected $_table = 'kronolith_icalendar_storage';

    /**
     * Remove existing item.
     *
     * If we wanted to name it delete,
     * we would need to make the rdo mapper a dependency
     * rather than inheriting from it.
     * @param string $calendarId The calendar ID to operate on
     * @param string $eventUid   The Event to operate on
     *
     * @throw InvalidArgumentException
     * @return void
     */
    public function remove(string $calendarId, string $eventUid): void
    {
        $this->_noNullString($calendarId, $eventUid);
        $entity = $this->_getEntity($calendarId, $eventUid);
        if ($entity) {
            $this->delete($entity);
        }
    }

    /**
     * Create or update item.
     *
     * @param string $calendarId The calendar ID to operate on
     * @param string $eventUid   The Event to operate on
     * @param string $data       The actual icalendar data
     *
     * @throw InvalidArgumentException
     * @return void
     */
    public function put(string $calendarId, string $eventUid, string $data): void
    {
        $entity = $this->_getEntity($calendarId, $eventUid);
        if (empty($entity)) {
            $entity = new Entity([
                'calendar_id' => $calendarId,
                'event_uid' => $eventUid,
                'event_data' => $data
            ]);
            $entity->setMapper($this);
        } else {
            $entity->event_data = $data;
        }
        $entity->save();
    }

    /**
     * Retrieve stored item.
     *
     * @param string $calendarId The calendar ID to operate on
     * @param string $eventUid   The Event to operate on
     *
     * @throw InvalidArgumentException
     * @return string
     */
    public function get(string $calendarId, string $eventUid): string
    {
        $entity = $this->_getEntity($calendarId, $eventUid);
        if ($entity) {
            return $entity->event_data;
        }
        return '';
    }


    protected function _getEntity(string $calendarId, string $eventUid): ?Entity
    {
        $this->_noNullString($calendarId, $eventUid);
        return $this->findOne([
            'calendar_id' => $calendarId,
            'event_uid' => $eventUid
        ]);
    }

    /**
     * Filter out invalid/malicious calls, throw Exception
     *
     * Rdo does undesirable actions on empty string arguments,
     * also they are not valid for our use case.
     *
     * @param string $calendarId The calendar ID to operate on
     * @param string $eventUid   The Event to operate on
     *
     * @throw InvalidArgumentException
     * @return void
     */
    protected function _noNullString(string $calendarId, string $eventUid)
    {
        if ($calendarId == '') {
            throw new InvalidArgumentException('Calendar ID must not be empty');
        }
        if ($eventUid == '') {
            throw new InvalidArgumentException('Event UID must not be empty');
        }
    }
}
