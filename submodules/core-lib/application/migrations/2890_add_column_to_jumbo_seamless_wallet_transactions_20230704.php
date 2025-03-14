<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jumbo_seamless_wallet_transactions_20230704 extends CI_Migration {
    private $tableName = 'jumbo_seamless_wallet_transactions';

    public function up() {
        $fields = [
            'valid_bet' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
        ];

        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('valid_bet', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            if ($this->db->field_exists('valid_bet', $this->tableName)) {
                $this->dbforge->drop_column($this->tableName, 'valid_bet');
            }
        }
    }
}
