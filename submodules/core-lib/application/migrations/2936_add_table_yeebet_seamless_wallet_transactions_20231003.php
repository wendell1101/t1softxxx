<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_yeebet_seamless_wallet_transactions_20231003 extends CI_Migration {
    private $tableName = 'yeebet_seamless_wallet_transactions';

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
            'player_id' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
            #request body
            'app_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'user_name' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'notify_id' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ],
            'type' => [
                'type' => 'INT',
                'constraint' => '10',
                'null' => true,
            ],
            'serial_number' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ],
            'sign' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ],
            'bets' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'game_create_time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // default
            'sbe_status' => [
                'type' => 'SMALLINT',
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
            'elapsed_time' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],
            'external_game_id' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            // SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true,
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
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
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_notify_id', 'notify_id');
            $this->player_model->addIndex($this->tableName, 'idx_serial_number', 'serial_number');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
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