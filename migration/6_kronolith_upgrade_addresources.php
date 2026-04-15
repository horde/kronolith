<?php

/**
 * Adds resource table.
 *
 * Copyright 2010-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class KronolithUpgradeAddResources extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();
        if (!in_array('kronolith_resources', $tableList)) {
            $t = $this->createTable('kronolith_resources', ['autoincrementKey' => false]);
            $t->column('resource_id', 'integer', ['null' => false]);
            $t->column('resource_name', 'string', ['limit' => 255]);
            $t->column('resource_calendar', 'string', ['limit' => 255]);
            $t->column('resource_description', 'text');
            $t->column('resource_response_type', 'integer', ['default' => 0]);
            $t->column('resource_type', 'string', ['limit' => 255, 'null' => false]);
            $t->column('resource_members', 'text');
            $t->primaryKey(['resource_id']);
            $t->end();

            $this->addIndex('kronolith_resources', ['resource_calendar']);
            $this->addIndex('kronolith_resources', ['resource_type']);
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->dropTable('kronolith_resources');
    }

}
