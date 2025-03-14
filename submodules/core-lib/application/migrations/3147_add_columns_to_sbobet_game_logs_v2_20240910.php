<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_sbobet_game_logs_v2_20240910 extends CI_Migration {
    private $tableName = 'sbobet_game_logs_v2';

    private $fields = [
        'turnover_by_stake' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
        'turnover_by_actual_stake' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
        'net_turnover_by_stake' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
        'net_turnover_by_actual_stake' => [
            'type' => 'DOUBLE',
            'null' => true,
        ],
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