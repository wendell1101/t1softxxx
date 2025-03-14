<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_wm_casino_transactions_20230831 extends CI_Migration {
    private $tableNames = [
        'wm_casino_transactions',
        'wm_casino_transactions_202307',
        'wm_casino_transactions_202308'
    ];

    public function up() {
        $fields = [
            'bet_result' => [
                'type' => 'JSON',
                'null' => true,
            ],
        ];

        foreach($this->tableNames as $tableName){
            if ($this->utils->table_really_exists($tableName)) {
                if (!$this->db->field_exists('bet_result', $tableName)) {
                    $this->dbforge->add_column($tableName, $fields);
                }
            }
        }
    }

    public function down() {

    }
}
