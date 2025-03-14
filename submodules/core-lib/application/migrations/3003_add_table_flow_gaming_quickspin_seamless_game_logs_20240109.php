<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_flow_gaming_quickspin_seamless_game_logs_20240109 extends CI_Migration {
    private $tableName = 'flow_gaming_quickspin_seamless_game_logs';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'wallet_code' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'external_ref' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'balance_type' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true,
            ],
            'balance' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'pool_amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'ext_item_id' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'item_id' => [
                'type' => 'VARCHAR',
                'constraint' => '70',
                'null' => true,
            ],
            'vendor' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ext_w_tx_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'tx_round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'context' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'parent_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
            'account_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'account_ext_ref' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'application_id' => [
                'type' => 'VARCHAR',
                'constraint' => '15',
                'null' => true,
            ],
            'currency_unit' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true,
            ],
            'transaction_time' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'created_by' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'created' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'session' => [
                'type' => 'VARCHAR',
                'constraint' => '70',
                'null' => true,
            ],
            'ip' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],

            # SBE additional info
            'extra_info' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_uniqueid' => [
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
                'null' => true,
            ]
        ];

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_ext_w_tx_id', 'ext_w_tx_id');
            $this->player_model->addIndex($this->tableName, 'idx_category', 'category');
            $this->player_model->addIndex($this->tableName, 'idx_type', 'type');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_parent_transaction_id', 'parent_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_item_id', 'item_id');
            $this->player_model->addIndex($this->tableName, 'idx_account_id', 'account_id');
            $this->player_model->addIndex($this->tableName, 'idx_account_ext_ref', 'account_ext_ref');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_time', 'transaction_time');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if ($this->db->table_exist($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}