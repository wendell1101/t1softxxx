<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_tournament_player_apply_records_20240116 extends CI_Migration {
    private $tableName = 'tournament_player_apply_records';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'tournamentId' => [
                'type' => 'INT',
            ],
            'eventId' => [
                'type' => 'INT',
            ],
            'playerId' => [
                'type' => 'INT',
            ],
            'eventScore' => [
                'type' => 'INT',
                'null' => true,
            ],
            'bonusAmount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,4',
            ],
            'applyTransId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'bonusTransId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'WithdrawalCondictionId' => [
                'type' => 'INT',
                'null' => true,
            ],
            'isReleased' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'createdAt DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'releaseTime' => [
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
            $this->player_model->addIndex($this->tableName, 'idx_tournamentId', 'tournamentId');
            $this->player_model->addIndex($this->tableName, 'idx_eventId', 'eventId');
            $this->player_model->addIndex($this->tableName, 'idx_playerId', 'playerId');
            $this->player_model->addIndex($this->tableName, 'idx_eventScore', 'eventScore');
            $this->player_model->addIndex($this->tableName, 'idx_bonusAmount', 'bonusAmount');
            $this->player_model->addIndex($this->tableName, 'idx_applyTransId', 'applyTransId');
            $this->player_model->addIndex($this->tableName, 'idx_bonusTransId', 'bonusTransId');
            $this->player_model->addIndex($this->tableName, 'idx_WithdrawalCondictionId', 'WithdrawalCondictionId');
            $this->player_model->addIndex($this->tableName, 'idx_createdAt', 'createdAt');
            $this->player_model->addIndex($this->tableName, 'idx_releaseTime', 'releaseTime');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
    }
