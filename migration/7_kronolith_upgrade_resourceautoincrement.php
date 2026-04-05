<?php

/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeResourceAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('kronolith_resources', 'resource_id', 'autoincrementKey');
        if (in_array('kronolith_resources_seq', $this->tables())) {
            $this->dropTable('kronolith_resources_seq');
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('kronolith_resources', 'resource_id', 'integer', ['null' => false, 'autoincrement' => false]);
    }

}
