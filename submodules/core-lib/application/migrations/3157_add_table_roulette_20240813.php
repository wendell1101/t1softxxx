<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_roulette_20240813 extends CI_Migration {
    private $tableName = 'roulette';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'name' => array(
                'type' => 'JSON',
                'null' => FALSE,
            ),
            'startAt' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'endAt' => array(
                'type' => 'DATETIME',
                'null' => TRUE
            ),
            'period' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'periodMonthDay' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => TRUE
            ),
            'periodWeekDay' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => TRUE
            ),
            'status' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'default' => 0,
                'null' => FALSE
            ),
            'sortOrder' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE
            ),
            'description' => array(
                'type' => 'MEDIUMTEXT',
                'null' => TRUE
            ),
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
            'createdBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => FALSE,
            ),
            'updatedBy' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('status', false);
            $this->dbforge->add_key('createdAt', false);
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
