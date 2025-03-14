<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modify_column_for_jili_seamless_wallet_transactions_20240424 extends CI_Migration {

    private $tableName = 'jili_seamless_wallet_transactions';

    public function up() {
        $column = [
            'round' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('round', $this->tableName)) {
                $this->dbforge->modify_column($this->tableName, $column);
            }
        }
    }

    public function down() {
    }
}