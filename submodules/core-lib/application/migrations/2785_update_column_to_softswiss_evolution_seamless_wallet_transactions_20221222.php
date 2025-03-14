<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_column_to_softswiss_evolution_seamless_wallet_transactions_20221222 extends CI_Migration {

    private $tableName = 'softswiss_evolution_seamless_wallet_transactions';

    public function up() {
        $field = [
            'round_id' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('round_id', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $field);
            }
        }
    }

    public function down() {
    }
}