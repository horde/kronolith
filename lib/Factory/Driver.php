<?php
/**
 * Horde_Injector based factory for Kronolith_Driver.
 */
class Kronolith_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the driver instance.
     *
     * @param string $driver  The storage backend to use.
     * @param array $params   Driver params.
     *
     * @return Kronolith_Driver
     * @throws Kronolith_Exception
     */
    public function create($driver, array $params = array())
    {
        $driver = basename($driver ?? '');

        switch ($driver) {
        case 'external':
        case 'tasklists':
            $driver = 'Horde';
            break;

        case 'holiday':
            $driver = 'Holidays';
            break;

        case 'internal':
            $driver = '';
            break;

        case 'remote':
            $driver = 'Ical';
            break;

        case 'resource':
        case 'Resource':
            $driver = 'Resource';
            break;
        }

        if (empty($driver)) {
            $driver = Horde_String::ucfirst($GLOBALS['conf']['calendar']['driver']);
        }

        if (!empty($this->_instances[$driver])) {
            return $this->_instances[$driver];
        }

        switch ($driver) {
        case 'Sql':
            $params = array_merge(Horde::getDriverConfig('calendar', 'sql'), $params);
            if (isset($params['driverconfig']) &&
                $params['driverconfig'] != 'horde') {
                $customParams = $params;
                unset($customParams['driverconfig'], $customParams['table'], $customParams['utc']);
                $params['db'] = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('kronolith', $customParams);
            } else {
                $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
            }
            break;

        case 'Resource':
            if (!isset($GLOBALS['conf']['calendar']['driver']) ||
                !isset($GLOBALS['conf']['resources']['enabled'])) {
                throw new Kronolith_Exception(_("Resources are disabled"));
            }
            // Need the 'utc' param here since we no longer extend the sql driver.
            $sqlparams = array_merge(Horde::getDriverConfig('calendar', 'sql'), $params);
            $params['utc'] = $sqlparams['utc'];
            break;

        case 'Kolab':
            $params['storage'] = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage');
            break;

        case 'Ical':
        case 'Mock':
            break;

        case 'Horde':
            $params['registry'] = $GLOBALS['registry'];
            break;

        case 'Holidays':
            if (empty($GLOBALS['conf']['holidays']['enable'])) {
                throw new Kronolith_Exception(_("Holidays are disabled"));
            }
            $params['language'] = $GLOBALS['language'];
            break;

        default:
            throw new InvalidArgumentException('No calendar driver specified');
            break;
        }

        $class = 'Kronolith_Driver_' . $driver;
        if (class_exists($class)) {
            $ob = new $class($params);
            try {
                $ob->initialize();
            } catch (Exception $e) {
                $ob = new Kronolith_Driver($params, sprintf(_("The Calendar backend is not currently available: %s"), $e->getMessage()));
            }
        } else {
            $ob = new Kronolith_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }
        $this->_instances[$driver] = $ob;

        return $ob;
    }

}
