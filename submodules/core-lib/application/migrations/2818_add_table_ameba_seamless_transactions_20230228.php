<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_ameba_seamless_transactions_20230228 extends CI_Migration {
    private $tableName = 'ameba_seamless_transactions';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'null' => false,
                'constraint' => '10'
            ],
            #from request
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true
            ],
            'site_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'account_name' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'bet_amt' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'payout_amt' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'rebate_amt' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'game_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'tx_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'free' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ],
            'sessionid' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'time' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'jp' => [
                'type' => 'JSON',
                'null' => true
            ],
            'prize_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'prize_amt' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'sum_payout_amt' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'json_request' => [
                'type' => 'JSON',
                'null' => true
            ],

            // SBE additional info
            'status' => [
                'type' => 'SMALLINT',
                'null' => true
            ],
            'transaction_type' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
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

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_tx_id', 'tx_id');
            $this->player_model->addIndex($this->tableName, 'idx_action', 'action');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_time', 'time');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}