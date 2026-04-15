<?php

use Horde\Util\Util;

/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

$c = Util::getFormData('calendar');
$driver = Util::getFormData('type');
$kronolith_driver = Kronolith::getDriver($driver, $c);
if ($eventID = Util::getFormData('eventID')) {
    try {
        $event = $kronolith_driver->getEvent($eventID);
    } catch (Exception $e) {
        if ($url = Horde::verifySignedUrl(Util::getFormData('url'))) {
            $url = new Horde_Url($url);
        } else {
            $url = Horde::url($prefs->getValue('defaultview') . '.php', true);
        }
        $url->redirect();
    }
    if ($driver != 'resource') {
        if ($driver == 'remote') {
            /* The remote server is doing the permission checks for us. */
            $have_perms = true;
        } else {
            $share = $injector->getInstance('Kronolith_Shares')->getShare($event->calendar);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE, $event->creator)) {
                $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
            } else {
                $have_perms = true;
            }
        }
    } else {
        if (!$registry->isAdmin()) {
            $notification->push(_("You do not have permission to delete this event."), 'horde.warning');
        } else {
            $have_perms = true;
        }
    }

    if (!empty($have_perms)) {
        $notification_type = Kronolith::ITIP_CANCEL;
        $instance = null;
        if (Util::getFormData('future')) {
            $recurEnd = new Horde_Date(['hour' => 0, 'min' => 0, 'sec' => 0,
                'month' => Util::getFormData('month', date('n')),
                'mday' => Util::getFormData('mday', date('j')) - 1,
                'year' => Util::getFormData('year', date('Y'))]);
            if ($event->end->compareDate($recurEnd) > 0) {
                try {
                    $kronolith_driver->deleteEvent($event->id);
                } catch (Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            } else {
                $event->recurrence->setRecurEnd($recurEnd);
                $event->save();
            }
            $notification_type = Kronolith::ITIP_REQUEST;
        } elseif (Util::getFormData('current')) {
            $event->recurrence->addException(
                Util::getFormData('year'),
                Util::getFormData('month'),
                Util::getFormData('mday')
            );
            $event->save();
            $instance = new Horde_Date(['year' => Util::getFormData('year'),
                'month' => Util::getFormData('month'),
                'mday' => Util::getFormData('mday')]);
        }

        if (!$event->recurs()
            || Util::getFormData('all')
            || !$event->recurrence->hasActiveRecurrence()) {
            try {
                $kronolith_driver->deleteEvent($event->id);
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
            }
        }

        if (Util::getFormData('sendupdates', false)) {
            Kronolith::sendITipNotifications($event, $notification, $notification_type, $instance);
        }
    }
}

if ($url = Horde::verifySignedUrl(Util::getFormData('url'))) {
    $url = new Horde_Url($url, true);
} else {
    $date = new Horde_Date(Util::getFormData('date'));
    $url = Horde::url($prefs->getValue('defaultview') . '.php', true)
        ->add('date', Util::getFormData('date', date('Ymd')));
}

// Make sure URL is unique.
$url->unique()->redirect();
