<?php

/**
 * Create kronolith base tables as of Kronolith 2.3.5
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
class KronolithBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('kronolith_events', $tableList)) {
            $t = $this->createTable('kronolith_events', ['autoincrementKey' => false]);
            $t->column('event_id', 'string', ['limit' => 32, 'null' => false]);
            $t->column('event_uid', 'string', ['limit' => 255, 'null' => false]);
            $t->column('calendar_id', 'string', ['limit' => 255, 'null' => false]);
            $t->column('event_creator_id', 'string', ['limit' => 255, 'null' => false]);
            $t->column('event_description', 'text');
            $t->column('event_location', 'text');
            $t->column('event_status', 'integer', ['default' => 0]);
            $t->column('event_attendees', 'text');
            $t->column('event_keywords', 'text');
            $t->column('event_exceptions', 'text');
            $t->column('event_title', 'string', ['limit' => 255]);
            $t->column('event_category', 'string', ['limit' => 80]);
            $t->column('event_recurtype', 'integer', ['default' => 0]);
            $t->column('event_recurinterval', 'integer');
            $t->column('event_recurdays', 'integer');
            $t->column('event_recurenddate', 'datetime');
            $t->column('event_recurcount', 'integer');
            $t->column('event_start', 'datetime');
            $t->column('event_end', 'datetime');
            $t->column('event_alarm', 'integer', ['default' => 0]);
            $t->column('event_modified', 'integer', ['default' => 0]);
            $t->column('event_private', 'integer', ['default' => 0, 'null' => false]);
            $t->primaryKey(['event_id']);
            $t->end();

            $this->addIndex('kronolith_events', ['calendar_id']);
            $this->addIndex('kronolith_events', ['event_uid']);
        }

        if (!in_array('kronolith_storage', $tableList)) {
            $t = $this->createTable('kronolith_storage');
            $t->column('vfb_owner', 'string', ['limit' => 255]);
            $t->column('vfb_email', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfb_serialized', 'text', ['null' => false]);
            $t->end();

            $this->addIndex('kronolith_storage', ['vfb_owner']);
            $this->addIndex('kronolith_storage', ['vfb_email']);
        }

        if (!in_array('kronolith_shares', $tableList)) {
            $t = $this->createTable('kronolith_shares', ['autoincrementKey' => false]);
            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('share_name', 'string', ['limit' => 255, 'null' => false]);
            $t->column('share_owner', 'string', ['limit' => 255, 'null' => false]);
            $t->column('share_flags', 'integer', ['default' => 0, 'null' => false]);
            $t->column('perm_creator', 'integer', ['default' => 0, 'null' => false]);
            $t->column('perm_default', 'integer', ['default' => 0, 'null' => false]);
            $t->column('perm_guest', 'integer', ['default' => 0, 'null' => false]);
            $t->column('attribute_name', 'string', ['limit' => 255, 'null' => false]);
            $t->column('attribute_desc', 'string', ['limit' => 255]);
            $t->primaryKey(['share_id']);
            $t->end();

            $this->addIndex('kronolith_shares', ['share_name']);
            $this->addIndex('kronolith_shares', ['share_owner']);
            $this->addIndex('kronolith_shares', ['perm_creator']);
            $this->addIndex('kronolith_shares', ['perm_default']);
            $this->addIndex('kronolith_shares', ['perm_guest']);
        }

        if (!in_array('kronolith_shares_groups', $tableList)) {
            $t = $this->createTable('kronolith_shares_groups');
            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('group_uid', 'string', ['limit' => 255, 'null' => false]);
            $t->column('perm', 'integer', ['null' => false]);
            $t->end();

            $this->addIndex('kronolith_shares_groups', ['share_id']);
            $this->addIndex('kronolith_shares_groups', ['group_uid']);
            $this->addIndex('kronolith_shares_groups', ['perm']);
        }

        if (!in_array('kronolith_shares_users', $tableList)) {
            $t = $this->createTable('kronolith_shares_users');

            $t->column('share_id', 'integer', ['null' => false]);
            $t->column('user_uid', 'string', ['limit' => 255, 'null' => false]);
            $t->column('perm', 'integer', ['null' => false]);
            $t->end();

            $this->addIndex('kronolith_shares_users', ['share_id']);
            $this->addIndex('kronolith_shares_users', ['user_uid']);
            $this->addIndex('kronolith_shares_users', ['perm']);
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('kronolith_events');
        $this->dropTable('kronolith_shares');
        $this->dropTable('kronolith_storage');
        $this->dropTable('kronolith_shares_groups');
        $this->dropTable('kronolith_shares_users');
    }

}
