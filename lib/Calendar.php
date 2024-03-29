<?php
/**
 * Kronolith_Calendar defines an API for single calendars.
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Calendar
{
    /**
     * The background.
     *
     * @var string
     */
    protected $_background;

    /**
     * Constructor.
     *
     * @param array $params  A hash with any parameters that this calendar
     *                       might need.
     */
    public function __construct($params = array())
    {
        foreach ($params as $param => $value) {
            $this->{'_' . $param} = $value;
        }
    }

    /**
     * Returns the owner of this calendar.
     *
     * @return string  This calendar's owner.
     */
    public function owner()
    {
        return $GLOBALS['registry']->getAuth();
    }

    /**
     * Returns the name of this calendar.
     *
     * @return string  This calendar's name.
     */
    abstract public function name();

    /**
     * Returns the description of this calendar.
     *
     * @return string  This calendar's description.
     */
    public function description()
    {
        return '';
    }

    /**
     * Returns the background color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function background()
    {
        return isset($this->_background) ? $this->_background : '#dddddd';
    }

    /**
     * Returns the foreground color for this calendar.
     *
     * @return string  A HTML color code.
     */
    public function foreground()
    {
        return Horde_Image::brightness($this->background()) < 128 ? '#fff' : '#000';
    }

    /**
     * Returns the CSS color definition for this calendar.
     *
     * @param boolean $with_attribute  Whether to wrap the colors inside a
     *                                 "style" attribute.
     *
     * @return string  A CSS string with color definitions.
     */
    public function css($with_attribute = true)
    {
        $css = 'background-color:' . $this->background() . ';color:' . $this->foreground();
        if ($with_attribute) {
            $css = ' style="' . $css . '"';
        }
        return $css;
    }

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for. Defaults
     *                             to the current user.
     * @param string $creator      An event creator, to check for creator
     *                             permissions.
     *
     * @return boolean  Whether the user has the permission on this calendar.
     */
    public function hasPermission($permission, $user = null, $creator = null)
    {
        switch ($permission) {
        case Horde_Perms::SHOW:
        case Horde_Perms::READ:
            return true;

        default:
            return false;
        }
    }

    /**
     * Whether this calendar is supposed to be displayed in lists.
     *
     * @return boolean  True if this calendar should be displayed.
     */
    abstract public function display();

    /**
     * Returns the CalDAV URL to this calendar.
     *
     * @return string  This calendar's CalDAV URL.
     */
    public function caldavUrl()
    {
        throw new LogicException('CalDAV is only available for internal calendars');
    }

    /**
     * Returns the CalDAV URL for a calendar or task list.
     *
     * @param string $id         A collection ID.
     * @param string $interface  The collection's application.
     *
     * @return string  The collection's CalDAV URL.
     */
    protected function _caldavUrl($id, $interface)
    {
        global $conf, $injector, $registry;

        $user = $registry->convertUsername($registry->getAuth(), false);
        try {
            $user = $injector->getInstance('Horde_Core_Hooks')
                ->callHook('davusername', 'horde', array($user, false));
        } catch (Horde_Exception_HookNotSet $e) {
        }
        try {
            $url = Horde::url(
                $registry->get('webroot', 'horde')
                    . ($conf['urls']['pretty'] == 'rewrite'
                        ? '/rpc/calendars/'
                        : '/rpc.php/calendars/'),
                true,
                -1
            );
            $url .= $user . '/';
            $url .= $injector->getInstance('Horde_Dav_Storage')
                ->getExternalCollectionId($id, $interface) . '/';
        } catch (Horde_Exception $e) {
            return null;
        }

        return $url;
    }

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        return array(
            'name'  => $this->name(),
            'desc'  => $this->description(),
            'owner' => true,
            'users' => array(),
            'fg'    => $this->foreground(),
            'bg'    => $this->background(),
        );
    }
}
