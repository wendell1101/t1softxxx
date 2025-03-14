<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_rtg_seamless_wallet_transactions_20240403 extends CI_Migration {
    private $tableName = 'rtg_seamless_wallet_transactions';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true,
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '10',
            ],
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
            'game_username' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'language' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'api_method' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'game_code' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'wallet_adjustment_status' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'before_balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'after_balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ],
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'reference_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'rollback_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],

            // additional
            'sessionid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'gamenum' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'bonuscode' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'bonusroundid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true,
            ],
            'bonusamount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'isbonusroundend' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'gameroundend' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'freegame' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'transactiondate' => [
                'type' => 'DATETIME',
                'null' => true,
            ],

            // default
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
            'request' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'response' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'win_amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'flag_of_updated_result' => [
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            ],
            'is_processed' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'seamless_service_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'external_game_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'external_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],

            // SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
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
                'null' => true,
            ]
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_token', 'token');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_username', 'game_username');
            $this->player_model->addIndex($this->tableName, 'idx_api_method', 'api_method');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_type', 'transaction_type');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_wallet_adjustment_status', 'wallet_adjustment_status');
            $this->player_model->addIndex($this->tableName, 'idx_start_at', 'start_at');
            $this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addIndex($this->tableName, 'idx_reference_transaction_id', 'reference_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_rollback_transaction_id', 'rollback_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_flag_of_updated_result', 'flag_of_updated_result');
            $this->player_model->addIndex($this->tableName, 'idx_is_processed', 'is_processed');
            $this->player_model->addIndex($this->tableName, 'idx_seamless_service_unique_id', 'seamless_service_unique_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_game_id', 'external_game_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_transaction_id', 'external_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

            // additional
            $this->player_model->addIndex($this->tableName, 'idx_sessionid', 'sessionid');
            $this->player_model->addIndex($this->tableName, 'idx_gamenum', 'gamenum');
            $this->player_model->addIndex($this->tableName, 'idx_bonuscode', 'bonuscode');
            $this->player_model->addIndex($this->tableName, 'idx_bonusroundid', 'bonusroundid');
            $this->player_model->addIndex($this->tableName, 'idx_transactiondate', 'transactiondate');

            // default
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}