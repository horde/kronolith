<?php

/**
 * Copyright 1999-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

use Horde\Date\Formatter\IcuFormatter;

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('month:' . Kronolith::currentDate()->dateString())->redirect();
}

$view = Kronolith::getView('Month');

$page_output->addScriptFile('tooltips.js', 'horde');
Kronolith::addCalendarLinks();

$page_output->header([
    'body_class' => $prefs->getValue('show_panel') ? 'rightPanel' : null,
    'title' => $view->date->format('MMMM yyyy', new IcuFormatter(), $GLOBALS['language']),
]);
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(['listeners' => 'status']);
Kronolith::tabs($view);
$view->html();
require KRONOLITH_TEMPLATES . '/calendar_titles.inc';
$page_output->footer();
