<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_quest_rule_20231227 extends CI_Migration
{
    private $tableName = 'quest_rule';

    public function up()
    {
        $fields = array(
            'questRuleId' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'questConditionType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'questConditionValue' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'bonusConditionType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'bonusConditionValue' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'rouletteConditionType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'rouletteTimes' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'withdrawalConditionType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'withdrawReqBonusTimes' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'withdrawReqBetAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'withdrawReqBettingTimes' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'personalInfoType' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'communityOptions' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'extraRules' => array(
                'type' => 'JSON',
                'null' => true,
            ),
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'status' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('questRuleId', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_createdAt','createdAt');
            $this->player_model->addIndex($this->tableName,'idx_updatedAt','updatedAt');
        }
    }

    public function down()
    {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}