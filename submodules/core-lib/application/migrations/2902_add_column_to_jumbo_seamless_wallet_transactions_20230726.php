<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jumbo_seamless_wallet_transactions_20230726 extends CI_Migration {
    private $tableName = 'jumbo_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'ref_transfer_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('ref_transfer_id', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName, 'idx_ref_transfer_id', 'ref_transfer_id');
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('ref_transfer_id', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'ref_transfer_id');
            }
        }
    }
}
