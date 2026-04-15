<?php
use Horde\Util\Util;

/**
 * Copyright 2005-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith', ['authentication' => 'none']);

$cal = Util::getFormData('c');
$id = Util::getFormData('e');
$uid = Util::getFormData('i');
$user = Util::getFormData('u');

switch (Util::getFormData('a')) {
    case 'accept':
        $action = Kronolith::RESPONSE_ACCEPTED;
        $msg = _("You have successfully accepted attendence to this event.");
        break;

    case 'decline':
        $action = Kronolith::RESPONSE_DECLINED;
        $msg = _("You have successfully declined attendence to this event.");
        break;

    case 'tentative':
        $action = Kronolith::RESPONSE_TENTATIVE;
        $msg = _("You have tentatively accepted attendence to this event.");
        break;

    default:
        $action = Kronolith::RESPONSE_NONE;
        $msg = '';
        break;
}

if (((empty($cal) || empty($id)) && empty($uid)) || empty($user)) {
    $notification->push(_("The request was incomplete. Some parameters that are necessary to accept or decline an event are missing."), 'horde.error', ['sticky']);
    $title = '';
} else {
    try {
        if (empty($uid)) {
            $event = Kronolith::getDriver(null, $cal)->getEvent($id);
        } else {
            $event = Kronolith::getDriver()->getByUID($uid);
        }
        if (!$event->hasAttendee($user)) {
            $notification->push(_("You are not an attendee of the specified event."), 'horde.error', ['sticky']);
            $title = $event->getTitle();
        } else {
            $event->addAttendee($user, Kronolith::PART_IGNORE, $action);
            try {
                $event->save();
                if (!empty($msg)) {
                    $notification->push($msg, 'horde.success', ['sticky']);
                }
            } catch (Exception $e) {
                $notification->push($e, 'horde.error', ['sticky']);
            }
            $title = $event->getTitle();
        }
    } catch (Exception $e) {
        $notification->push($e, 'horde.error', ['sticky']);
        $title = '';
    }
}

$page_output->topbar = $page_output->sidebar = false;
$page_output->header([
    'title' => $title,
]);
require KRONOLITH_TEMPLATES . '/javascript_defs.php';

?>
<div id="menu"><h1>&nbsp;<?php echo htmlspecialchars($title) ?></h1></div>
<?php

$notification->notify(['listeners' => 'status']);
$page_output->footer();
