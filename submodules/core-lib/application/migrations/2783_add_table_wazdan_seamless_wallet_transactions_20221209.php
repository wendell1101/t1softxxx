<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_wazdan_seamless_wallet_transactions_20221209 extends CI_Migration {
    private $tableName = 'wazdan_seamless_wallet_transactions';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '10'
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'game_username' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'game_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'before_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'after_balance' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],

            // additional
            'skin_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'end_round' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'free_spin' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'free_round' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'last_free_spin' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'last_free_round' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'cash_drop_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'prize_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'promotion_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'bet_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'original_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'bonus_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'free_round_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'cash_drop_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'round_info' => [
                'type' => 'JSON',
                'null' => true
            ],

            // default
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'win_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'flag_of_updated_result' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0
            ],

            // SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_username', 'game_username');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_type', 'transaction_type');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_status', 'status');
            $this->player_model->addIndex($this->tableName, 'idx_start_at', 'start_at');
            $this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addIndex($this->tableName, 'idx_flag_of_updated_result', 'flag_of_updated_result');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addIndex($this->tableName, 'idx_bet_transaction_id', 'bet_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_original_transaction_id', 'original_transaction_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}