<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_roulette_withdrawal_condition_20240813 extends CI_Migration {
    private $tableName = 'roulette_withdrawal_condition';
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
            'withdrawalConditionType' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'withdrawalRequireBetAmount' => array(
                'type' => 'decimal',
                'constraint' => '10,2',
                'null' => TRUE,
            ),
            'withdrawalRequireBonusMultiplier' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'null' => TRUE,
            ),
            'withdrawalRequirementDepositConditionType' => array(
                'type' => 'TINYINT',
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0
            ),
            'withdrawalRequireMinDeposit' => array(
                'type' => 'decimal',
                'constraint' => '10,2',
                'null' => TRUE,
            ),
            'withdrawalRequireLifetimeDeposit' => array(
                'type' => 'decimal',
                'constraint' => '10,2',
                'null' => TRUE,
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
