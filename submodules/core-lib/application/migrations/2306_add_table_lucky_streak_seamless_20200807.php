<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_lucky_streak_seamless_20200807 extends CI_Migration {

    private $tableName = 'lucky_streak_seamless_game_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'betId' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'playerId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'operatorId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'playername' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'roundId' => [
                'type' => 'INT',
                'constraint' => '25',
                'null' => true
            ],
            'gameId' => [
                'type' => 'INT',
                'constraint' => '6',
                'null' => true
            ],
            'gameName' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'gameType' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'betAmount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'winAmount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'income' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'betTime' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '6',
                'null' => true
            ],
            'sessionId' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_luckystreakthb1_playerId','playerId');
            $this->player_model->addIndex($this->tableName,'idx_luckystreakthb1_roundId','roundId');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_luckystreakthb1_external_unique_id', 'external_unique_id');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}