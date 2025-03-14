<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_tada_seamless_wallet_transactions_20240620 extends CI_Migration {
    private $tableName = 'tada_seamless_wallet_transactions';

    public function up() {
        $remote_wallet_status_field = [
            'remote_wallet_status' => [
                'type' => 'INT',
                'null' => true,
            ],
        ];

        $seamless_service_unique_id_field = [
            'seamless_service_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
        ];

        $is_processed_field = [
            'is_processed' => [
                'type' => 'BOOLEAN',
                'null' => true,
            ],
        ];

        $this->load->model('player_model');

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('remote_wallet_status', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $remote_wallet_status_field);
                $this->player_model->addIndex($this->tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
            }

            if (!$this->db->field_exists('seamless_service_unique_id', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $seamless_service_unique_id_field);
                $this->player_model->addIndex($this->tableName, 'idx_seamless_service_unique_id', 'seamless_service_unique_id');
            }

            if (!$this->db->field_exists('is_processed', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $is_processed_field);
                $this->player_model->addIndex($this->tableName, 'idx_is_processed', 'is_processed');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('remote_wallet_status', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'remote_wallet_status');
            }

            if ($this->db->field_exists('seamless_service_unique_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'seamless_service_unique_id');
            }

            if ($this->db->field_exists('is_processed', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'is_processed');
            }
        }
    }
}