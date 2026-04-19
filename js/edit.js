/**
 * edit.js - Base application logic.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Kronolith
 */

var KronolithEdit =
{
    calendarSelect: function(e)
    {
        var prefix;

        switch (e.target.id) {
        case 'end_img':
            prefix = 'end';
            break;

        case 'recur_end_img':
            prefix = 'recur_end';
            break;

        case 'start_img':
            prefix = 'start';
            break;

        default:
            return;
        }

        document.getElementById(prefix + '_year').value = e.detail.getFullYear();
        document.getElementById(prefix + '_month').value = e.detail.getMonth() + 1;
        document.getElementById(prefix + '_day').value = e.detail.getDate();

        this.doAction(prefix + '_year');
    },

    updateWday: function(p)
    {
        document.getElementById(p + '_wday').textContent = '(' + Horde_Calendar.fullweekdays[this.getFormDate(p).getDay()] + ')';
    },

    getFormDate: function(p)
    {
        return new Date(document.getElementById(p + '_year').value, document.getElementById(p + '_month').value - 1, document.getElementById(p + '_day').value);
    },

    clickHandler: function(e)
    {
        if (e.button === 2) {
            return;
        }

        var elt = e.target,
            id = elt.getAttribute('id');

        switch (id) {
        case 'allday':
        case 'allday_label':
            this.doAction('allday');
            break;

        case 'am':
        case 'am_label':
        case 'pm':
        case 'pm_label':
            this.doAction('am');
            break;

        case 'attendees_button':
            HordePopup.popup({
                params: {
                    startdate: (('000' + document.getElementById('start_year').value).slice(-4) + ('0' + document.getElementById('start_month').value).slice(-2) + ('0' + document.getElementById('start_day').value).slice(-2)),
                },
                url: elt.getAttribute('href')
            });
            e.preventDefault();
            e.stopPropagation();
            break;

        case 'eam':
        case 'eam_label':
        case 'epm':
        case 'epm_label':
            this.doAction('eam');
            break;

        case 'edit_current':
        case 'edit_future':
            document.getElementById('start_year').value = parseInt(document.getElementById('recur_ex').value.substr(0, 4), 10);
            document.getElementById('start_month').selectedIndex = parseInt(document.getElementById('recur_ex').value.substr(4, 2), 10) - 1;
            document.getElementById('start_day').selectedIndex = parseInt(document.getElementById('recur_ex').value.substr(6, 2), 10) - 1;

            this.updateWday('start');
            this.updateEndDate();

            this.uncheckWeeklyCheckboxes();
            break;

        case 'end_img':
        case 'recur_end_img':
        case 'start_img':
            Horde_Calendar.open(elt, this.getFormDate(id.slice(0, -4)));
            e.preventDefault();
            e.stopPropagation();
            break;

        case 'mo':
        case 'tu':
        case 'we':
        case 'th':
        case 'fr':
        case 'sa':
        case 'su':
            this.setInterval('recurweekly', 'recur_weekly_interval');
            this.setRecur(2);
            break;

        case 'nooverwrite':
        case 'yesoverwrite':
            if (document.getElementById('nooverwrite').checked) {
                document.getElementById('notification_options').hidden = true;
            } else {
                document.getElementById('notification_options').hidden = false;
                document.getElementById('yesalarm').checked = true;
            }
            break;

        case 'recurdaily':
        case 'recurdaily_label':
            this.setInterval('recurdaily', 'recur_daily_interval');
            break;

        case 'recurmonthday':
        case 'recurmonthday_label':
            this.setInterval('recurmonthday', 'recur_day_of_month_interval');
            break;

        case 'recurmonthweek':
        case 'recurmonthweek_label':
            this.setInterval('recurmonthweek', 'recur_week_of_month_interval');
            break;

        case 'recurmonthlastweek':
        case 'recurmonthlastweek_label':
            this.setInterval('recurmonthlastweek', 'recur_last_week_of_month_interval');
            break;

        case 'recurnone':
            this.clearFields(0);
            break;

        case 'recurweekly':
        case 'recurweekly_label':
            this.setInterval('recurweekly', 'recur_weekly_interval');
            break;

        case 'recuryear':
        case 'recuryear_label':
            this.setInterval('recuryear', 'recur_yearly_interval');
            break;

        case 'recuryearday':
        case 'recuryearday_label':
            this.setInterval('recuryearday', 'recur_yearly_day_interval');
            break;

        case 'recuryearweekday':
        case 'recuryearweekday_label':
            this.setInterval('recuryearweekday', 'recur_yearly_weekday_interval');
            break;

        default:
            if (elt.getAttribute('name') == 'resetButton') {
                document.getElementById('eventform').reset();
                this.updateWday('start');
                this.updateWday('end');
            } else {
                if (!elt.matches('TD')) {
                    elt = elt.closest('TD');
                }
                if (elt && elt.classList.contains('toggle')) {
                    var first = elt.firstElementChild;
                    if (first) {
                        this.toggleDisplay(first);
                        if (first.nextElementSibling) {
                            this.toggleDisplay(first.nextElementSibling);
                        }
                    }
                    this.toggleDisplay(document.getElementById('section' + elt.id.substr(6)));
                }
            }
            break;
        }
    },

    /**
     * Toggle an element's visibility via its display style, matching the
     * semantics of Prototype's Element.toggle().
     */
    toggleDisplay: function(elt)
    {
        if (!elt) {
            return;
        }
        var hidden = elt.style.display === 'none' ||
            window.getComputedStyle(elt).display === 'none';
        elt.style.display = hidden ? '' : 'none';
    },

    /**
     * Uncheck the recurrence checkboxes/radios that are siblings of the
     * weekly interval field (Prototype: $(...).adjacent('.checkbox')).
     */
    uncheckWeeklyCheckboxes: function()
    {
        var elt = document.getElementById('recur_weekly_interval');
        if (!elt || !elt.parentNode) {
            return;
        }
        Array.prototype.forEach.call(elt.parentNode.children, function(sib) {
            if (sib !== elt && sib.matches('.checkbox')) {
                sib.checked = false;
            }
        });
    },

    changeHandler: function(e)
    {
        this.doAction(e.target.getAttribute('id'));
    },

    keypressHandler: function(e)
    {
        switch (e.target.getAttribute('id')) {
        case 'recur_daily_interval':
            this.setRecur(1);
            break;

        case 'recur_weekly_interval':
            this.setRecur(2);
            break;

        case 'recur_day_of_month_interval':
            this.setRecur(3);
            break;

        case 'recur_week_of_month_interval':
            this.setRecur(4);
            break;

        case 'recur_last_week_of_month_interval':
            this.setRecur(8);
            break;

        case 'recur_yearly_interval':
            this.setRecur(5);
            break;

        case 'recur_yearly_day_interval':
            this.setRecur(6);
            break;

        case 'recur_yearly_weekday_interval':
            this.setRecur(7);
            break;
        }
    },

    doAction: function(id)
    {
        var endDate, endHour, duration, durHour, durMin, failed, startDate,
            startHour;

        switch (id) {
        case 'allday':
            if (document.getElementById('allday').checked) {
                if (KronolithVar.twentyFour) {
                    document.getElementById('start_hour').selectedIndex = 0;
                } else {
                    document.getElementById('start_hour').selectedIndex = 11;
                    document.getElementById('am').checked = true;
                }
                document.getElementById('start_min').value = 0;
                document.getElementById('dur_day').value = 1;
                document.getElementById('dur_hour').value = 0;
                document.getElementById('dur_min').value = 0;
            }
            this.updateEndDate();
            document.getElementById('duration').value = 1;
            break;

        case 'am':
            document.getElementById('allday').checked = false;
            this.updateEndDate();
            break;

        case 'dur_day':
        case 'dur_hour':
        case 'dur_min':
            document.getElementById('allday').checked = false;
            this.updateEndDate();
            document.getElementById('end').value = 1;
            break;

        case 'eam':
        case 'epm':
            break;

        case 'end_year':
        case 'end_month':
        case 'end_day':
            this.updateWday('end');
            // Fall-through

        case 'end_hour':
        case 'end_min':
        case 'pm':
            document.getElementById('end').value = 1;

            startHour = this.convertTo24Hour(parseInt(document.getElementById('start_hour').value, 10), 'pm');
            endHour = this.convertTo24Hour(parseInt(document.getElementById('end_hour').value, 10), 'epm');
            startDate = Date.UTC(
                document.getElementById('start_year').value,
                document.getElementById('start_month').value - 1,
                document.getElementById('start_day').value,
                startHour,
                document.getElementById('start_min').value
            );
            endDate = Date.UTC(
                document.getElementById('end_year').value,
                document.getElementById('end_month').value - 1,
                document.getElementById('end_day').value,
                endHour,
                document.getElementById('end_min').value
            );

            if (endDate < startDate) {
                if (KronolithVar.twentyFour &&
                    document.getElementById('start_year').value == document.getElementById('end_year').value &&
                    document.getElementById('start_month').value == document.getElementById('end_month').value &&
                    document.getElementById('start_day').value == document.getElementById('end_day').value &&
                    !document.getElementById('pm').checked && !document.getElementById('epm').checked) {
                    /* If the end hour is marked as the (default) AM, and
                     * the start hour is also AM, automatically default
                     * the end hour to PM if the date is otherwise the
                     * same - assume that the user wants a 9am-2pm event
                     * (for example), instead of throwing an error. */

                    // Toggle the end date to PM.
                    document.getElementById('epm').checked = true;

                    // Recalculate end time
                    endHour = this.convertTo24Hour(parseInt(document.getElementById('end_hour').value, 10), 'epm');
                    endDate = Date.UTC(
                        document.getElementById('end_year').value,
                        document.getElementById('end_month').value - 1,
                        document.getElementById('end_day').value,
                        endHour,
                        document.getElementById('end_min').value
                    );
                } else {
                    alert(KronolithText.enddate_error);
                    endDate = startDate;
                    failed = true;
                }
            }

            duration = (endDate - startDate) / 1000;
            document.getElementById('dur_day').value = Math.floor(duration / 86400);
            duration %= 86400;

            durHour = Math.floor(duration / 3600);
            duration %= 3600;

            durMin = Math.floor(duration / 60 / 5);

            document.getElementById('dur_hour').selectedIndex = durHour;
            document.getElementById('dur_min').selectedIndex = durMin;
            document.getElementById('allday').checked = false;

            if (failed) {
                this.updateEndDate();
            }
            break;

        case 'recur_end_year':
        case 'recur_end_month':
        case 'recur_end_day':
            document.getElementById('recur_end_type').value = 1;
            this.updateWday('recur_end');
            break;

        case 'recur_daily_interval':
            this.setRecur(1);
            break;

        case 'recur_weekly_interval':
            this.setRecur(2);
            break;

        case 'recur_day_of_month_interval':
            this.setRecur(3);
            break;

        case 'recur_week_of_month_interval':
            this.setRecur(4);
            break;

        case 'recur_last_week_of_month_interval':
            this.setRecur(8);
            break;

        case 'recur_yearly_interval':
            this.setRecur(5);
            break;

        case 'recur_yearly_day_interval':
            this.setRecur(6);
            break;

        case 'recur_yearly_weekday_interval':
            this.setRecur(7);
            break;

        case 'start_year':
        case 'start_month':
        case 'start_day':
            this.updateWday('start');
            // Fall-through

        case 'start_hour':
        case 'start_min':
            document.getElementById('allday').checked = false;
            this.updateEndDate();
            break;
        }
    },

    updateEndDate: function()
    {
        var endHour, endYear, msecs,
            startHour = this.convertTo24Hour(parseInt(document.getElementById('start_hour').value, 10), 'pm'),
            startDate = new Date(
                document.getElementById('start_year').value,
                document.getElementById('start_month').value - 1,
                document.getElementById('start_day').value,
                startHour,
                document.getElementById('start_min').value
            ),
            endDate = new Date(),
            minutes = document.getElementById('dur_day').value * 1440;

        minutes += document.getElementById('dur_hour').value * 60;
        minutes += parseInt(document.getElementById('dur_min').value);
        msecs = minutes * 60000;

        endDate.setTime(startDate.getTime() + msecs);

        endYear = endDate.getFullYear();

        document.getElementById('end_year').value = endYear;
        document.getElementById('end_month').selectedIndex = endDate.getMonth();
        document.getElementById('end_day').selectedIndex = endDate.getDate() - 1;

        endHour = endDate.getHours();
        if (!KronolithVar.twentyFour) {
            if (endHour < 12) {
                if (endHour === 0) {
                    endHour = 12;
                }
                document.getElementById('eam').checked = true;
            } else {
                if (endHour > 12) {
                    endHour -= 12;
                }
                document.getElementById('epm').checked = true;
            }
            endHour -= 1;
       }

        document.getElementById('end_hour').selectedIndex = endHour;
        document.getElementById('end_min').selectedIndex = endDate.getMinutes() / 5;

        this.updateWday('end');
    },

    // Converts a 12 hour based number to its 24 hour format
    convertTo24Hour: function(val, elt)
    {
        if (!KronolithVar.twentyFour) {
            if (document.getElementById(elt).checked) {
                if (val != 12) {
                    val += 12;
                }
            } else if (val == 12) {
                val = 0;
            }
        }

        return val;
    },

    setInterval: function(elt, id)
    {
        if (!document.getElementById(id).value) {
            document.getElementById(elt).value = 1;
        }

        switch (id) {
        case 'recur_daily_interval':
            KronolithEdit.clearFields(1);
            break;

        case 'recur_weekly_interval':
            KronolithEdit.clearFields(2);
            break;

        case 'recur_day_of_month_interval':
            KronolithEdit.clearFields(3);
            break;

        case 'recur_week_of_month_interval':
            KronolithEdit.clearFields(4);
            break;

        case 'recur_last_week_of_month_interval':
            KronolithEdit.clearFields(8);
            break;

        case 'recur_yearly_interval':
            KronolithEdit.clearFields(5);
            break;
        }
    },

    setRecur: function(index)
    {
        document.eventform.recur[index].checked = true;
        KronolithEdit.clearFields(index);
    },

    clearFields: function(index)
    {
        if (index != 1) {
            document.getElementById('recur_daily_interval').value = '';
        }
        if (index != 2) {
            document.getElementById('recur_weekly_interval').value = '';
            this.uncheckWeeklyCheckboxes();
        }
        if (index != 3) {
            document.getElementById('recur_day_of_month_interval').value = '';
        }
        if (index != 4) {
            document.getElementById('recur_week_of_month_interval').value = '';
        }
        if (index != 8) {
            document.getElementById('recur_last_week_of_month_interval').value = '';
        }
        if (index != 5) {
            document.getElementById('recur_yearly_interval').value = '';
        }
    },

    onDomLoad: function()
    {
        this.updateWday('start');
        this.updateWday('end');
        if (document.getElementById('recur_end_wday')) {
            this.updateWday('recur_end');
        }
        var eventform = document.getElementById('eventform');
        eventform.addEventListener('click', this.clickHandler.bind(this));
        eventform.addEventListener('change', this.changeHandler.bind(this));
        eventform.addEventListener('keypress', this.keypressHandler.bind(this));

        document.getElementById('title').focus();
    }

};

document.addEventListener('DOMContentLoaded', KronolithEdit.onDomLoad.bind(KronolithEdit));
document.addEventListener('Horde_Calendar:select', KronolithEdit.calendarSelect.bind(KronolithEdit));
