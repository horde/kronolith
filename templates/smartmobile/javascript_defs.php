<?php

global $prefs, $registry;

$kronolith_webroot = $registry->get('webroot');
$horde_webroot = $registry->get('webroot', 'horde');
$has_tasks = Kronolith::hasApiPermission('tasks');

/* Variables used in core javascript files. */
$code['conf'] = [
    'images' => [
        'attendees' => (string) Horde_Themes::img('attendees-fff.png'),
        'alarm'     => (string) Horde_Themes::img('alarm-fff.png'),
        'recur'     => (string) Horde_Themes::img('recur-fff.png'),
        'exception' => (string) Horde_Themes::img('exception-fff.png'),
    ],
    'user' => $GLOBALS['registry']->convertUsername($GLOBALS['registry']->getAuth(), false),
    'prefs_url' => strval($registry->getServiceLink('prefs', 'kronolith')->setRaw(true)),
    'name' => $registry->get('name'),
    'has_tasks' => $has_tasks,
    'default_calendar' => 'internal|' . Kronolith::getDefaultCalendar(Horde_Perms::EDIT),
    'week_start' => (int) $prefs->getValue('week_start_monday'),
    'max_events' => (int) $prefs->getValue('max_events'),
    'date_format' => str_replace(
        ['%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'],
        ['d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'],
        Horde_Nls::getLangInfo(D_FMT)
    ),
    'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
    'status' => ['tentative' => Kronolith::STATUS_TENTATIVE,
        'confirmed' => Kronolith::STATUS_CONFIRMED,
        'cancelled' => Kronolith::STATUS_CANCELLED,
        'free' => Kronolith::STATUS_FREE],
    'recur' => [Horde_Date_Recurrence::RECUR_NONE => 'None',
        Horde_Date_Recurrence::RECUR_DAILY => 'Daily',
        Horde_Date_Recurrence::RECUR_WEEKLY => 'Weekly',
        Horde_Date_Recurrence::RECUR_MONTHLY_DATE => 'Monthly',
        Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => 'Monthly',
        Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY => 'Monthly',
        Horde_Date_Recurrence::RECUR_YEARLY_DATE => 'Yearly',
        Horde_Date_Recurrence::RECUR_YEARLY_DAY => 'Yearly',
        Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY => 'Yearly'],
    'perms' => ['all' => Horde_Perms::ALL,
        'show' => Horde_Perms::SHOW,
        'read' => Horde_Perms::READ,
        'edit' => Horde_Perms::EDIT,
        'delete' => Horde_Perms::DELETE,
        'delegate' => Kronolith::PERMS_DELEGATE],
];
if ($has_tasks) {
    $code['conf']['tasks'] = $registry->tasks->ajaxDefaults();
}
// Calendars
foreach ([true, false] as $my) {
    foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_CALENDARS) as $id => $calendar) {
        $owner = $GLOBALS['registry']->getAuth()
            && $calendar->owner() == $GLOBALS['registry']->getAuth();
        if (($my && $owner) || (!$my && !$owner)) {
            $code['conf']['calendars']['internal'][$id] = [
                'name' => ($owner || !$calendar->owner() ? '' : '[' . $GLOBALS['registry']->convertUsername($calendar->owner(), false) . '] ')
                    . $calendar->name(),
                'desc' => $calendar->description(),
                'owner' => $owner,
                'fg' => $calendar->foreground(),
                'bg' => $calendar->background(),
                'show' => in_array($id, $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS)),
                'edit' => $calendar->hasPermission(Horde_Perms::EDIT),
                'feed' => (string) Kronolith::feedUrl($id),
                'embed' => Kronolith::embedCode($id)];
            if ($owner) {
                $code['conf']['calendars']['internal'][$id]['perms'] = Kronolith::permissionToJson($calendar->share()->getPermission());
            }
        }
    }

    // Tasklists
    if (!$has_tasks) {
        continue;
    }
    foreach ($registry->tasks->listTasklists($my, Horde_Perms::SHOW) as $id => $tasklist) {
        $owner = $GLOBALS['registry']->getAuth()
            && $tasklist->get('owner') == $GLOBALS['registry']->getAuth();
        if (($my && $owner) || (!$my && !$owner)) {
            $code['conf']['calendars']['tasklists']['tasks/' . $id] = [
                'name' => Kronolith::getLabel($tasklist),
                'desc' => $tasklist->get('desc'),
                'owner' => $owner,
                'fg' => Kronolith::foregroundColor($tasklist),
                'bg' => Kronolith::backgroundColor($tasklist),
                'show' => in_array('tasks/' . $id, $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS)),
                'edit' => $tasklist->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)];
            if ($owner) {
                $code['conf']['calendars']['tasklists']['tasks/' . $id]['perms'] = Kronolith::permissionToJson($tasklist->getPermission());
            }
        }
    }
}

