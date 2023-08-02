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

class KronolithOtherAttributesTypechange extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        /**
         * NOTE: Because of a size-related issue with "X-ALT-DESC"
         * we decided to change the datatype text to mediumtext
         * for the other_attributes field.
         */

        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('other_attributes', array_keys($cols))) {
            $this->changeColumn('kronolith_events', 'other_attributes', 'mediumtext', ['default' => '[]']);
        }
    }

    /**
    * Downgrade
    */
    public function down()
    {
        $this->changeColumn('kronolith_events', 'other_attributes', 'text', ['default' => '[]']);
    }
}
