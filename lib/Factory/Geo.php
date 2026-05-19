<?php
use Horde\Injector\Injector;

/**
 * Horde_Injector based factory for Kronolith_Geo drivers
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Factory_Geo extends Horde_Core_Factory_Injector
{
    /**
     * Return the driver instance.
     *
     * @return Kronolith_Geo_Base
     */
    public function create(Horde_Injector|Injector $injector)
    {
        $geodriver = $GLOBALS['conf']['maps']['geodriver'] ?? '';

        if (empty($geodriver) || $geodriver === 'false') {
            return new Kronolith_Geo_Null();
        }

        $class = 'Kronolith_Geo_' . $geodriver;

        try {
            $db = $injector->getInstance('Horde_Db_Adapter');
            return new $class($db);
        } catch (Throwable $e) {
            throw new Kronolith_Exception($e->getMessage());
        }
    }

}
