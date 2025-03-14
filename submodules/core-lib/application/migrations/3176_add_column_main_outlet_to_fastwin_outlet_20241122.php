<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_main_outlet_to_fastwin_outlet_20241122 extends CI_Migration {
    private $tableName = 'fastwin_outlet';

    private $fields = [
        'main_outlet ' => [
            'type' => 'VARCHAR',
            'constraint' => '100',
            'null' => true,
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