<?php

/**
 * This class represent a view of multiple free busy information sets.
 *
 * Copyright 2003-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_FreeBusy_View
{
    protected $_requiredMembers = [];
    protected $_optionalMembers = [];
    protected $_requiredResourceMembers = [];
    protected $_optionalResourceMembers = [];
    protected $_timeBlocks = [];

    protected $_startHour;
    protected $_endHour;

    protected $_start;
    protected $_end;

    /**
     * Adds a required attendee
     *
     * @param Horde_Icalendar_Vfreebusy $vFreebusy
     */
    public function addRequiredMember(Horde_Icalendar_Vfreebusy $vFreebusy)
    {
        $this->_requiredMembers[] = clone $vFreebusy;
    }

    /**
     * Adds an optional attendee
     *
     * @param Horde_Icalendar_Vfreebusy $vFreebusy
     */
    public function addOptionalMember(Horde_Icalendar_Vfreebusy $vFreebusy)
    {
        $this->_optionalMembers[] = clone $vFreebusy;
    }

    /**
     * Adds an optional resource
     *
     * @param Horde_Icalendar_Vfreebusy $vFreebusy
     */
    public function addOptionalResourceMember(Horde_Icalendar_Vfreebusy $vFreebusy)
    {
        $this->_optionalResourceMembers[] = clone $vFreebusy;
    }

    /**
     * Adds a required resource
     *
     * @param Horde_Icalendar_Vfreebusy $vFreebusy
     */
    public function addRequiredResourceMember(Horde_Icalendar_Vfreebusy $vFreebusy)
    {
        $this->_requiredResourceMembers[] = clone $vFreebusy;
    }

    /**
     * Renders the fb view
     *
     * @global Horde_Prefs $prefs
     * @param  Horde_Date $day  The day to render
     *
     * @return string  The html of the rendered fb view.
     */
    public function render(?Horde_Date $day = null)
    {
        global $prefs;

        $this->_startHour = floor($prefs->getValue('day_hour_start') / 2);
        $this->_endHour = floor(($prefs->getValue('day_hour_end') + 1) / 2);

        $this->_render($day);

        $view = new Horde_View(['templatePath' => KRONOLITH_TEMPLATES . '/fbview']);

        $vCal = new Horde_Icalendar();

        /* Required members */
        $required = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        foreach ($this->_requiredMembers as $member) {
            $required->merge($member, false);
        }
        foreach ($this->_requiredResourceMembers as $member) {
            $required->merge($member, false);
        }
        $required->simplify();

        /* Optional members */
        $optional = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        foreach ($this->_optionalMembers as $member) {
            $optional->merge($member, false);
        }
        foreach ($this->_optionalResourceMembers as $member) {
            $optional->merge($member, false);
        }
        $optional->simplify();

        /* Optimal time calculation */
        $optimal = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        $optimal->merge($required, false);
        $optimal->merge($optional);

        $html = $view->render('header', ['title' => $this->_title()]);

        $hours_html = $this->_hours();

        // Set C locale to avoid localized decimal separators during CSS width
        // calculation.
        $lc = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');

        // Required to attend.
        if (count($this->_requiredMembers) > 0) {
            $rows = '';
            foreach ($this->_requiredMembers as $member) {
                $member->simplify();
                $blocks = $this->_getBlocks($view, $member, $member->getBusyPeriods(), 'busyblock', _("Busy"));
                $rows .= $view->renderPartial('row', ['locals' => [
                    'blocks' => $blocks,
                    'name' => htmlspecialchars($member->getName()),
                ]]);
            }

            $html .= $view->render('section', [
                'title' => _("Required Attendees"),
                'rows' => $rows,
                'span' => count($this->_timeBlocks),
                'hours' => $hours_html,
            ]);
        }

        // Optional to attend.
        if (count($this->_optionalMembers) > 0) {
            $rows = '';
            foreach ($this->_optionalMembers as $member) {
                $member->simplify();
                $blocks = $this->_getBlocks($view, $member, $member->getBusyPeriods(), 'busyblock', _("Busy"));
                $rows .= $view->renderPartial('row', ['locals' => [
                    'blocks' => $blocks,
                    'name' => htmlspecialchars($member->getName()),
                ]]);
            }

            $html .= $view->render('section', [
                'title' => _("Optional Attendees"),
                'rows' => $rows,
                'span' => count($this->_timeBlocks),
                'hours' => $hours_html,
            ]);
        }

        // Resources
        if (count($this->_requiredResourceMembers) > 0 || count($this->_optionalResourceMembers) > 0) {
            $rows = '';
            foreach ($this->_requiredResourceMembers as $member) {
                $member->simplify();
                $blocks = $this->_getBlocks($view, $member, $member->getBusyPeriods(), 'busyblock', _("Busy"));
                $rows .= $view->renderPartial('row', ['locals' => [
                    'blocks' => $blocks,
                    'name' => htmlspecialchars($member->getName()),
                ]]);
            }
            foreach ($this->_optionalResourceMembers as $member) {
                $member->simplify();
                $blocks = $this->_getBlocks($view, $member, $member->getBusyPeriods(), 'busyblock', _("Busy"));
                $rows .= $view->renderPartial('row', ['locals' => [
                    'blocks' => $blocks,
                    'name' => htmlspecialchars($member->getName()),
                ]]);
            }
            $html .= $view->render('section', [
                'title' => _("Required Resources"),
                'rows' => $rows,
                'span' => count($this->_timeBlocks),
                'hours' => $hours_html,
            ]);
        }

        // Possible meeting times.
        $optimal->setAttribute('ORGANIZER', _("All Attendees"));
        $blocks = $this->_getBlocks(
            $view,
            $optimal,
            $optimal->getFreePeriods($this->_start->timestamp(), $this->_end->timestamp()),
            'meetingblock',
            _("All Attendees")
        );

        $rows = $view->renderPartial('row', ['locals' => [
            'name' => _("All Attendees"),
            'blocks' => $blocks,
        ]]);

        // Possible meeting times.
        $required->setAttribute('ORGANIZER', _("Required Attendees"));
        $blocks = $this->_getBlocks(
            $view,
            $required,
            $required->getFreePeriods($this->_start->timestamp(), $this->_end->timestamp()),
            'meetingblock',
            _("Required Attendees")
        );

        $rows .= $view->renderPartial('row', ['locals' => [
            'name' => _("Required Attendees"),
            'blocks' => $blocks,
        ]]);

        // Reset locale.
        setlocale(LC_NUMERIC, $lc);

        $html .= $view->render('section', [
            'rows' => $rows,
            'title' => _("Overview"),
            'span' => count($this->_timeBlocks),
            'hours' => $hours_html,
        ]);

        $legend = '';
        if ($prefs->getValue('show_fb_legend')) {
            $legend = $view->render('legend', ['span' => count($this->_timeBlocks)]);
        }
        $html .= $view->render('footer', ['legend' => $legend]);

        return $html;
    }

    /**
     * Attempts to return a concrete Kronolith_FreeBusy_View instance based on
     * $view.
     *
     * @param string $view  The type of concrete Kronolith_FreeBusy_View
     *                      subclass to return.
     *
     * @return mixed  The newly created concrete Kronolith_FreeBusy_View
     *                instance, or false on an error.
     */
    public static function factory($view)
    {
        $driver = basename($view);
        $class = 'Kronolith_FreeBusy_View_' . $driver;
        if (class_exists($class)) {
            return new $class();
        }

        return false;
    }

    /**
     * Attempts to return a reference to a concrete Kronolith_FreeBusy_View
     * instance based on $view.  It will only create a new instance if no
     * Kronolith_FreeBusy_View instance with the same parameters currently
     * exists.
     *
     * This method must be invoked as:
     * $var = &Kronolith_FreeBusy_View::singleton()
     *
     * @param string $view  The type of concrete Kronolith_FreeBusy_View
     *                      subclass to return.
     *
     * @return mixed  The created concrete Kronolith_FreeBusy_View instance, or
     *                false on an error.
     */
    public static function &singleton($view)
    {
        static $instances = [];

        if (!isset($instances[$view])) {
            $instances[$view] = Kronolith_FreeBusy_View::factory($view);
        }

        return $instances[$view];
    }

    /**
     * Render the blocks
     *
     * @param Horde_View $view              View instance for rendering
     * @param Horde_Icalendar_Vfreebusy $member  Member's freebusy info
     * @param array $periods                     Free periods
     * @param string $blockName                  Partial template name (e.g. 'busyblock')
     * @param string $label                      Label to use
     *
     * @return string  The block html
     */
    protected function _getBlocks(Horde_View $view, $member, $periods, $blockName, $label)
    {
        reset($periods);
        [$periodStart, $periodEnd] = each($periods);

        $blocks = '';
        foreach ($this->_timeBlocks as $span) {
            /* Horde_Icalendar_Vfreebusy only supports timestamps at the
             * moment. */
            $start = $span[0]->timestamp();
            $end = $span[1]->timestamp();
            if ($member->getStart() > $start
                || $member->getEnd() < $end) {
                $blocks .= $view->renderPartial('unknownblock');
                continue;
            }

            while ($start > $periodEnd
                   && [$periodStart, $periodEnd] = each($periods));

            if (($periodStart <= $start && $periodEnd >= $start)
                || ($periodStart <= $end && $periodEnd >= $end)
                || ($periodStart <= $start && $periodEnd >= $end)
                || ($periodStart >= $start && $periodEnd <= $end)) {

                $l_start = ($periodStart < $start) ? $start : $periodStart;
                $l_end = ($periodEnd > $end) ? $end : $periodEnd;
                $plen = ($end - $start) / 100.0;

                $left = ($l_start - $start) / $plen;
                $width = ($l_end - $l_start) / $plen;

                $blocks .= $view->renderPartial($blockName, ['locals' => [
                    'left' => $left . '%',
                    'width' => $width . '%',
                    'label' => $label,
                ]]);
            } else {
                $blocks .= $view->renderPartial('emptyblock');
            }
        }

        return $blocks;
    }

    abstract protected function _title();
    abstract protected function _hours();
    abstract protected function _render(?Horde_Date $day = null);

}
