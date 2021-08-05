<?php
declare(strict_types=1);

namespace Horde\Kronolith\Calendar;
use Horde_Icalendar_Vevent as Vevent;

/**
 * Event Put Operation Handler
 * 
 * Reimplements the original davPutObject and
 * Icalendar_Handler / Icalendar_Handler_Dav logic
 * but with some changes:
 * 
 * - Namespaced code, injection, no/less globals
 * - Run same code for changes from frontend, import, interapp API
 * - Events/Messages are fired to listeners (itip, notification, ...)
 * - Always save/modify caldav representation
 * - Listeners should work on provided objects, not on saved state
 * 
 * Workflow:
 * 
 * Find relevant backend
 * Check Permission [checkPermissionListener]
 * check for existing event
 * create:
 * - create event in event storage
 * - create event in caldav storage
 * - handle attendees, if any
 * - handle resources, if any
 * - initialize change history
 * 
 * update:
 * - Compare Events
 * - Reject outdated updates
 * - Apply valid updates to calendar event backend
 * - Apply valid updates to caldav storage backend
 * - Document change history
 * 
 */
class EventPutListener
{
    public function __construct(
        \Horde_Dav_Storage $dav,
        \Kronolith_Icalendar_Storage $icalStore,
        PutHandlerListeners $listeners        
    )
    {

    }

    public function __invoke(EventPut $event)
    {

    }
}