<?php
/**
 * Kronolith resources
 *
 */
abstract class Kronolith_Resource_Base
{
    protected $_params = array();
    protected $_id = '';

    public function __construct($params = array())
    {
        if (!empty($params['id'])) {
            // Existing resource
            $this->_id = $params['id'];
        }

        $this->_params = $params;
    }

    /**
     *
     *  Properties:
     * name        - Display name of resource.
     * calendar_id - The calendar associated with this resource.
     * category    - The category of this resource...an arbitrary label used
     *               to group multiple resources for the resource_group implementation
     * properties  - any other properties this resource may have?
     *               (max capacity of room, size of TV, whatever...)
     *               probably just for display, not sure how this would work
     *               if we wanted to be able to search since we are implementing these generically.
     *               Don't think we want a datatree-style attirbutes table for this.
     */
    public function __get($property)
    {
        if ($property == 'id') {
            return $this->_id;
        }

        if (isset($this->_params[$property])) {
            return $this->_params[$property];
        } else {
            throw new Horde_Exception(sprintf(_("Invalid property, %s, requested in Kronolith_Resource"), $property));
        }
    }

    /**
     * Should this take an event, or a time range?
     *
     * @param $startTime
     * @param $endTime
     * @return unknown_type
     */
    abstract public function isFree($startTime, $endTime);

    /**
     * Adds $event to this resource's calendar - thus blocking the time
     * for any other event.
     *
     * @param $event
     * @return unknown_type
     */
    abstract public function attachToEvent($event);

    /**
     * Remove this event from resource's calendar
     *
     * @param $event
     * @return unknown_type
     */
    abstract public function detachFromEvent($event);

    /**
     * Obtain the freebusy information for this resource.  Takes into account
     * if this is a group of resources or not. (Returns the cumulative FB info
     * for all the resources in the group.
     * @return unknown_type
     */
    abstract public function getFreeBusy();

    /**
     * Sets the current resource's id. Must not be an existing resource.
     *
     * @param int $id  The id for this resource
     *
     * @return unknown_type
     */
    abstract public function setId($id);

}