<?php

use Horde\Date\Formatter\IcuFormatter;

/**
 * Display a mini month view of calendar items.
 */
class Kronolith_Block_Month extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = [])
    {
        parent::__construct($app, $params);

        $this->_name = _("This Month");
    }

    /**
     */
    protected function _params()
    {
        $params = [
            'calendar' => [
                'name' => _("Calendar"),
                'type' => 'enum',
                'default' => '__all',
            ],
        ];

        $params['calendar']['values']['__all'] = _("All Visible");
        foreach (Kronolith::listCalendars(Horde_Perms::SHOW, true) as $id => $cal) {
            $params['calendar']['values'][$id] = $cal->name();
        }

        return $params;
    }

    /**
     */
    protected function _title()
    {
        $title = _("All Calendars");
        $url = Horde::url($GLOBALS['registry']->getInitialPage(), true);
        if (isset($this->_params['calendar'])
            && $this->_params['calendar'] != '__all') {
            $calendars = Kronolith::listCalendars();
            if (isset($calendars[$this->_params['calendar']])) {
                $title = htmlspecialchars($calendars[$this->_params['calendar']]->name());
            } else {
                $title = _("Calendar not found");
            }
            $url->add('display_cal', $this->_params['calendar']);
        }
        $date = new Horde_Date(time());

        return $title . ', ' . $url->link() . $date->format('MMMM, yyyy', new IcuFormatter(), $GLOBALS['language']) . '</a>';
    }

    /**
     * Return events occurring on the given calendar day.
     *
     * Events are loaded with cover_dates disabled (like the Upcoming Events
     * block). Match by date span so multi-day events appear on each day.
     *
     * @param array $all_events  Events from listEvents().
     * @param Horde_Date $day    The day to check.
     *
     * @return Kronolith_Event[]
     */
    protected function _eventsForDay(array $all_events, Horde_Date $day)
    {
        $on_day = [];

        $dayStart = clone $day;
        $dayStart->hour = 0;
        $dayStart->min = 0;
        $dayStart->sec = 0;

        $dayEnd = clone $dayStart;
        $dayEnd->mday++;

        foreach ($all_events as $events) {
            if (!is_array($events)) {
                continue;
            }
            foreach ($events as $event) {
                if (!($event instanceof Kronolith_Event)) {
                    continue;
                }
                // Treat end time as exclusive so events ending at 00:00 don't spill into the next day.
                if ($event->start->compareDateTime($dayEnd) >= 0
                    || $event->end->compareDateTime($dayStart) <= 0) {
                    continue;
                }
                $on_day[$event->id] = $event;
            }
        }

        return $on_day;
    }

    /**
     */
    protected function _content()
    {
        global $prefs;

        if (empty($GLOBALS['calendar_manager'])) {
            Kronolith::initialize();
        }

        if (isset($this->_params['calendar'])
            && $this->_params['calendar'] != '__all') {
            $calendars = Kronolith::listCalendars();
            if (!isset($calendars[$this->_params['calendar']])) {
                return _("Calendar not found");
            }
            if (!$calendars[$this->_params['calendar']]->hasPermission(Horde_Perms::READ)) {
                return _("Permission Denied");
            }
        }

        $now = new Horde_Date($_SERVER['REQUEST_TIME']);
        $year = $now->year;
        $month = $now->month;
        $weekStartMonday = (bool) $prefs->getValue('week_start_monday');

        $startday = new Horde_Date(['mday' => 1,
            'month' => $month,
            'year' => $year]);
        $startday = $startday->dayOfWeek();
        if (!$weekStartMonday) {
            $startOfView = 1 - $startday;
        } elseif ($startday == Horde_Date::DATE_SUNDAY) {
            $startOfView = -5;
        } else {
            $startOfView = 2 - $startday;
        }

        $startDate = new Horde_Date([
            'year' => $year,
            'month' => $month,
            'mday' => $startOfView,
        ]);
        $endDate = new Horde_Date([
            'year' => $year,
            'month' => $month,
            'mday' => Horde_Date_Utils::daysInMonth($month, $year) + 1,
        ]);
        $endDate->mday
            += (7 - ($endDate->format('w') - ($weekStartMonday ? 1 : 0))) % 7;

        $listOptions = [
            'show_recurrence' => true,
            'cover_dates' => false,
        ];

        /* Table start. and current month indicator. */
        $html = '<table cellspacing="1" class="monthgrid" width="100%"><tr>';

        /* Set up the weekdays. */
        $weekdays = [_("Mo"), _("Tu"), _("We"), _("Th"), _("Fr"), _("Sa")];
        if (!$prefs->getValue('week_start_monday')) {
            array_unshift($weekdays, _("Su"));
        } else {
            $weekdays[] = _("Su");
        }
        foreach ($weekdays as $weekday) {
            $html .= '<th class="item">' . $weekday . '</th>';
        }

        try {
            if (isset($this->_params['calendar'])
                && $this->_params['calendar'] != '__all') {
                [$type, $calendar] = explode('_', $this->_params['calendar'], 2);
                $driver = Kronolith::getDriver($type, $calendar);
                $all_events = $driver->listEvents(
                    $startDate,
                    $endDate,
                    $listOptions
                );
            } else {
                $all_events = Kronolith::listEvents(
                    $startDate,
                    $endDate,
                    null,
                    $listOptions
                );
            }
        } catch (Horde_Exception $e) {
            Horde::log($e, Horde_Log::ERR);
            return '<em>' . htmlspecialchars($e->getMessage()) . '</em>';
        }
        if (!is_array($all_events)) {
            $all_events = [];
        }

        $weekday = 0;
        $week = -1;
        for ($date_ob = new Kronolith_Day($month, $startOfView, $year);
            $date_ob->compareDate($endDate) < 0;
            $date_ob->mday++) {
            if ($weekday == 7) {
                $weekday = 0;
            }
            if ($weekday == 0) {
                ++$week;
                $html .= '</tr><tr>';
            }

            if ($date_ob->isToday()) {
                $td_class = 'kronolith-today';
            } elseif ($date_ob->month != $month) {
                $td_class = 'kronolith-othermonth';
            } elseif ($date_ob->dayOfWeek() == 0 || $date_ob->dayOfWeek() == 6) {
                $td_class = 'kronolith-weekend';
            } else {
                $td_class = '';
            }
            $html .= '<td align="center" class="' . $td_class . '">';

            /* Set up the link to the day view. */
            $url = Horde::url('day.php', true)
                ->add('date', $date_ob->dateString());
            if (isset($this->_params['calendar'])
                && $this->_params['calendar'] != '__all') {
                $url->add('display_cal', $this->_params['calendar']);
            }

            $day_events_list = $this->_eventsForDay($all_events, $date_ob);
            if (empty($day_events_list)) {
                /* No events, plain link to the day. */
                $cell = Horde::linkTooltip($url, _("View Day")) . $date_ob->mday . '</a>';
            } else {
                /* There are events; create a cell with tooltip to
                 * list them. */
                $day_events = '';
                foreach ($day_events_list as $event) {
                    if ($event->isAllDay()) {
                        $day_events .= _("All day");
                    } else {
                        $day_events .= $event->start->format($prefs->getValue('twentyFour') ? 'HH:mm' : 'h:mma', new IcuFormatter(), $GLOBALS['language'] ?? 'en_US') . '-' . $event->end->format($prefs->getValue('twentyFour') ? 'HH:mm' : 'h:mma', new IcuFormatter(), $GLOBALS['language'] ?? 'en_US');
                    }
                    $location = $event->getLocation();
                    $day_events .= ':'
                        . ($location ? ' (' . htmlspecialchars($location) . ')' : '')
                        . ' ' . $event->getTitle() . "\n";
                }
                $cell = Horde::linkTooltip($url, _("View Day"), '', '', '', $day_events) . $date_ob->mday . '</a>';
            }

            /* Bold the cell if there are events. */
            if (!empty($day_events_list)) {
                $cell = '<strong>' . $cell . '</strong>';
            }

            $html .= $cell . '</td>';
            ++$weekday;
        }

        return $html . '</tr></table>';
    }

}
