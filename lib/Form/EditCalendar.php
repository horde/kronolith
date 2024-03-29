<?php
/**
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_Form_EditCalendar class provides the form for editing a
 * calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_EditCalendar extends Horde_Form
{
    /**
     * Calendar being edited.
     *
     * @var Horde_Share_Object
     */
    protected $_calendar;

    public function __construct($vars, $calendar)
    {
        global $calendar_manager, $conf, $injector, $registry;

        $this->_calendar = $calendar;

        $owner = $calendar->get('owner') == $registry->getAuth() ||
            (is_null($calendar->get('owner')) &&
             $registry->isAdmin());

        parent::__construct(
            $vars,
            $owner
                ? sprintf(_("Edit %s"), $calendar->get('name'))
                : $calendar->get('name')
        );

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);

        if (!$owner) {
            $v = $this->addVariable(_("Owner"), 'owner', 'text', false);
            $owner_name = $injector
                ->getInstance('Horde_Core_Factory_Identity')
                ->create($calendar->get('owner'))
                ->getValue('fullname');
            if (trim($owner_name) == '') {
                $owner_name = $calendar->get('owner');
            }
            $v->setDefault($owner_name ? $owner_name : _("System"));
        }

        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        if ($registry->isAdmin()) {
            $this->addVariable(_("System Calendar"), 'system', 'boolean', false, false, _("System calendars don't have an owner. Only administrators can change the calendar settings and permissions."));
        }
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Tags"), 'tags', 'Kronolith:KronolithTags', false);

        /* Display URL. */
        $url = Horde::url('month.php', true, -1)
            ->add('display_cal', $calendar->getName());
        $this->addVariable(
             _("Display URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $url,
                 'text' => $url,
                 'title' => _("Click or copy this URL to display this calendar"),
                 'target' => '_blank')
             )
        );

        /* Subscription URLs. */
        $calendarObject = $calendar_manager->getEntry(
            Kronolith::ALL_CALENDARS,
            $calendar->getName()
        );

        if (!$calendarObject) {
            throw new Horde_Exception_NotFound();
        }

        $url = $registry->get('webroot', 'horde');
        if (isset($conf['urls']['pretty']) &&
            $conf['urls']['pretty'] == 'rewrite') {
            $webdavUrl = $url . '/rpc/kronolith/';
            $accountUrl = $url . '/rpc/';
        } else {
            $webdavUrl = $url . '/rpc.php/kronolith/';
            $accountUrl = $url . '/rpc.php/';
        }
        $caldavUrl = $calendarObject->caldavUrl();
        if ($caldavUrl) {
            $user = $registry->convertUsername($registry->getAuth(), false);
            try {
                $user = $injector->getInstance('Horde_Core_Hooks')
                    ->callHook('davusername', 'horde', array($user, false));
            } catch (Horde_Exception_HookNotSet $e) {
            }
            $accountUrl = Horde::url($accountUrl, true, -1)
                . 'principals/' . $user;
            $this->addVariable(
                 _("CalDAV Subscription URL"), '', 'link', false, false, null,
                 array(array(
                     'url' => $caldavUrl,
                     'text' => $caldavUrl,
                     'title' => _("Copy this URL to a CalDAV client to subscribe to this calendar"),
                     'target' => '_blank')
                 )
            );
            $this->addVariable(
                 _("CalDAV Account URL"), '', 'link', false, false, null,
                 array(array(
                     'url' => $accountUrl,
                     'text' => $accountUrl,
                     'title' => _("Copy this URL to a CalDAV client to subscribe to all your calendars"),
                     'target' => '_blank')
                 )
            );
        }
        $webdavUrl = Horde::url($webdavUrl, true, -1);
        if ($calendar->get('owner')) {
            $user = $registry->convertUsername($calendar->get('owner'), false);
            try {
                $user = $injector->getInstance('Horde_Core_Hooks')
                    ->callHook('davusername', 'horde', array($user, false));
            } catch (Horde_Exception_HookNotSet $e) {
            }
        } else {
            $webdavUrl .= '-system-';
        }
        $webdavUrl .= '/' . $calendar->getName() . '.ics';
        $this->addVariable(
             _("WebDAV/ICS Subscription URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $webdavUrl,
                 'text' => $webdavUrl,
                 'title' => _("Copy this URL to a WebDAV or ICS client to subscribe to this calendar"),
                 'target' => '_blank')
             )
        );

        /* Feed URL. */
        $url = Kronolith::feedUrl($calendar->getName());
        $this->addVariable(
             _("Feed URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $url,
                 'text' => $url,
             'title' => _("Copy this URL to a news feed reader to subscribe to this calendar"),
                 'target' => '_blank')
             )
        );

        /* Embed code. */
        $v = $this->addVariable(
            _("Embed code"), '', 'longtext', false, false,
            _("To embed this calendar in another website, use the code above."),
            array(4, 60));
        $v->setHelp('embed');
        $v->setDefault(Kronolith::embedCode($calendar->getName()));

        /* Permissions link. */
        if (empty($conf['share']['no_sharing']) && $owner) {
            $url = Horde::url('perms.php')->add('share', $calendar->getName());
            $this->addVariable(
                 '', '', 'link', false, false, null,
                 array(array(
                     'url' => $url,
                     'text' => _("Change Permissions"),
                     'onclick' => Horde::popupJs(
                          $url,
                          array('params' => array('urlencode' => true)))
                          . 'return false;',
                     'class' => 'horde-button',
                     'target' => '_blank')
                 )
            );
        }

        $this->setButtons(array(
            _("Save"),
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        switch ($this->_vars->submitbutton) {
        case _("Save"):
            $info = array();
            foreach (array('name', 'color', 'description', 'tags', 'system') as $key) {
                $info[$key] = $this->_vars->get($key);
            }
            Kronolith::updateShare($this->_calendar, $info);
            break;
        case _("Delete"):
            Horde::url('calendars/delete.php')
                ->add('c', $this->_vars->c)
                ->redirect();
            break;
        case _("Cancel"):
            Horde::url($GLOBALS['prefs']->getValue('defaultview') . '.php', true)
                ->redirect();
            break;
        }
    }

    public function renderActive($renderer = null, $vars = null, $action = '',
                          $method = 'get', $enctype = null, $focus = true)
    {
        return parent::renderActive(
            $this->getRenderer(array('varrenderer_driver' => array('kronolith', 'kronolith'))),
            $this->_vars,
            $action,
            $method,
            $enctype,
            $focus
        );
    }

}
