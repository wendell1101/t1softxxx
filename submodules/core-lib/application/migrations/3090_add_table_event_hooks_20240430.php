<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_event_hooks_20240430 extends CI_Migration
{
    private $tableName = 'event_hooks';
    public function up()
    {
        $fields = [
            'eventHookId' => [
                'type' => 'BIGINT',
                'auto_increment' => true,
            ],
            'event' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'hook' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'eventConditions' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'hookConditions' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'params' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sortOrder' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'status' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'createdBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updateBy' => [
                'type' => 'INT',
                'null' => true,
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('eventHookId', true);
            $this->dbforge->add_key('event', false);
            $this->dbforge->add_key('hook', false);
            $this->dbforge->add_key('sortOrder', false);
            $this->dbforge->add_key('status', false);
            $this->dbforge->create_table($this->tableName);
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
