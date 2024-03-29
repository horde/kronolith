<?php
/**
 * Copyright 2004-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

use Sabre\DAV\Client;
use Sabre\CalDAV;

/**
 * The Kronolith_Driver_Ical class implements the Kronolith_Driver API for
 * iCalendar data.
 *
 * Possible driver parameters:
 * - url:      The location of the remote calendar.
 * - proxy:    A hash with HTTP proxy information.
 * - user:     The user name for HTTP Basic Authentication.
 * - password: The password for HTTP Basic Authentication.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Ical extends Kronolith_Driver
{
    /**
     * Cache events as we fetch them to avoid fetching or parsing the same
     * event twice.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * DAV client object.
     *
     * @var \Sabre\DAV\Client
     */
    protected $_client;

    /**
     * A list of DAV support levels.
     *
     * @var array
     */
    protected $_davSupport;

    /**
     * The Horde_Perms permissions mask matching the CalDAV ACL.
     *
     * @var integer
     */
    protected $_permission;

    /**
     * Selects a calendar as the currently opened calendar.
     *
     * @param string $calendar  A calendar identifier.
     */
    public function open($calendar)
    {
        parent::open($calendar);
        $this->_client = null;
        $this->_permission = 0;
        unset($this->_davSupport);
    }

    /**
     * Returns the background color of the current calendar.
     *
     * @return string  The calendar color.
     */
    public function backgroundColor()
    {
        return $GLOBALS['calendar_manager']->getEntry(Kronolith::ALL_REMOTE_CALENDARS, $this->calendar)
            ? $GLOBALS['calendar_manager']->getEntry(Kronolith::ALL_REMOTE_CALENDARS, $this->calendar)->background()
            : '#dddddd';
    }

    public function listAlarms($date, $fullevent = false)
    {
        return array();
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startDate  The start of range date.
     * @param Horde_Date $endDate    The end of date range.
     * @param array $options         Additional options:
     *   - show_recurrence: (boolean) Return every instance of a recurring
     *                       event?
     *                      DEFAULT: false (Only return recurring events once
     *                      inside $startDate - $endDate range)
     *   - has_alarm:       (boolean) Only return events with alarms.
     *                      DEFAULT: false (Return all events)
     *   - json:            (boolean) Store the results of the event's toJson()
     *                      method?
     *                      DEFAULT: false
     *   - cover_dates:     (boolean) Add the events to all days that they
     *                      cover?
     *                      DEFAULT: true
     *   - hide_exceptions: (boolean) Hide events that represent exceptions to
     *                      a recurring event.
     *                      DEFAULT: false (Do not hide exception events)
     *   - fetch_tags:      (boolean) Fetch tags for all events.
     *                      DEFAULT: false (Do not fetch event tags)
     *
     * @throws Kronolith_Exception
     */
    protected function _listEvents(Horde_Date $startDate = null,
                                   Horde_Date $endDate = null,
                                   array $options = array())
    {
        if ($this->isCalDAV()) {
            try {
                return $this->_listCalDAVEvents(
                    $startDate, $endDate, $options['show_recurrence'],
                    $options['has_alarm'], $options['json'],
                    $options['cover_dates'], $options['hide_exceptions']);
            } catch (Kronolith_Exception $e) {
                // Fall back to regular ICS downloads. At least Nextcloud
                // advertises calendars as CalDAV capable, but then denying
                // CalDAV requests.
                $this->_davSupport = false;
            }
        }
        return $this->_listWebDAVEvents(
            $startDate, $endDate, $options['show_recurrence'],
            $options['has_alarm'], $options['json'],
            $options['cover_dates'], $options['hide_exceptions']);
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $hasAlarm          Only return events with alarms?
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     * $param boolean $hideExceptions    Hide events that represent exceptions
     *                                   to a recurring event.
     *
     * @return array  Events in the given time range.
     * @throws Kronolith_Exception
     */
    protected function _listWebDAVEvents(
        $startDate = null, $endDate = null, $showRecurrence = false,
        $hasAlarm = false, $json = false, $coverDates = true,
        $hideExceptions = false
    )
    {
        $events = $this->_getRemoteEvents();

        if (is_null($startDate)) {
            $startDate = new Horde_Date(array('mday' => 1,
                                              'month' => 1,
                                              'year' => 0000));
        }
        if (is_null($endDate)) {
            $endDate = new Horde_Date(array('mday' => 31,
                                            'month' => 12,
                                            'year' => 9999));
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        $results = array();
        $this->_processComponents(
            $results, $events, $startDate, $endDate, $showRecurrence, $json,
            $coverDates, $hideExceptions
        );

        return $results;
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $hasAlarm          Only return events with alarms?
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     * $param boolean $hideExceptions    Hide events that represent exceptions
     *                                   to a recurring event.
     *
     * @return array  Events in the given time range.
     * @throws Kronolith_Exception
     */
    protected function _listCalDAVEvents(
        $startDate = null, $endDate = null, $showRecurrence = false,
        $hasAlarm = false, $json = false, $coverDates = true,
        $hideExceptions = false
    )
    {
        if (!is_null($startDate)) {
            $startDate = clone $startDate;
            $startDate->hour = $startDate->min = $startDate->sec = 0;
        }
        if (!is_null($endDate)) {
            $endDate = clone $endDate;
            $endDate->hour = 23;
            $endDate->min = $endDate->sec = 59;
        }

        /* Build report query. */
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument();
        $xml->startElementNS('C', 'calendar-query', 'urn:ietf:params:xml:ns:caldav');
        $xml->writeAttribute('xmlns:D', 'DAV:');
        $xml->startElement('D:prop');
        $xml->writeElement('D:getetag');
        $xml->startElement('C:calendar-data');
        $xml->startElement('C:comp');
        $xml->writeAttribute('name', 'VCALENDAR');
        $xml->startElement('C:comp');
        $xml->writeAttribute('name', 'VEVENT');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('C:filter');
        $xml->startElement('C:comp-filter');
        $xml->writeAttribute('name', 'VCALENDAR');
        $xml->startElement('C:comp-filter');
        $xml->writeAttribute('name', 'VEVENT');
        if (!is_null($startDate) ||
            !is_null($endDate)) {
            $xml->startElement('C:time-range');
            if (!is_null($startDate)) {
                $xml->writeAttribute('start', $startDate->toiCalendar());
            }
            if (!is_null($endDate)) {
                $xml->writeAttribute('end', $endDate->toiCalendar());
            }
        }
        $xml->endDocument();

        $url = $this->_getUrl();
        list($response, $events) = $this->_request('REPORT', $url, $xml,
                                                   array('Depth' => 1));
        if (!$events->children('DAV:')->response) {
            return array();
        }
        if (isset($response['headers']['content-location'])) {
            $path = $response['headers']['content-location'];
        } else {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];
        }

        $results = array();
        foreach ($events->children('DAV:')->response as $response) {
            if (!$response->children('DAV:')->propstat) {
                continue;
            }
            $ical = new Horde_Icalendar();
            try {
                $ical->parsevCalendar($response->children('DAV:')->propstat->prop->children('urn:ietf:params:xml:ns:caldav')->{'calendar-data'});
            } catch (Horde_Icalendar_Exception $e) {
                throw new Kronolith_Exception($e);
            }
            $this->_processComponents(
                $results, $this->_convertEvents($ical), $startDate, $endDate,
                $showRecurrence, $json, $coverDates, $hideExceptions,
                trim(str_replace($path, '', $response->href), '/')
            );
        }

        return $results;
    }

    /**
     * Converts all components of a Horde_Icalendar container into a
     * Kronolith_Event list.
     *
     * @param Horde_Icalendar $ical  A Horde_Icalendar container.
     *
     * @return array  List of Kronolith_Event_Ical objects.
     * @throws Kronolith_Exception
     */
    protected function _convertEvents($ical)
    {
        $events = array();
        foreach ($ical->getComponents() as $component) {
            if ($component->getType() == 'vEvent') {
                try {
                    $events[] = new Kronolith_Event_Ical($this, $component);
                } catch (Kronolith_Exception $e) {
                    Horde::log(
                        sprintf(
                            'Failed to parse event from remote calendar: url = "%s"',
                            $this->calendar
                        ),
                        'INFO'
                    );
                }
            }
        }
        return $events;
    }

    /**
     * Processes the components of a Horde_Icalendar container into an event
     * list.
     *
     * @param array $results             Gets filled with the events in the
     *                                   given time range.
     * @param array $events              A list of Kronolith_Event_Ical objects.
     * @param Horde_Date $startInterval  Start of range date.
     * @param Horde_Date $endInterval    End of range date.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     * @param boolean $hideExceptions    Hide events that represent exceptions
     *                                   to a recurring event.
     * @param string $id                 Enforce a certain event id (not UID).
     *
     * @throws Kronolith_Exception
     */
    protected function _processComponents(
        &$results, $events, $startDate, $endDate, $showRecurrence, $json,
        $coverDates, $hideExceptions, $id = null
    )
    {
        $processed = array();
        foreach (array_values($events) as $i => $event) {
            $event->permission = $this->getPermission();
            // Force string so JSON encoding is consistent across drivers.
            $event->id = $id ? $id : 'ical' . $i;

            /* Catch RECURRENCE-ID attributes which mark single recurrence
             * instances. */
            if (isset($event->recurrenceid) &&
                isset($event->uid) &&
                isset($event->sequence)) {
                $exceptions[$event->uid][$event->sequence] = $event->recurrenceid;
                if ($hideExceptions) {
                    continue;
                }
                $event->id .= '/' . $event->recurrenceid;
            }

            /* Ignore events out of the period. */
            $recurs = $event->recurs();
            if (
                /* Starts after the period. */
                ($endDate && $event->start->compareDateTime($endDate) > 0) ||
                /* End before the period and doesn't recur. */
                ($startDate && !$recurs &&
                 $event->end->compareDateTime($startDate) < 0)) {
                continue;
            }

            if ($recurs && $startDate) {
                // Fixed end date? Check if end is before start period.
                if ($event->recurrence->hasRecurEnd() &&
                    $event->recurrence->recurEnd->compareDateTime($startDate) < 0) {
                    continue;
                } elseif ($endDate) {
                    $next = $event->recurrence->nextRecurrence($startDate);
                    if ($next == false || $next->compareDateTime($endDate) > 0) {
                        continue;
                    }
                }
            }

            $processed[] = $event;
        }

        /* Loop through all explicitly defined recurrence instances and create
         * exceptions for those in the event with the matching recurrence. */
        foreach ($processed as $key => $event) {
            if ($event->recurs() &&
                isset($exceptions[$event->uid][$event->sequence])) {
                $timestamp = $exceptions[$event->uid][$event->sequence];
                $processed[$key]->recurrence->addException(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
            }
            Kronolith::addEvents($results, $event, $startDate, $endDate,
                                 $showRecurrence, $json, $coverDates);
        }
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!$eventId) {
            $event = new Kronolith_Event_Ical($this);
            $event->permission = $this->getPermission();
            return $event;
        }

        if ($this->isCalDAV()) {
            if (preg_match('/(.*)-(\d+)$/', $eventId, $matches)) {
                $eventId = $matches[1];
                //$recurrenceId = $matches[2];
            }
            $url = trim($this->_getUrl(), '/') . '/' . $eventId;
            try {
                $response = $this->_getClient($url)->request('GET');
            } catch (\Sabre\HTTP\ClientException $e) {
                throw new Kronolith_Exception($e);
            } catch (\Sabre\DAV\Exception $e) {
                throw new Kronolith_Exception($e);
            }
            if ($response['statusCode'] == 200) {
                $ical = new Horde_Icalendar();
                try {
                    $ical->parsevCalendar($response['body']);
                } catch (Horde_Icalendar_Exception $e) {
                    throw new Kronolith_Exception($e);
                }
                $results = array();
                $this->_processComponents(
                    $results, $this->_convertEvents($ical), null, null, false,
                    false, false, false, $eventId
                );
                $event = reset(reset($results));
                if (!$event) {
                    throw new Horde_Exception_NotFound(_("Event not found"));
                }
                return $event;
            }
        }

        $eventId = str_replace('ical', '', $eventId);
        $events = $this->_getRemoteEvents();
        if (isset($events[$eventId])) {
            $event = $events[$eventId];
            $event->permission = $this->getPermission();
            $event->id = 'ical' . $eventId;
            return $event;
        }

        throw new Horde_Exception_NotFound(_("Event not found"));
    }

    /**
     * Updates an existing event in the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _updateEvent(Kronolith_Event $event)
    {
        $response = $this->_saveEvent($event);
        if (!in_array($response['statusCode'], array(200, 204))) {
            // To find out if $response still contains the final URL after the refactoring
            Horde::debug($response);
            Horde::log(
                sprintf(
                    'Failed to update event on remote calendar: url = "%s", status = %s',
                    // TODO need response text here.
                    ''/*$response['url']*/, $response['body']
                ),
                'INFO'
            );
            throw new Kronolith_Exception(_("The event could not be updated on the remote server."));
        }
        return $event->id;
    }

    /**
     * Adds an event to the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _addEvent(Kronolith_Event $event)
    {
        if (!$event->uid) {
            $event->uid = (string)new Horde_Support_Uuid;
        }
        if (!$event->id) {
            $event->id = $event->uid . '.ics';
        }

        $response = $this->_saveEvent($event);
        if (!in_array($response['statusCode'], array(200, 201, 204))) {
            Horde::log(
                sprintf(
                    'Failed to create event on remote calendar: status = %s',
                    // TODO: need response text instead.
                    $response['body']
                ),
                'INFO'
            );
            throw new Kronolith_Exception(_("The event could not be added to the remote server."));
        }
        return $event->id;
    }

    /**
     * Updates an existing event in the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return \Sabre\HTTP\ResponseInterface  The HTTP response.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _saveEvent($event)
    {
        $ical = new Horde_Icalendar();
        $ical->addComponent($event->toiCalendar($ical));

        $url = trim($this->_getUrl(), '/') . '/' . $event->id;
        try {
            return $this->_getClient($url)
                ->request(
                    'PUT',
                    '',
                    $ical->exportvCalendar(),
                    array('Content-Type' => 'text/calendar')
                );
        } catch (\Sabre\HTTP\ClientException $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        } catch (\Sabre\DAV\Exception $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Deletes an event.
     *
     * @param string $eventId  The ID of the event to delete.
     * @param boolean $silent  Don't send notifications, used when deleting
     *                         events in bulk from maintenance tasks.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     * @throws Horde_Mime_Exception
     */
    protected function _deleteEvent($eventId, $silent = false)
    {
        /* Fetch the event for later use. */
        if ($eventId instanceof Kronolith_Event) {
            $event = $eventId;
            $eventId = $event->id;
        } else {
            $event = $this->getEvent($eventId);
        }

        if (!$this->isCalDAV()) {
            throw new Kronolith_Exception(_("Deleting events is not supported with this remote calendar."));
        }

        if (preg_match('/(.*)-(\d+)$/', $eventId)) {
            throw new Kronolith_Exception(_("Cannot delete exceptions (yet)."));
        }

        $url = trim($this->_getUrl(), '/') . '/' . $eventId;
        try {
            $response = $this->_getClient($url)->request('DELETE');
        } catch (\Sabre\HTTP\ClientException $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        } catch (\Sabre\DAV\Exception $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
        if (!in_array($response['statusCode'], array(200, 202, 204))) {
            Horde::log(
                sprintf(
                    'Failed to delete event from remote calendar: url = "%s", status = %s',
                    // TODO need response text here.
                    $url, $response['body']
                ),
                'INFO'
            );
            throw new Kronolith_Exception(_("The event could not be deleted from the remote server."));
        }

        return $event;
    }

    /**
     * Fetches a remote calendar into the cache and return the data.
     *
     * @param boolean $cache  Whether to return data from the cache.
     *
     * @return Horde_Icalendar  The calendar data.
     * @throws Kronolith_Exception
     */
    public function getRemoteCalendar($cache = true)
    {
        $url = $this->_getUrl();
        $cacheOb = $GLOBALS['injector']->getInstance('Horde_Cache');
        $cacheVersion = 2;
        $signature = 'kronolith_remote_'  . $cacheVersion . '_' . $url . '_' . serialize($this->_params);
        if ($cache) {
            $calendar = $cacheOb->get($signature, 3600);
            if ($calendar) {
                $calendar = unserialize($calendar);
                if (!is_object($calendar)) {
                    throw new Kronolith_Exception($calendar);
                }
                return $calendar;
            }
        }

        $error = sprintf(_("Could not open %s"), $url);
        try {
            $response = $this->_getClient($url)->request('GET');
            if ($response['statusCode'] != 200) {
                throw new Kronolith_Exception($error, $response['statusCode']);
            }
        } catch (\Sabre\HTTP\ClientException $e) {
            throw new Kronolith_Exception($e);
        } catch (\Sabre\DAV\Exception $e) {
            Horde::log(
                sprintf('Failed to retrieve remote calendar: url = "%s", status = %s',
                        $url, $e->getHttpStatus()),
                'INFO'
            );
            $error .= ': ' . $e->getResponse()->getStatusText();
            if ($cache) {
                $cacheOb->set($signature, serialize($error));
            }
            throw new Kronolith_Exception($error, $e->getHttpStatus());
        }

        /* Log fetch at DEBUG level. */
        Horde::log(
            sprintf('Retrieved remote calendar for %s: url = "%s"',
                    $GLOBALS['registry']->getAuth(), $url),
            'DEBUG'
        );

        $ical = new Horde_Icalendar();
        try {
            $ical->parsevCalendar($response['body']);
        } catch (Horde_Icalendar_Exception $e) {
            if ($cache) {
                $cacheOb->set($signature, serialize($e->getMessage()));
            }
            throw new Kronolith_Exception($e);
        }

        if ($cache) {
            $cacheOb->set($signature, serialize($ical));
        }

        return $ical;
    }

    /**
     * Fetches a remote calendar and converts it to Kronolith_Event objects.
     *
     * The converted event objects will be cached for an hour.
     *
     * @return array  List of Kronolith_Event_Ical objects.
     * @throws Kronolith_Exception
     */
    protected function _getRemoteEvents()
    {
        $cacheOb = $GLOBALS['injector']->getInstance('Horde_Cache');
        $cacheVersion = 1;
        $signature = 'kronolith_remote_events_'  . $cacheVersion . '_' . $this->_getUrl() . '_' . serialize($this->_params);
        $events = $cacheOb->get($signature, 3600);
        if ($events) {
            $events = unserialize($events);
            if (is_array($events)) {
                return $events;
            }
        }
        $events = $this->_convertEvents($this->getRemoteCalendar(false));
        $cacheOb->set($signature, serialize($events));
        return $events;
    }

    /**
     * Returns whether the remote calendar is a CalDAV server, and propagates
     * the $_davSupport propery with the server's DAV capabilities.
     *
     * @return boolean  True if the remote calendar is a CalDAV server.
     * @throws Kronolith_Exception
     */
    public function isCalDAV()
    {
        if (isset($this->_davSupport)) {
            return $this->_davSupport
                ? in_array('calendar-access', $this->_davSupport)
                : false;
        }

        $client = $this->_getClient($this->_getUrl());
        try {
            $this->_davSupport = $client->options();
        } catch (\Sabre\HTTP\ClientException $e) {
            $this->_davSupport = false;
            return false;
        } catch (\Sabre\DAV\Exception $e) {
            if ($e->getHttpStatus() != 405) {
                Horde::log($e, 'INFO');
            }
            $this->_davSupport = false;
            return false;
        }

        if (!$this->_davSupport) {
            $this->_davSupport = false;
            return false;
        }

        if (!in_array('calendar-access', $this->_davSupport)) {
            return false;
        }

        /* Check if this URL is a collection. */
        try {
            $properties = $client->propfind(
                '',
                ['{DAV:}resourcetype', '{DAV:}current-user-privilege-set']
             );
        } catch (\Sabre\HTTP\ClientException $e) {
            Horde::log($e, 'INFO');
            return false;
        } catch (\Sabre\DAV\Exception $e) {
            Horde::log($e, 'INFO');
            return false;
        } catch (Horde_Dav_Exception $e) {
            Horde::log($e, 'INFO');
            return false;
        }

        if (!$properties['{DAV:}resourcetype']->is('{DAV:}collection')) {
            throw new Kronolith_Exception(_("The remote server URL does not point to a CalDAV directory."));
        }

        /* Read ACLs. */
        if (!empty($properties['{DAV:}current-user-privilege-set'])) {
            $privileges = $properties['{DAV:}current-user-privilege-set'];
            // TODO: Move this to a helper/iterator
            foreach ($privileges as $id => $privilege)
            {
                if (empty($privilege['value'][0]['name'])) {
                    continue;
                }
                $privilegeName = $privilege['value'][0]['name'];
                if ($privilegeName == '{DAV:}read') {
                /* GET access. */
                    $this->_permission |= Horde_Perms::SHOW;
                    $this->_permission |= Horde_Perms::READ;
                }
                if ($privilegeName == '{DAV:}write' ||
                    $privilegeName == '{DAV:}write-content') {
                /* PUT access. */
                    $this->_permission |= Horde_Perms::EDIT;
                }
                if ($privilegeName == '{DAV:}unbind') {
                /* DELETE access. */
                    $this->_permission |= Horde_Perms::DELETE;
                }
            }

        }

        return true;
    }

    /**
     * Returns calendar information.
     *
     * @return array  A hash with the keys 'name', 'desc', and 'color'.
     */
    public function getCalendarInfo()
    {
        $result = array('name' => '', 'desc' => '', 'color' => '');
        if ($this->isCalDAV()) {
            $client = $this->_getClient($this->_getUrl());
            try {
                $properties = $client->propfind(
                    '',
                    array(
                        '{DAV:}displayname',
                        '{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description',
                        '{http://apple.com/ns/ical/}calendar-color'
                    )
                );
                if (isset($properties['{DAV:}displayname'])) {
                    $result['name'] = $properties['{DAV:}displayname'];
                }
                if (isset($properties['{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description'])) {
                    $result['desc'] = $properties['{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description'];
                }
                if (isset($properties['{http://apple.com/ns/ical/}calendar-color'])) {
                    $result['color'] = substr(
                        $properties['{http://apple.com/ns/ical/}calendar-color'],
                        0,
                        7
                    );
                }
            } catch (Exception $e) {
            }
        } else {
            $ical = $this->getRemoteCalendar(false);
            try {
                $name = $ical->getAttribute('X-WR-CALNAME');
                $result['name'] = $name;
            } catch (Horde_Icalendar_Exception $e) {
            }
            try {
                $desc = $ical->getAttribute('X-WR-CALDESC');
                $result['desc'] = $desc;
            } catch (Horde_Icalendar_Exception $e) {
            }
        }
        return $result;
    }

    /**
     * Returns the permissions for the current calendar.
     *
     * @return integer  A Horde_Perms permission bit mask.
     */
    public function getPermission()
    {
        if ($this->isCalDAV()) {
            return $this->_permission;
        }
        return Horde_Perms::SHOW | Horde_Perms::READ;
    }

    /**
     * Sends a CalDAV request.
     *
     * @param string $method  A request method.
     * @param string $url     A request URL.
     * @param XMLWriter $xml  An XMLWriter object with the body content.
     * @param array $headers  A hash with additional request headers.
     *
     * @return array  The Horde_Http_Response object and the parsed
     *                SimpleXMLElement results.
     * @throws Kronolith_Exception
     */
    protected function _request($method, $url, XMLWriter $xml = null,
                                array $headers = array())
    {
        try {
            $response = $this->_getClient($url)
                ->request($method,
                          '',
                          $xml ? $xml->outputMemory() : null,
                          array_merge(array('Cache-Control' => 'no-cache',
                                            'Pragma' => 'no-cache',
                                            'Content-Type' => 'application/xml'),
                                      $headers));
        } catch (\Sabre\HTTP\ClientException $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        } catch (\Sabre\DAV\Exception $e) {
            Horde::log($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
        if ($response['statusCode'] != 207) {
            throw new Kronolith_Exception(_("Unexpected response from remote server."));
        }
        libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($response['body']);
        } catch (Exception $e) {
            throw new Kronolith_Exception($e);
        }
        return array($response, $xml);
    }

    /**
     * Returns the URL of this calendar.
     *
     * Does any necessary trimming and URL scheme fixes on the user-provided
     * calendar URL.
     *
     * @return string  The URL of this calendar.
     */
    protected function _getUrl()
    {
        $url = trim($this->calendar);
        if (strpos($url, 'http') !== 0) {
            $url = str_replace(array('webcal://', 'webdav://', 'webdavs://'),
                               array('http://', 'http://', 'https://'),
                               $url);
        }
        return $url;
    }

    /**
     * Returns a configured, cached DAV client.
     *
     * @param string $uri  The base URI for any requests.
     *
     * @return \Sabre\DAV\Client  A DAV client.
     * @throws \Sabre\DAV\Exception
     * @throws \Sabre\HTTP\ClientException
     */
    protected function _getClient($uri)
    {
        global $conf;

        $options = array('baseUri' => $uri);
        if (!empty($this->_params['user'])) {
            $options['userName'] = $this->_params['user'];
            $options['password'] = $this->_params['password'];
        }

        $this->_client = new Client($options);

        $this->_client->addCurlSetting(
            CURLOPT_TIMEOUT,
            isset($this->_params['timeout']) ? $this->_params['timeout'] : 5
        );
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $this->_client->addCurlSetting(
                CURLOPT_PROXY,
                $conf['http']['proxy']['proxy_host']
            );
            $this->_client->addCurlSetting(
                CURLOPT_PROXYPORT,
                $conf['http']['proxy']['proxy_port']
            );
            if (!empty($conf['http']['proxy']['proxy_user']) &&
                !empty($conf['http']['proxy']['proxy_pass'])) {
                $this->_client->addCurlSetting(
                    CURLOPT_PROXYUSERPWD,
                    $conf['http']['proxy']['proxy_user'] . ':' .
                    $conf['http']['proxy']['proxy_pass']
                );
            }
        }

        return $this->_client;
    }

}
