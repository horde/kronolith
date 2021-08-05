<?php
declare(strict_types=1);

namespace Horde\Kronolith\Calendar;

/**
 * Event Delete Operation Handler
 * 
 * Reimplements the original davDeleteObject logic
 * but with some changes:
 * 
 * - Namespaced code, injection rather than globals
 * - Run same code for changes from frontend, import, interapp API
 * - Events/Messages are fired to listeners (itip, notification, ...)
 * - Always save/modify caldav representation
 */
class EventDeletedListener
{
    public function __construct(
        \Horde_Dav_Storage $dav, 
        \Kronolith_Icalendar_Storage $icalStore,
        DeleteHandlerListeners $listeners
    )
    {
        
    }

    public function __invoke(EventDeleted $event)
    {

    }
   
}