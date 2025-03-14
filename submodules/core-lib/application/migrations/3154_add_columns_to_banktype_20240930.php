<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_banktype_20240930 extends CI_Migration {
    private $tableName = 'banktype';

    private $fields = [
        'bank_order' => [
            'type' => 'INT'
        ]
    ];

    public function up() {
        $this->add_columns($this->tableName, $this->fields);
    }

    public function down() {
        $columns = array_keys($this->fields);
        $this->drop_columns($this->tableName, $columns);
    }
}