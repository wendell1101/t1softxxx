<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_remote_wallet_transactions_20231239 extends CI_Migration
{
    private $tableName = 'remote_wallet_transactions';

    public function up()
    {
        $fields = [
            // default
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false
            ],
            'full_url' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => false
            ],
            'status' => [
                'type' => 'INT',
                'null' => false
            ],
            'external_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'response_result_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            // add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id_game_platform_id', 'player_id, game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_external_transaction_id', 'external_transaction_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}