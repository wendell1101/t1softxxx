<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_king_maker_seamless_wallet_transactions_20230902 extends CI_Migration {
    private $tableName = 'king_maker_seamless_wallet_transactions';

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
                'constraint' => '25',
                'null' => true,
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
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

            // additional
            'authtoken' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'brandcode' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'refptxid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'timestamp' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'senton' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'gpcode' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'gamename' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'externalgameid' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'txtype' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ],
            'platformtype' => [
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            ],
            'gametype' => [
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            ],
            'bonustype' => [
                'type' => 'INT',
                'constraint' => '5',
                'null' => true,
            ],
            'externalroundid' => [
                'type' => 'VARCHAR',
                'constraint' => '75',
                'null' => true,
            ],
            'betid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'externalbetid' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'isclosinground' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'isbuyingame' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
            'ggr' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'turnover' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'commission' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'unsettledbets' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'walletcode' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'bonuscode' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'redeemcode' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'desc' => [
                'type' => 'TEXT',
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
            $this->player_model->addIndex($this->tableName, 'idx_flag_of_updated_result', 'flag_of_updated_result');
            $this->player_model->addIndex($this->tableName, 'idx_is_processed', 'is_processed');
            $this->player_model->addIndex($this->tableName, 'idx_seamless_service_unique_id', 'seamless_service_unique_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_game_id', 'external_game_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_transaction_id', 'external_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');

            // additional
            $this->player_model->addIndex($this->tableName, 'idx_brandcode', 'brandcode');
            $this->player_model->addIndex($this->tableName, 'idx_refptxid', 'refptxid');
            $this->player_model->addIndex($this->tableName, 'idx_gpcode', 'gpcode');
            $this->player_model->addIndex($this->tableName, 'idx_txtype', 'txtype');
            $this->player_model->addIndex($this->tableName, 'idx_gametype', 'gametype');
            $this->player_model->addIndex($this->tableName, 'idx_externalroundid', 'externalroundid');
            $this->player_model->addIndex($this->tableName, 'idx_betid', 'betid');
            $this->player_model->addIndex($this->tableName, 'idx_externalbetid', 'externalbetid');
            $this->player_model->addIndex($this->tableName, 'idx_isbuyingame', 'isbuyingame');

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