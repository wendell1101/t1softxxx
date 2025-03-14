<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_sa_gaming_seamless_wallet_transactions_20241113 extends CI_Migration {
    private $tableName = 'sa_gaming_seamless_wallet_transactions';

    public function up() {
        $field = array(
            'reference_transaction_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ),
        );

        if (!$this->db->field_exists('reference_transaction_id', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $field);
        }

        $this->player_model->addIndex($this->tableName, 'idx_reference_transaction_id', 'reference_transaction_id');
    }

    public function down() {
        $this->dbforge->drop_column($this->tableName, 'reference_transaction_id');
    }
}
