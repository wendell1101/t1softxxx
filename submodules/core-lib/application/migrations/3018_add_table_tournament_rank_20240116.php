<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_rank_20240116 extends CI_Migration {
    private $tableName = 'tournament_rank';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'eventId' => [
                'type' => 'INT',
            ],
            'rankFrom' => [
                'type' => 'INT',
            ],
            'rankTo' => [
                'type' => 'INT',
            ],
            'bonusType' => [
                'type' => 'INT',
                'default' => 1,
            ],
            'bonusValue' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'withdrawalConditionFixedAmount' => [
                'type' => 'INT',
                'null' => true,
            ],
            'withdrawalConditionTimes' => [
                'type' => 'INT',
                'null' => true,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'deletedBy' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'deletedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_eventId', 'eventId');
            $this->player_model->addIndex($this->tableName, 'idx_rankFrom', 'rankFrom');
            $this->player_model->addIndex($this->tableName, 'idx_rankTo', 'rankTo');
            $this->player_model->addIndex($this->tableName, 'idx_bonusType', 'bonusType');
            $this->player_model->addIndex($this->tableName, 'idx_bonusValue', 'bonusValue');
            $this->player_model->addIndex($this->tableName, 'idx_withdrawalConditionFixedAmount', 'withdrawalConditionFixedAmount');
            $this->player_model->addIndex($this->tableName, 'idx_withdrawalConditionTimes', 'withdrawalConditionTimes');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_updatedAt', 'updatedAt');
            $this->player_model->addIndex($this->tableName, 'idx_deletedAt', 'deletedAt');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
    }
