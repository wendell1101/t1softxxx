<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_pos_player_latest_game_logs_20230420 extends CI_Migration {
    private $tableName = 'pos_player_latest_game_logs';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ],
            'bet_number' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => false,
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => false,
            ],
            'payout_amount' => [
                'type' => 'DOUBLE',
                'null' => false,
            ],
            'bet_details' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'bet_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],

            // additional
            'game_username' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'game_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'game_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'game_code' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'game_type_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'game_description_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false,
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true,
            ],

            // default
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_bet_at', 'bet_at');
            $this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addIndex($this->tableName, 'idx_game_username', 'game_username');
            $this->player_model->addIndex($this->tableName, 'idx_game_type', 'game_type');
            $this->player_model->addIndex($this->tableName, 'idx_game_name', 'game_name');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_type_id', 'game_type_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_bet_number', 'bet_number');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}