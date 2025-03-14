<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_fc_seamless_transactions_20230209 extends CI_Migration {
    private $tableName = 'fc_seamless_transactions';

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
            'record_id' => [
                'type' => 'VARCHAR',
                'constraint' => '24',
                'null' => true
            ],
            'bet_id' => [
                'type' => 'VARCHAR',
                'constraint' => '24',
                'null' => true
            ],
            'bank_id' => [
                'type' => 'BIGINT',
                'null' => true
            ],
            'member_account' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'game_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'game_type' => [
                'type' => 'INT',
                'null' => true
            ],
            'is_buy_feature' => [
                'type' => 'BOOLEAN',
                'null' => true,
                'default' => 0,
            ],
            'bet' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'jp_bet' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'jp_prize' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'net_win' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'require_amt' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'refund' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'game_date' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'create_date' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'ts' => [
                'type' => 'BIGINT',
                'null' => true
            ],
            'settle_bet_ids' => [
                'type' => 'JSON',
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
            $this->player_model->addIndex($this->tableName, 'idx_record_id', 'record_id');
            $this->player_model->addIndex($this->tableName, 'idx_bet_id', 'bet_id');
            $this->player_model->addIndex($this->tableName, 'idx_bank_id', 'bank_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_date', 'game_date');
            $this->player_model->addIndex($this->tableName, 'idx_create_date', 'create_date');
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