<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_betby_seamless_wallet_transactions_20230504 extends CI_Migration {
    private $tableName = 'betby_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'player_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'session_id' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ],
            'bonus_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'bonus_type' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            #transaction item
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'betslip_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'operator_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'operator_brand_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'timestamp' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'date_time' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'cross_rate_euro' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'operation' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            #end of transaction item
            'potential_win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'potential_comboboost_win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'bet_transaction_id' => [
                'type' => 'BIGINT',
                'null' => true
            ],
            'parent_transaction_id' => [
                'type' => 'BIGINT',
                'null' => true
            ],
            'is_cashout' => [
                'type' => 'SMALLINT',
                'null' => true
            ],
            'is_snr_lost' => [
                'type' => 'SMALLINT',
                'null' => true
            ],
            'transaction' => [
                'type' => 'JSON',
                'null' => true
            ],
            'betslip' => [
                'type' => 'JSON',
                'null' => true
            ],
            'selections' => [
                'type' => 'JSON',
                'null' => true
            ],
            'request_json' => [
                'type' => 'JSON',
                'null' => true
            ],
            'odds' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            #default
            'sbe_status' => [
                'type' => 'SMALLINT',
                'null' => true
            ],
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'result_amount' => [
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
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_bet_transaction_id', 'bet_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_parent_transaction_id', 'parent_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_date_time', 'date_time');
            $this->player_model->addIndex($this->tableName, 'idx_betslip_id', 'betslip_id');
            $this->player_model->addIndex($this->tableName, 'idx_bonus_id', 'bonus_id');
            $this->player_model->addIndex($this->tableName, 'idx_bonus_type', 'bonus_type');
            $this->player_model->addIndex($this->tableName, 'idx_action', 'action');
            $this->player_model->addIndex($this->tableName, 'idx_operation', 'operation');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}