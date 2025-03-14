<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_king_maker_seamless_service_logs_20230902 extends CI_Migration {
    private $tableName = 'king_maker_seamless_service_logs';

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
            'status_code' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ],
            'status_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'response_code' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'response_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'flag' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true,
            ],
            'call_count' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => true,
            ],

            // additional
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
            'gpcode' => [
                'type' => 'VARCHAR',
                'constraint' => '60',
                'null' => true,
            ],
            'txtype' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true,
            ],
            'gametype' => [
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
            'isbuyingame' => [
                'type' => 'BOOLEAN',
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
            'response_result_id' => [
                'type' => 'INT',
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
            $this->player_model->addIndex($this->tableName, 'idx_transaction_type', 'transaction_type');
            $this->player_model->addIndex($this->tableName, 'idx_transaction_id', 'transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_round_id', 'round_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_code', 'game_code');
            $this->player_model->addIndex($this->tableName, 'idx_status_code', 'status_code');
            $this->player_model->addIndex($this->tableName, 'idx_response_code', 'response_code');
            $this->player_model->addIndex($this->tableName, 'idx_call_count', 'call_count');
            $this->player_model->addIndex($this->tableName, 'idx_seamless_service_unique_id', 'seamless_service_unique_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_game_id', 'external_game_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addIndex($this->tableName, 'idx_md5_sum', 'md5_sum');

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
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}