<?php
/**
 * Horde_Injector based factory for Icalendar storage.
 */
class Kronolith_Factory_IcalendarStorage
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the caldav driver instance.
     *
     * @param string $driver  The storage backend to use.
     * @param array $params   Driver params.
     *
     * @return Kronolith_Caldav_Storage
     * @throws Kronolith_Exception
     */
    public function create(Horde_Injector $injector): Kronolith_Icalendar_Storage
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['icalendar']['driver']);

        if (!empty($this->_instances[$driver])) {
            return $this->_instances[$driver];
        }

        switch ($driver) {
        case 'Sql':
            $params = Horde::getDriverConfig('icalendar', 'Sql');
            if (isset($params['driverconfig']) &&
                $params['driverconfig'] != 'horde') {
                $customParams = $params;
                unset($customParams['driverconfig'], $customParams['table']);
                $db = $injector->getInstance('Horde_Core_Factory_Db')->create('kronolith', $customParams);
            } else {
                $db = $injector->getInstance('Horde_Db_Adapter');
            }
            $instance = new Kronolith_Icalendar_Storage_Sql($db);
            break;

        case 'null':
        default:
            $instance = new Kronolith_Icalendar_Storage_Null($db);
            break;
        }
        $this->_instances[$driver] = $instance;

        return $instance;
    }

}
