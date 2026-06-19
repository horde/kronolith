<?php

/**
 * EAS 16.0 related Kronolith_Event import/export tests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2026 Horde LLC (http://www.horde.org)
 * @package   Kronolith
 */

require_once __DIR__ . '/../Stub/Driver.php';
require_once __DIR__ . '/../Stub/CalendarManager.php';
require_once __DIR__ . '/../Stub/Registry.php';

use PHPUnit\Framework\TestCase;

class Kronolith_Unit_TestActiveSyncEvent extends Kronolith_Event
{
    /** @var Kronolith_Event[] */
    private array $_testBoundExceptions = [];

    public function setTestBoundExceptions(array $exceptions): void
    {
        $this->_testBoundExceptions = $exceptions;
    }

    public function boundExceptions($flat = true)
    {
        return $this->_testBoundExceptions;
    }

    public function listFiles()
    {
        return [];
    }

    public function isPrivate($user = null)
    {
        return (bool) $this->private;
    }
}

class Kronolith_Unit_EventActiveSyncTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!isset($GLOBALS['registry'])) {
            $setup = new Horde_Test_Setup();
            $setup->setup([
                '_PARAMS' => [
                    'user' => 'test@example.com',
                    'app' => 'kronolith',
                ],
                'Horde_Alarm' => 'Alarm',
                'Horde_Cache' => 'Cache',
                'Horde_Group' => 'Group',
                'Horde_History' => 'History',
                'Horde_Prefs' => 'Prefs',
                'Horde_Perms' => 'Perms',
                'Horde_Registry' => 'Registry',
                'Horde_Session' => 'Session',
            ]);
            $setup->makeGlobal([
                'injector' => 'Horde_Injector',
                'prefs' => 'Horde_Prefs',
                'registry' => 'Horde_Registry',
                'session' => 'Horde_Session',
            ]);
            $GLOBALS['registry'] = new Kronolith_Stub_Registry('test@example.com', 'kronolith');
            $GLOBALS['injector']->setInstance('Horde_Registry', $GLOBALS['registry']);
            $GLOBALS['conf']['prefs']['driver'] = 'Null';
            $GLOBALS['calendar_manager'] = new Kronolith_Stub_CalendarManager();
        }
    }

    protected function setUp(): void
    {
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }
    }

    protected function _createEvent(): Kronolith_Unit_TestActiveSyncEvent
    {
        $driver = new Kronolith_Stub_Driver();
        $event = new Kronolith_Unit_TestActiveSyncEvent($driver);
        $event->uid = 'event-uid-1';
        $event->creator = $GLOBALS['registry']->getAuth();
        $event->tags = [];
        $event->title = 'Test Event';
        $event->start = new Horde_Date('2026-06-16T10:00:00', 'UTC');
        $event->end = new Horde_Date('2026-06-16T11:00:00', 'UTC');
        $event->initialized = true;

        return $event;
    }

    protected function _createAppointment(array $extra = []): Horde_ActiveSync_Message_Appointment
    {
        $logger = new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null());

        return new Horde_ActiveSync_Message_Appointment(array_merge([
            'logger' => $logger,
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN,
        ], $extra));
    }

    public function testEasClientUidRoundtrip()
    {
        $event = $this->_createEvent();
        $this->assertNull($event->getEasClientUid());

        $event->setEasClientUid('client-uid-abc');
        $this->assertSame('client-uid-abc', $event->getEasClientUid());

        $event->setEasClientUid(null);
        $this->assertNull($event->getEasClientUid());
    }

    public function testFromASAppointmentStoresClientUid()
    {
        $event = $this->_createEvent();
        $message = $this->_createAppointment();
        $message->clientuid = 'mobile-client-uid-42';
        $message->subject = 'Imported';
        $message->starttime = new Horde_Date('2026-06-16T10:00:00', 'UTC');
        $message->endtime = new Horde_Date('2026-06-16T11:00:00', 'UTC');

        $event->fromASAppointment($message);

        $this->assertSame('mobile-client-uid-42', $event->getEasClientUid());
    }

    public function testToASAppointmentExportsClientUidAndInstanceId()
    {
        $event = $this->_createEvent();
        $event->setEasClientUid('export-client-uid');
        $event->baseid = 'series-master-uid';
        $event->exceptionoriginaldate = new Horde_Date('2026-06-16T10:00:00', 'UTC');

        $message = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN,
        ]);

        $this->assertSame('export-client-uid', $message->clientuid);
        $this->assertInstanceOf('Horde_Date', $message->instanceid);
        $this->assertSame(
            '20260616T100000Z',
            $message->instanceid->format('Ymd\THis\Z')
        );
    }

    public function testToASAppointmentOmitsModifiedExceptionsForEas16()
    {
        $event = new Kronolith_Unit_TestActiveSyncEvent(new Kronolith_Stub_Driver());
        $event->uid = 'series-master';
        $event->creator = $GLOBALS['registry']->getAuth();
        $event->tags = [];
        $event->title = 'Series';
        $event->start = new Horde_Date('2026-06-16T10:00:00', 'UTC');
        $event->end = new Horde_Date('2026-06-16T11:00:00', 'UTC');
        $event->initialized = true;
        $event->recurrence = new Horde_Date_Recurrence($event->start);
        $event->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
        $event->recurrence->addException('2026', '06', '18');
        $GLOBALS['prefs']->setValue('week_start_monday', '0');

        $modified = $this->_createEvent();
        $modified->uid = 'exception-uid';
        $modified->baseid = 'series-master';
        $modified->exceptionoriginaldate = new Horde_Date('2026-06-17T10:00:00', 'UTC');
        $event->setTestBoundExceptions([$modified]);

        $message = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN,
        ]);

        $exceptions = $message->getProperty('exceptions') ?? [];
        $this->assertCount(1, $exceptions);
        $this->assertTrue($exceptions[0]->deleted);
    }

    public function testLocationImportExportForEas16()
    {
        $event = $this->_createEvent();
        $message = $this->_createAppointment();
        $message->location = new Horde_ActiveSync_Message_AirSyncBaseLocation([
            'logger' => new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null()),
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN,
        ]);
        $message->location->displayname = 'Horde HQ';
        $message->location->latitude = '52.5200';
        $message->location->longitude = '13.4050';
        $message->subject = 'Located';
        $message->starttime = new Horde_Date('2026-06-16T10:00:00', 'UTC');
        $message->endtime = new Horde_Date('2026-06-16T11:00:00', 'UTC');

        $event->fromASAppointment($message);

        $this->assertSame('Horde HQ', $event->location);
        $this->assertSame('52.5200', $event->geoLocation['lat']);
        $this->assertSame('13.4050', $event->geoLocation['lon']);

        $exported = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEEN,
        ]);

        $this->assertInstanceOf('Horde_ActiveSync_Message_AirSyncBaseLocation', $exported->location);
        $this->assertSame('Horde HQ', $exported->location->displayname);
        $this->assertSame('52.5200', $exported->location->latitude);
        $this->assertSame('13.4050', $exported->location->longitude);
    }

    public function testToASAppointmentExportsAttendeeProposedTimesForEas161()
    {
        $event = $this->_createEvent();
        $event->status = Kronolith::STATUS_CONFIRMED;
        $event->attendees->add(new Kronolith_Attendee([
            'email' => 'guest@example.com',
            'name' => 'Guest',
            'response' => Kronolith::RESPONSE_TENTATIVE,
            'proposedStart' => new Horde_Date('2026-06-17T14:00:00', 'UTC'),
            'proposedEnd' => new Horde_Date('2026-06-17T15:00:00', 'UTC'),
        ]));

        $message = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_SIXTEENONE,
        ]);

        $attendees = $message->getAttendees();
        $this->assertCount(1, $attendees);
        $this->assertInstanceOf('Horde_Date', $attendees[0]->proposedstarttime);
        $this->assertInstanceOf('Horde_Date', $attendees[0]->proposedendtime);
        $this->assertSame(
            '20260617T140000Z',
            $attendees[0]->proposedstarttime->format('Ymd\THis\Z')
        );
        $this->assertSame(
            '20260617T150000Z',
            $attendees[0]->proposedendtime->format('Ymd\THis\Z')
        );
    }

    public function testToASAppointmentClearsProposedTimesWhenRequested()
    {
        $event = $this->_createEvent();
        $event->status = Kronolith::STATUS_CONFIRMED;
        $event->attendees->add(new Kronolith_Attendee([
            'email' => 'guest@example.com',
            'name' => 'Guest',
            'response' => Kronolith::RESPONSE_TENTATIVE,
        ]));
        $event->setEasProposalClear(true);

        $registry = $GLOBALS['registry'];
        $previousAuth = $registry->getAuth();
        $previousCreds = $registry->getAuthCredential();
        if (!is_array($previousCreds)) {
            $previousCreds = [];
        }
        try {
            $registry->setAuth('guest@example.com', []);

            $message = $event->toASAppointment([
                'protocolversion' => Horde_ActiveSync::VERSION_SIXTEENONE,
            ]);

            $attendees = $message->getAttendees();
            $this->assertCount(1, $attendees);
            $this->assertTrue($attendees[0]->clearProposedTimes);
        } finally {
            $registry->setAuth($previousAuth, $previousCreds);
        }
    }

    public function testToASAppointmentClearsProposedTimesForAttendeeUserId()
    {
        $event = $this->_createEvent();
        $event->status = Kronolith::STATUS_CONFIRMED;
        $event->attendees->add(new Kronolith_Attendee([
            'user' => 'guest@example.com',
            'name' => 'Guest',
            'response' => Kronolith::RESPONSE_TENTATIVE,
        ]));
        $event->setEasProposalClear(true);

        $registry = $GLOBALS['registry'];
        $previousAuth = $registry->getAuth();
        $previousCreds = $registry->getAuthCredential();
        if (!is_array($previousCreds)) {
            $previousCreds = [];
        }
        try {
            $registry->setAuth('guest@example.com', []);

            $message = $event->toASAppointment([
                'protocolversion' => Horde_ActiveSync::VERSION_SIXTEENONE,
            ]);

            $attendees = $message->getAttendees();
            $this->assertCount(1, $attendees);
            $this->assertTrue($attendees[0]->clearProposedTimes);
        } finally {
            $registry->setAuth($previousAuth, $previousCreds);
        }
    }

    public function testDisallowNewTimeProposalRoundTrip()
    {
        $event = $this->_createEvent();
        $message = $this->_createAppointment();
        $message->disallownewtimeproposal = true;
        $message->subject = 'No proposals';
        $message->starttime = new Horde_Date('2026-06-16T10:00:00', 'UTC');
        $message->endtime = new Horde_Date('2026-06-16T11:00:00', 'UTC');

        $event->fromASAppointment($message);

        $exported = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_FOURTEEN,
        ]);
        $this->assertTrue($exported->disallownewtimeproposal);

        $legacy = $event->toASAppointment([
            'protocolversion' => Horde_ActiveSync::VERSION_TWELVEONE,
        ]);
        $this->assertEmpty($legacy->disallownewtimeproposal);
    }
}
