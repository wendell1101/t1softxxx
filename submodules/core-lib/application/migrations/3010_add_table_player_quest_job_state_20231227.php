<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_player_quest_job_state_20231227 extends CI_Migration
{
    private $tableName = 'player_quest_job_state';

    public function up()
    {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ),
            'playerId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'questManagerId' => array(
                'type' => 'INT',
                'null' => false,
                'default' => '1'
            ),
            'questJobId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ),
            'jobStats' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'rewardStatus' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
                'default' => 1
            ),
            'bonusAmount' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
            'withdrawConditionId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'transactionId' => array(
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ),
            'playerRequestIp' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ),
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false
            ),
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false
            )
        );

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_playerId','playerId');
            $this->player_model->addIndex($this->tableName,'idx_questJobId','questJobId');
            $this->player_model->addIndex($this->tableName,'idx_questManagerId','questManagerId');
            $this->player_model->addIndex($this->tableName,'idx_withdrawConditionId','withdrawConditionId');
            $this->player_model->addIndex($this->tableName,'idx_transactionId','transactionId');
            $this->player_model->addIndex($this->tableName,'idx_jobStats','jobStats');
            $this->player_model->addIndex($this->tableName,'idx_rewardStatus','rewardStatus');
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
