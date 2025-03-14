<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_gfg_seamless_transactions_20230331 extends CI_Migration {
    private $tableName = 'gfg_seamless_transactions';

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
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'money' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'lock_money' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'game_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'order_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'account_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'min_lock_money' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'timestamp' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'date_time' => [
                'type' => 'DATETIME',
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
            $this->player_model->addIndex($this->tableName, 'idx_order_id', 'order_id');
            $this->player_model->addIndex($this->tableName, 'idx_account_id', 'account_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_id', 'game_id');
            $this->player_model->addIndex($this->tableName, 'idx_date_time', 'date_time');
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