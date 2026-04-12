/**
 * goto.js - Menu goto handling.
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

var KronolithGoto =
{
    // Variables defined externally: dayurl, monthurl, weekurl, yearurl

    calendarSelect: function(type, e)
    {
        // Only trigger if this is the goto menu.
        if (!e.target.closest('A.kgotomenu')) {
            return;
        }

        var q, url,
            d = e.detail,
            dateStr = d.getFullYear() +
                String(d.getMonth() + 1).padStart(2, '0') +
                String(d.getDate()).padStart(2, '0'),
            params = new URLSearchParams({ date: dateStr });

        switch (type) {
        case 'day':
            url = this.dayurl;
            break;

        case 'month':
            url = this.monthurl;
            break;

        case 'week':
            url = this.weekurl;
            break;

        case 'year':
            url = this.yearurl;
            break;
        }

        q = url.indexOf('?');
        if (q != -1) {
            var existing = new URLSearchParams(url.substring(q + 1));
            existing.forEach(function(v, k) { params.set(k, v); });
            url = url.substring(0, q);
        }

        window.location = url + '?' + params.toString();
    },

    onDomLoad: function()
    {
        document.getElementById('horde-sidebar').querySelector('A.kgotomenu').addEventListener('click', function(e) {
            Horde_Calendar.open(e.target, typeof window.KronolithDate === 'undefined' ? new Date() : window.KronolithDate);
        });
    }

};

document.addEventListener('DOMContentLoaded', KronolithGoto.onDomLoad.bind(KronolithGoto));
document.addEventListener('Horde_Calendar:select', KronolithGoto.calendarSelect.bind(KronolithGoto, 'day'));
document.addEventListener('Horde_Calendar:selectMonth', KronolithGoto.calendarSelect.bind(KronolithGoto, 'month'));
document.addEventListener('Horde_Calendar:selectWeek', KronolithGoto.calendarSelect.bind(KronolithGoto, 'week'));
document.addEventListener('Horde_Calendar:selectYear', KronolithGoto.calendarSelect.bind(KronolithGoto, 'year'));
