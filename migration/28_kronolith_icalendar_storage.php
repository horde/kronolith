<?php
/**
 * Copyright 2020-2021 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * Add a table for storing caldav event details
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithIcalendarStorage extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        /**
         * NOTE: We use horde/rdo for SQL icalendar storage.
         * The ical_id is never read but the attribute helps us circumvent
         * rdo's notion that an item with an existing primary key must already
         * exist in backend. That would issue an UPDATE where an INSERT is more
         * appropriate.
         */
        if (!in_array('kronolith_icalendar_storage', $this->tables())) {
            $t = $this->createTable('kronolith_icalendar_storage', ['autoincrementKey' => 'ical_id']);
            $t->column('calendar_id', 'string', ['limit' => 255, 'null' => false]);
            $t->column('event_uid', 'string', ['limit' => 255, 'null' => false]);
            $t->column('event_data', 'text', ['null' => false]);
            $t->end();
            $this->addIndex('kronolith_icalendar_storage', ['calendar_id', 'event_uid'], ['unique' => true]);
        }
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('other_attributes', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'other_attributes', 'text', ['default' => '[]']);
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('kronolith_events', 'other_attributes');
        $this->dropTable('kronolith_icalendar_storage');
    }
}
