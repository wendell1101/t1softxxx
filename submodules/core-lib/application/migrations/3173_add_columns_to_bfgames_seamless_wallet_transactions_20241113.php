<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_bfgames_seamless_wallet_transactions_20241113 extends CI_Migration {
    private $tableName = 'bfgames_seamless_wallet_transactions';

    private $fields = [
        'rollback_action_id ' => [
            'type' => 'VARCHAR',
            'constraint' => '100',
            'null' => true,
        ],
        'offline' => array(
            'type' => 'INT',
            'default' => 0
        ),
    ];

    public function up() {
        $this->add_columns($this->tableName, $this->fields);
    }

    public function down() {
        $columns = array_keys($this->fields);
        $this->drop_columns($this->tableName, $columns);
    }
}