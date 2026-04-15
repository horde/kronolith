<?php

/**
 * Special prefs handling for the 'default_alarm_management' preference.
 *
 * Copyright 2012-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Prefs_Special_DefaultAlarm implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui) {}

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $prefs;

        $view = new Horde_View(['templatePath' => KRONOLITH_TEMPLATES . '/prefs']);

        if ($alarm_value = $prefs->getValue('default_alarm')) {
            if ($alarm_value % 10080 == 0) {
                $alarm_value /= 10080;
                $view->week = true;
            } elseif ($alarm_value % 1440 == 0) {
                $alarm_value /= 1440;
                $view->day = true;
            } elseif ($alarm_value % 60 == 0) {
                $alarm_value /= 60;
                $view->hour = true;
            } else {
                $view->minute = true;
            }
        } else {
            $view->minute = true;
        }

        $view->alarm_value = intval($alarm_value);

        return $view->render('defaultalarm');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        $GLOBALS['prefs']->setValue('default_alarm', intval($ui->vars->alarm_value) * intval($ui->vars->alarm_unit));
        return true;
    }

}
