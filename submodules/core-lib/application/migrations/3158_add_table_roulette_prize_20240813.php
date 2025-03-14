<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_roulette_prize_20240813 extends CI_Migration {
    private $tableName = 'roulette_prize';
    public function up()
    {
        $fields = [
            'id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'rouletteId' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'name' => array(
                'type' => 'JSON',
                'null' => FALSE,
            ),
            'status' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'default' => 1,
                'null' => FALSE
            ),
            'flag' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'prizeType' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => FALSE
            ),
            'prizeCondition' => array(
                'type' => 'JSON',
                'null' => TRUE
            ),
            'defaultProbability' => array(
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => FALSE
            ),
            'sortOrder' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
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
            $this->dbforge->add_key('rouletteId', false);
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
