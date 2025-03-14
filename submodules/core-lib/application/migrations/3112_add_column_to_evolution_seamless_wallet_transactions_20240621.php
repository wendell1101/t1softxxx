<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_evolution_seamless_wallet_transactions_20240621 extends CI_Migration {
    private $tableNames = [
        'evolution_seamless_wallet_transactions',
        'evolution_btg_seamless_wallet_transactions',
        'evolution_netent_seamless_wallet_transactions',
        'evolution_nlc_seamless_wallet_transactions',
        'evolution_redtiger_seamless_wallet_transactions',
    ];

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

        foreach ($this->tableNames as $tableName) {
            if ($this->utils->table_really_exists($tableName)) {
                if ($this->utils->table_really_exists($tableName)) {
                    if (!$this->db->field_exists('remote_wallet_status', $tableName)) {
                        $this->dbforge->add_column($tableName, $remote_wallet_status_field);
                        $this->player_model->addIndex($tableName, 'idx_remote_wallet_status', 'remote_wallet_status');
                    }
        
                    if (!$this->db->field_exists('seamless_service_unique_id', $tableName)) {
                        $this->dbforge->add_column($tableName, $seamless_service_unique_id_field);
                        $this->player_model->addIndex($tableName, 'idx_seamless_service_unique_id', 'seamless_service_unique_id');
                    }
        
                    if (!$this->db->field_exists('is_processed', $tableName)) {
                        $this->dbforge->add_column($tableName, $is_processed_field);
                        $this->player_model->addIndex($tableName, 'idx_is_processed', 'is_processed');
                    }
                }
            }
        }
    }

    public function down() {
        foreach ($this->tableNames as $tableName) {
            if ($this->utils->table_really_exists($tableName)) {
                if ($this->db->field_exists('remote_wallet_status', $tableName)) {
                    $this->dbforge->drop_column($tableName, 'remote_wallet_status');
                }
    
                if ($this->db->field_exists('seamless_service_unique_id', $tableName)) {
                    $this->dbforge->drop_column($tableName, 'seamless_service_unique_id');
                }
    
                if ($this->db->field_exists('is_processed', $tableName)) {
                    $this->dbforge->drop_column($tableName, 'is_processed');
                }
            }
        }
    }
}