// Timeobjects
foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_EXTERNAL_CALENDARS) as $id => $calendar) {
    if ($calendar->api() == 'tasks') {
        continue;
    }
    if (!$calendar->display()) {
        continue;
    }
    $code['conf']['calendars']['external'][$id] = [
        'name' => $calendar->name(),
        'fg' => $calendar->foreground(),
        'bg' => $calendar->background(),
        'api' => $registry->get('name', $registry->hasInterface($calendar->api())),
        'show' => in_array($id, $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS))];
}

// Remote calendars
foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_REMOTE_CALENDARS) as $url => $calendar) {
    $code['conf']['calendars']['remote'][$url] = array_merge(
        ['name' => $calendar->name(),
            'desc' => $calendar->description(),
            'owner' => true,
            'fg' => $calendar->foreground(),
            'bg' => $calendar->background(),
            'show' => in_array($url, $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_REMOTE_CALENDARS))],
        $calendar->credentials()
    );
}

// Holidays
foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_HOLIDAYS) as $id => $calendar) {
    $code['conf']['calendars']['holiday'][$id] = [
        'name' => $calendar->name(),
        'fg' => $calendar->foreground(),
        'bg' => $calendar->background(),
        'show' => in_array($id, $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_HOLIDAYS))];
}

/* Gettext strings used in core javascript files. */
$code['text'] = [
    'ajax_error' => _("Error when communicating with the server."),
    'allday' => _("All day"),
    'noevents' => _("No events to display"),
    'yesterday' => _("Yesterday"),
    'today' => _("Today"),
    'tomorrow' => _("Tomorrow"),
];

/* Map day masks to localized day names for recursion */
$masks = [
    Horde_Date::MASK_SUNDAY => Horde_Nls::getLangInfo(DAY_1),
    Horde_Date::MASK_MONDAY => Horde_Nls::getLangInfo(DAY_2),
    Horde_Date::MASK_TUESDAY => Horde_Nls::getLangInfo(DAY_3),
    Horde_Date::MASK_WEDNESDAY => Horde_Nls::getLangInfo(DAY_4),
    Horde_Date::MASK_THURSDAY => Horde_Nls::getLangInfo(DAY_5),
    Horde_Date::MASK_FRIDAY => Horde_Nls::getLangInfo(DAY_6),
    Horde_Date::MASK_SATURDAY => Horde_Nls::getLangInfo(DAY_7)];
foreach ($masks as $i => $text) {
    $code['text']['weekday'][$i] = $text;
}

$code['text']['recur']['desc'] = [
    Horde_Date_Recurrence::RECUR_DAILY => [
        _("Recurs daily"),
        sprintf(_("Recurs every %s days"), "#{interval}")],
    Horde_Date_Recurrence::RECUR_WEEKLY => [
        sprintf(_("Recurs weekly on every %s"), "#{weekday}"),
        sprintf(_("Recurs every %s weeks on %s"), "#{interval}", "#{weekday}")],
    Horde_Date_Recurrence::RECUR_MONTHLY_DATE => [
        sprintf(_("Recurs on the %s of every month"), "#{date}"),
        sprintf(_("Recurs every %s months on the %s"), "#{interval}", "#{date}")],
    Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => [
        _("Recurs every month on the same weekday"),
        sprintf(_("Recurs every %s months on the same weekday"), "#{interval}")],
    Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY => [
        _("Recurs every month on the same last weekday"),
        sprintf(_("Recurs every %s months on the same last weekday"), "#{interval}")],
    Horde_Date_Recurrence::RECUR_YEARLY_DATE => [
        sprintf(_("Recurs once a year, on %s"), '#{date}'),
        sprintf(_("Recurs every %s years on %s"), '#{interval}', '#{date}')],
    Horde_Date_Recurrence::RECUR_YEARLY_DAY => [
        _("Recurs once a year, on the same day"),
        sprintf(_("Recurs every %s years on the same day"), '#{interval}')],
    Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY => [
        _("Recurs every year on the same weekday"),
        sprintf(_("Recurs every %s years on the same weekday"), "#{interval}")],
];
$code['text']['recur']['exception'] = _("Exception");

echo $GLOBALS['page_output']->addInlineJsVars([
    'var Kronolith' => $code,
], ['top' => true]);
