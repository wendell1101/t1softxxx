<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_joker_game_logs_20240906 extends CI_Migration {
    private $tableName = 'joker_game_logs';

    private $fields = [
        'before_balance' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
        'after_balance' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
        'extra_info' => [
            'type' => 'JSON',
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